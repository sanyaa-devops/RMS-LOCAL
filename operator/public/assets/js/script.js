
var $websocketURL = websockets;


class WebSocketClient {
    constructor(url, options = {}) {
        this.url = url;
        this.options = {
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || Infinity,
            debug: options.debug || false,
            onMessage: options.onMessage || (() => { })
        };
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.socket = null;
        this.connect();
    }

    connect() {
        this.log('Attempting to connect...');
        updateStatus('connecting');
        console.log('connecting')
        this.socket = new WebSocket(this.url);

        this.socket.onopen = () => {
            this.log('Connected to WebSocket server');
            this.reconnectAttempts = 0;
        };

        this.socket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data) {
                    this.options.onMessage(data);
                } else {
                    console.warn("No data returned");
                }

            } catch (e) {
                this.log('Error parsing message: ' + e.message);
            }
        };

        this.socket.onerror = (error) => {
            this.log('WebSocket error: ' + error.message);
        };

        this.socket.onclose = () => {
            //updateStatus('disconnected');
            this.log('Connection closed');
            this.scheduleReconnect();
        };
    }

    scheduleReconnect() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }

        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.options.reconnectInterval;
            this.log(`Scheduling reconnect in ${delay}ms (attempt ${this.reconnectAttempts})`);
            this.reconnectTimer = setTimeout(() => this.connect(), delay);
        } else {
            this.log('Max reconnection attempts reached');
        }
    }

    send(data) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            const message = typeof data === 'string' ? data : JSON.stringify(data);
            this.socket.send(message);
            this.log(`Sent: ${message}`);
        } else {
            this.log('Cannot send message - connection not open');
        }
    }

    handleMessage(data) {
        // Handle different message types
        if (data.type === 'msg') {
            this.log(`Server message: ${data.data}`);
        }
    }

    log(message) {
        if (this.options.debug) {
            console.log(`[WebSocket] ${message}`);
        }
        // logMessage(message);
    }
}


function navbarData() {


    return {

        apiURL: $baseurl,
        tunnelActive:false,
        init(){
            this.tunnelActive = false;
            this.checkTunnelStatus();
			 this.startCronJob();
         },
         checkTunnelStatus(){
            this.makeApiRequest(`${this.apiURL}/controllers/checktunnel.php`, {
                 method: 'POST'
             }).then((res)=>{
                if(res.flag == 1){
                    this.tunnelActive = true;
                }else{
                    this.tunnelActive = false;
                }
             })
             
          },
		  startCronJob() {
            // Run the cron job immediately
            this.autoCronJob();
        
            // Schedule the cron job to run every 2hr
            setInterval(() => {
                this.autoCronJob();
            }, 2 * 60 * 60 * 1000); // 2 hr
        },
		 autoCronJob(){
            this.makeApiRequest(`${this.apiURL}/controllers/cron.php`, {
                method: 'POST',
            }).then(data => {
                console.log("Cron runner executed at:", new Date().toLocaleString());
            }).catch(()=>{
                console.error("Error running the cron job");
            })
        },
        
        runCron() {
            this.makeApiRequest(`${this.apiURL}/controllers/cron.php`, {
                method: 'POST',
            }).then(data => {
                alert("Cron has been completed successfully");
            }).catch(()=>{
                console.error("Error running the cron job");
            })
        },
        tunnelholdCustomer() {
            const msg = this.tunnelActive ?'Do you want to make Tunnel UnHold?':'Do you want to make Tunnel Hold?';
            if (confirm(msg)) {
                this.makeApiRequest(`${this.apiURL}/controllers/tunnelhold.php`, {
                    method: 'POST',
                }).then(data => {
                  if(data.flag == 1){
                    this.tunnelActive = true;
                  }else{
                    this.tunnelActive = false;
                  }
                })
               
            }
        },
        logout() {
            if(confirm('Are you sure want to logout?')){
                this.makeApiRequest(`${this.apiURL}/controllers/logout.php`, {
                    method: 'POST',
                }).then(data => {
                    
                    if (data.status) {
                        setTimeout(()=>{
                            window.location.href = `${this.apiURL}/login`; 
                        },1000)
                        Toast.fire({
                            icon: "success",
                            title: "Logout successfully"
                        });
                    }
                })
            }
        },
        /**
         * Make an API request to the specified URL with optional parameters.
         * @param {string} url - The API endpoint to request.
         * @param {Object} [options] - Optional parameters for the fetch request.
         * @returns {Promise} - A promise that resolves to the response data.
         */
        async makeApiRequest(url, options = {}) {
            
            // Set default headers if not provided
            options.headers = {
                'Content-Type': 'application/json',
                ...(options.headers || {}), // Merge any provided headers
            };
            return fetch(url, options)
                .then(async response => {
                  
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                  
                    return await response.json();
                }).catch(()=>{
                   
                });
        },
    }

}

