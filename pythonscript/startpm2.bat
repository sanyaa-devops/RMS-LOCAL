@echo off
:: Stop PM2 processes
echo Stopping PM2 processes...
call pm2 stop all

echo Waiting for 15 seconds...
timeout 15

echo Starting all PM2 processes...
call pm2 start all

echo PM2 processes restarted successfully.

call pm2 status

pause
