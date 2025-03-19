import asyncio
import websockets
import json
import requests
import time
from threading import Thread
from queue import Queue
import pymysql
from datetime import date

# Store connected clients and last API response
clients = set()
should_exit = False
last_api_response = None
message_queue = Queue()

# Load database configuration
try:
    with open('db_config/db_config.json') as config_file:
        db_config = json.load(config_file)
        DB_PORT = db_config.get("port", 3306)  # Default to 3306 if port isn't specified
except Exception as e:
    print(f"Error loading database configuration: {e}")
    db_config = None

connection_pool = None
if db_config:
    try:
        connection_pool = pymysql.connect(
            host=db_config['host'],
            database=db_config['database'],
            user=db_config['user'],
            password=db_config['password'],
            port=DB_PORT,
            cursorclass=pymysql.cursors.DictCursor
        )
        print("Connection pool created successfully")
    except Exception as e:
        print(f"Error creating connection pool: {e}")

def fetch_data():
    """Fetch and process data similar to the PHP code provided."""
    query = """
        SELECT 
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
    
    try:
        # Ensure connection pool is available
        if not connection_pool:
            return json.dumps({"status": "error", "message": "No database connection available"})

        with connection_pool.cursor() as cursor:
            cursor.execute(query)
            result_set = cursor.fetchall()

        # Process the fetched data
        result_array = []
        for row in result_set:
            crow = {
                'slot_start_time': row['slot_start_time'],
                'g_customer_id': row['g_customer_id'],
                'g_customer_name': row['g_customer_name'],
                'g_start_time': row['g_start_time'],
                'g_duration': row['g_duration'],
                'g_end_time': row['g_end_time'],
                'g_status': row['g_status'],
                'card_id': row['card_id'],
                'g_order_by': row['g_order_by']
            }
            result_array.append(crow)

        result = {}
        for rrow in result_array:
            g_customer_id = rrow['g_customer_id'].split(',')
            g_customer_name = rrow['g_customer_name'].split(',')
            g_start_time = rrow['g_start_time'].split(',')
            g_duration = rrow['g_duration'].split(',')
            g_status = rrow['g_status'].split(',')
            g_card_id = rrow['card_id'].split(',')
            g_order_by = rrow['g_order_by'].split(',')
            g_end_time = rrow['g_end_time'].split(',') if rrow['g_end_time'] else []

            for key, val in enumerate(g_start_time):
                slot_time = rrow['slot_start_time']
                if slot_time not in result:
                    result[slot_time] = {}
                
                result[slot_time][val] = {
                    'g_customer_name': g_customer_name[key],
                    'g_start_time': g_start_time[key],
                    'g_duration': g_duration[key],
                    'g_status': g_status[key],
                    'g_card_id': g_card_id[key],
                    'g_end_time': g_end_time[key] if key < len(g_end_time) else None,
                    'g_customer_id': g_customer_id[key] if key < len(g_customer_id) else None,
                    'g_order_by': g_order_by[key] if key < len(g_order_by) else None
                }

            # Sort the entries by 'g_order_by' value
            result[slot_time] = dict(sorted(
                result[slot_time].items(),
                key=lambda item: int(item[1]['g_order_by'].split(',')[0])
            ))

        # JSON output
        return json.dumps({"status": "success", "message":"success", "data": result}, default=str)
    
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

def json_object_to_string(json_object):
    return json.dumps(json_object, separators=(',', ':'), ensure_ascii=False)

def fetch_api_data():
    """Fetch data from the GoPro API"""
    try:
        response = requests.get('http://192.168.21.26/gopro/getslots.php')
        if response.status_code == 200:
            print(response)
            # Get the text content and handle it as needed
            content = response.text.strip()  # Remove any whitespace
            return content
        else:
            print(f"API Error: Status code {response.status_code}")
            return None
    except Exception as e:
        print(f"API fetch error: {str(e)}")
        return None

def api_polling_thread():
    """Background thread for polling the API"""
    global last_api_response
    
    while not should_exit:
        try:
            # Fetch current API data
            current_data = fetch_api_data()
            print("fetch")
            # If this is the first fetch or data has changed
            """print(last_api_response)
            print(current_data)"""
            if json.loads(current_data) != json.loads(last_api_response):
                print("API data changed, queueing update...")
                
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

async def main():
    # First API fetch to initialize last_api_response
    global last_api_response
    last_api_response = fetch_api_data()
    # Start the API polling thread
    polling_thread = Thread(target=api_polling_thread, daemon=True)
    polling_thread.start()
    
    server = await websockets.serve(
        handle_client,
        "192.168.21.26",
        55777,
        ping_interval=20,
        ping_timeout=60
    )
    
    print("WebSocket server started on ws://192.168.21.26:55777")
    
    # Create the broadcast task
    broadcast_task = asyncio.create_task(broadcast_messages())
    
    try:
        while not should_exit:
            await asyncio.sleep(1)
    except asyncio.CancelledError:
        pass
    finally:
        # Cancel the broadcast task
        broadcast_task.cancel()
        try:
            await broadcast_task
        except asyncio.CancelledError:
            pass
        await shutdown_server(server, asyncio.get_event_loop())

if __name__ == "__main__":
    # Get the event loop
    loop = asyncio.get_event_loop()
    
    # Set up shutdown handlers
    main_task = loop.create_task(main())
    
    try:
        loop.run_until_complete(main_task)
    except KeyboardInterrupt:
        print("\nReceived keyboard interrupt...")
        should_exit = True
        main_task.cancel()
        try:
            loop.run_until_complete(main_task)
        except asyncio.CancelledError:
            pass
    finally:
        loop.close()
        print("Server shutdown complete")