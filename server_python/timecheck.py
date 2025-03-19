import requests
# import cv2
import json
import time
import requests
from datetime import datetime, timedelta
import threading
import pymysql
from pymysql.cursors import DictCursor
from pathlib import Path
from base64 import b64encode

# Global variables
should_exit = False
camera_action = "stopped"
capture = None
video_writer = None
count = 0
recording_thread = None 

writing_file_name = ""

# Database connection pool
try:
    with open('db_config/db_config.json') as config_file:
        db_config = json.load(config_file)    
    connection_pool = pymysql.connect(
        host=db_config['host'],
        database=db_config['database'],
        user=db_config['user'],
        password=db_config['password']
    )
    print("Connection pool created successfully")
except Exception as e:
    print(f"Error: {e}")

def start_stream():
    username = "gopro"
    password = "18eC1P6Pymv9"
    certificate = str(Path("cohn.crt"))    
    token = b64encode(f"{username}:{password}".encode("utf-8")).decode("ascii")   
    url = "https://192.168.0.177/gopro/camera/stream/start"
    print(f"Starting the preview stream: sending {url}")

    response = requests.get(
        url,
        timeout=10,
        headers={"Authorization": f"Basic {token}"},
        verify=str(certificate),
    )
    response.raise_for_status()
    print("Stream started successfully")

def stream_stop():
    username = "gopro"
    password = "18eC1P6Pymv9"
    certificate = str(Path("cohn.crt"))    
    token = b64encode(f"{username}:{password}".encode("utf-8")).decode("ascii")   
    url = "https://192.168.0.177/gopro/camera/stream/stop"
    print(f"Stopping the preview stream: sending {url}")

    response = requests.get(
        url,
        timeout=10,
        headers={"Authorization": f"Basic {token}"},
        verify=str(certificate),
    )
    response.raise_for_status()
    print("Stream stopped successfully")

def fetch_data_from_db():
    query = """
        SELECT 
            slot_start_time, 
            GROUP_CONCAT(SUBSTRING_INDEX(C.customer_id, ' ', 1)) AS g_customer_id,
            GROUP_CONCAT(FR.id) AS card_id,
            GROUP_CONCAT(SUBSTRING_INDEX(C.customer_name, ' ', 1)) AS g_customer_name,
            GROUP_CONCAT(FR.status) AS g_status,
            GROUP_CONCAT(start_time) AS g_start_time, 
            GROUP_CONCAT(duration) AS g_duration, 
            GROUP_CONCAT(end_time) AS g_end_time,
            MAX(FR.id) AS max_id
        FROM 
            flight_rotation AS FR 
        JOIN 
            customer AS C ON FR.customer_id = C.customer_id 
        WHERE DATE(FR.slot_start_time) = CURRENT_DATE
        GROUP BY 
            FR.slot_start_time, FR.order_by
        ORDER BY 
            max_id ASC
    """

    with connection_pool.cursor() as cursor:
        cursor.execute(query)
        result_set = cursor.fetchall()

    result_array = []
    for row in result_set:
        crow = {
            'slot_start_time': row[0],
            'g_customer_id': row[1],
            'g_customer_name': row[3],
            'g_start_time': row[5],
            'g_duration': row[6],
            'g_end_time': row[7],
            'g_status': row[4],
            'card_id': row[2]
        }
        result_array.append(crow)

    result = {}
    for rrow in result_array:
        g_customer_id = rrow['g_customer_id'].split(",")
        g_customer_name = rrow['g_customer_name'].split(",")
        g_start_time = rrow['g_start_time'].split(",")
        g_duration = rrow['g_duration'].split(",")
        g_status = rrow['g_status'].split(",")
        g_card_id = rrow['card_id'].split(",")
        
        if rrow['g_end_time']:
            g_end_time = rrow['g_end_time'].split(",")
        else:
            g_end_time = [None] * len(g_start_time)

        for key, val in enumerate(g_start_time):
            result.setdefault(rrow['slot_start_time'], {}).setdefault(val, {})
            result[rrow['slot_start_time']][val]['g_customer_name'] = g_customer_name[key]
            result[rrow['slot_start_time']][val]['g_start_time'] = g_start_time[key]
            result[rrow['slot_start_time']][val]['g_duration'] = g_duration[key]
            result[rrow['slot_start_time']][val]['g_status'] = g_status[key]
            result[rrow['slot_start_time']][val]['g_card_id'] = g_card_id[key]
            result[rrow['slot_start_time']][val]['g_end_time'] = g_end_time[key]
            result[rrow['slot_start_time']][val]['g_customer_id'] = g_customer_id[key]

    return result
    
