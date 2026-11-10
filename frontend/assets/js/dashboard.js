
/**
 * Elementos del Dashboard.
 */
const usuarioNombre = document.querySelector('#usuario-nombre');
const usuarioRol = document.querySelector('#usuario-rol');
const logoutButton = document.querySelector('#logout-button');


/**
 * Traduce el identificador del rol a un nombre legible.
 *
 * @param {number|string} roleId Identificador del rol.
 * @returns {string} Nombre del rol.
 */
function getRoleName(roleId) {

    const roles = {
        1: 'Administrador',
        2: 'Peluquero',
        3: 'Profesor'
    };

    return roles[roleId] || 'Rol desconocido';
}


/**
 * Carga los datos del usuario autenticado.
 */
async function loadDashboard() {

    try {

        // Comprobar la conexión con la API.
        const apiAvailable = await checkApi();

        if (!apiAvailable) {
            throw new Error('No se puede conectar con el servidor.');
        }

        // Obtener y validar los datos del usuario.
        const user = await getAuthenticatedUser();

        // Mostrar el nombre del usuario.
        usuarioNombre.textContent = [
            user.nombre,
            user.apellido1,
            user.apellido2
        ].filter(Boolean).join(' ');

        // Mostrar el rol del usuario.
        usuarioRol.textContent = getRoleName(user.rol);

        // Mostrar únicamente los módulos permitidos para su rol.
        applyRoleVisibility(user);

    } catch (error) {

        console.error('Error al cargar el Dashboard:', error);

        // Si la sesión no es válida, eliminar el token.
        if (error.status === 401 || error.message === 'No hay una sesión activa') {
            clearToken();
        }

        // Informar al usuario.
        alert(
            'No se ha podido acceder al panel. Inicia sesión nuevamente.'
        );

        // Regresar a la pantalla de login.
        window.location.href = 'index.html';
    }
}


/**
 * Gestiona el cierre de sesión.
 */
async function logout() {

    try {

        // Revocar el token en el servidor.
        const token = getToken();

        if (token) {
            await apiRequest('/api/logout', {
                method: 'POST',
                headers: {
                    Authorization: `Bearer ${token}`
                }
            });
        }

    } catch (error) {

        console.error('Error al cerrar la sesión en el servidor:', error);

    } finally {

        // Eliminar el token localmente.
        clearToken();

        // Volver al login.
        window.location.href = 'index.html';
    }
}


/**
 * Inicialización del Dashboard.
 */
async function initializeDashboard() {

    // Asociar el evento de cierre de sesión.
    logoutButton.addEventListener('click', logout);

    // Cargar los datos del usuario y comprobar su sesión.
    await loadDashboard();
}


// Inicializar el Dashboard.
initializeDashboard();
