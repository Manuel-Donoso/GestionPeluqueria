
// ========================================
// ELEMENTOS DEL DOM
// ========================================

// Cuerpo de la tabla donde se mostrarán los peluqueros.
const hairdressersList = document.querySelector('#hairdressers-list');

// Contador de usuarios.
const hairdresserCount = document.querySelector('#hairdresser-count');

// Selector de roles.
const filterRole = document.querySelector('#filter-role');

// Mensajes de administración.
const adminMessage = document.querySelector('#admin-message');

// Buscador de peluqueros.
const searchHairdresser = document.querySelector('#search-hairdresser');

// Selector de estado.
const filterStatus = document.querySelector('#filter-status');

// Modal de nuevo peluquero.
const hairdresserModal = document.querySelector('#hairdresser-modal');
const newHairdresserButton = document.querySelector('#new-hairdresser-button');
const closeHairdresserModal = document.querySelector('#close-hairdresser-modal');
const cancelHairdresserButton = document.querySelector('#cancel-hairdresser-button');

// Formulario de nuevo peluquero.
const hairdresserForm = document.querySelector('#hairdresser-form');
const newRole = document.querySelector('#new-rol');
const hairdresserFormMessage = document.querySelector('#hairdresser-form-message');
const saveHairdresserButton = document.querySelector('#save-hairdresser-button');

// ========================================
// VARIABLES
// ========================================

// Almacenará los peluqueros recibidos desde la API.
let hairdressers = [];

// Almacenará los roles recibidos desde la API.
let roles = [];

// ========================================
// COMPROBAR SESIÓN Y PERMISOS
// ========================================

async function initializeAdministration() {

    try {

        // Comprobar que la API está disponible.
        const apiAvailable = await checkApi();

        if (!apiAvailable) {
            throw new Error('No se puede conectar con la API');
        }

        // Obtener los datos del usuario autenticado.
        const user = await getAuthenticatedUser();

        // Comprobar que el usuario es administrador.
        if (String(user.rol) !== '1') {
            window.location.href = '../dashboard.html';
            return;
        }

        // Cargar los roles y los peluqueros.
        await Promise.all([
        loadRoles(),
        loadHairdressers()
        ]);

    // Inicializar los filtros.
    initializeFilters();
    // Inicializar el modal de nuevo peluquero.
    initializeHairdresserModal();       
        } catch (error) {

            console.error('Error al inicializar la administración:', error);

            if (error.status === 401 || error.message === 'No hay una sesión activa') {
                window.location.href = '../index.html';
                return;
            }

        showAdminMessage(
            error.message || 'No se ha podido cargar la administración',
            'error'
        );
    }
}

// ========================================
// CARGAR ROLES
// ========================================

