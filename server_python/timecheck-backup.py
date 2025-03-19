import time
import requests
from datetime import datetime
from threading import Thread
from mysql.connector import pooling


# Global variable to control the exit of the thread
should_exit = False
last_api_response = None
connection_pool = pooling.MySQLConnectionPool(pool_name="pynative_pool", pool_size=1,pool_reset_session=True,host='localhost',database='sales',user='root',password='')

def fetch_api_data():
    """Fetch data from the GoPro API."""
    try:
        start_time = time.time()
        response = requests.get('http://192.168.21.26/gopro/getslots.php')
        end_time = time.time()
        # Calculate the time taken
        time_taken = end_time - start_time

        # Print the result
        print(f"Time taken to get the response: {time_taken:.4f} seconds")        
        if response.status_code == 200:
            return response.json()  # Return the JSON response
        else:
            print(f"API Error: Status code {response.status_code}")
            return None
    except Exception as e:
        print(f"API fetch error: {str(e)}")
        return None


def updateDB(status, g_card_id):
    """Simulated database update function."""
    # In a real application, this function would execute an SQL query to update the status
    query = "UPDATE flight_rotation SET status = '" + status + "' WHERE id = '" + g_card_id + "'"
    execute_query(query)
    #print(f"Updating card_id {g_card_id} to status '{status}'")

def checkNext():
    query = "Select id from flight_rotation WHERE status = 'schedule' ORDER BY slot_start_time, order_by ASC LIMIT 0,1"
    result = execute_query(query,'select');
    print(result[0]['id'])
    if result and len(result) > 0 and 'id' in result[0]:
        # Extract the id
        flight_id = result[0]['id']
        print(flight_id)
        query = "Select id from flight_rotation WHERE status = 'next' ORDER BY slot_start_time, order_by ASC LIMIT 0,1"
        result = execute_query(query, 'select');
        # Query to update the status to 'next'
        if not (result and len(result) > 0 and 'id' in result[0]):
            update_query = f"UPDATE flight_rotation SET status = 'next' WHERE id = '{flight_id}'"
            execute_query(update_query)

def execute_query(query,type="",selectone="no"):
    try:
        # Get a connection from the pool
        cnx = connection_pool.get_connection()
        # Create a cursor object to execute queries
        cursor = cnx.cursor(dictionary=True)
        if type == "select" and selectone == "no" :
            # Execute the query
            cursor.execute(query)
            # Get the results
            result = cursor.fetchall()
            # Return the results
            return result
        elif type == "select" and selectone == "yes" :
            # Execute the query
            cursor.execute(query)
            # Get the results
            result = cursor.fetchone()
            # Commit the changes to the database
            return result
        else:
            cursor.execute(query)
            # Commit the changes to the database
            cnx.commit()
    except Exception as e:
            print(f"Error executing query: {e}")
    finally:
        # Close the cursor and connection
        if cursor is not None:
            cursor.close()
        if cnx is not None:
            cnx.close()

def check_schedule(data):
    next_slot_found = False
    ongoing_slot_found = False
    """Check the schedule for matches with the current system time."""
    current_time = datetime.now().strftime("%H:%M")   # Get current time in HH:MM:SS format
    #print(current_time)
    # Iterate over the data to find matches
    previous_min_time = '';
    for date, times in data.get("data", {}).items():
        for start_time, details in times.items():
            end_time = details.get("g_end_time")
            g_status = details.get("g_status")
            g_id = details.get("g_card_id")
            print([current_time , end_time[:5],g_status])
            if start_time and end_time:
                #print([start_time,current_time,end_time])
                # If slot is currently ongoing
                if start_time[:5] <= current_time < end_time[:5]:  # Compare only HH:MM
                    #print(f"Slot is ongoing: Start Time: {start_time}, Current Time: {current_time}:00")
                    updateDB('ongoing', g_id)  # Replace with your logic to get g_card_id
                    continue  # Move to the next time slot
                elif current_time >= end_time[:5]:
                    #print(f"Slot is completed: Start Time: {start_time}, End Time: {end_time}")
                    updateDB('complete', g_id)
                    continue                
            checkNext()
            # Check for end time to determine if the next entry should start

            if end_time and end_time == current_time:
                print(f"Reached End Time: {end_time}, Next Entry Starts Now!")

def api_polling_thread():
    """Background thread for polling the API."""
    global last_api_response
    
    while not should_exit:
        try:
            # Fetch current API data
            current_data = fetch_api_data()
            #print("API data changed, checking schedule...")
            #print(current_data)
            check_schedule(current_data)
           
            # Update last known response
            last_api_response = current_data
            
        except Exception as e:
            print(f"Error in API polling thread: {str(e)}")
        
        # Wait for 500ms before next check
        time.sleep(0.5)

def main():
    print("hello")
    global should_exit  # Declare the use of the global variable
    # Start the API polling thread
    polling_thread = Thread(target=api_polling_thread, daemon=True)
    polling_thread.start()
    
    try:
        while not should_exit:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\nReceived keyboard interrupt...")
        should_exit = True  # Set the flag to exit the loop

if __name__ == "__main__":
    print("hi")
    main()
