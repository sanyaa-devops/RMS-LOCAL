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

        init() {
            this.connect();
            this.$nextTick(() => this.scrollToClass('ongoing'));
        },

        effect() {
            this.$nextTick(() => this.scrollToClass('ongoing'));
        },

        scrollToClass(className) {
            const element = document.querySelector(`.${className}`);
            if (element) {
                element.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
            }
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

        handleWebSocketMessage(data) {
            if (data.status === 'success') {
                this.sorted_data = data.data; // Update sorted_data
                this.retransform();
                this.$nextTick(() => this.scrollToClass('ongoing'));
            } else {
                console.error('Error status from server:', data);
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

        this.socket.onmessage = (event) => this.handleMessage(event.data);
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