async function loadRoles() {

    try {

        // Obtener el token de sesión.
        const token = getToken();

        // Consultar los roles disponibles.
        const result = await apiRequest('/api/roles', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        if (result.status !== 'ok') {
            throw new Error('No se han podido obtener los roles');
        }

        roles = result.roles;

        // Añadir los roles a los selectores.
        roles.forEach(role => {

            // Opción para el filtro.
            const filterOption = document.createElement('option');

            filterOption.value = role.IDRoles;
            filterOption.textContent = role.rol;

            filterRole.appendChild(filterOption);

            // Opción para el formulario de alta.
            const formOption = document.createElement('option');

            formOption.value = role.IDRoles;
            formOption.textContent = role.rol;

            newRole.appendChild(formOption);

        });

    } catch (error) {

        console.error('Error al cargar los roles:', error);
        throw error;

    }

}


// ========================================
// CARGAR PELUQUEROS
// ========================================

async function loadHairdressers() {

    try {

        // Obtener el token de sesión.
        const token = getToken();

        // Consultar los peluqueros.
        const result = await apiRequest('/api/peluqueros', {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        if (result.status !== 'ok') {
            throw new Error('No se han podido obtener los peluqueros');
        }

        hairdressers = result.peluqueros;

        // Mostrar los peluqueros en la tabla.
        renderHairdressers();

    } catch (error) {

        console.error('Error al cargar los peluqueros:', error);
        throw error;

    }
}

// ========================================
// MOSTRAR PELUQUEROS EN LA TABLA
// ========================================


// ========================================
// MOSTRAR PELUQUEROS EN LA TABLA
// ========================================

function renderHairdressers() {

    renderFilteredHairdressers(hairdressers);

}


// ========================================
// COMPROBAR ESTADO DEL PELUQUERO
// ========================================

/**
 * Comprueba si un peluquero está dado de baja.
 *
 * Se considera inactivo si:
 * - El campo baja tiene valor 1.
 * - El campo baja es true.
 * - Tiene una fecha de baja registrada.
 *
 * @param {Object} hairdresser - Datos del peluquero.
 * @returns {boolean} True si está de baja, false si está activo.
 */
function isHairdresserInactive(hairdresser) {

    return Number(hairdresser.baja) === 1 ||
        hairdresser.baja === true ||
        hairdresser.FechaBaja != null;

}


// ========================================
// MOSTRAR RESULTADOS FILTRADOS
// ========================================

/**
 * Muestra los peluqueros recibidos en la tabla.
 *
 * Genera una fila por cada peluquero y adapta las acciones
 * disponibles dependiendo de si su cuenta está activa o de baja.
 *
 * @param {Array} filteredHairdressers - Lista de peluqueros filtrados.
 */
function renderFilteredHairdressers(filteredHairdressers) {

    // Limpiar el contenido actual de la tabla.
    hairdressersList.replaceChildren();

    // Actualizar el contador de resultados.
    hairdresserCount.textContent =
        `${filteredHairdressers.length} de ${hairdressers.length} usuarios`;

    // Comprobar si existen resultados.
    if (filteredHairdressers.length === 0) {

        // Crear una fila para mostrar el mensaje.
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        // La celda ocupará las seis columnas de la tabla.
        cell.colSpan = 6;
        cell.textContent = 'No se han encontrado peluqueros';

        // Añadir la fila a la tabla.
        row.appendChild(cell);
        hairdressersList.appendChild(row);

        // Finalizar la función al no haber resultados.
        return;

    }

    // Crear una fila por cada peluquero.
    filteredHairdressers.forEach(hairdresser => {

        // Crear una nueva fila.
        const row = document.createElement('tr');


        // ----------------------------------------
        // NOMBRE Y APELLIDOS
        // ----------------------------------------

        const nameCell = document.createElement('td');

        // Construir el nombre completo.
        // Se eliminan los valores vacíos para evitar espacios innecesarios.
        nameCell.textContent = [
            hairdresser.Nombre,
            hairdresser.Apellido1,
            hairdresser.Apellido2
        ].filter(Boolean).join(' ');

        row.appendChild(nameCell);


        // ----------------------------------------
        // NOMBRE DE USUARIO
        // ----------------------------------------

        row.appendChild(
            createTableCell(hairdresser.Usuario)
        );


        // ----------------------------------------
        // CORREO ELECTRÓNICO
        // ----------------------------------------

        row.appendChild(
            createTableCell(hairdresser.Email)
        );


        // ----------------------------------------
        // ROL DEL PELUQUERO
        // ----------------------------------------

        row.appendChild(
            createTableCell(hairdresser.NombreRol)
        );


        // ----------------------------------------
        // ESTADO DE LA CUENTA
        // ----------------------------------------

        const statusCell = document.createElement('td');
        const status = document.createElement('span');

        // Comprobar si el peluquero está dado de baja.
        const inactive = isHairdresserInactive(hairdresser);

        // Mostrar el estado correspondiente.
        status.textContent = inactive ? 'De baja' : 'Activo';

        // Aplicar la clase CSS según el estado.
        status.className = inactive
            ? 'hairdresser-status inactive'
            : 'hairdresser-status active';

        // Añadir el indicador de estado a la celda.
        statusCell.appendChild(status);
        row.appendChild(statusCell);


        // ----------------------------------------
        // ACCIONES DISPONIBLES
        // ----------------------------------------

        const actionsCell = document.createElement('td');

        // Comprobar las acciones disponibles según el estado.
        if (inactive) {

            // ------------------------------------
            // CUENTA DE BAJA
            // ------------------------------------

            // Crear el botón para reactivar la cuenta.
            const reactivateButton = document.createElement('button');

            reactivateButton.type = 'button';
            reactivateButton.textContent = 'Reactivar';

            // Aplicar las clases CSS.
            reactivateButton.className =
                'action-button reactivate-button';

            // Guardar la acción y el identificador del peluquero.
            // Estos datos permitirán identificar el botón al pulsarlo.
            reactivateButton.dataset.action = 'reactivate';
            reactivateButton.dataset.id = hairdresser.IDPeluquero;

            // Añadir el botón a la celda de acciones.
            actionsCell.appendChild(reactivateButton);

        } else {

            // ------------------------------------
            // CUENTA ACTIVA
            // ------------------------------------

            // Crear el botón para dar de baja.
            const deactivateButton = document.createElement('button');

            deactivateButton.type = 'button';
            deactivateButton.textContent = 'Dar de baja';

            // Aplicar las clases CSS.
            deactivateButton.className =
                'action-button deactivate-button';

            // Guardar la acción y el identificador del peluquero.
            deactivateButton.dataset.action = 'deactivate';
            deactivateButton.dataset.id = hairdresser.IDPeluquero;

            // Añadir el botón a la celda de acciones.
            actionsCell.appendChild(deactivateButton);


            // ------------------------------------
            // RESET DE CONTRASEÑA
            // ------------------------------------

            // Crear el botón para restablecer la contraseña.
            const resetPasswordButton = document.createElement('button');

            resetPasswordButton.type = 'button';
            resetPasswordButton.textContent = 'Resetear contraseña';

            // Aplicar las clases CSS.
            resetPasswordButton.className =
                'action-button reset-password-button';

            // Guardar la acción y el identificador del peluquero.
            resetPasswordButton.dataset.action = 'reset-password';
            resetPasswordButton.dataset.id = hairdresser.IDPeluquero;

            // Añadir el botón a la celda de acciones.
            actionsCell.appendChild(resetPasswordButton);

        }


        // ----------------------------------------
        // AÑADIR FILA A LA TABLA
        // ----------------------------------------

        // Añadir la celda de acciones a la fila.
        row.appendChild(actionsCell);

        // Añadir la fila completa al cuerpo de la tabla.
        hairdressersList.appendChild(row);

    });

}
    

// ========================================
// EVENTOS DE LOS FILTROS
// ========================================

function initializeFilters() {

    // Buscar mientras se escribe.
    searchHairdresser.addEventListener('input', filterHairdressers);

    // Filtrar por rol.
    filterRole.addEventListener('change', filterHairdressers);

    // Filtrar por estado.
    filterStatus.addEventListener('change', filterHairdressers);

}

// ========================================
// CREAR CELDAS DE LA TABLA
// ========================================

function createTableCell(value) {

    const cell = document.createElement('td');

    cell.textContent = value || '—';

    return cell;

}

// ========================================
// MOSTRAR MENSAJES
// ========================================

function showAdminMessage(message, type) {

    adminMessage.textContent = message;
    adminMessage.className = `form-message ${type}`;
    adminMessage.hidden = false;

}




// ========================================
// FILTRAR PELUQUEROS
// ========================================

function filterHairdressers() {

    // Obtener los valores de los filtros.
    const search = searchHairdresser.value.trim().toLowerCase();
    const selectedRole = filterRole.value;
    const selectedStatus = filterStatus.value;


// ========================================
// Filtrar los peluqueros según los criterios seleccionados
// ========================================

    // Filtrar los peluqueros.
    const filteredHairdressers = hairdressers.filter(hairdresser => {

        // Preparar el texto de búsqueda.
        const fullName = [
            hairdresser.Nombre,
            hairdresser.Apellido1,
            hairdresser.Apellido2,
            hairdresser.Usuario,
            hairdresser.Email
        ].filter(Boolean).join(' ').toLowerCase();

        // Comprobar el texto introducido.
        const matchesSearch = fullName.includes(search);

        // Comprobar el rol seleccionado.
        const matchesRole =
            selectedRole === '' ||
            String(hairdresser.FK_rol) === selectedRole;

        // Comprobar el estado del peluquero.
        const inactive =
            Number(hairdresser.baja) === 1 ||
            hairdresser.baja === true ||
            hairdresser.FechaBaja !== null;

        const matchesStatus =
            selectedStatus === '' ||
            (selectedStatus === 'activo' && !inactive) ||
            (selectedStatus === 'baja' && inactive);

        return matchesSearch && matchesRole && matchesStatus;

    });

    // Mostrar únicamente los resultados filtrados.
    renderFilteredHairdressers(filteredHairdressers);

}


// ========================================
// MODAL DE NUEVO PELUQUERO
// ========================================

// Abrir el formulario.
function openHairdresserModal() {

    hairdresserForm.reset();

    hairdresserFormMessage.hidden = true;

    hairdresserModal.hidden = false;

}

// Cerrar el formulario.
function closeHairdresserModalWindow() {

    hairdresserModal.hidden = true;

    hairdresserForm.reset();

    hairdresserFormMessage.hidden = true;

}

// Registrar los eventos del modal.
function initializeHairdresserModal() {

    newHairdresserButton.addEventListener(
        'click',
        openHairdresserModal
    );

    closeHairdresserModal.addEventListener(
        'click',
        closeHairdresserModalWindow
    );

    cancelHairdresserButton.addEventListener(
        'click',
        closeHairdresserModalWindow
    );

        // Registrar un nuevo peluquero.
    hairdresserForm.addEventListener(
        'submit',
        handleHairdresserSubmit
    );  

}


// ========================================
// REGISTRAR NUEVO PELUQUERO
// ========================================

async function handleHairdresserSubmit(event) {

     console.log('Se ha ejecutado el evento submit');

    // Evitar el envío tradicional del formulario.
    event.preventDefault();

    // Obtener los valores del formulario.
    const password = document.querySelector('#new-password').value;
    const passwordConfirm = document.querySelector('#new-password-confirm').value;

    // Comprobar que las contraseñas coinciden.
    if (password !== passwordConfirm) {

        showHairdresserFormMessage(
            'Las contraseñas no coinciden',
            'error'
        );

        return;
    }

    // Comprobar la longitud mínima.
    if (password.length < 8) {

        showHairdresserFormMessage(
            'La contraseña debe tener al menos 8 caracteres',
            'error'
        );

        return;
    }

    // Preparar los datos del nuevo peluquero.
    const hairdresserData = {

        Usuario: document.querySelector('#new-usuario').value.trim(),

        Nombre: document.querySelector('#new-nombre').value.trim(),

        Apellido1: document.querySelector('#new-apellido1').value.trim(),

        Apellido2: document.querySelector('#new-apellido2').value.trim(),

        Telefono: document.querySelector('#new-telefono').value.trim(),

        Email: document.querySelector('#new-email').value.trim(),

        Direccion: document.querySelector('#new-direccion').value.trim(),

        Notas: document.querySelector('#new-notas').value.trim(),

        PW: password,

        FK_rol: newRole.value

    };

    // Evitar envíos repetidos.
    saveHairdresserButton.disabled = true;
    saveHairdresserButton.textContent = 'Registrando...';

    hairdresserFormMessage.hidden = true;

    try {

        // Obtener el token de sesión.
        const token = getToken();

        if (!token) {
            throw new Error('No hay una sesión activa');
        }

        // Enviar los datos a la API.
        const result = await apiRequest('/api/peluqueros', {

            method: 'POST',

            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json'
            },

            body: JSON.stringify(hairdresserData)

        });

        if (result.status !== 'ok') {
            throw new Error(
                result.message || 'No se ha podido registrar el peluquero'
            );
        }

        // Cerrar y limpiar el formulario.
        closeHairdresserModalWindow();

        // Mostrar el mensaje de éxito.
        showAdminMessage(
            'El peluquero se ha registrado correctamente',
            'success'
        );

        // Actualizar el listado.
        await loadHairdressers();

        // Volver a aplicar los filtros actuales.
        filterHairdressers();

    } catch (error) {

        console.error('Error al registrar el peluquero:', error);

        // Si la sesión ha caducado, volver al login.
        if (error.status === 401) {
            clearToken();
            window.location.href = '../index.html';
            return;
        }

        showHairdresserFormMessage(
            error.message || 'Error al registrar el peluquero',
            'error'
        );

    } finally {

        // Recuperar el botón.
        saveHairdresserButton.disabled = false;
        saveHairdresserButton.textContent = 'Registrar peluquero';

    }
console.log('Modal inicializado correctamente');
}

// ========================================
// MOSTRAR MENSAJES DEL FORMULARIO
// ========================================

function showHairdresserFormMessage(message, type) {

    hairdresserFormMessage.textContent = message;
    hairdresserFormMessage.className = `form-message ${type}`;
    hairdresserFormMessage.hidden = false;

}




// ========================================
// INICIALIZAR PÁGINA
// ========================================

document.addEventListener('DOMContentLoaded', initializeAdministration);
