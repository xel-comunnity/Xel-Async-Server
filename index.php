<!DOCTYPE html>
<html>
<head>
  <title>CSRF Token Test</title>
</head>
<body>
  <h1>CSRF Token Test</h1>
  <button id="sendRequest">Send Request</button>
  <pre id="output"></pre>
  <script type="module">
    import { fetchCSRFToken } from "./xelcsrf.js";

    const sendRequestButton = document.getElementById('sendRequest');
    const outputElement = document.getElementById('output');

    sendRequestButton.addEventListener('click', async () => {
      try {
        // Fetch CSRF token
        const csrfToken = await fetchCSRFToken();
        console.log('CSRF Token:', csrfToken);
        outputElement.textContent = 'Fetching CSRF token...';

        // Generate a CSRF token
        const response = await fetch('http://localhost:9501/test', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({ message: 'Hello from the frontend' })
        });

        if (response.ok) {
          const data = await response.text();
          outputElement.textContent = data;
        } else {
          throw new Error('Request failed');
        }
      } catch (error) {
        outputElement.textContent = `Error: ${error.message}`;
      }
    });
  </script>
</body>
</html>