def fetch_current_data_from_db():
    query = """
        select * from flight_rotation where status = "ongoing"
    """

    # Execute the query using connection pool
    with connection_pool.cursor() as cursor:
        cursor.execute(query)
        result_set = cursor.fetchone()  # Fetch a single record

    # Print or process the result
    if result_set:
        return result_set
    else:
        return None   
    

def updateAllSchedules(time):
    global writing_file_name
    try:
        current_time = time
        cnx = connection_pool
        cursor = cnx.cursor()

        completed_query = "UPDATE flight_rotation SET status = 'complete' WHERE end_time < %s"
        cursor.execute(completed_query, (current_time,))
        
        ongoing_query = """
        UPDATE flight_rotation 
        SET status = 'ongoing' 
        WHERE start_time <= %s AND end_time >= %s
        """
        cursor.execute(ongoing_query, (current_time, current_time))
        
        completed_query = "UPDATE flight_rotation SET status = 'schedule' WHERE start_time > %s"
        cursor.execute(completed_query, (current_time,))
        
        update_query = "UPDATE flight_rotation SET status = 'next' WHERE id = (SELECT id FROM ( SELECT id FROM flight_rotation WHERE status = 'schedule' ORDER BY start_time ASC LIMIT 1 ) AS temp_table )"
        cursor.execute(update_query)
        
        select_query = """
        SELECT * FROM flight_rotation 
        WHERE status = 'ongoing' 
        LIMIT 1;
        """
        
        cursor.execute(select_query)
        result = cursor.fetchone()
        
        if result:
            writing_file_name = str(result[2])+"_"+str(result[4]).replace(':', '-')
            
        cnx.commit()
        
    except Exception as e:
        print(f"Error executing update: {e}")
    finally:
        if cursor:
            cursor.close()

def updateDB(status, g_card_id):
    query = "UPDATE flight_rotation SET status = '" + str(status) + "' WHERE id = '" + str(g_card_id) + "'"
    execute_query(query)

def checkPreviousNextExist():
    update_query = "UPDATE flight_rotation SET status = 'complete'  WHERE id IN (SELECT id FROM ( SELECT id FROM flight_rotation WHERE start_time < CURRENT_TIME AND status NOT IN ('ongoing', 'hold') AND status IN ('next') ORDER BY start_time DESC) AS temp)"
    execute_query(update_query)

def checkNext():
    checkPreviousNextExist()
    update_query = "UPDATE flight_rotation SET status = 'next' WHERE id = (SELECT id FROM ( SELECT id FROM flight_rotation WHERE status = 'schedule' ORDER BY start_time ASC LIMIT 1 ) AS temp_table )"
    execute_query(update_query)

def execute_query(query, type="", selectone="no"):
    try:
        cnx = connection_pool
        cursor = cnx.cursor(pymysql.cursors.DictCursor)
        if type == "select" and selectone == "no":
            cursor.execute(query)
            result = cursor.fetchall()
            return result
        elif type == "select" and selectone == "yes":
            cursor.execute(query)
            result = cursor.fetchone()
            return result
        else:
            cursor.execute(query)
            cnx.commit()
    except Exception as e:
        print(f"Error executing query: {e}")
    finally:
        if cursor:
            cursor.close()
            
