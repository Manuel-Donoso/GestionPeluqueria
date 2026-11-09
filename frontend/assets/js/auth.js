
/**
 * Clave utilizada para almacenar el token de autenticación.
 */
const TOKEN_KEY = 'peluqueria_token';


/**
 * Guarda el token de acceso en la sesión del navegador.
 *
 * @param {string} token Token devuelto por la API.
 */
function saveToken(token) {
    sessionStorage.setItem(TOKEN_KEY, token);
}


/**
 * Recupera el token de acceso almacenado.
 *
 * @returns {string|null} Token o null si no existe.
 */
function getToken() {
    return sessionStorage.getItem(TOKEN_KEY);
}


/**
 * Elimina el token de acceso almacenado.
 */
function clearToken() {
    sessionStorage.removeItem(TOKEN_KEY);
}


/**
 * Obtiene los datos del usuario autenticado.
 * Comprueba la validez de la sesión mediante la API.
 *
 * @returns {Promise<object>} Datos del usuario.
 */
async function getAuthenticatedUser() {

    const token = getToken();

    // Comprobar que existe un token.
    if (!token) {
        throw new Error('No hay una sesión activa');
    }

    try {

        const result = await apiRequest('/api/me', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        // Comprobar que la respuesta contiene los datos esperados.
        if (result.status !== 'ok' || !result.usuario) {
            throw new Error('No se han podido obtener los datos del usuario');
        }

        return result.usuario;

    } catch (error) {

        // Si el servidor rechaza el token, eliminarlo.
        if (error.status === 401) {
            clearToken();
        }

        throw error;
    }
}


/**
 * Controla la visibilidad de los elementos según el rol del usuario.
 *
 * Cada elemento debe tener un atributo data-roles.
 * Ejemplo: data-roles="1,2,3"
 *
 * @param {object} user Datos del usuario autenticado.
 */
function applyRoleVisibility(user) {

    const userRole = String(user.rol);

    document.querySelectorAll('[data-roles]').forEach(element => {

        const allowedRoles = element.dataset.roles
            .split(',')
            .map(role => role.trim());

        element.hidden = !allowedRoles.includes(userRole);
    });
}

    