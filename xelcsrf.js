export async function fetchCSRFToken() {
    try {
      const response = await fetch('http://localhost:9501/xel-csrf');
      return response.headers.get('X-CSRF-Token');
    } catch (error) {
      console.error('Error fetching CSRF token:', error);
      throw error;
    }
}