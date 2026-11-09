
/**
 * Configuración de la API.
 */
const API_BASE_URL =
    window.peluqueria?.apiBaseUrl || 'http://127.0.0.1:8081';


/**
 * Realiza peticiones HTTP a la API.
 *
 * @param {string} endpoint Ruta del endpoint.
 * @param {object} options Opciones de fetch.
 * @returns {Promise<object>} Respuesta JSON.
 */
async function apiRequest(endpoint, options = {}) {

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...options.headers
        }
    });

    // Intentar interpretar la respuesta como JSON.
    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error('La API ha devuelto una respuesta no válida');
    }

    // Comprobar el código HTTP.
    if (!response.ok) {
        const error = new Error(
            result.message || 'Error al comunicarse con la API'
        );

        error.status = response.status;
        error.data = result;

        throw error;
    }

    return result;
}


/**
 * Comprueba el estado de la API y la base de datos.
 */
async function checkApi() {

    const statusElement = document.querySelector('#status');

    // Si la página no tiene indicador de estado, no hacemos nada.
    if (!statusElement) {
        return;
    }

    try {

        const result = await apiRequest('/api/health');

        if (result.status !== 'ok') {
            throw new Error('API o base de datos no disponible');
        }

        statusElement.textContent = 'API y base de datos conectadas';
        statusElement.className = 'status ok';

    } catch {

        statusElement.textContent = 'API no disponible';
        statusElement.className = 'status error';

    }
}



