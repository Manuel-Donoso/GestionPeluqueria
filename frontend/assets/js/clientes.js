/**
 * Módulo de gestión de clientes.
 *
 * Permite:
 * - Consultar clientes.
 * - Buscar y filtrar clientes.
 * - Registrar nuevos clientes.
 * - Editar clientes.
 * - Dar de baja clientes.
 * - Reactivar clientes.
 */

// ========================================
// ELEMENTOS DEL DOM
// ========================================

// Tabla de clientes.
const clientesList = document.querySelector("#clientes-tbody");

// Contador de clientes.
const clientesCount = document.querySelector("#total-clientes");

// Buscador.
const searchClientes = document.querySelector("#busqueda");

// Selector de estado.
const filterEstado = document.querySelector("#estado-filtro");

// Mensajes generales.
const clientesMessage = document.querySelector("#clientes-message");

// Botones principales.
const newClienteButton = document.querySelector("#nuevo-cliente-button");
const clearFiltersButton = document.querySelector("#limpiar-filtros-button");
const backButton = document.querySelector("#volver-button");

// Modal.
const clienteModal = document.querySelector("#cliente-modal");
const closeClienteModalButton = document.querySelector("#cerrar-modal-button");
const cancelClienteButton = document.querySelector("#cancelar-cliente-button");

// Formulario.
const clienteForm = document.querySelector("#cliente-form");
const saveClienteButton = document.querySelector("#guardar-cliente-button");
const clienteFormMessage = document.querySelector("#form-message");

// Campos del formulario.
const clienteNombre = document.querySelector("#nombre");
const clienteApellido1 = document.querySelector("#apellido1");
const clienteApellido2 = document.querySelector("#apellido2");
const clienteTelefono = document.querySelector("#telefono");
const clienteEmail = document.querySelector("#email");
const clienteDireccion = document.querySelector("#direccion");
const clienteNotas = document.querySelector("#notas");

// ========================================
// VARIABLES
// ========================================

// Clientes recibidos desde la API.
let clientes = [];

// Identificador del cliente que se está editando.
// Si es null, se está creando un cliente nuevo.
let selectedClienteId = null;

// ========================================
// COMPROBAR SESIÓN Y CONEXIÓN
// ========================================

async function initializeClientes() {
  try {
    // Comprobar que la API está disponible.
    const apiAvailable = await checkApi();

    if (!apiAvailable) {
      throw new Error("No se puede conectar con la API");
    }

    // Comprobar que existe un token.
    const token = getToken();

    if (!token) {
      throw new Error("No hay una sesión activa");
    }

    // Obtener los datos del usuario autenticado.
    const user = await getAuthenticatedUser();

    // Comprobar que se ha obtenido el usuario.
    if (!user) {
      throw new Error("No se han podido obtener los datos del usuario");
    }

    // Cargar los clientes.
    await loadClientes();

    // Inicializar los filtros.
    initializeFilters();

    // Inicializar el modal.
    initializeClienteModal();

    // Inicializar las acciones de la tabla.
    initializeClienteActions();

    // Inicializar la navegación.
    initializeNavigation();
  } catch (error) {
    console.error("Error al inicializar Clientes:", error);

    // Si no existe sesión o el token ha caducado,
    // volver a la página de inicio de sesión.
    if (error.status === 401 || error.message === "No hay una sesión activa") {
      clearToken();
      window.location.href = "../index.html";
      return;
    }

    showClientesMessage(
      error.message || "No se ha podido cargar el módulo de clientes",
      "error",
    );
  }
}

// ========================================
// OBTENER TOKEN DE SESIÓN
// ========================================

/**
 * Comprueba que existe un token y lo devuelve.
 *
 * @returns {string} Token de autenticación.
 */
function getSessionToken() {
  const token = getToken();

  if (!token) {
    throw new Error("No hay una sesión activa");
  }

  return token;
}

// ========================================
// CARGAR CLIENTES
// ========================================

