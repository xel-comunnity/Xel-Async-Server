<!DOCTYPE html>
<html>
<head>
    <title>Swoole Server Example</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 50px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
<h1>Swoole Server Example</h1>
<button onclick="sendRequest(9501)">Port 9501</button>
<button onclick="connectWebSocket(9502)">WebSocket (Port 9502)</button>
<button onclick="sendRequest(9503)">Port 9503</button>
<div id="response"></div>

<script>
    let webSocket;

    function sendRequest(port) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `http://localhost:${port}`, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                document.getElementById('response').textContent = xhr.responseText;
            }
        }
        xhr.send();
    }

    function connectWebSocket(port) {
        webSocket = new WebSocket(`ws://localhost:${port}`);
        webSocket.onopen = function() {
            document.getElementById('response').textContent = 'WebSocket connection opened';
        };
        webSocket.onmessage = function(event) {
            document.getElementById('response').textContent = event.data;
        };
        webSocket.onclose = function() {
            document.getElementById('response').textContent = 'WebSocket connection closed';
        };
    }

    function sendWebSocketMessage(message) {
        if (webSocket && webSocket.readyState === WebSocket.OPEN) {
            webSocket.send(message);
        } else {
            document.getElementById('response').textContent = 'WebSocket connection not established';
        }
    }
</script>
</body>
</html>