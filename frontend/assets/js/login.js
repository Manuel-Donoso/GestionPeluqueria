
/**
 * Elementos del formulario de inicio de sesión.
 */
const loginForm = document.querySelector('#login-form');
const usuarioInput = document.querySelector('#usuario');
const passwordInput = document.querySelector('#password');
const loginButton = loginForm.querySelector('button[type="submit"]');


/**
 * Elemento donde mostraremos los mensajes del inicio de sesión.
 * Se crea desde JavaScript para no tener que modificar el HTML.
 */
const loginMessage = document.createElement('p');

loginMessage.id = 'login-message';
loginMessage.setAttribute('role', 'alert');
loginMessage.hidden = true;

loginForm.appendChild(loginMessage);


/**
 * Muestra un mensaje en el formulario.
 *
 * @param {string} message Texto que se mostrará.
 * @param {boolean} isError Indica si es un mensaje de error.
 */
function showLoginMessage(message, isError = true) {

    loginMessage.textContent = message;
    loginMessage.hidden = false;

    loginMessage.className = isError
        ? 'login-message error'
        : 'login-message success';
}


/**
 * Oculta el mensaje del formulario.
 */
function hideLoginMessage() {

    loginMessage.textContent = '';
    loginMessage.hidden = true;
}


/**
 * Gestiona el envío del formulario de inicio de sesión.
 */
async function handleLogin(event) {

    event.preventDefault();

    hideLoginMessage();

    const identificador = usuarioInput.value.trim();
    const password = passwordInput.value;

    // Comprobar que se han introducido las credenciales.
    if (!identificador || !password) {
        showLoginMessage('Introduce el usuario y la contraseña.');
        return;
    }

    // Evitar que se envíe el formulario varias veces.
    loginButton.disabled = true;
    loginButton.textContent = 'Accediendo...';

    try {

        // Enviar las credenciales a la API.
        const result = await apiRequest('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                identificador: identificador,
                password: password
            })
        });

        // Comprobar que la API ha devuelto un token.
        if (result.status !== 'ok' || !result.token) {
            throw new Error(
                result.message || 'No se ha podido iniciar sesión.'
            );
        }

        // Guardar el token mediante auth.js.
        saveToken(result.token);

// Comprobar si el usuario debe restablecer su contraseña.
        if (result.action === 'password_reset_required') {

            // Guardar el token temporal para utilizarlo en el restablecimiento.
            sessionStorage.setItem('password_reset_token', result.token);

            // Redirigir a la página de restablecimiento.
            window.location.href = 'pages/restablecer-password.html';

            return;
        }


        // Acceder al panel principal.
        window.location.href = 'dashboard.html';

    } catch (error) {

        // Mostrar un mensaje según el tipo de error.
        if (error.status === 401) {
            showLoginMessage('Usuario o contraseña incorrectos.');
        } else if (error.status === 403) {
            showLoginMessage('No tienes permiso para acceder.');
        } else {
            showLoginMessage(
                error.message || 'No se ha podido conectar con el servidor.'
            );
        }

    } finally {

        // Restaurar el botón.
        loginButton.disabled = false;
        loginButton.textContent = 'Entrar al Panel';
    }
}


/**
 * Inicialización de la pantalla de inicio de sesión.
 */
async function initializeLogin() {

    // Comprobar la conexión con la API.
    await checkApi();

    // Escuchar el envío del formulario.
    loginForm.addEventListener('submit', handleLogin);
}


// Inicializar la pantalla.
initializeLogin();