function scheduleData() {
    return {
        loading: true,
        sorted_data: {},
        valueToChange: 0.5,
        slots: {},
        cards: {},
        wsClient: null,
        socket: null,
        apiURL: $baseurl,
        footerContents:[],
        hasScrolled:false,
         init() {
            this.connect();
         },
        effect() {
            // this.scrollToClass()
        },
        showLoader(){
            document.getElementById('overlay').style.display = 'flex';
        },
        hideLoader(){
            setTimeout(() => {
                document.getElementById('overlay').style.display = 'none';
            }, 1000); // 2 seconds delay
        },
        async scrollToClass() {
            if(this.hasScrolled){
                 return;
            }
            await this.$nextTick(() => {
                const element = document.querySelector('.ongoing') ||
                    document.querySelector('.next') ||
                    [...document.querySelectorAll('.complete')].pop();

                    if (element && element != undefined) {
                    // If any of the elements are found, scroll to the first one found
                    element.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
                } else {
                    console.warn('No elements found to scroll to.');
                    //console.log('Current DOM:', document.body.innerHTML);
                }
            });
            this.hasScrolled = true;
        },
        saveOriginalPosition(evt) {
            // Save the original position before dragging
            const cardId = evt.item.getAttribute('data-id');
            const timeSlot = evt.from.getAttribute('x-sort');

            // Find the original index
            const originalIndex = Array.from(evt.from.children).findIndex(child => {
                return child.getAttribute('data-id') === cardId;
            });
            this.originalPosition = { timeSlot, originalIndex };
        },

        /**
         * Update customer data on the server and refresh the schedule.
         * @param {Object} customer - The customer object to be updated.
         */
        update(customer, fulldetails) {
            this.makeApiRequest(`${this.apiURL}/controllers/update_duration.php`, {
                method: 'POST',
                body: JSON.stringify(fulldetails),
            })
                .then(data => {
                    this.sorted_data = data; // Update sorted_data with the new data
                    console.log("Updated schedule after change:", data);
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error updating schedule:', error);
                    this.loading = false;
                });
        },
        handleEnter(customerDetails, fulldetails) {
            let currentDuration = parseFloat(customerDetails.duration);
            customerDetails.duration = Math.min(30, Math.max(this.valueToChange, Math.ceil(currentDuration * 2) / 2));
            this.update(customerDetails, fulldetails);
        },

        /**
         * Decrease the duration for a customer and send the update to the server.
         * @param {Object} customer - The customer whose duration will be decreased.
         */
        decreaseDuration(customer, fulldetails) {

            customer.duration = Math.max(parseFloat(customer.duration) - this.valueToChange, this.valueToChange).toString();
            //this.update(customer, fulldetails);
			customer.showOkClose = true;
        },

        /**
         * Increase the duration for a customer and send the update to the server.
         * @param {Object} customer - The customer whose duration will be increased.
         */
        increaseDuration(customer, fulldetails) {

            const newDuration = Number(customer.duration) + this.valueToChange;
            if (newDuration <= 30) customer.duration = newDuration.toString();
           // this.update(customer, fulldetails);
		   customer.showOkClose = true;

        },
		send(customerDetails,fulldetails) {
            // Call your send function here
           // console.log('Sending updated value:', customerDetails.duration);
            this.update(customerDetails,fulldetails);
            customerDetails.previousDuration = customerDetails.duration;
           //this.retransform();
            customerDetails.showOkClose = false;
        },
        
        close(customerDetails){
            customerDetails.duration = customerDetails.previousDuration || customerDetails.duration;
           // console.log(customerDetails);
            customerDetails.showOkClose = false;
        },
		checkAllCardIsScheduled(timeSlotCards){
            const scheduleCount = timeSlotCards.filter(customer => customer.g_status === 'schedule').length;
            const totalCards = timeSlotCards.length;
            return scheduleCount===totalCards;
            
        },
        showOkCloseButtons(customerDetails) {
            // Show the OK and Close buttons if the duration has changed
            customerDetails.showOkClose = true;
            // Store the previous duration before the change
            if (!customerDetails.previousDuration) {
                customerDetails.previousDuration = customerDetails.duration;
            }
        },

        isButtonDisabledForDecrease(time_slot) {
            return this.isButtonDisabled(time_slot, 'decrease');
        },

        isButtonDisabledForInput(time_slot) {
            return this.isButtonDisabled(time_slot, 'input');
        },

        isButtonDisabledForIncrease(time_slot) {
            return this.isButtonDisabled(time_slot, 'increase');
        },

        isButtonDisabledForDelete(time_slot) {
            return this.isButtonDisabled(time_slot, 'delete');
        },

        isButtonDisabledForHold(time_slot) {
            return this.isButtonDisabled(time_slot, 'hold');
        },

        isButtonDisabled(time_slot, type) {
            const allComplete = this.cards[time_slot].every(customer => customer.g_status === 'complete');
            const ongoingCount = this.cards[time_slot].filter(customer => customer.g_status === 'ongoing').length;
            const nextCount = this.cards[time_slot].filter(customer => customer.g_status === 'next').length;
            const scheduleCount = this.cards[time_slot].filter(customer => customer.g_status === 'schedule').length;
            const totalDuration = this.slots[time_slot].reduce((sum, slot) => sum + parseFloat(slot.duration || 0), 0);
            const holdCount = this.cards[time_slot].filter(customer => customer.g_status === 'hold').length;
            switch (type) {
                case 'decrease':
                    return allComplete || ((ongoingCount === 1 || nextCount === 1) && scheduleCount === 0);
                case 'input':
                    return allComplete || ((ongoingCount === 1 || nextCount === 1) && scheduleCount === 0);
                case 'increase':
                    return totalDuration >= 30 || allComplete || ((ongoingCount === 1 || nextCount === 1) && scheduleCount === 0);
                case 'delete':
                case 'hold':
                    return allComplete || ((ongoingCount === 1 || nextCount === 1) && scheduleCount === 0 && holdCount===0);
                default:
                    return false;
            }
        },
        hasTimePassed() {
            // Get the date from the element's data attribute
            let dataDate = this.$el.getAttribute('data-date');
            const now = new Date();

            // Split the time slot into start and end times
            const [startTime, endTime] = dataDate.split(' - ').map(t => {
                const [hour, minute] = t.split(':').map(Number); // Convert to numbers
                return { hour, minute }; // Return an object with hour and minute
            });



            // Convert current time, start time, and end time to minutes
            const currentTimeInMinutes = now.getHours() * 60 + now.getMinutes();
            const startTimeInMinutes = startTime.hour * 60 + startTime.minute;
            const endTimeInMinutes = endTime.hour * 60 + endTime.minute;

            // Return true if current time is before start time or after end time
            return currentTimeInMinutes < startTimeInMinutes || currentTimeInMinutes > endTimeInMinutes;
        },

        hasTimePassed(dataDate) {

            if (!dataDate) return false; // Handle missing or invalid date data

            const now = new Date();
            const [startTime, endTime] = dataDate.split(' - ').map(time => {
                const [hour, minute] = time.split(':').map(Number);
                const date = new Date(now); // Use today's date
                date.setHours(hour, minute, 0, 0); // Set hour, minute, second, and millisecond
                return date;
            });
            // console.log([now,endTime,now>endTime])
            // Only return true if the current time is after the end time
            return now > endTime;
        },


        async removeCustomer(customer, timeSlot) {
            if (confirm('Do you want to delete the slot?')) {
                
				const index = this.slots[timeSlot].indexOf(customer);

				// If the customer is found, remove them from the array
				if (index !== -1) {
					this.slots[timeSlot].splice(index, 1);
					this.cards[timeSlot] = [];
				}
				this.makeApiRequest(`${this.apiURL}/controllers/delete_rotation.php`, {
					method: 'POST',
					body: JSON.stringify(customer),
				}).then(data => {
                   
					if(this.slots[timeSlot].length===0){
						const slotElement = document.querySelector(`[data-id='schedule-row-${timeSlot}']`);
						if (slotElement) {
							slotElement.remove(); // Remove the parent div if the slot is empty
						}
					} 
				   //console.log(data)
				}).catch(()=>{
                   
                })
				//console.log(this.slots[timeSlot].length)
			}
            
        },
        updateRotation() {
            this.makeApiRequest(`${this.apiURL}/controllers/update_rotation.php`, {
                method: 'POST',
                body: JSON.stringify(this.rotationForm),
            }).then(data => {
               // console.log(JSON.stringify(data))
            })
        },

        /**
         * Make an API request to the specified URL with optional parameters.
         * @param {string} url - The API endpoint to request.
         * @param {Object} [options] - Optional parameters for the fetch request.
         * @returns {Promise} - A promise that resolves to the response data.
         */
        async makeApiRequest(url, options = {}) {
            try {
                // Show loader before making the request
                this.showLoader();
        
                // Set default headers if not provided
                options.headers = {
                    'Content-Type': 'application/json',
                    ...(options.headers || {}), // Merge any provided headers
                };
        
                // Make the fetch request
                const response = await fetch(url, options);
        
                // Check if the response is okay (status 200-299)
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.statusText}`);
                }
        
                // Parse JSON response
                const data = await response.json();
        
                // Hide the loader after receiving the response
                this.hideLoader();
        
                return data;
        
            } catch (error) {
                // In case of any error (network error, parsing error, etc.), hide the loader and log the error
                this.hideLoader();
                console.error('API request failed:', error); // Log the error for debugging
                throw error; // Re-throw the error to be handled by the caller
            }
        },
         formatTimeSlot(time_slot) {
            const date = new Date(time_slot);
            const startHour = date.getHours();
            const startMinute = date.getMinutes();
            const endDate = new Date(date.getTime() + 30 * 60000); // Add 30 minutes
            return `${this.padTime(startHour)}:${this.padTime(startMinute)} - ${this.padTime(endDate.getHours())}:${this.padTime(endDate.getMinutes())}`;
        },

        padTime(value) {
            return String(value).padStart(2, '0'); // Pad single digits with a leading zero
        },



        connect() {
           
            if(!this.wsClient){
                this.wsClient = new WebSocketClient($websocketURL, {
                    reconnectInterval: 5000,
                    maxReconnectAttempts: Infinity,
                    debug: false,
                    onMessage: this.handleWebSocketMessage.bind(this)
                });
            }
        },
        async handleWebSocketMessage(data) {
            try {
                // Check if data contains 'data' property before parsing
                 // Check if data contains 'data' property before parsing
                    if (!data?.data && data.type !== 'ip_status_update') {
                        console.log('No "data" field in received WebSocket message:');
                        this.slots = {};
                        this.cards = {};
                        return;
                    }
                    if(data?.data){    
                        const response = JSON.parse(data.data);

                        // Verify response has a success status
                        if (response.status !== 'success') {
                            console.error('Error status from server:', data);
                            return;
                        }

                        // Update sorted_data and determine which transformation function to call

                        this.sorted_data = { ...response.data };
                        // console.log(data.type);
                        if (data.type === 'initial_state' ||data.type === 'api_update') {
                           
                            this.retransform();
                        }
                       

                    }else{
                        if(data.type=== 'ip_status_update'){
                            this.updateOrAddCameraStatus(data);
                        }
                    }
                    if (!this.scrollToClass()) {
                        console.warn('Element with class "ongoing" not found for scrolling.');
                    }

            } catch (error) {
                console.error("Failed to parse JSON:", error);
                //console.log("Received data:", data);
                // Optional: Handle invalid JSON data
            }
        },

       

        updateOrAddCameraStatus(data) {
            const index = this.footerContents.findIndex(item => item.ip === data.ip);
            if (index !== -1) {
              // Update existing entry
              this.footerContents[index].reachable = data.reachable;
              this.footerContents[index].timestamp = data.timestamp;
              this.footerContents[index].output = data.output;
            } else {
              // Add new entry
              this.footerContents.push({
                ip: data.ip,
                camera_name: data.camera_name,
                reachable: data.reachable,
                timestamp: data.timestamp,
                output: data.output
              });
            }
           
          },

        retransform2() {
            //console.log(this.sorted_data);
            Object.keys(this.sorted_data).forEach(timeSlot => {
                // Initialize arrays for slots and cards if not already created
                if (!this.slots[timeSlot]) {
                    this.slots[timeSlot] = []; // For unique customers
                    this.cards[timeSlot] = [];  // For customer details
                }
                //console.log(timeSlot)
                // Extract customers for the current timeSlot and sort them by g_start_time
                const customers = Object.values(this.sorted_data[timeSlot]);

                // Temporary array for sorted customer details
                const tempCustomerDetails = [];

                // Populate tempCustomerDetails and slots array
                customers.forEach(customerDetails => {
                    // Push customer details into tempCustomerDetails array
                    // console.log(customerDetails)
                    tempCustomerDetails.push(customerDetails);

                    // Check if customer already exists in the slots array
                    const existingCustomer = this.slots[timeSlot].find(
                        customer => customer.g_customer_name === customerDetails.g_customer_name
                    );

                    // If not already added, push unique customer information into slots
                    if (!existingCustomer) {
                        this.slots[timeSlot].push({
                            g_customer_name: customerDetails.g_customer_name,
                            g_customer_id: customerDetails.g_customer_id,
                            g_slot_time: timeSlot,
                            duration: customerDetails.g_duration
                        });
                    }


                });
                // Assign sorted customer details to the cards array for the current timeSlot
                this.cards[timeSlot] = tempCustomerDetails;

            });


            this.slots = Object.fromEntries(
                Object.entries(this.slots).sort((a, b) => new Date(a[0]) - new Date(b[0]))
            );

            this.cards = Object.fromEntries(
                Object.entries(this.cards).sort((a, b) => parseInt(a[1].g_order_by) - parseInt(b[1].g_order_by))
            );


        },

        retransformCards($sortedData){
            if (!$sortedData || Object.keys($sortedData).length === 0) {
                this.cards = {};
                return;
            }

            for (const [timeSlot, customerData] of Object.entries($sortedData)) {
                // Initialize containers for current time slot
                const customers = Object.values(customerData);
                this.cards[timeSlot] = customers;
               }
        },
        retransform() {
            // console.log(this.sorted_data);
             // Early exit with empty initialization
             if (!this.sorted_data || Object.keys(this.sorted_data).length === 0) {
                 this.slots = {};
                 this.cards = {};
                 return;
             }
 
             // Initialize containers
            /*  this.slots = {};
             this.cards = {}; */
            // console.log(this.sorted_data);
             // Process all time slots in a single pass
             for (const [timeSlot, customerData] of Object.entries(this.sorted_data)) {
                 // Initialize containers for current time slot
                 const customers = Object.values(customerData);
                 const uniqueCustomers = new Map();
                 const customerStatusMap = new Map();
                 const customerHoldMap = new Map();
 
                 // First pass: Build status map and collect unique customers
                 for (const customer of customers) {
                     const customerId = customer.g_customer_id;
                     const customerKey = `${timeSlot}_${customerId}`;
 
                     // Update hold status
                     customerStatusMap.set(
                         customerKey,
                         customerStatusMap.has(customerKey) ?
                             customerStatusMap.get(customerKey) || (customer.g_status === 'hold' && customer.g_th==='0'):
                             (customer.g_status === 'hold' && customer.g_th==='0')
                     );
 
                     
 
                    
 
                     // Store unique customers with their latest data
                     if (!uniqueCustomers.has(customer.g_customer_name)) {
                         uniqueCustomers.set(customer.g_customer_name, customer);
                     }
                 }
                 this.cards[timeSlot] = customers;
                 // Create slot entries for unique customers
                 this.slots[timeSlot] = Array.from(uniqueCustomers.values()).map(customer => {
                     const customerId = customer.g_customer_id;
                     const customerKey = `${timeSlot}_${customerId}`;
 
                     
 
                     return {
                         g_customer_name: customer.g_customer_name,
                         g_customer_id: customer.g_customer_id,
                         g_slot_time: timeSlot,
                         duration:customer.g_duration,
                         g_status: customerStatusMap.get(customerKey),
                         status: customer.g_status,
                         previousDuration:customer.g_duration,
                         tunnelHoldStatus:customerHoldMap.get(customerKey)
                     };
                 });
 
                
                
                 // Add formatted time once per slot
                 this.slots[timeSlot].formattedTime = this.formatTimeSlot(timeSlot);
                 this.setCurrentStatus(this.sorted_data[this.slots[timeSlot][0]['g_slot_time']],this.slots[timeSlot]);
            
              }
            this.slots = Object.fromEntries(
                 Object.entries(this.slots)
                     .sort(([a], [b]) => new Date(a) - new Date(b))  // Sort by time slot
                     .map(([key, value]) => [key, value.sort((a, b) => a.g_customer_name.localeCompare(b.g_customer_name))])  // Sort customers alphabetically within each time slot
             );
             
         },
       
        handleMove(evt){
         
            const draggedItem = evt.dragged;
            const targetItem = evt.related;
            const nextItem = targetItem.nextElementSibling;
           const prevItem = targetItem.previousElementSibling;
            //console.log(draggedItem,targetItem,nextItem,prevItem);
            if (
                targetItem.classList.contains('filtered') ||
                (nextItem && nextItem.classList.contains('filtered')) ||
                (prevItem && prevItem.classList.contains('filtered'))
              ) {
                return false; // Disallow moving into this position
              }
          
              // Otherwise, allow the move
              return true;
        },
        setCurrentStatus(slots,customers){
            const allowedStatuses = ["next","schedule"];
            customers.forEach(customer => {
                // Loop through each slot and find a match
                const matchingSlot = Object.values(slots).find(slot => 
                    slot.g_customer_name === customer.g_customer_name &&
                    allowedStatuses.includes(slot.g_status)
                );
                if (matchingSlot) {
                   customer.duration = matchingSlot.g_duration;
               }
            });
            
           
        }, 
         async handleCardDrop(evt, timeSlot) {
         
            const cards = Alpine.raw(this.cards[timeSlot]);
            const droppedCard = cards.splice(evt.oldIndex, 0)[0];
            cards.splice(evt.newIndex, 0, droppedCard);
            this.cards[timeSlot] = cards;
            var cid = '';
            evt.from.querySelectorAll('.name-card').forEach(function (e) {
                cid += e.getAttribute('data-id') + ","
            });
            let result = cid.replace(/,$/, '');
            await fetch(`${this.apiURL}/controllers/card_change.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', // Specify the content type
                   
                },
                body: JSON.stringify({ 'data': result })
            })
            //this.retransform();

        },



        holdCustomer(customer) {
           // customer.g_status = !customer.g_status;
            this.makeApiRequest(`${this.apiURL}/controllers/state_change.php`, {
                method: 'POST',
                body: JSON.stringify(customer),
            }).then(data => {
               // console.log(JSON.stringify(data))
            })
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.magic('now', () => {
        let currentTime = Alpine.reactive({
            value: new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            })
        });

        // Set an interval to update the time every second
        setInterval(() => {
            currentTime.value = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
        }, 1000);

        // Return a function that accesses the reactive value
        return () => currentTime.value;
    });

});


