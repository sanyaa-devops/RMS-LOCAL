var $websocketURL = websockets;

function scheduleData() {
    return {
        loading: true,
        sorted_data: {},
        nextSlotFound: false,
        valueToChange: 0.5,
        details: [],
        slots: {},
        cards: {},
        apiURL: $baseurl,
        wsClient: null,
        footerContents:[],

        init() {
            this.connect();
            this.$nextTick(() => this.scrollToClass());
            this.footerContents = [];
        },

        effect() {
            this.$nextTick(() => this.scrollToClass());
        },

        async scrollToClass() {
            await this.$nextTick(() => {
                const element = document.querySelector('.ongoing') ||
                    document.querySelector('.next') ||
                    [...document.querySelectorAll('.complete')].pop();

                if (element && element !== undefined) {
                    // If any of the elements are found, scroll to the first one found
                    element.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
                } else {
                    console.warn('No elements found to scroll to.');
                    //console.log('Current DOM:', document.body.innerHTML);
                }
            });
        },

        fetchSchedule() {
            this.makeApiRequest(`${this.apiURL}/controllers/getslots.php`)
                .then(data => {
                    this.sorted_data = data.data;
                    this.loading = false;
                    this.nextSlotFound = false;
                    this.transform();
                })
                .catch(error => {
                    console.error('Error fetching schedule:', error);
                    this.loading = false;
                });
        },

        async makeApiRequest(url, options = {}) {
            options.headers = {
                'Content-Type': 'application/json',
                ...(options.headers || {})
            };
            const response = await fetch(url, options);
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
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
            this.wsClient = new WebSocketClient($websocketURL, {
                reconnectInterval: 5000,
                maxReconnectAttempts: Infinity,
                debug: true,
                onMessage: this.handleWebSocketMessage.bind(this)
            });
        },

        
        async handleWebSocketMessage(data) {
            try {
                
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
                        if (data.type === 'initial_state' || data.type === 'api_update') {
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
                console.log(error);
                console.error("Failed to parse JSON:", error);
               
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

        transform() {
            Object.keys(this.sorted_data).sort().forEach(timeSlot => {
                this.slots[timeSlot] = this.slots[timeSlot] || [];
                this.cards[timeSlot] = this.cards[timeSlot] || [];

                const customers = Object.values(this.sorted_data[timeSlot]);
                const tempCustomerDetails = customers.map(customer => ({
                    ...customer,
                    duration: customer.g_duration
                }));

                tempCustomerDetails.sort((a, b) => new Date(`1970-01-01T${a.g_start_time}Z`) - new Date(`1970-01-01T${b.g_start_time}Z`));

                this.cards[timeSlot] = tempCustomerDetails;

                tempCustomerDetails.forEach(customerDetails => {
                    if (!this.slots[timeSlot].some(customer => customer.g_customer_name === customerDetails.g_customer_name)) {
                        this.slots[timeSlot].push({
                            g_customer_name: customerDetails.g_customer_name,
                            duration: customerDetails.g_duration
                        });
                    }
                });
            });

            this.slots = this.sortEntriesByDate(this.slots);
            this.cards = this.sortEntriesByDate(this.cards);
        },

        retransform() {
            this.slots = {};
            this.cards = {};
            this.transform();
        },

        sortEntriesByDate(data) {
            return Object.fromEntries(
                Object.entries(data).sort(([keyA], [keyB]) => new Date(keyA) - new Date(keyB))
            );
        }
    };
}



class WebSocketClient {
    constructor(url = $websocketURL, options = {}) {
        this.url = url;
        this.options = {
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || Infinity,
            debug: options.debug || false,
            onMessage: options.onMessage || (() => {})
        };
        this.reconnectAttempts = 0;
        this.reconnectTimer = null;
        this.socket = null;
        this.connect();
    }

    connect() {
        this.log('Attempting to connect...');
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
        this.socket.onerror = (error) => this.log('WebSocket error: ' + error.message);
        this.socket.onclose = () => {
            this.log('Connection closed');
            this.scheduleReconnect();
        };
    }

    scheduleReconnect() {
        clearTimeout(this.reconnectTimer);
        if (this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.reconnectAttempts++;
            this.log(`Scheduling reconnect in ${this.options.reconnectInterval}ms (attempt ${this.reconnectAttempts})`);
            this.reconnectTimer = setTimeout(() => this.connect(), this.options.reconnectInterval);
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
        try {
            const parsedData = JSON.parse(data);
            const res = JSON.parse(parsedData.data);
            if (res.status === 'success') {
                this.options.onMessage(res);
            }
        } catch (e) {
            this.log('Error parsing message: ' + e.message);
        }
    }

    log(message) {
        if (this.options.debug) {
            console.log(`[WebSocket] ${message}`);
        }
    }
}

// Initialize WebSocket connection
const wsClient = new WebSocketClient($websocketURL, {
    reconnectInterval: 5000,
    maxReconnectAttempts: Infinity,
    debug: true
});

function sendTestMessage() {
    wsClient.send(JSON.stringify({
        type: 'test',
        data: 'Hello server!'
    }));
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