async function loadClientes() {
  try {
    // Obtener el token de sesión.
    const token = getSessionToken();

    // Obtener los filtros actuales.
    const busqueda = searchClientes.value.trim();
    const estado = filterEstado.value;

    // Construir los parámetros de la petición.
    const params = new URLSearchParams();

    if (busqueda) {
      params.set("busqueda", busqueda);
    }

    if (estado) {
      params.set("estado", estado);
    }

    const queryString = params.toString();

    const endpoint = queryString
      ? `/api/clientes?${queryString}`
      : "/api/clientes";

    // Consultar los clientes.
    const result = await apiRequest(endpoint, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    // Comprobar la respuesta.
    if (!result.success) {
      throw new Error(
        result.message || "No se han podido obtener los clientes",
      );
    }

    // Guardar los clientes recibidos.
    clientes = result.clientes || [];

    // Mostrar los resultados.
    renderClientes();
  } catch (error) {
    console.error("Error al cargar los clientes:", error);

    if (error.status === 401) {
      clearToken();
      window.location.href = "../index.html";
      return;
    }

    showClientesMessage(
      error.message || "Error al cargar los clientes",
      "error",
    );

    throw error;
  }
}

// ========================================
// MOSTRAR CLIENTES
// ========================================

function renderClientes() {
  // Limpiar la tabla.
  clientesList.replaceChildren();

  // Actualizar el contador.
  clientesCount.textContent = clientes.length;

  // Comprobar si existen clientes.
  if (clientes.length === 0) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");

    cell.colSpan = 6;
    cell.textContent = "No se han encontrado clientes";

    row.appendChild(cell);
    clientesList.appendChild(row);

    return;
  }

  // Crear una fila por cada cliente.
  clientes.forEach((cliente) => {
    const row = document.createElement("tr");

    // Guardar el identificador del cliente.
    row.dataset.id = cliente.IDCliente;

    // ------------------------------------
    // NOMBRE COMPLETO
    // ------------------------------------

    const nameCell = document.createElement("td");

    nameCell.className = "cliente-nombre";

    nameCell.textContent = [
      cliente.Nombre,
      cliente.Apellido1,
      cliente.Apellido2,
    ]
      .filter(Boolean)
      .join(" ");

    row.appendChild(nameCell);

    // ------------------------------------
    // TELÉFONO
    // ------------------------------------

    const phoneCell = document.createElement("td");

    phoneCell.textContent = cliente.Telefono || "—";

    row.appendChild(phoneCell);

    // ------------------------------------
    // EMAIL
    // ------------------------------------

    const emailCell = document.createElement("td");

    emailCell.textContent = cliente.Email || "—";

    row.appendChild(emailCell);

    // ------------------------------------
    // FECHA DE ALTA
    // ------------------------------------

    const dateCell = document.createElement("td");

    dateCell.textContent = formatDate(cliente.FechaAlta);

    row.appendChild(dateCell);

    // ------------------------------------
    // ESTADO
    // ------------------------------------

    const statusCell = document.createElement("td");
    const status = document.createElement("span");

    const inactive = isClienteInactive(cliente);

    status.textContent = inactive ? "De baja" : "Activo";

    status.className = inactive
      ? "cliente-estado baja"
      : "cliente-estado activo";

    statusCell.appendChild(status);
    row.appendChild(statusCell);

    // ------------------------------------
    // ACCIONES
    // ------------------------------------

    const actionsCell = document.createElement("td");

    actionsCell.className = "cliente-actions";

    // Botón de edición.
    const editButton = document.createElement("button");

    editButton.type = "button";
    editButton.textContent = "Editar";
    editButton.className = "btn-table";

    editButton.dataset.action = "edit";
    editButton.dataset.id = cliente.IDCliente;

    actionsCell.appendChild(editButton);

    if (inactive) {
      // Botón para reactivar.
      const reactivateButton = document.createElement("button");

      reactivateButton.type = "button";
      reactivateButton.textContent = "Reactivar";
      reactivateButton.className = "btn-table success";

      reactivateButton.dataset.action = "reactivate";
      reactivateButton.dataset.id = cliente.IDCliente;

      actionsCell.appendChild(reactivateButton);
    } else {
      // Botón para dar de baja.
      const deactivateButton = document.createElement("button");

      deactivateButton.type = "button";
      deactivateButton.textContent = "Dar de baja";
      deactivateButton.className = "btn-table danger";

      deactivateButton.dataset.action = "deactivate";
      deactivateButton.dataset.id = cliente.IDCliente;

      actionsCell.appendChild(deactivateButton);
    }

    row.appendChild(actionsCell);

    // ------------------------------------
    // AÑADIR FILA
    // ------------------------------------

    clientesList.appendChild(row);
  });
}

// ========================================
// COMPROBAR ESTADO DEL CLIENTE
// ========================================

function isClienteInactive(cliente) {
  return (
    Number(cliente.baja) === 1 ||
    cliente.baja === true ||
    cliente.FechaBaja != null
  );
}

// ========================================
// FORMATEAR FECHA
// ========================================

