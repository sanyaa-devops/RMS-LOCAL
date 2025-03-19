import asyncio
import websockets
import json
import requests
import time
from threading import Thread
from queue import Queue
import pymysql
from pymysql.cursors import DictCursor
import subprocess
import platform
from pathlib import Path
from base64 import b64encode
import signal

# Store connected clients and last API response
clients = set()
should_exit = False
last_api_response = None
message_queue = Queue()

# Database connection pool
try:
    with open('db_config/db_config.json') as config_file:
        db_config = json.load(config_file)    
    connection_pool = pymysql.connect(
        host=db_config['host'],
        database=db_config['database'],
        user=db_config['user'],
        password=db_config['password'],
        autocommit=True
    )
except Exception as e:
    print(f"Error: {e}")
    
with connection_pool.cursor() as cursor:
    cursor.execute("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;")
connection_pool.commit()   

def fetch_data():
    query = """
        SELECT SQL_NO_CACHE
            slot_start_time, 
            GROUP_CONCAT(SUBSTRING_INDEX(C.customer_id, ' ', 1)) AS g_customer_id,
            GROUP_CONCAT(FR.id) AS card_id,
            GROUP_CONCAT(SUBSTRING_INDEX(TRIM(C.customer_name), ' ', 1)) AS g_customer_name,
            GROUP_CONCAT(FR.status) AS g_status,
            GROUP_CONCAT(start_time) AS g_start_time, 
            GROUP_CONCAT(duration) AS g_duration, 
            GROUP_CONCAT(end_time) AS g_end_time,
            GROUP_CONCAT(FR.order_by) AS g_order_by,
            MAX(FR.id) AS max_id
        FROM 
            flight_rotation AS FR 
        JOIN 
            customer AS C ON FR.customer_id = C.customer_id 
        WHERE DATE(FR.slot_start_time) = CURRENT_DATE
        GROUP BY 
            FR.slot_start_time
        ORDER BY 
            max_id DESC
    """
    
    with connection_pool.cursor(pymysql.cursors.DictCursor) as cursor:
        cursor.execute(query)
        result = cursor.fetchall()     
        
    result_dict = {}
    for row in result:
        
        slot_start_time = row['slot_start_time']
        
        g_customer_id = row['g_customer_id'].split(",")
        g_customer_name = row['g_customer_name'].split(",")
        g_start_time = row['g_start_time'].split(",")
        g_duration = row['g_duration'].split(",")
        g_status = row['g_status'].split(",")
        g_card_id = row['card_id'].split(",")
        g_order_by = row['g_order_by'].split(",")
        g_end_time = row['g_end_time'].split(",") if row['g_end_time'] else []

        result_dict[slot_start_time] = {}
        
        for i, start_time in enumerate(g_start_time):
            result_dict[slot_start_time][start_time] = {
                "g_customer_name": g_customer_name[i],
                "g_start_time": start_time,
                "g_duration": g_duration[i],
                "g_status": g_status[i],
                "g_card_id": g_card_id[i],
                "g_end_time": g_end_time[i] if i < len(g_end_time) else None,
                "g_customer_id": g_customer_id[i] if i < len(g_customer_id) else None,
                "g_order_by": g_order_by[i] if i < len(g_order_by) else None
            }
            
        # Sort by g_order_by if available
        result_dict[slot_start_time] = dict(sorted(
            result_dict[slot_start_time].items(),
            key=lambda x: int(x[1]["g_order_by"].split(",")[0]) if x[1]["g_order_by"] else 0
        )) 
        
    response = {
        "status": "success",
        "message": "Data fetched successfully.",
        "data": result_dict
    }
    return json.dumps(response) 


def api_polling_thread():
    """Background thread for polling the API"""
    global last_api_response
    
    while not should_exit:
        try:          
            # Fetch current API data
            current_data = fetch_data()
            
            # If this is the first fetch or data has changed
            if current_data is None or current_data != last_api_response:
                #print("API data changed, queueing update...")
                
                # Prepare the message
                message = {
                    "type": "api_update",
                    "timestamp": time.time(),
                    "data": current_data
                }
                
                # Put the message in the queue for the asyncio loop to handle
                message_queue.put(message)
                
                # Update last known response
                last_api_response = current_data
            
        except Exception as e:
            print(f"Error in API polling thread: {str(e)}")
        
        # Wait for 500ms before next check
        time.sleep(0.5)

