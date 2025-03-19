# connect_sta.py/Open GoPro, Version 2.0 (C) Copyright 2021 GoPro, Inc. (http://gopro.com/OpenGoPro).
# This copyright was auto-generated on Wed Mar 27 22:05:49 UTC 2024

import sys
import os
import json
import asyncio
import argparse
from typing import Generator, Final
from pathlib import Path
from dataclasses import dataclass, asdict
from datetime import datetime
import pytz
from tzlocal import get_localzone

from bleak import BleakClient
from tutorial_modules import GoProUuid, proto, logger, ResponseManager
from bluetooth_connect import connect_to_bluetooth



def yield_fragmented_packets(payload: bytes) -> Generator[bytes, None, None]:
    """Generate fragmented packets from a monolithic payload to accommodate the max BLE packet size of 20 bytes.

    Args:
        payload (bytes): input payload to fragment

    Raises:
        ValueError: Input payload is too large.

    Yields:
        Generator[bytes, None, None]: fragmented packets.
    """
    length = len(payload)

    CONTINUATION_HEADER: Final = bytearray([0x80])
    MAX_PACKET_SIZE: Final = 20
    is_first_packet = True

    # Build initial length header
    if length < (2**13 - 1):
        header = bytearray((length | 0x2000).to_bytes(2, "big", signed=False))
    elif length < (2**16 - 1):
        header = bytearray((length | 0x6400).to_bytes(2, "big", signed=False))
    else:
        raise ValueError(f"Data length {length} is too big for this protocol.")

    byte_index = 0
    while bytes_remaining := length - byte_index:
        # If this is the first packet, use the appropriate header. Else use the continuation header
        if is_first_packet:
            packet = bytearray(header)
            is_first_packet = False
        else:
            packet = bytearray(CONTINUATION_HEADER)
        # Build the current packet
        packet_size = min(MAX_PACKET_SIZE - len(packet), bytes_remaining)
        packet.extend(bytearray(payload[byte_index : byte_index + packet_size]))
        yield bytes(packet)
        # Increment byte_index for continued processing
        byte_index += packet_size


async def fragment_and_write_gatt_char(client: BleakClient, char_specifier: str, data: bytes) -> None:
    """Fragment the data into BLE packets and send each packet via GATT write.

    Args:
        client (BleakClient): Bleak client to perform GATT Writes with
        char_specifier (str): BLE characteristic to write to
        data (bytes): data to fragment and write.
    """
    for packet in yield_fragmented_packets(data):
        await client.write_gatt_char(char_specifier, packet, response=True)


