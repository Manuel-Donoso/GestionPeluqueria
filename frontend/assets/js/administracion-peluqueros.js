
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
// MOSTRAR RESULTADOS FILTRADOS
// ========================================

function renderFilteredHairdressers(filteredHairdressers) {

    // Limpiar el contenido actual de la tabla.
    hairdressersList.replaceChildren();

    // Actualizar el contador.
    hairdresserCount.textContent =
        `${filteredHairdressers.length} de ${hairdressers.length} usuarios`;

    // Comprobar si existen resultados.
    if (filteredHairdressers.length === 0) {

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 6;
        cell.textContent = 'No se han encontrado peluqueros';

        row.appendChild(cell);
        hairdressersList.appendChild(row);

        return;
    }

    // Crear una fila por cada peluquero.
    filteredHairdressers.forEach(hairdresser => {

        const row = document.createElement('tr');

        // Nombre y apellidos.
        const nameCell = document.createElement('td');

        nameCell.textContent = [
            hairdresser.Nombre,
            hairdresser.Apellido1,
            hairdresser.Apellido2
        ].filter(Boolean).join(' ');

        row.appendChild(nameCell);

        // Nombre de usuario.
        row.appendChild(
            createTableCell(hairdresser.Usuario)
        );

        // Correo electrónico.
        row.appendChild(
            createTableCell(hairdresser.Email)
        );

        // Rol.
        row.appendChild(
            createTableCell(hairdresser.NombreRol)
        );

        // Estado.
        const statusCell = document.createElement('td');
        const status = document.createElement('span');

        const inactive =
            Number(hairdresser.baja) === 1 ||
            hairdresser.baja === true ||
            hairdresser.FechaBaja !== null;

        status.textContent = inactive ? 'De baja' : 'Activo';
        status.className = inactive
            ? 'hairdresser-status inactive'
            : 'hairdresser-status active';

        statusCell.appendChild(status);
        row.appendChild(statusCell);

        // Acciones (las implementaremos más adelante).
        const actionsCell = document.createElement('td');

        actionsCell.textContent = 'Próximamente';

        row.appendChild(actionsCell);

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