async def broadcast_messages():
    """Coroutine to broadcast messages from the queue to all clients"""
    while not should_exit:
        # Check if there are any messages to broadcast
        while not message_queue.empty():
            message = message_queue.get()
            
            if clients:
                websockets_tasks = []
                for client in clients.copy():
                    try:
                        task = asyncio.create_task(
                            client.send(json.dumps(message))
                        )
                        websockets_tasks.append(task)
                    except Exception as e:
                        print(f"Error preparing broadcast: {str(e)}")
                
                # Wait for all send tasks to complete
                if websockets_tasks:
                    await asyncio.gather(*websockets_tasks, return_exceptions=True)
        
        # Short sleep to prevent busy-waiting
        await asyncio.sleep(0.1)

async def handle_client(websocket, path):
    try:
        # Add client to the set
        clients.add(websocket)
        client_id = id(websocket)
        print(f"New client connected (ID: {client_id}). Total clients: {len(clients)}")
        
        # Send initial API data if available
        if last_api_response is not None:
            welcome_message = {
                "type": "initial_state",
                "timestamp": time.time(),
                "data": last_api_response
            }
            await websocket.send(json.dumps(welcome_message))
        
        # Handle incoming messages
        async for message in websocket:
            try:
                print(f"Received message from client {client_id}: {message}")
                response = {
                    "type": "response",
                    "data": f"Server received: {message}"
                }
                await websocket.send(json.dumps(response))
            except json.JSONDecodeError:
                print(f"Received invalid JSON message from client {client_id}")
    
    except websockets.exceptions.ConnectionClosed:
        print(f"Client {client_id} connection closed unexpectedly")
    except Exception as e:
        print(f"Error handling client {client_id}: {str(e)}")
    finally:
        clients.remove(websocket)
        print(f"Client {client_id} disconnected. Total clients: {len(clients)}")

async def shutdown_server(server, loop):
    """Cleanup tasks tied to the service's shutdown."""
    print("Shutting down server...")
    server.close()
    await server.wait_closed()
    await asyncio.sleep(0.1)
    loop.stop()
    
def get_camera_ips():
    global camera_settings
    """Fetch camera IPs from the database."""
    try:
        with connection_pool.cursor() as cursor:
            sql = "SELECT * FROM camera_settings where status = '1'"
            cursor.execute(sql)
            camera_settings = cursor.fetchall() 
        return camera_settings
    except Exception as e:
        print(f"Error while fetching camera settings: {e}")
        return None 
        
def update_camera_status(id, output, status):
    """Update camera status and output in the database."""
    try:
        with connection_pool.cursor() as cursor:
            sql = """
                UPDATE camera_settings 
                SET output = %s, status = %s 
                WHERE id = %s
            """
            cursor.execute(sql, (output, status, id))
            connection_pool.commit()  # Ensure the changes are saved
    except Exception as e:
        print(f"Error while updating camera settings: {e}")
        return None  
        
def stream(ip, password, path, type):
    username = "gopro"
    certificate = str(Path(path))    
    token = b64encode(f"{username}:{password}".encode("utf-8")).decode("ascii")
    
    try:
        # Stop the stream
        url = f"https://{ip}/gopro/camera/stream/stop"
        response = requests.get(
            url,
            timeout=10,
            headers={"Authorization": f"Basic {token}"},
            verify=certificate,
        )
        print(response)
        
    except requests.exceptions.RequestException as e:
        print(f"Failed to stop the stream: {e}")
        return

    # If type is "both", also start the stream
    if type == "both":
        try:
            url = f"https://{ip}/gopro/camera/stream/start"
            response = requests.get(
                url,
                timeout=10,
                headers={"Authorization": f"Basic {token}"},
                verify=certificate,
            )
            # Print the complete response details
            print("Status Code:", response.status_code)
            print("Headers:", response.headers)
            print("Content:", response.content.decode('utf-8'))
            print("URL:", response.url)
            print("Request Headers:", response.request.headers)
            
        except requests.exceptions.RequestException as e:
            print(f"Failed to start the stream: {e}")         

def start_streaming_for_cameras():
    camera_ips = get_camera_ips()
    #print(camera_ips)
    successful_cameras = []
    for idx, camera_ip in enumerate(camera_ips):
        ip_address = camera_ip[3]
        camera_name = camera_ip[1]
        flag,output = ping_ip(ip_address)
        if flag:
            update_camera_status(camera_ip[0],output,"1")
            successful_cameras.append((ip_address,camera_name,camera_ip[5],camera_ip[6]))
            #stream(camera_ip[3],camera_ip[5],camera_ip[6],"both")
            #stream_start(camera_ip[3],camera_ip[5],camera_ip[6])
        else:
            update_camera_status(camera_ip[0],output,"1")
            successful_cameras.append((ip_address,camera_name,camera_ip[5],camera_ip[6]))
        return successful_cameras
        
