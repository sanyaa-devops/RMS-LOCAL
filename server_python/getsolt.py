import pymysql
import json
from datetime import date

# Load database configuration
try:
    with open('db_config/db_config.json') as config_file:
        db_config = json.load(config_file)
        DB_PORT = db_config.get("port", 3306)  # Default to 3306 if port isn't specified
except Exception as e:
    print(f"Error loading database configuration: {e}")
    db_config = None

# Establish a connection pool (if config was loaded successfully)
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
        return json.dumps({"status": "success", "data": result}, default=str)
    
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    response = fetch_data()
    print(response)