def checkNextSlot(current_time_str):
    global camera_action,writing_file_name
    # Update query to set status to 'ongoing'
    update_query = f"""
        UPDATE flight_rotation 
        SET status = 'ongoing' 
        WHERE '{current_time_str}' BETWEEN start_time AND end_time 
        AND (status != 'ongoing' and status != 'hold');
    """

    # Execute the update query
    try:
        with connection_pool.cursor() as cursor:
            cursor.execute(update_query)
            connection_pool.commit()  # Commit the transaction
            affected_rows = cursor.rowcount  # Get the number of affected rows
            
            if affected_rows > 0:
                writing_file_name = "yes"
                camera_action = "run"
                result = fetch_current_data_from_db()
                writing_file_name = str(result[2])+"_"+str(result[4]).replace(':', '-')
                print(f"{affected_rows} record(s) updated. Executing checkNext...")
                checkNext()  # Call your checkNext function here
            else:
                print("No records updated. Skipping checkNext.")
    except Exception as e:
        print(f"Error executing update query: {e}")

def check_schedule(data):
    global camera_action
    current_time = datetime.now() - timedelta(minutes=0)
    current_time_str = current_time.strftime("%H:%M:%S")    
    if data is None:
        checkNextSlot(current_time_str)
        return

    end_time = data[6]
    g_id = data[0]
    if current_time_str <= end_time:
        camera_action = "run"
    elif current_time_str > end_time:
        print("camera stopping")
        camera_action = "stop"
        updateDB('complete', g_id)
    

def api_polling_thread():
    global last_api_response
    
    while not should_exit:
        try:
            current_data = fetch_current_data_from_db()
            check_schedule(current_data)
            
        except Exception as e:
            print(f"Error in API polling thread: {str(e)}")
        
        time.sleep(0.3)
        
def capture_video():
    global should_exit,writing_file_name
    udp_url = f"udp://@192.168.21.109:8554"
    cap = cv2.VideoCapture(udp_url)
    
    while not should_exit:
        ret, frame = cap.read()
        if not ret:
            print("Failed to read frame from the stream.")
            break  # Exit if the stream fails
            
        # Only create a file if we have a valid frame and camera_action is "run"
        if camera_action == "run":
            # Generate timestamped file name
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            filename = f"gopro_stream_{writing_file_name}.mp4"
            
            frame_height, frame_width = frame.shape[:2]
            fourcc = cv2.VideoWriter_fourcc(*'mp4v')
            out = cv2.VideoWriter(filename, fourcc, 25, (frame_width, frame_height))
            start_time = time.time()
            frames_written = 0  # Counter to track if any frames were written
            
            while camera_action == "run":
                ret, frame = cap.read()
                if ret:
                    out.write(frame)
                    frames_written += 1
                else:
                    break
                    
            # Only save the file if we actually wrote some frames
            out.release()
            if frames_written > 0:
                end_time = time.time()
                duration = end_time - start_time
                print(f"Saved video: {filename} | Duration: {duration:.2f} seconds")
            else:
                # If no frames were written, remove the empty file
                try:
                    os.remove(filename)
                except OSError:
                    print(f"Failed to remove empty file: {filename}")
        
        # Add a small delay to prevent excessive CPU usage when camera_action is "stop"
        elif camera_action == "stop":
            time.sleep(0.1)
            
    cap.release()      

def main():
    global should_exit,writing_file_name
    current_time = datetime.now() - timedelta(minutes=0)
    current_time_str = current_time.strftime("%H:%M:%S")
    updateAllSchedules(current_time_str)
    # stream_stop()
    # start_stream()
    # capture_thread = threading.Thread(target=capture_video, daemon=True)
    # capture_thread.start()
    
    polling_thread = threading.Thread(target=api_polling_thread, daemon=True)
    polling_thread.start()
    
    try:
        while not should_exit:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\nReceived keyboard interrupt...")
        should_exit = True
        # stream_stop()
if __name__ == "__main__":
    main()