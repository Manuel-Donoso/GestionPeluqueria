
// ========================================
// RESTABLECIMIENTO DE CONTRASEÑA
// ========================================

// Clave utilizada para recuperar el token temporal.
const RESET_TOKEN_KEY = 'peluqueria_reset_token';

// ========================================
// ELEMENTOS DEL FORMULARIO
// ========================================

const resetForm = document.querySelector('#reset-password-form');

const passwordNuevaInput = document.querySelector('#password-nueva');

const confirmarPasswordInput = document.querySelector('#confirmar-password');

const resetButton = resetForm.querySelector('button[type="submit"]');

const statusElement = document.querySelector('#status');

// ========================================
// MENSAJES
// ========================================

// Crear el elemento donde mostraremos los mensajes.
const resetMessage = document.createElement('p');

resetMessage.id = 'reset-message';
resetMessage.setAttribute('role', 'alert');
resetMessage.hidden = true;

resetForm.appendChild(resetMessage);


/**
 * Muestra un mensaje en el formulario.
 *
 * @param {string} message Texto que se mostrará.
 * @param {boolean} isError Indica si es un mensaje de error.
 */
function showResetMessage(message, isError = true) {

    resetMessage.textContent = message;
    resetMessage.hidden = false;

    resetMessage.className = isError
        ? 'login-message error'
        : 'login-message success';
}


/**
 * Oculta el mensaje del formulario.
 */
function hideResetMessage() {

    resetMessage.textContent = '';
    resetMessage.hidden = true;
}


// ========================================
// VALIDACIÓN DEL TOKEN
// ========================================

/**
 * Comprueba que existe un token temporal de restablecimiento.
 *
 * Si no existe, impide continuar con el formulario.
 *
 * @returns {string|null} Token temporal o null.
 */
function getResetToken() {

    return sessionStorage.getItem(RESET_TOKEN_KEY);
}


/**
 * Impide utilizar el formulario si no existe un token.
 */
function validateResetToken() {

    const token = getResetToken();

    if (!token) {

        resetForm.hidden = true;

        showResetMessage(
            'No existe una solicitud de restablecimiento activa. Debes iniciar sesión de nuevo.'
        );

        // Añadir un enlace para volver al inicio de sesión.
        const loginLink = document.createElement('a');

        loginLink.href = '../index.html';
        loginLink.textContent = 'Volver al inicio de sesión';
        loginLink.className = 'btn-submit';

        resetForm.parentElement.appendChild(loginLink);

        return false;
    }

    return true;
}


// ========================================
// FINALIZAR RESTABLECIMIENTO
// ========================================

/**
 * Muestra el resultado satisfactorio del restablecimiento.
 *
 * @param {string} message Mensaje devuelto por la API.
 */
function showResetSuccess(message) {

    // Ocultar el formulario.
    resetForm.hidden = true;

    // Eliminar el token temporal.
    sessionStorage.removeItem(RESET_TOKEN_KEY);

    // Mostrar el mensaje de confirmación.
    const successContainer = document.createElement('div');

    successContainer.className = 'login-message success';
    successContainer.setAttribute('role', 'status');
    successContainer.textContent =
        message || 'Tu contraseña se ha actualizado correctamente.';

    // Crear enlace para volver al inicio de sesión.
    const loginLink = document.createElement('a');

    loginLink.href = '../index.html';
    loginLink.textContent = 'Volver al inicio de sesión';
    loginLink.className = 'btn-submit';

    // Añadir los elementos a la tarjeta.
    resetForm.parentElement.appendChild(successContainer);
    resetForm.parentElement.appendChild(loginLink);
}


// ========================================
// ENVIAR NUEVA CONTRASEÑA
// ========================================

/**
 * Gestiona el envío del formulario de restablecimiento.
 */
async function handlePasswordReset(event) {

    event.preventDefault();

    hideResetMessage();

    // Recuperar el token temporal.
    const token = getResetToken();

    if (!token) {

        validateResetToken();
        return;
    }

    // Obtener las contraseñas introducidas.
    const passwordNueva = passwordNuevaInput.value;
    const confirmarPassword = confirmarPasswordInput.value;

    // Comprobar que ambos campos contienen información.
    if (!passwordNueva || !confirmarPassword) {

        showResetMessage('Debes completar ambos campos.');
        return;
    }

    // Comprobar la longitud mínima.
    if (passwordNueva.length < 8) {

        showResetMessage(
            'La nueva contraseña debe tener al menos 8 caracteres.'
        );

        return;
    }

    // Comprobar el límite de 72 bytes utilizado por el backend.
    const passwordBytes = new TextEncoder().encode(passwordNueva).length;

    if (passwordBytes > 72) {

        showResetMessage(
            'La contraseña no puede superar los 72 bytes.'
        );

        return;
    }

    // Comprobar que las contraseñas coinciden.
    if (passwordNueva !== confirmarPassword) {

        showResetMessage(
            'Las contraseñas introducidas no coinciden.'
        );

        return;
    }

    // Evitar envíos duplicados.
    resetButton.disabled = true;
    resetButton.textContent = 'Actualizando...';

    try {

        // Enviar la nueva contraseña a la API.
        const result = await apiRequest('/api/password-reset', {

            method: 'PUT',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                token: token,
                password_nueva: passwordNueva,
                confirmar_password: confirmarPassword
            })
        });

        // Comprobar que la operación se ha realizado correctamente.
        if (result.status !== 'success' && result.status !== 'ok') {

            throw new Error(
                result.message || 'No se ha podido actualizar la contraseña.'
            );
        }

        // Mostrar el resultado satisfactorio.
        showResetSuccess(
            result.message || 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.'
        );

    } catch (error) {

        // El token puede haber caducado o haber sido utilizado.
        if (error.status === 401 || error.status === 403) {

            sessionStorage.removeItem(RESET_TOKEN_KEY);

            showResetMessage(
                'La solicitud de restablecimiento ha caducado o ya no es válida. Debes iniciar sesión de nuevo.'
            );

            resetForm.hidden = true;

            // Añadir enlace para volver al inicio de sesión.
            const loginLink = document.createElement('a');

            loginLink.href = '../index.html';
            loginLink.textContent = 'Volver al inicio de sesión';
            loginLink.className = 'btn-submit';

            resetForm.parentElement.appendChild(loginLink);

        } else {

            showResetMessage(
                error.message || 'No se ha podido conectar con el servidor.'
            );
        }

    } finally {

        // Restaurar el botón si el formulario sigue visible.
        if (!resetForm.hidden) {

            resetButton.disabled = false;
            resetButton.textContent = 'Guardar nueva contraseña';
        }
    }
}


// ========================================
// INICIALIZACIÓN
// ========================================

async function initializePasswordReset() {

    // Comprobar que existe el token temporal.
    if (!validateResetToken()) {
        return;
    }

    // Comprobar la conexión con la API.
    await checkApi();

    // Escuchar el envío del formulario.
    resetForm.addEventListener('submit', handlePasswordReset);
}


// Inicializar la pantalla.
initializePasswordReset();