function formatDate(dateValue) {
  if (!dateValue) {
    return "—";
  }

  // Evitar problemas de zona horaria con fechas SQL.
  const datePart = String(dateValue).split(" ")[0];

  const parts = datePart.split("-");

  if (parts.length !== 3) {
    return dateValue;
  }

  return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

// ========================================
// INICIALIZAR FILTROS
// ========================================

function initializeFilters() {
  // Buscar al escribir.
  searchClientes.addEventListener("input", loadClientes);

  // Filtrar por estado.
  filterEstado.addEventListener("change", loadClientes);

  // Limpiar filtros.
  clearFiltersButton.addEventListener("click", () => {
    searchClientes.value = "";
    filterEstado.value = "todos";

    loadClientes();
  });
}

// ========================================
// ACCIONES DE LOS CLIENTES
// ========================================

function initializeClienteActions() {
  clientesList.addEventListener("click", handleClienteAction);

  // Doble clic para abrir la ficha del cliente.
  clientesList.addEventListener("dblclick", (event) => {
    const row = event.target.closest("tr[data-id]");

    if (!row) {
      return;
    }

    const clienteId = row.dataset.id;

    window.location.href = `ficha-cliente.html?id=${encodeURIComponent(clienteId)}`;
  });
}

// ========================================
// GESTIONAR ACCIONES
// ========================================

function handleClienteAction(event) {
  const button = event.target.closest("button[data-action]");

  if (!button || !clientesList.contains(button)) {
    return;
  }

  const action = button.dataset.action;
  const clienteId = button.dataset.id;

  if (action === "edit") {
    openEditClienteModal(clienteId);
  } else if (action === "deactivate") {
    deactivateCliente(clienteId, button);
  } else if (action === "reactivate") {
    reactivateCliente(clienteId, button);
  }
}

// ========================================
// MODAL DE CLIENTE
// ========================================

function initializeClienteModal() {
  // Abrir modal de nuevo cliente.
  newClienteButton.addEventListener("click", openNewClienteModal);

  // Cerrar modal.
  closeClienteModalButton.addEventListener("click", closeClienteModal);

  // Cancelar.
  cancelClienteButton.addEventListener("click", closeClienteModal);

  // Enviar formulario.
  clienteForm.addEventListener("submit", handleClienteSubmit);
}

// ========================================
// ABRIR MODAL DE NUEVO CLIENTE
// ========================================

function openNewClienteModal() {
  selectedClienteId = null;

  clienteForm.reset();

  clearClienteFormMessage();

  // Cambiar el texto del botón.
  saveClienteButton.textContent = "Guardar cliente";

  // Mostrar modal.
  clienteModal.showModal();
}

// ========================================
// ABRIR MODAL DE EDICIÓN
// ========================================

function openEditClienteModal(clienteId) {
  const cliente = clientes.find(
    (item) => String(item.IDCliente) === String(clienteId),
  );

  if (!cliente) {
    showClientesMessage("No se ha encontrado el cliente seleccionado", "error");

    return;
  }

  selectedClienteId = cliente.IDCliente;

  // Cargar los datos actuales.
  clienteNombre.value = cliente.Nombre || "";
  clienteApellido1.value = cliente.Apellido1 || "";
  clienteApellido2.value = cliente.Apellido2 || "";
  clienteTelefono.value = cliente.Telefono || "";
  clienteEmail.value = cliente.Email || "";
  clienteDireccion.value = cliente.Direccion || "";
  clienteNotas.value = cliente.Notas || "";

  clearClienteFormMessage();

  saveClienteButton.textContent = "Guardar cambios";

  clienteModal.showModal();
}

// ========================================
// CERRAR MODAL
// ========================================

function closeClienteModal() {
  clienteModal.close();

  clienteForm.reset();

  selectedClienteId = null;

  clearClienteFormMessage();
}

// ========================================
// GUARDAR CLIENTE
// ========================================

async function handleClienteSubmit(event) {
  event.preventDefault();

  // Obtener los datos del formulario.
  const clienteData = {
    Nombre: clienteNombre.value.trim(),

    Apellido1: clienteApellido1.value.trim(),

    Apellido2: clienteApellido2.value.trim(),

    Telefono: clienteTelefono.value.trim(),

    Email: clienteEmail.value.trim(),

    Direccion: clienteDireccion.value.trim(),

    Notas: clienteNotas.value.trim(),
  };

  // Validar campos obligatorios.
  if (!clienteData.Nombre || !clienteData.Apellido1) {
    showClienteFormMessage(
      "El nombre y el primer apellido son obligatorios",
      "error",
    );

    return;
  }

  // Evitar envíos repetidos.
  saveClienteButton.disabled = true;
  saveClienteButton.textContent = "Guardando...";

  clearClienteFormMessage();

  try {
    const token = getSessionToken();

    // Determinar si es un alta o una actualización.
    const isEditing = selectedClienteId !== null;

    const endpoint = isEditing
      ? `/api/clientes/${selectedClienteId}`
      : "/api/clientes";

    const method = isEditing ? "PUT" : "POST";

    // Enviar los datos a la API.
    const result = await apiRequest(endpoint, {
      method,

      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },

      body: JSON.stringify(clienteData),
    });

    if (!result.success) {
      throw new Error(result.message || "No se ha podido guardar el cliente");
    }

    // Guardar el tipo de operación antes de cerrar el modal.
    const message = isEditing
      ? "Los datos del cliente se han actualizado correctamente"
      : "El cliente se ha registrado correctamente";

    // Cerrar modal.
    closeClienteModal();

    // Mostrar confirmación.
    showClientesMessage(message, "success");

    // Actualizar listado.
    await loadClientes();
  } catch (error) {
    console.error("Error al guardar el cliente:", error);

    if (error.status === 401) {
      clearToken();

      window.location.href = "../index.html";

      return;
    }

    // Mostrar errores de duplicidad y validación.
    showClienteFormMessage(
      error.message || "No se ha podido guardar el cliente",
      "error",
    );
  } finally {
    saveClienteButton.disabled = false;

    saveClienteButton.textContent =
      selectedClienteId !== null ? "Guardar cambios" : "Guardar cliente";
  }
}

