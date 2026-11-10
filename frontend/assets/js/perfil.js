
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
xº



/**
 * Gestiona el envío del formulario de datos personales.
 *
 * @param {Event} event Evento de envío.
 */
async function handleProfileSubmit(event) {

    event.preventDefault();

    // Ocultar mensajes anteriores.
    hideMessage(profileMessage);

    // Obtener los datos del formulario.
    const perfil = {
        Nombre: document.querySelector('#nombre').value.trim(),
        Apellido1: document.querySelector('#apellido1').value.trim(),
        Apellido2: document.querySelector('#apellido2').value.trim(),
        Telefono: document.querySelector('#telefono').value.trim(),
        Email: document.querySelector('#email').value.trim(),
        Direccion: document.querySelector('#direccion').value.trim(),
        Notas: document.querySelector('#notas').value.trim()
    };

    // Comprobar los campos obligatorios.
    if (!perfil.Nombre || !perfil.Apellido1 || !perfil.Email) {

        showMessage(
            profileMessage,
            'El nombre, el primer apellido y el correo electrónico son obligatorios.'
        );

        return;
    }

    // Deshabilitar el botón mientras se procesa la petición.
    saveProfileButton.disabled = true;
    saveProfileButton.textContent = 'Guardando...';

    try {

        // Obtener el token de autenticación.
        const token = getToken();

        if (!token) {
            throw new Error('No hay una sesión activa.');
        }

        // Enviar los cambios al servidor.
        const result = await apiRequest('/api/perfil', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`
            },
            body: JSON.stringify(perfil)
        });

        // Comprobar la respuesta.
        if (result.status !== 'success') {
            throw new Error(
                result.message || 'No se han podido guardar los cambios.'
            );
        }

        // Mostrar confirmación.
        showMessage(
            profileMessage,
            result.message || 'Tus datos se han actualizado correctamente.',
            'success'
        );

        // Recargar los datos actualizados desde el servidor.
        await loadProfile();

    } catch (error) {

        console.error('Error al actualizar el perfil:', error);

        // Gestionar una sesión caducada.
        if (error.status === 401) {

            clearToken();

            alert('Tu sesión ha caducado. Inicia sesión nuevamente.');

            window.location.href = '../index.html';

            return;
        }

        // Gestionar los posibles errores.
        let message = error.message || 'No se han podido guardar los cambios.';

        if (error.status === 409) {
            message = 'El correo electrónico ya está registrado por otro usuario.';
        }

        showMessage(profileMessage, message);

    } finally {

        // Restaurar el botón.
        saveProfileButton.disabled = false;
        saveProfileButton.textContent = 'Guardar cambios';

    }

}


// Gestionar el envío del formulario de cambio de contraseña
async function handlePasswordSubmit(event) {
    event.preventDefault();

    // Limpiar mensajes anteriores
    hideMessage(passwordMessage);

    // Obtener los valores del formulario
    const passwordActual = document.querySelector('#password-actual').value;
    const passwordNueva = document.querySelector('#password-nueva').value;
    const confirmarPassword = document.querySelector('#confirmar-password').value;

    // Validaciones básicas en el frontend
    if (!passwordActual || !passwordNueva || !confirmarPassword) {
        showMessage(passwordMessage, 'Debes completar todos los campos.');
        return;
    }

    if (passwordNueva.length < 8) {
        showMessage(passwordMessage, 'La nueva contraseña debe tener al menos 8 caracteres.');
        return;
    }

    if (passwordNueva !== confirmarPassword) {
        showMessage(passwordMessage, 'La nueva contraseña y su confirmación no coinciden.');
        return;
    }

    // Deshabilitar el botón mientras se procesa la petición
    changePasswordButton.disabled = true;
    changePasswordButton.textContent = 'Actualizando...';

    try {
        const token = getToken();

        if (!token) {
            throw new Error('No hay una sesión activa.');
        }

        // Enviar la solicitud a la API
        const result = await apiRequest('/api/perfil/password', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`
            },
            body: JSON.stringify({
                password_actual: passwordActual,
                password_nueva: passwordNueva,
                confirmar_password: confirmarPassword
            })
        });

        if (result.status !== 'success') {
            throw new Error(result.message || 'No se ha podido cambiar la contraseña.');
        }

        // Limpiar los campos del formulario
        passwordForm.reset();

        // Informar del resultado
        showMessage(
            passwordMessage,
            result.message || 'Contraseña actualizada correctamente.',
            'success'
        );

        // El backend ha revocado todos los tokens.
        // Eliminar el token local y volver al inicio de sesión.
        clearToken();

        // Dar tiempo a leer el mensaje antes de redirigir
        setTimeout(() => {
            window.location.href = '../index.html';
        }, 2500);

    } catch (error) {
        console.error('Error al cambiar la contraseña:', error);

        if (error.status === 401 || error.message === 'No hay una sesión activa.') {
            clearToken();
            alert('Tu sesión ha caducado. Inicia sesión nuevamente.');
            window.location.href = '../index.html';
            return;
        }

        showMessage(
            passwordMessage,
            error.message || 'No se ha podido cambiar la contraseña.'
        );

    } finally {
        changePasswordButton.disabled = false;
        changePasswordButton.textContent = 'Cambiar contraseña';
    }
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