async def scan_for_networks(manager: ResponseManager) -> int:
    """Scan for WiFi networks

    Args:
        manager (ResponseManager): manager used to perform the operation

    Raises:
        RuntimeError: Received unexpected response.

    Returns:
        int: Scan ID to use to retrieve scan results
    """
    logger.info(msg="Scanning for available Wifi Networks")

    start_scan_request = bytearray(
        [
            0x02,  # Feature ID
            0x02,  # Action ID
            *proto.RequestStartScan().SerializePartialToString(),
        ]
    )
    start_scan_request.insert(0, len(start_scan_request))

    # Send the scan request
    logger.debug(f"Writing: {start_scan_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.NETWORK_MANAGEMENT_REQ_UUID.value, start_scan_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0x02:
            raise RuntimeError("Only expect to receive Feature ID 0x02 responses after scan request")
        if response.action_id == 0x82:  # Initial Scan Response
            manager.assert_generic_protobuf_success(response.data)
        elif response.action_id == 0x0B:  # Scan Notifications
            scan_notification: proto.NotifStartScanning = response.data  # type: ignore
            logger.info(f"Received scan notification: {scan_notification}")
            if scan_notification.scanning_state == proto.EnumScanning.SCANNING_SUCCESS:
                return scan_notification.scan_id
        else:
            raise RuntimeError("Only expect to receive Action ID 0x02 or 0x0B responses after scan request")
    raise RuntimeError("Loop should not exit without return")


async def get_scan_results(manager: ResponseManager, scan_id: int) -> list[proto.ResponseGetApEntries.ScanEntry]:
    """Retrieve the results from a completed Wifi Network scan

    Args:
        manager (ResponseManager): manager used to perform the operation
        scan_id (int): identifier returned from completed scan

    Raises:
        RuntimeError: Received unexpected response.

    Returns:
        list[proto.ResponseGetApEntries.ScanEntry]: list of scan entries
    """
    logger.info("Getting the scanned networks.")

    results_request = bytearray(
        [
            0x02,  # Feature ID
            0x03,  # Action ID
            *proto.RequestGetApEntries(start_index=0, max_entries=100, scan_id=scan_id).SerializePartialToString(),
        ]
    )
    results_request.insert(0, len(results_request))

    # Send the request
    logger.debug(f"Writing: {results_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.NETWORK_MANAGEMENT_REQ_UUID.value, results_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0x02 or response.action_id != 0x83:
            raise RuntimeError("Only expect to receive Feature ID 0x02 Action ID 0x83 responses after scan request")
        entries_response: proto.ResponseGetApEntries = response.data  # type: ignore
        manager.assert_generic_protobuf_success(entries_response)
        logger.info("Found the following networks:")
        for entry in entries_response.entries:
            logger.info(str(entry))
        return list(entries_response.entries)
    raise RuntimeError("Loop should not exit without return")


async def connect_to_network(
    manager: ResponseManager, entry: proto.ResponseGetApEntries.ScanEntry, password: str
) -> None:
    """Connect to a WiFi network

    Args:
        manager (ResponseManager): manager used to perform the operation
        entry (proto.ResponseGetApEntries.ScanEntry): scan entry that contains network (and its metadata) to connect to
        password (str): password corresponding to network from `entry`

    Raises:
        RuntimeError: Received unexpected response.
    """
    logger.info(f"Connecting to {entry.ssid}")

    if entry.scan_entry_flags & proto.EnumScanEntryFlags.SCAN_FLAG_CONFIGURED:
        connect_request = bytearray(
            [
                0x02,  # Feature ID
                0x04,  # Action ID
                *proto.RequestConnect(ssid=entry.ssid).SerializePartialToString(),
            ]
        )
    else:
        connect_request = bytearray(
            [
                0x02,  # Feature ID
                0x05,  # Action ID
                *proto.RequestConnectNew(ssid=entry.ssid, password=password).SerializePartialToString(),
            ]
        )

    # Send the request
    logger.debug(f"Writing: {connect_request.hex(':')}")
    await fragment_and_write_gatt_char(manager.client, GoProUuid.NETWORK_MANAGEMENT_REQ_UUID.value, connect_request)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0x02:
            raise RuntimeError("Only expect to receive Feature ID 0x02 responses after connect request")
        if response.action_id == 0x84:  # RequestConnect Response
            manager.assert_generic_protobuf_success(response.data)
        elif response.action_id == 0x85:  # RequestConnectNew Response
            manager.assert_generic_protobuf_success(response.data)
        elif response.action_id == 0x0C:  # NotifProvisioningState Notifications
            provisioning_notification: proto.NotifProvisioningState = response.data  # type: ignore
            logger.info(f"Received network provisioning status: {provisioning_notification}")
            if provisioning_notification.provisioning_state == proto.EnumProvisioning.PROVISIONING_SUCCESS_NEW_AP:
                return
            if provisioning_notification.provisioning_state != proto.EnumProvisioning.PROVISIONING_STARTED:
                raise RuntimeError(f"Unexpected provisioning state: {provisioning_notification.provisioning_state}")
        else:
            raise RuntimeError("Only expect to receive Action ID 0x84, 0x85, or 0x0C responses after scan request")
    raise RuntimeError("Loop should not exit without return")


async def connect_to_access_point(manager: ResponseManager, ssid: str, password: str) -> None:
    """Top level method to connect to an access point.

    Args:
        manager (ResponseManager): manager used to perform the operation
        ssid (str): SSID of WiFi network to connect to
        password (str): password of WiFi network  to connect to

    Raises:
        RuntimeError: Received unexpected response.
    """
    entries = await get_scan_results(manager, await scan_for_networks(manager))
    try:
        entry = [entry for entry in entries if entry.ssid == ssid][0]
    except IndexError as exc:
        raise RuntimeError(f"Did not find {ssid}") from exc

    await connect_to_network(manager, entry, password)
    logger.info(f"Successfully connected to {ssid}")
    
async def set_date_time(manager: ResponseManager) -> None:
    """Get and then set the camera's date, time, timezone, and daylight savings time status

    Args:
        manager (ResponseManager): manager used to perform the operation
    """
    # First find the current time, timezone and is_dst
    tz = pytz.timezone(get_localzone().key)
    now = tz.localize(datetime.now(), is_dst=None)
    try:
        is_dst = now.tzinfo._dst.seconds != 0  # type: ignore
        offset = (now.utcoffset().total_seconds() - now.tzinfo._dst.seconds) / 60  # type: ignore
    except AttributeError:
        is_dst = False
        offset = (now.utcoffset().total_seconds()) / 60  # type: ignore
    if is_dst:
        offset += 60  # Handle daylight savings time
    offset = int(offset)
    logger.info(f"Setting the camera's date and time to {now}:{offset} {is_dst=}")

    # Build the request bytes
    datetime_request = bytearray(
        [
            0x0F,  # Command ID
            10,  # Length of following datetime parameter
            *now.year.to_bytes(2, "big", signed=False),  # uint16 year
            now.month,
            now.day,
            now.hour,
            now.minute,
            now.second,
            *offset.to_bytes(2, "big", signed=True),  # int16 offset in minutes
            is_dst,
        ]
    )
    datetime_request.insert(0, len(datetime_request))

    # Send the request
    logger.debug(f"Writing: {datetime_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.COMMAND_REQ_UUID.value, datetime_request, response=True)
    response = await manager.get_next_response_as_tlv()
    assert response.id == 0x0F
    assert response.status == 0x00
    logger.info("Successfully set the date time.")


async def clear_certificate(manager: ResponseManager) -> None:
    """Clear the camera's COHN certificate.

    Args:
        manager (ResponseManager): manager used to perform the operation

    Raises:
        RuntimeError: Received unexpected response
    """
    logger.info("Clearing any preexisting COHN certificate.")

    clear_request = bytearray(
        [
            0xF1,  # Feature ID
            0x66,  # Action ID
            *proto.RequestClearCOHNCert().SerializePartialToString(),
        ]
    )
    clear_request.insert(0, len(clear_request))

    # Send the request
    logger.debug(f"Writing: {clear_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.COMMAND_REQ_UUID.value, clear_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0xF1 or response.action_id != 0xE6:
            raise RuntimeError(
                "Only expect to receive Feature ID 0xF1 Action ID 0xE6 responses after clear cert request"
            )
        manager.assert_generic_protobuf_success(response.data)
        logger.info("COHN certificate successfully cleared")
        return
    raise RuntimeError("Loop should not exit without return")


async def create_certificate(manager: ResponseManager) -> None:
    """Instruct the camera to create the COHN certificate.

    Args:
        manager (ResponseManager): manager used to perform the operation

    Raises:
        RuntimeError: Received unexpected response
    """
    logger.info("Creating a new COHN certificate.")

    create_request = bytearray(
        [
            0xF1,  # Feature ID
            0x67,  # Action ID
            *proto.RequestCreateCOHNCert().SerializePartialToString(),
        ]
    )
    create_request.insert(0, len(create_request))

    # Send the request
    logger.debug(f"Writing: {create_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.COMMAND_REQ_UUID.value, create_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0xF1 or response.action_id != 0xE7:
            raise RuntimeError(
                "Only expect to receive Feature ID 0xF1 Action ID 0xE7 responses after create cert request"
            )
        manager.assert_generic_protobuf_success(response.data)
        logger.info("COHN certificate successfully created")
        return
    raise RuntimeError("Loop should not exit without return")


@dataclass(frozen=True)
class Credentials:
    """COHN credentials."""

    certificate: str
    username: str
    password: str
    ip_address: str
    macaddress:str

    def __str__(self) -> str:
        return json.dumps(asdict(self), indent=4)


async def get_cohn_certificate(manager: ResponseManager) -> str:
    """Get the camera's COHN certificate

    Args:
        manager (ResponseManager): manager used to perform the operation

    Raises:
        RuntimeError: Received unexpected response

    Returns:
        str: certificate in string form.
    """
    logger.info("Getting the current COHN certificate.")

    cert_request = bytearray(
        [
            0xF5,  # Feature ID
            0x6E,  # Action ID
            *proto.RequestCOHNCert().SerializePartialToString(),
        ]
    )
    cert_request.insert(0, len(cert_request))

    # Send the request
    logger.debug(f"Writing: {cert_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.QUERY_REQ_UUID.value, cert_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0xF5 or response.action_id != 0xEE:
            raise RuntimeError("Only expect to receive Feature ID 0xF5 Action ID 0xEE responses after get cert request")
        cert_response: proto.ResponseCOHNCert = response.data  # type: ignore
        manager.assert_generic_protobuf_success(cert_response)
        logger.info("COHN certificate successfully retrieved")
        return cert_response.cert
    raise RuntimeError("Loop should not exit without return")


async def get_cohn_status(manager: ResponseManager) -> proto.NotifyCOHNStatus:
    """Get the COHN status until it is provisioned and connected.

    Args:
        manager (ResponseManager): manager used to perform the operation

    Raises:
        RuntimeError: Received unexpected response

    Returns:
        proto.NotifyCOHNStatus: Connected COHN status that includes the credentials.
    """
    logger.info("Checking COHN status until provisioning is complete")

    status_request = bytearray(
        [
            0xF5,  # Feature ID
            0x6F,  # Action ID
            *proto.RequestGetCOHNStatus(register_cohn_status=True).SerializePartialToString(),
        ]
    )
    status_request.insert(0, len(status_request))

    # Send the scan request
    logger.debug(f"Writing: {status_request.hex(':')}")
    await manager.client.write_gatt_char(GoProUuid.QUERY_REQ_UUID.value, status_request, response=True)
    while response := await manager.get_next_response_as_protobuf():
        if response.feature_id != 0xF5 or response.action_id != 0xEF:
            raise RuntimeError(
                "Only expect to receive Feature ID 0xF5, Action ID 0xEF responses after COHN status request"
            )
        cohn_status: proto.NotifyCOHNStatus = response.data  # type: ignore
        logger.info(f"Received COHN Status: {cohn_status}")
        if cohn_status.state == proto.EnumCOHNNetworkState.COHN_STATE_NetworkConnected:
            return cohn_status
    raise RuntimeError("Loop should not exit without return")


async def provision_cohn(manager: ResponseManager) -> Credentials:
    """Helper method to provision COHN.

    Args:
        manager (ResponseManager): manager used to perform the operation

    Returns:
        Credentials: COHN credentials to use for future COHN communication.
    """
    logger.info("Provisioning COHN")
    await clear_certificate(manager)
    await create_certificate(manager)
    certificate = await get_cohn_certificate(manager)
    # Wait for COHN to be provisioned and get the provisioned status
    status = await get_cohn_status(manager)
    logger.info("Successfully provisioned COHN.")
    credentials = Credentials(
        certificate=certificate,
        username=status.username,
        password=status.password,
        ip_address=status.ipaddress,
        macaddress=status.macaddress
    )
    logger.info(credentials)
    return credentials



async def start_station_mode(ssid: str, password: str, identifier: str | None) -> None:
    manager = ResponseManager()
    try: 
        client = await connect_to_bluetooth(manager.notification_handler, identifier)
        manager.set_client(client)
        await set_date_time(manager)
        await connect_to_access_point(manager, ssid, password)
        credentials = await provision_cohn(manager)  
        cleaned_identifier = identifier.replace(" ", "")
        certificate_path = os.path.join(os.path.dirname(__file__), 'certificates', f'{cleaned_identifier}.crt')
        with open(certificate_path, "w") as fp:
            fp.write(credentials.certificate)
            #logger.info(f"Certificate written to {certificate.resolve()}") 
        return credentials
    except Exception as exc:  # pylint: disable=broad-exception-caught
        logger.error(repr(exc))
    finally:
        if manager.is_initialized:
            await manager.client.disconnect()