// ========================================
// DAR DE BAJA A UN CLIENTE
// ========================================

async function deactivateCliente(clienteId, button) {
  const cliente = clientes.find(
    (item) => String(item.IDCliente) === String(clienteId),
  );

  if (!cliente) {
    showClientesMessage("No se ha encontrado el cliente seleccionado", "error");

    return;
  }

  const fullName = [cliente.Nombre, cliente.Apellido1, cliente.Apellido2]
    .filter(Boolean)
    .join(" ");

  const confirmed = window.confirm(
    `¿Estás seguro de que quieres dar de baja a ${fullName}?\n\n` +
      "Sus datos y su historial se conservarán.",
  );

  if (!confirmed) {
    return;
  }

  button.disabled = true;
  button.textContent = "Procesando...";

  try {
    const token = getSessionToken();

    const result = await apiRequest(`/api/clientes/${clienteId}/baja`, {
      method: "PATCH",

      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido dar de baja al cliente",
      );
    }

    showClientesMessage(
      `El cliente ${fullName} ha sido dado de baja correctamente`,
      "success",
    );

    await loadClientes();
  } catch (error) {
    console.error("Error al dar de baja al cliente:", error);

    if (error.status === 401) {
      clearToken();

      window.location.href = "../index.html";

      return;
    }

    showClientesMessage(
      error.message || "Error al dar de baja al cliente",
      "error",
    );
  } finally {
    button.disabled = false;

    if (button.isConnected) {
      button.textContent = "Dar de baja";
    }
  }
}

// ========================================
// REACTIVAR CLIENTE
// ========================================

async function reactivateCliente(clienteId, button) {
  const cliente = clientes.find(
    (item) => String(item.IDCliente) === String(clienteId),
  );

  if (!cliente) {
    showClientesMessage("No se ha encontrado el cliente seleccionado", "error");

    return;
  }

  const fullName = [cliente.Nombre, cliente.Apellido1, cliente.Apellido2]
    .filter(Boolean)
    .join(" ");

  const confirmed = window.confirm(`¿Quieres reactivar a ${fullName}?`);

  if (!confirmed) {
    return;
  }

  button.disabled = true;
  button.textContent = "Procesando...";

  try {
    const token = getSessionToken();

    const result = await apiRequest(`/api/clientes/${clienteId}/reactivar`, {
      method: "PATCH",

      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!result.success) {
      throw new Error(result.message || "No se ha podido reactivar al cliente");
    }

    showClientesMessage(
      `El cliente ${fullName} ha sido reactivado correctamente`,
      "success",
    );

    await loadClientes();
  } catch (error) {
    console.error("Error al reactivar al cliente:", error);

    if (error.status === 401) {
      clearToken();

      window.location.href = "../index.html";

      return;
    }

    showClientesMessage(
      error.message || "Error al reactivar al cliente",
      "error",
    );
  } finally {
    button.disabled = false;

    if (button.isConnected) {
      button.textContent = "Reactivar";
    }
  }
}

// ========================================
// MOSTRAR MENSAJES GENERALES
// ========================================

function showClientesMessage(message, type) {
  clientesMessage.textContent = message;

  clientesMessage.className = `clientes-message visible ${type}`;
}

// ========================================
// MOSTRAR MENSAJES DEL FORMULARIO
// ========================================

function showClienteFormMessage(message, type) {
  clienteFormMessage.textContent = message;

  clienteFormMessage.className = `form-message visible ${type}`;
}

// ========================================
// LIMPIAR MENSAJE DEL FORMULARIO
// ========================================

function clearClienteFormMessage() {
  clienteFormMessage.textContent = "";
  clienteFormMessage.className = "form-message";
}

// ========================================
// NAVEGACIÓN
// ========================================

function initializeNavigation() {
  if (backButton) {
    backButton.addEventListener("click", () => {
      window.location.href = "../dashboard.html";
    });
  }
}

// ========================================
// INICIALIZAR PÁGINA
// ========================================

document.addEventListener("DOMContentLoaded", initializeClientes);
