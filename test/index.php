<!DOCTYPE html>
<html>
<head>
  <title>CSRF Token Test</title>
</head>
<body>
  <h1>CSRF Token Test</h1>
  <button id="sendRequest">Send Request</button>
  <pre id="output"></pre>
  <script>
    const sendRequestButton = document.getElementById('sendRequest');
    const outputElement = document.getElementById('output');

    sendRequestButton.addEventListener('click', () => {
      outputElement.textContent = 'Fetching CSRF token...';

      // Generate a CSRF token
      fetch('http://localhost:9501/xel-csrf')
        .then(response => response.headers.get('X-CSRF-Token'))
        .then(csrfToken => {
          outputElement.textContent = 'Sending POST request with CSRF token...';
          console.log();
          // Make a POST request with the CSRF token
          fetch('http://localhost:9501/test', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ message: 'Hello from the frontend' })
          })
          .then(response => {
            if (response.ok) {
              return response.text();
            } else {
              throw new Error('Request failed');
            }
          })
          .then(data => outputElement.textContent = data)
          .catch(error => outputElement.textContent = `Error: ${error.message}`);
        })
        .catch(error => outputElement.textContent = `Error: ${error.message}`);
    });
  </script>
</body>
</html>