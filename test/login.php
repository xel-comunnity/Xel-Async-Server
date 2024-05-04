<!DOCTYPE html>
<html>
<head>
  <title>Login Form</title>
  <script src="https://unpkg.com/htmx.org@1.8.4"></script>
  <script src="https://cdn.jsdelivr.net/npm/redux@4.2.1/dist/redux.min.js"></script>
</head>
<body>
  <h1>Login</h1>
  <form id="loginForm" hx-post="/login" hx-target="this" hx-swap="outerHTML" hx-trigger="submitLogin">
    <div>
      <label for="username">Username:</label>
      <input type="text" id="username" name="username" required>
    </div>
    <div>
      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="button" hx-get="/csrf-token" hx-trigger="click delay:500ms">Login</button>
  </form>

  <script>
    // Redux store setup
    const initialState = {
      token: null
    };

    const reducer = (state = initialState, action) => {
      switch (action.type) {
        case 'SET_TOKEN':
          return { ...state, token: action.payload };
        default:
          return state;
      }
    };

    const store = Redux.createStore(reducer);

    // HTMX event handlers
    htmx.on('htmx:beforeRequest', (event) => {
      event.detail.headers['X-CSRF-Token'] = 'your-csrf-token';
    });

    htmx.on('htmx:swapSuccess', (event) => {
      const response = event.detail.response;
      const token = response.getResponseHeader('X-JWT-Token');

      if (token) {
        store.dispatch({ type: 'SET_TOKEN', payload: token });
        console.log('JWT token stored in Redux:', store.getState().token);
        // Optionally, you can redirect to another page or perform additional actions
      } else {
        alert('Login failed. Please try again.');
      }
    });

    const submitLogin = () => {
      const form = document.getElementById('loginForm');
      htmx.trigger(form, 'submitLogin');
    };
  </script>
</body>
</html>