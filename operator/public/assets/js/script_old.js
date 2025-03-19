
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
        //updateStatus('connecting');
        // console.log('connecting')
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
        runCron() {
            this.makeApiRequest(`${this.apiURL}/controllers/cron.php`, {
                method: 'POST',
            }).then(data => {
                alert("cron has been completed successfully");
            })
        },
        tunnelholdCustomer() {
            if (confirm('Do you want to make Tunnel Hold?')) {
              
                this.makeApiRequest(`${this.apiURL}/controllers/tunnelhold.php`, {
                    method: 'POST',
                }).then(data => {
                    //console.log(data);
                    if(data.flag===2){
                        this.enableInteractions();
                    }else{
                        this.disableInteraction();
                    }
                 })
                .catch(error => {
                    // Handle errors if any occur during the API request
                    console.error('Error:', error);
                     this.enableInteractions();
                });
            }
        },
        enableInteractions(){
            document.getElementById('container').classList.remove('disabled'); // Remove disabled class
            document.getElementById('overlay').style.display = 'none'; // Hide the overlay
        },
        disableInteraction(){
            document.getElementById('container').classList.add('disabled'); // Add disabled class
            document.getElementById('overlay').style.display = 'flex'; // Show the overlay
        },
        logout() {
            this.makeApiRequest(`${this.apiURL}/controllers/logout.php`, {
                method: 'POST',
            }).then(data => {
                //console.log(data);
                if (data.status) {
                    window.location.href = `${this.apiURL}/login`;  // Example redirect
                }
            })
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
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
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
        async scrollToClass() {
            if(this.hasScrolled){
                 return;
            }
            await this.$nextTick(() => {
                const element = document.querySelector('.ongoing') ||
                    document.querySelector('.next') ||
                    [...document.querySelectorAll('.complete')].pop();

                if (element !== null) {
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
            })
            //console.log(this.slots[timeSlot].length)
            
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
            // Set default headers if not provided
            options.headers = {
                'Content-Type': 'application/json',
                ...(options.headers || {}), // Merge any provided headers
            };
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                throw new Error('Response is not in JSON format');
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
           //console.log(this.sorted_data);
            // Process all time slots in a single pass
            for (const [timeSlot, customerData] of Object.entries(this.sorted_data)) {
                // Initialize containers for current time slot
                const customers = Object.values(customerData);
                const uniqueCustomers = new Map();
                const customerStatusMap = new Map();
                const customerStatusHold = new Map();

                // First pass: Build status map and collect unique customers
                for (const customer of customers) {
                    const customerId = customer.g_customer_id;
                    const customerKey = `${timeSlot}_${customerId}`;

                    // Update hold status
                    customerStatusMap.set(
                        customerKey,
                        customerStatusMap.has(customerKey) ?
                            customerStatusMap.get(customerKey) || (customer.g_status === 'hold' && customer.g_th==='0') :
                            (customer.g_status === 'hold' && customer.g_th==='0')
                    );
                    /* customerStatusHold.set(
                        customerKey,
                        customerStatusHold.has(customerKey) ?
                        customerStatusHold.get(customerKey) || (customer.g_th === "0" && customer.g_status === 'hold') :
                        (customer.g_th === "0" && customer.g_status === 'hold')
                    );  */

                   

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
                        //g_th:customerStatusHold.get(customerKey)
                    };
                });

               //console.log(this.slots[timeSlot]);

                // Store all customer details for the time slot
               
                // Add formatted time once per slot
                this.slots[timeSlot].formattedTime = this.formatTimeSlot(timeSlot);
                this.setCurrentStatus(this.sorted_data[this.slots[timeSlot][0]['g_slot_time']],this.slots[timeSlot]);
           
             }

               


           // console.log(this.cards);
          
            // console.log(this.slots);
            // Sort slots by time
            /*  this.slots = Object.fromEntries(
                Object.entries(this.slots)
                    .sort(([a], [b]) => new Date(a) - new Date(b))
            );  */
            this.slots = Object.fromEntries(
                Object.entries(this.slots)
                    .sort(([a], [b]) => new Date(a) - new Date(b))  // Sort by time slot
                    .map(([key, value]) => [key, value.sort((a, b) => a.g_customer_name.localeCompare(b.g_customer_name))])  // Sort customers alphabetically within each time slot
            );

            // Sort cards by order
            /* this.cards = Object.fromEntries(
                Object.entries(this.cards)
                    .sort(([, a], [, b]) => {
                        const orderA = parseInt(a[0]?.g_order_by) || 0;
                        const orderB = parseInt(b[0]?.g_order_by) || 0;
                        return orderA - orderB;
                    })
            ); */
            
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
        wifi_name: '',
        wifi_password: '',
        
        saveWifiSettings() {
            const data = {
                wifi_name: this.wifi_name,
                wifi_password: this.wifi_password
            };
            this.makeApiRequest(`${this.apiURL}/controllers/updateWifiSettings.php`, {
                method: 'POST',
                body: JSON.stringify(data)
            }).then(data => {
                //console.log(JSON.stringify(data))
               // alert(data.message);
            })
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
                    this.loadershow = true;
                    // Send an AJAX request to the backend
                    const response = await fetch(`${this.apiURL}/controllers/add_camera.php`, {
                        method: 'POST', // Use POST method
                        headers: {
                            'Content-Type': 'application/json' // Set the content type
                        },
                        body: JSON.stringify(data) // Convert data to JSON string
                    });

                    if (response.ok) {
                        const result = await response.json(); // Parse the response
                        this.loadershow = false;

                        if (result.status == 'max') {
                            //alert(result.message);
                            this.showNewInput = false;
                            return;
                        }
                        // Assuming the backend returns the added camera with an id
                        await getCameraSettings(this);

                        this.camerainfo = 'Camera Added Successfully!';
                        this.infoshow = true;

                        //redirect to patch page in new tab
                        window.open(`${this.apiURL}/controllers/configure_camera.php`, '_blank');

                        this.showNewInput = false; // Hide input fields after submission
                    } else {
                        console.error('Error adding camera:', response.statusText);
                        this.loadershow = false;

                    }
                } catch (error) {
                    this.loadershow = false;
                    console.error('Error:', error);
                }
            } else {
                this.errorMessage = true
            }

        },
        cancelNewInput() {
            this.errorMessage = false
            this.showNewInput = false; // Hide input fields without saving
        },
        async removeCamera(data, index) {
            if (confirm("Are your sure to Delete?")) {
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
                    }

                } catch (error) {
                    console.error('Error:', error);
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
            try {
                const response = await fetch(`${this.apiURL}/refreshCamera`, {
                    method: 'POST', // Use POST method
                    headers: {
                        'Content-Type': 'application/json' // Set the content type
                    },
                    body: JSON.stringify(data) // Convert data to JSON string
                });
                if (response.ok) {
                    const result = await response.json();
                }
            } catch (error) {
                console.error('Error:', error);
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
        //console.log(obj.cameras);
    } catch (error) {
        console.error('Error fetching camera settings:', error);
    }
}


/* function footerContents() {
    let content = {
        '192.168.21.109': {
            ip: '192.168.21.109',
            type: 'ip_status_update',
            camera_name: 'GoPro 9058',
            reachable: false
        },
        '192.168.21.10': {
            ip: '192.168.21.10',
            type: 'ip_status_update',
            camera_name: 'GoPro 9058',
            reachable: true
        }
    }
    return Object.values(content);
} */
