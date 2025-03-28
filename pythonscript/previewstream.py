# wifi_command_preview_stream.py/Open GoPro, Version 2.0 (C) Copyright 2021 GoPro, Inc. (http://gopro.com/OpenGoPro).
# This copyright was auto-generated on Wed, Sep  1, 2021  5:06:04 PM

import sys
import json
import argparse
from pathlib import Path
from base64 import b64encode
import requests
import time

from tutorial_modules import GOPRO_BASE_URL, logger


def main() -> None:
    username = "gopro"
    #password = "3DFb1LIhT32j"
    password = "EOoN8LdV64m4"
    certificate = str(Path("certificates/GoPro7418.crt"))    
    token = b64encode(f"{username}:{password}".encode("utf-8")).decode("ascii")   
    url = "https://10.3.47.14/gopro/camera/stream/start?port=8556"
    logger.info(f"Starting the preview stream: sending {url}")
    # Send the GET request and retrieve the response
    response = requests.get(
        url,
        timeout=10,
        headers={"Authorization": f"Basic {token}"},
        verify=str(certificate),
    )
    # Check for errors (if an error is found, an exception will be raised)
    response.raise_for_status()
    logger.info("Command sent successfully")
    # Log response as json
    logger.info(f"Response: {json.dumps(response.json(), indent=4)}")   
    """time.sleep(60)
    url = "https://192.168.21.109/gopro/camera/stream/stop"
    logger.info(f"Stopping the preview stream: sending {url}")

    # Send the GET request and retrieve the response
    response = requests.get(
        url,
        timeout=10,
        headers={"Authorization": f"Basic {token}"},
        verify=str(certificate),
    )
    # Check for errors (if an error is found, an exception will be raised)
    response.raise_for_status()
    logger.info("Command sent successfully")
    # Log response as json
    logger.info(f"Response: {json.dumps(response.json(), indent=4)}") """

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Enable the preview stream.")
    parser.parse_args()

    try:
        main()
    except Exception as e:  # pylint: disable=broad-exception-caught
        logger.error(e)
        sys.exit(-1)
    else:
        sys.exit(0)
