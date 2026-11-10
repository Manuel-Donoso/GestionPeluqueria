
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
 *
 * @returns {Promise<boolean>} Indica si la API está disponible.
 */
async function checkApi() {

    const statusElement = document.querySelector('#status');

    try {

        const result = await apiRequest('/api/health');

        if (result.status !== 'ok') {
            throw new Error('API o base de datos no disponible');
        }

        // Actualizar el indicador si existe en la página.
        if (statusElement) {
            statusElement.textContent = 'API y base de datos conectadas';
            statusElement.className = 'status ok';
        }

        return true;

    } catch (error) {

        console.error('Error al comprobar la API:', error);

        // Actualizar el indicador si existe en la página.
        if (statusElement) {
            statusElement.textContent = 'API no disponible';
            statusElement.className = 'status error';
        }

        return false;

    }
}



