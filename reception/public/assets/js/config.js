/* var $baseurl = 'http://localhost/python/inflight_operator/public'; */
const config = {
    protocol: window.location.protocol,
    host: window.location.host,
    path: 'inflightdubai/reception/public'
};

// Construct the full base URL using the config object
var $baseurl = `${config.protocol}//${config.host}/${config.path}`;



var websockets = 'ws://10.3.2.100:55777/';

console.log($baseurl);  