def ping_ip(ip_address):
    """Ping an IP address and return its reachability status."""
    param = '-n' if platform.system().lower() == 'windows' else '-c'
    command = ['ping', '-c', '1', str(ip_address)]  # Ping once

    try:
        # Execute the ping command
        output = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        print("Raw output:", output.stdout)
        
        if "Destination host unreachable" in output.stdout or "Request timed out" in output.stdout:
            return False,output.stdout
        return True,output.stdout
    except Exception as e:
        print(f"An error occurred while pinging: {e}")
        return False,output.stdout     
        
async def check_ip_reachability(successful_camera_ips):
    """Periodically check the reachability of the specified IP addresses."""
    while not should_exit:
        if successful_camera_ips:
            for ip,camera_name in successful_camera_ips:
                is_reachable, output = ping_ip(ip)  # Ping each IP
                status_message = {"type": "ip_status_update", "ip": "192.168.21.109", "camera_name": "GoPro 9058", "reachable": false, "output": "\nPinging 192.168.21.109 with 32 bytes of data:\nReply from 192.168.21.80: Destination host unreachable.\n\nPing statistics for 192.168.21.109:\n   Packets: Sent = 1, Received = 1, Lost = 0 (0% loss),\n", "timestamp": 1729851799.3727145}
                if clients:  # Only send updates if there are connected clients
                    websockets_tasks = []
                    for client in clients.copy():
                        try:
                            task = asyncio.create_task(client.send(json.dumps(status_message)))
                            websockets_tasks.append(task)
                        except Exception as e:
                            print(f"Error sending IP status update: {str(e)}")

                    if websockets_tasks:
                        await asyncio.gather(*websockets_tasks, return_exceptions=True)
        else:
            # No successful cameras available; notify clients
            no_status_message = {"type": "ip_status_update", "ip": "192.168.21.109", "camera_name": "GoPro 9058", "reachable": False, "output": "\nPinging 192.168.21.109 with 32 bytes of data:\nReply from 192.168.21.80: Destination host unreachable.\n\nPing statistics for 192.168.21.109:\n   Packets: Sent = 1, Received = 1, Lost = 0 (0% loss),\n", "timestamp": 1729851799.3727145}
            if clients:
                websockets_tasks = []
                for client in clients.copy():
                    try:
                        task = asyncio.create_task(client.send(json.dumps(no_status_message)))
                        websockets_tasks.append(task)
                    except Exception as e:
                        print(f"Error sending no cameras update: {str(e)}")

                if websockets_tasks:
                    await asyncio.gather(*websockets_tasks, return_exceptions=True)            

        await asyncio.sleep(1)  # Wait for 10 seconds before the next check         

# Signal handler
def signal_handler(signum, frame):
    global should_exit
    print("Signal received, stopping...")
    should_exit = True  # Set the flag to exit the main loop
    
async def main():
    # First API fetch to initialize last_api_response
    global last_api_response,camera_settings
    last_api_response = fetch_data()
    successful_camera_ips  = start_streaming_for_cameras()
    # Start the API polling thread
    polling_thread = Thread(target=api_polling_thread, daemon=True)
    polling_thread.start()
    
    server = await websockets.serve(
        handle_client,
        "192.168.0.177",
        55777,
        ping_interval=20,
        ping_timeout=60
    )
    
    print("WebSocket server started on ws://192.168.0.177:55777")
    
    # Create the broadcast task
    broadcast_task = asyncio.create_task(broadcast_messages())
    ip_check_task = asyncio.create_task(check_ip_reachability(successful_camera_ips))
    
    try:
        while not should_exit:
            await asyncio.sleep(1)
    except asyncio.CancelledError:
        pass
    finally:
        # Cancel the broadcast task 
        broadcast_task.cancel()
        ip_check_task.cancel()
        for ip,camera_name,username,path in successful_camera_ips:
            stream(ip,username,path,"single")
        try:
            await asyncio.gather(broadcast_task,ip_check_task)
        except asyncio.CancelledError:
            pass
        await shutdown_server(server, asyncio.get_event_loop())

if __name__ == "__main__":
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)    
    # Get the event loop
    loop = asyncio.get_event_loop()
    
    # Set up shutdown handlers
    main_task = loop.create_task(main())
    
    try:
        loop.run_until_complete(main_task)
    except KeyboardInterrupt:
        print("\nReceived keyboard interrupt...")
        should_exit = True
        connection_pool.close()
        main_task.cancel()
        try:
            loop.run_until_complete(main_task)
        except asyncio.CancelledError:
            pass
    finally:
        loop.close()
        print("Server shutdown complete")