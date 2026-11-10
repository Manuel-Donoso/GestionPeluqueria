
/**
 * Elementos del formulario de perfil.
 */
const profileForm = document.querySelector('#profile-form');
const passwordForm = document.querySelector('#password-form');

const profileMessage = document.querySelector('#profile-message');
const passwordMessage = document.querySelector('#password-message');

const saveProfileButton = document.querySelector('#save-profile-button');
const changePasswordButton = document.querySelector('#change-password-button');



/**
 * Convierte una fecha al formato dd/mm/aaaa.
 *
 * @param {string} date Fecha recibida de la API.
 * @returns {string} Fecha formateada.
 */
function formatDate(date) {

    if (!date) {
        return '';
    }

    // Separar la fecha de la hora.
    const datePart = date.split(/[ T]/)[0];

    // Separar año, mes y día.
    const [year, month, day] = datePart.split('-');

    // Comprobar que la fecha tiene un formato válido.
    if (!year || !month || !day) {
        return date;
    }

    return `${day}/${month}/${year}`;
}

/**
 * Muestra un mensaje en pantalla.
 *
 * @param {HTMLElement} element Elemento donde mostrar el mensaje.
 * @param {string} message Texto del mensaje.
 * @param {string} type Tipo de mensaje: success o error.
 */
function showMessage(element, message, type = 'error') {

    element.textContent = message;
    element.className = `form-message ${type}`;
    element.hidden = false;

}


/**
 * Oculta y limpia un mensaje.
 *
 * @param {HTMLElement} element Elemento del mensaje.
 */
function hideMessage(element) {

    element.textContent = '';
    element.hidden = true;

}


/**
 * Carga los datos del perfil desde la API.
 */
async function loadProfile() {

    try {

        // Comprobar la disponibilidad de la API.
        const apiAvailable = await checkApi();

        if (!apiAvailable) {
            throw new Error('No se puede conectar con el servidor.');
        }

        // Obtener el token de autenticación.
        const token = getToken();

        if (!token) {
            throw new Error('No hay una sesión activa.');
        }

        // Consultar el perfil del usuario.
        const result = await apiRequest('/api/perfil', {
            method: 'GET',
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        // Comprobar la respuesta.
        if (result.status !== 'success' || !result.data) {
            throw new Error(
                result.message || 'No se han podido obtener los datos del perfil.'
            );
        }

        // Recuperar los datos del perfil.
        const perfil = result.data;

        // Rellenar los campos personales.
        document.querySelector('#nombre').value = perfil.Nombre ?? '';
        document.querySelector('#apellido1').value = perfil.Apellido1 ?? '';
        document.querySelector('#apellido2').value = perfil.Apellido2 ?? '';
        document.querySelector('#telefono').value = perfil.Telefono ?? '';
        document.querySelector('#email').value = perfil.Email ?? '';
        document.querySelector('#direccion').value = perfil.Direccion ?? '';
        document.querySelector('#notas').value = perfil.Notas ?? '';

        // Rellenar la información de la cuenta.
        document.querySelector('#usuario').value = perfil.Usuario ?? '';
     
         
        document.querySelector('#fecha-alta').value = formatDate(perfil.FechaAlta);

    } catch (error) {

        console.error('Error al cargar el perfil:', error);

        // Si la sesión ha caducado, eliminar el token.
        if (error.status === 401 || error.message === 'No hay una sesión activa.') {

            clearToken();

            alert('Tu sesión ha caducado. Inicia sesión nuevamente.');

            window.location.href = '../index.html';

            return;
        }

        showMessage(
            profileMessage,
            error.message || 'No se ha podido cargar el perfil.'
        );

    }

}


/**
 * Gestiona el envío del formulario de datos personales.
 *
 * La actualización se implementará cuando tengamos
 * definido el endpoint correspondiente en PHP.
 *
 * @param {Event} event Evento de envío.
 */
function handleProfileSubmit(event) {

    event.preventDefault();

    showMessage(
        profileMessage,
        'La actualización de los datos personales todavía no está implementada.'
    );

}


/**
 * Gestiona el envío del formulario de cambio de contraseña.
 *
 * La operación se implementará cuando tengamos
 * definido el endpoint correspondiente en PHP.
 *
 * @param {Event} event Evento de envío.
 */
function handlePasswordSubmit(event) {

    event.preventDefault();

    showMessage(
        passwordMessage,
        'El cambio de contraseña todavía no está implementado.'
    );

}


/**
 * Inicializa la página de perfil.
 */
async function initializeProfile() {

    // Asociar los eventos de los formularios.
    profileForm.addEventListener('submit', handleProfileSubmit);
    passwordForm.addEventListener('submit', handlePasswordSubmit);

    // Cargar los datos del perfil.
    await loadProfile();

}




// Inicializar la página.
initializeProfile();