function wifisettingData() {
    return {
        apiURL: $baseurl,
        showModal: true,
        buttonName:'Save',
        errorMessage:false,
        wifi_name: '',
        wifi_password: '',
        saveWifiSettings() {
            if(!this.wifi_name){
                this.errorMessage = true;
                return;
            }
            if(!this.wifi_password){
                this.errorMessage = true;
                return;
            }
            this.errorMessage = false;
            this.buttonName = 'Saving....';
            const data = {
                wifi_name: this.wifi_name,
                wifi_password: this.wifi_password
            };
            this.makeApiRequest(`${this.apiURL}/controllers/updateWifiSettings.php`, {
                method: 'POST',
                body: JSON.stringify(data)
            }).then(data => {
                setTimeout(()=>{
                    this.buttonName = 'Saved';
                 },1000)
                 setTimeout(() => {
                    this.buttonName = 'Save';
                }, 2000); 
            }).catch(error => {
                console.error("Error saving WiFi settings:", error);
                this.buttonName = 'Error'; // Show "Error" if needed
                setTimeout(() => {
                    this.buttonName = 'Save';
                }, 1500); // Reset to "Save" after showing "Error"
            });
        },
        async init() {
            
            try {

                const response = await this.makeApiRequest(`${this.apiURL}/controllers/getWifiSettings.php`, {
                    method: 'POST',
                });

                const result = await response; // Parse the response
                // Assuming the backend returns the added camera with an id
                this.wifi_name = result.wifi_name;
                this.wifi_password = result.wifi_password;

            } catch (error) {
                console.error('Error:', error);

            }
        },
        /**
         * Make an API request to the specified URL with optional parameters.
         * @param {string} url - The API endpoint to request.
         * @param {Object} [options] - Optional parameters for the fetch request.
         * @returns {Promise} - A promise that resolves to the response data.
         */
        async makeApiRequest(url, options = {}) {
            // Set default headers if not provided
            options.headers = {
                'Content-Type': 'application/json',
                ...(options.headers || {}), // Merge any provided headers
            };
            const response = await fetch(url, options);
            if (!response.ok) {

                throw new Error('Network response was not ok');

            }
            return await response.json();   
        },
    }

}


