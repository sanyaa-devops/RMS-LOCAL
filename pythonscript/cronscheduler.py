import schedule
import time
import requests
import logging
from datetime import datetime
import subprocess
import psutil  # Install with `pip install psutil` if not already installed
import signal

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
server_process = None  # Global variable for server.py process
timecheck_process = None  # Global variable for timecheck.py process

def createschedulecron():
    url = "http://localhost/inflightdubai/operator/public/controllers/cron.php"
    try:
        r = requests.get(url)
        r.raise_for_status()  # Raises an HTTPError for bad responses (4xx or 5xx)
        logging.info(f"Success: {r.status_code}")

        # Assuming the response contains JSON with start_time and end_time
        data = r.json()  # Parse the JSON response
        #start_time = data.get("min")  # e.g., "14:55"
        #end_time = data.get("max")      # e.g., "15:05"
        start_time = "06:00"
        end_time = "00:01"
        #print(start_time)
        #print(end_time)
        if start_time and end_time:
            schedule_camera_tasks(start_time, end_time)

    except requests.exceptions.RequestException as e:
        logging.error(f"Error: {e}")
        start_time = "06:00"
        end_time = "00:01"

        if start_time and end_time:
            schedule_camera_tasks(start_time, end_time)        

def schedule_camera_tasks(start_time, end_time):
    # Clear existing jobs to avoid scheduling conflicts
    schedule.clear('camera')

    # Schedule the start camera task
    schedule.every().day.at(start_time).do(start_camera).tag('camera')
    logging.info(f"Scheduled start camera at {start_time}")

    # Schedule the stop camera task
    schedule.every().day.at(end_time).do(stop_camera).tag('camera')
    logging.info(f"Scheduled stop camera at {end_time}")
    
def start_program(filename):
    """Function to start a program in a new command prompt."""
    print(f"Starting {filename} in a new command prompt...")
    process = subprocess.Popen(
        ["cmd", "/c", "start", "cmd", "/c", f"python {filename}"],
        shell=True
    )
    time.sleep(1)  # Allow some time for the process to start
    return process    
    
def stop_program(process, command_name):
    """Stop a specific Python program and close the command prompt."""
    print(f"Stopping {command_name} and closing the command prompt...")

    # Find and terminate the specific process
    program_process = find_python_process(command_name)
    if program_process:
        program_process.terminate()  # Attempt graceful termination
        program_process.wait(timeout=5)  # Wait for termination
        print(f"{command_name} process terminated.")
    else:
        print(f"{command_name} process not found.")

    # Terminate the cmd window if it's still open
    if process and process.poll() is None:
        process.terminate()
        process.wait()
        print("Command prompt window closed.")

def find_python_process(command):
    # Search for the specific Python process running server.py
    for proc in psutil.process_iter(attrs=['pid', 'name', 'cmdline']):
        if proc.info['name'] == 'python.exe' and command in proc.info['cmdline']:
            return proc
    return None

def start_camera():
    global server_process, timecheck_process
    logging.info("Starting server.py...")
    server_process = start_program("server.py")

    # Wait 30 seconds before starting timecheck.py
    time.sleep(30)
    
    logging.info("Starting timecheck.py after delay...")
    timecheck_process = start_program("timecheck.py")

def stop_camera():
    global server_process, timecheck_process
    logging.info("Stopping camera programs...")
    
    # Stop server.py and timecheck.py individually
    stop_program(server_process, "server.py")
    server_process = None  # Reset process after stopping

    stop_program(timecheck_process, "timecheck.py")
    timecheck_process = None  # Reset process after stopping
signal.signal(signal.SIGTERM, lambda _signum, _frame: stop_camera())    
createschedulecron()
time.sleep(1)
start_camera()
# Schedule the cron job to check for start and end times
schedule.every().day.at("01:00").do(createschedulecron)

# Main loop
try:
    while True:
        schedule.run_pending()
        time.sleep(60)  # wait one minute
except KeyboardInterrupt:
    stop_camera()
    logging.info("Scheduler stopped.")
