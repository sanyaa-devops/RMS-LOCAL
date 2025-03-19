import asyncio
import pymysql
import json
import os
from station_mode_connect import start_station_mode  # Import the function

# Database connection pool setup
def create_connection_pool():
    try:
        config_path = os.path.join(os.path.dirname(__file__), 'db_config', 'db_config.json')
        with open(config_path) as config_file:
            db_config = json.load(config_file)    
        return pymysql.connect(
            host=db_config['host'],
            database=db_config['database'],
            user=db_config['user'],
            password=db_config['password']
        )
    except Exception as e:
        print(f"Error creating database connection pool: {e}")
        return None

async def main():
    connection_pool = create_connection_pool()
    if connection_pool is None:
        return  # Exit if connection pool creation failed

    try:
        query = """
                SELECT 
                    ws.wifi_name, 
                    ws.wifi_password, 
                    COALESCE(cs.camera_name, 'No Camera Found') AS camera_name 
                FROM 
                    wifi_settings ws
                LEFT JOIN 
                    (SELECT camera_name 
                     FROM camera_settings 
                     WHERE status = 0 
                     ORDER BY id DESC 
                     LIMIT 1) cs
                ON 1 = 1
                LIMIT 1
                    """

        with connection_pool.cursor() as cursor:
            # Execute the query to get Wi-Fi settings
            cursor.execute(query)
            # Fetch all the rows
            wifi_settings = cursor.fetchall()
            
            # Iterate over and print the result
            for wifi in wifi_settings:
                ssid = wifi[0]
                password = wifi[1]
                identifier = wifi[2]

        if identifier == "No Camera Found":
            print("Please add a camera or Bluetooth device.")
        else:
            credentials = await start_station_mode(ssid, password, identifier)
            if credentials:
                cleaned_identifier = identifier.replace(" ", "")
                crt_path = "certificates/"+cleaned_identifier+".crt"
                with connection_pool.cursor() as cursor:
                    update_query = """
                        UPDATE camera_settings
                        SET camera_username = %s, camera_password = %s, camera_ip_address = %s, camera_crt_path = %s, camera_crt = %s, status = %s, camera_macaddress = %s
                        WHERE camera_name = %s
                    """
                    cursor.execute(update_query, (credentials.username, credentials.password, credentials.ip_address, crt_path, credentials.certificate, "1", credentials.macaddress, identifier))
                    connection_pool.commit()
                    print("Camera settings updated successfully.")
            else:
                print("Failed to retrieve credentials from start_station_mode.")
    except Exception as e:
        print(f"Error in main function: {e}")
    finally:
        connection_pool.close()  # Ensure the connection pool is closed

if __name__ == "__main__":
    asyncio.run(main())