function cameraSettings() {
    return {
        apiURL: $baseurl,
        isOpen: false,  // Modal open/close state
        showNewInput: false,
        errorMessage: false,
        newCamera: { name: '', bluetoothAddress: '' }, // Temporary new camera object
        camerainfo: '',
        infoshow: false,
        loadershow: false,
        cameras: [],

        addCamera() {
            this.showNewInput = true; // Show the input fields for a new camera
            this.newCamera = { name: '', bluetoothAddress: '' }; // Reset for fresh input
        },
        async submitNewCamera() {

            if (this.newCamera.name && this.newCamera.bluetoothAddress) {
                this.errorMessage = false;
                const data = {
                    name: this.newCamera.name,
                    bluetooth_name: this.newCamera.bluetoothAddress,
                };

                try {
                    this.showLoader();
                    //this.loadershow = true;
                    // Send an AJAX request to the backend
                    const response = await fetch(`${this.apiURL}/controllers/add_camera.php`, {
                        method: 'POST', // Use POST method
                        headers: {
                            'Content-Type': 'application/json' // Set the content type
                        },
                        body: JSON.stringify(data) 
                    });

                    if (response.ok) {
                        const result = await response.json(); // Parse the response

                        if(result){
                            this.hideLoader();
                            await getCameraSettings(this);
                           console.log(this.cameras)
                        }

                        if (result.status == 'max') {
                            //alert(result.message);
                            this.showNewInput = false;
                            return;
                        }
                        // Assuming the backend returns the added camera with an id
                       

                        this.camerainfo = 'Camera Added Successfully! Need to run the batch file in the following path. C:/sample_location/';
                        this.infoshow = true;

                        //redirect to patch page in new tab
                        window.open(`${this.apiURL}/controllers/addcamera.bat`, '');

                        this.showNewInput = false; // Hide input fields after submission
                    } else {
                        console.error('Error adding camera:', response.statusText);
                        //this.loadershow = false;
                        this.hideLoader();

                    }
                } catch (error) {
                    this.hideLoader();
                    //this.loadershow = false;
                    console.error('Error:', error);
                }
            } else {
                this.errorMessage = true
            }

        },
        showLoader(){
            document.getElementById('overlay').style.display = 'flex';
        },
        hideLoader(){
            document.getElementById('overlay').style.display = 'none';
            setTimeout(() => {
                document.getElementById('overlay').classList.remove('fadeOut');
                document.getElementById('overlay').style.display = 'none';
            }, 1000); // 2 seconds delay
        },
        cancelNewInput() {
            this.errorMessage = false
            this.showNewInput = false; // Hide input fields without saving
        },
        async removeCamera(data, index) {
            if (confirm("Are your sure to Delete?")) {
                this.showLoader();
                try {
                    // Send an AJAX request to the backend
                    const response = await fetch(`${this.apiURL}/controllers/deleteCameraSettings.php`, {
                        method: 'POST', // Use POST method
                        headers: {
                            'Content-Type': 'application/json' // Set the content type
                        },
                        body: JSON.stringify(data) // Convert data to JSON string
                    });
                    if (response.ok) {
                        this.cameras.splice(index, 1);
                        setTimeout(()=>{
                            this.hideLoader();
                        },1000)
                    }

                } catch (error) {
                    console.error('Error:', error);
                    this.hideLoader();
                }
            }
        },
        async getCameraInfo(id) {
            try {
                const response = await fetch(`${this.apiURL}/controllers/getCameraInfo.php`, {
                    method: 'POST', // Use POST method
                    headers: {
                        'Content-Type': 'application/json' // Set the content type
                    },
                    body: JSON.stringify({ 'id': id }) // Convert data to JSON string
                });
                if (response.ok) {
                    const result = await response.json();
                    this.camerainfo = result;
                    this.infoshow = true;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },
        async refreshCamera(data, index) {
            this.showLoader();
            try {
                const response = await fetch(`${this.apiURL}/controllers/refreshCamera.php`, {
                    method: 'POST', // Use POST method
                    headers: {
                        'Content-Type': 'application/json' // Set the content type
                    },
                    body: JSON.stringify(data) // Convert data to JSON string
                });
                if (response.ok) {
                   
                    const result = await response.json();
                    //console.log(this.cameras,index);
                    if (index >= 0 && index < this.cameras.length) {
                        this.cameras[index] = result.data;
                    }
                       
                }
                this.hideLoader();
            } catch (error) {
                console.error('Error:', error);
                this.hideLoader();
            }
        },
        async init() {
            await getCameraSettings(this);
        },


    };
}
async function getCameraSettings(obj) {
    try {
        const response = await fetch(`${obj.apiURL}/controllers/getCameraSettings.php`);

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        obj.cameras = await response.json(); // Assign the fetched cameras to the state
      
    } catch (error) {
        console.error('Error fetching camera settings:', error);
    }
}


document.addEventListener('alpine:init', () => {
    Alpine.store('globalState', {
        loading: false,
        startLoader() {
            this.loading = true;
        },
        hideLoader() {
            setTimeout(() => {
                this.loading = false;
            }, 1000);
        }
    });
    saveButtonDirective(Alpine);
});

function fileSaveSettings(){
    return {
        fileLoc:'',
        init(){
           this.fetchFileLocation();
        },
        fetchFileLocation() {
            fetch(`${$baseurl}/controllers/savefilepath.php`)  // Adjust with the actual endpoint
              .then(response => response.json())
              .then(data => {
                if (data && data.videoLocation) {
                  this.fileLoc = data.videoLocation; // Set the fetched value to x-model
                }
              })
              .catch(error => {
                console.error('Error fetching file location:', error);
              });
          }
    }
}

function saveButtonDirective(Alpine) {
    //console.log("called")
    Alpine.directive('save-btn', (el, { expression }, { evaluateLater, effect }) => {
        let isSaved = false;

        // Extract ajaxPath and validate from the passed object
        const options = expression ? JSON.parse(expression) : {};
        const ajaxPath = options.ajaxPath || '';  // Default to an empty string if no path is provided
        const validate = options.validate || false;  // Check if validate is true
        const classList = options.classList || [];  // Default to an empty array if no classList is provided

        // Add additional classes to the button
        classList.forEach(cls => {
            el.classList.add(cls);
        });

        // Function to update the button text
        const updateText = () => {
            el.innerText = isSaved ? 'Saved' : 'Save';
        };

        // Validate all input fields in the form manually
        const validateForm = () => {
            const form = el.closest('form'); // Find the form element
            const inputs = form.querySelectorAll('[x-model]'); // Select all elements with x-model
            let isValid = true;

            inputs.forEach(input => {
                const modelName = input.getAttribute('x-model');
                const value = input.type === 'checkbox' ? input.checked : input.value.trim(); // Handle checkbox and text input

                // Custom validation: mark invalid if empty or not valid based on criteria
                if (!value) {
                    isValid = false;
                    input.classList.add('is-invalid'); // Add invalid class
                    //showError(input, modelName, 'This field cannot be empty.');
                } else {
                    input.classList.remove('is-invalid'); // Remove invalid class
                    hideError(input);
                }

                // Clear error message on input change
                input.addEventListener('input', () => {
                    console.log("hello")
                    if (value.trim() !== '') {
                        input.classList.remove('is-invalid'); // Remove invalid class
                        hideError(input); // Remove error message
                    }
                });
            });

            return isValid;
        };

        // Show error message
        const showError = (input, modelName, message) => {
            let errorMessage = input.nextElementSibling;
            if (!errorMessage || !errorMessage.classList.contains('invalid-feedback')) {
                errorMessage = document.createElement('div');
                errorMessage.classList.add('invalid-feedback');
                input.insertAdjacentElement('afterend', errorMessage);
            }
            errorMessage.textContent = message;
        };

        // Hide error message
        const hideError = (input) => {
            let errorMessage = input.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('invalid-feedback')) {
                errorMessage.remove();
            }
        };

        // Function to get x-model data (send keys even if values are empty)
        const getXModelData = () => {
            const modelData = {};
            const modelElements = el.closest('form').querySelectorAll('[x-model]');

            modelElements.forEach(element => {
                const modelName = element.getAttribute('x-model');
                if (modelName) {
                    // Always include the key (modelName), even if the value is empty
                    modelData[modelName] = element.value || element.checked || element.selected || ''; // Default to empty string if no value
                }
            });

            return modelData;
        };

        // Handle the button click (with AJAX and disable)
        const handleClick = (event) => {
            event.preventDefault(); // Prevent form submission and page reload

            if (!isSaved) {
                if (validate && !validateForm()) {
                    // If validation fails, prevent the save action
                    //alert('Please fill in all required fields correctly.');
                    return; // Prevent further actions if validation fails
                }

                // Disable the button by adding the 'disabled' class
                el.classList.add('disabled');
                el.disabled = true;

                // Get x-model data
                const modelData = getXModelData();

                // Make AJAX request (simulate with a fetch request)
                if (ajaxPath) {
                    fetch(ajaxPath, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'save', data: modelData }) // Send x-model data only
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Optionally handle the response data
                        //console.log(data);
                        //console.log(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        // After AJAX, toggle button text and re-enable
                        isSaved = true;
                        updateText();
                        
                        // Remove 'disabled' class after saving
                        setTimeout(() => {
                            isSaved = false;
                            updateText();
                            el.classList.remove('disabled');
                            el.disabled = false;
                        }, 2000);
                    });
                }
            }
        };

        // Add the event listener to the button
        el.addEventListener('click', handleClick);

        // Initial button text
        updateText();
    });
}
function modalDataFunction(){
    return {
        apiURL:$baseurl,
        person:{
            interval:0,
            name:'',
            timeslot:null,
        },
        showButton:true,
        modalData: {
            details: {
                interval: 0
            }
        },
        modalClose(){
            this.showButton = true;
        },
        
        increaseInterval(person){
            if(person.interval<10){
                person.interval++;
            }else{
                this.showButton = false;
            }
        },
        decreaseInterval(person){
            if (person.interval > 0) {
                person.interval--;
            }else{
                this.showButton = false;
            }
        },
        async send(data){
            data = {...data,'timeslot':this.modalData.timeSlot}
            //console.log(data);
            try {
                const response = await fetch(`${this.apiURL}/controllers/interval.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const res = await response.json();
                
               await  this.close();  
                //console.log(res);
                
                if (res.status !== 'success') {
                    alert(res.message);
                    return;
                }else{
                    alert(res.message); 
                    this.close(); 
                }
               
                // this.hideLoader();
            } catch (error) {
                console.error('Error:', error);
                // this.hideLoader();
            }

           
        },
        close(){
            
            document.querySelector('button.btn-close').click();
            this.person.interval = 0;

            /* document.getElementById("intervalmodel").style.display = "none";
                var backdrop = document.querySelector('.modal-backdrop.fade.show');
                console.log(backdrop)
                if (backdrop) {
                    backdrop.remove();
                } */
        }
    };
}
