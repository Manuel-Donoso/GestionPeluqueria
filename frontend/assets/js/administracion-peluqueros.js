
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

        // Añadir los roles al selector.
        roles.forEach(role => {

            const option = document.createElement('option');

            option.value = role.IDRoles;
            option.textContent = role.rol;

            filterRole.appendChild(option);

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

function renderHairdressers() {

    // Limpiar el contenido actual de la tabla.
    hairdressersList.replaceChildren();

    // Actualizar el contador.
    hairdresserCount.textContent =
        `${hairdressers.length} usuarios`;

    // Comprobar si existen peluqueros.
    if (hairdressers.length === 0) {

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 6;
        cell.textContent = 'No hay peluqueros registrados';

        row.appendChild(cell);
        hairdressersList.appendChild(row);

        return;
    }

    // Crear una fila por cada peluquero.
    hairdressers.forEach(hairdresser => {

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
// INICIALIZAR PÁGINA
// ========================================

document.addEventListener('DOMContentLoaded', initializeAdministration);
