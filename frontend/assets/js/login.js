// Selecciona el elemento del DOM con el ID 'status' donde se mostrará el mensaje de estado
const statusElement = document.querySelector('#status');

// Define una función asíncrona para comprobar el estado de la API
async function checkApi() {
  // Obtiene la URL base desde un objeto global (window.peluqueria.apiBaseUrl) 
  // o usa 'http://127.0.0.1:8080' por defecto si no está definida.
  const baseUrl = window.peluqueria?.apiBaseUrl || 'http://127.0.0.1:8080';

  try {
    // Realiza una petición HTTP GET al endpoint '/api/health' indicando que se espera JSON
    const response = await fetch(`${baseUrl}/api/health`, {
      headers: { Accept: 'application/json' }
    });
    
    // Parsea la respuesta del servidor a formato JSON
    const result = await response.json();

    // Comprueba si el código HTTP no es exitoso (fuera del rango 200-299) 
    // o si el estado devuelto por el servidor no es 'ok'
    if (!response.ok || result.status !== 'ok') {
      throw new Error('API o base de datos no disponible');
    }

    // Si todo es correcto, actualiza el texto y la clase CSS para mostrar éxito (ej. color verde)
    statusElement.textContent = 'API y base de datos conectadas';
    statusElement.className = 'status ok';
    
  } catch {
    // Si ocurre un fallo en la red, la API está caída o se lanza el error anterior,
    // actualiza el texto y la clase CSS para mostrar error (ej. color rojo)
    statusElement.textContent = 'API no disponible';
    statusElement.className = 'status error';
  }
}

// Ejecuta la función de comprobación inmediatamente al cargar el script
checkApi();
