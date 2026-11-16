/**
 * ==========================================================
 * FICHA DE CLIENTE
 * ==========================================================
 *
 * Gestiona la información de un cliente individual:
 * - Carga de datos personales.
 * - Edición de datos.
 * - Baja y reactivación.
 * - Preparación de la sección de historial.
 *
 * El ID del cliente se obtiene de la URL:
 * ficha-cliente.html?id=1
 */

// ==========================================================
// ELEMENTOS DEL DOM
// ==========================================================

// Información general
const clienteEstado = document.getElementById("cliente-estado");
const clienteMessage = document.getElementById("cliente-message");

// Formulario de datos personales
const clienteDatosForm = document.getElementById("cliente-datos-form");

const inputNombre = document.getElementById("nombre");
const inputApellido1 = document.getElementById("apellido1");
const inputApellido2 = document.getElementById("apellido2");
const inputTelefono = document.getElementById("telefono");
const inputEmail = document.getElementById("email");
const inputDireccion = document.getElementById("direccion");
const inputFechaAlta = document.getElementById("fecha-alta");
const inputNotas = document.getElementById("notas");

// Botones de edición
const editarClienteButton = document.getElementById("editar-cliente-button");
const guardarClienteButton = document.getElementById("guardar-cliente-button");
const cancelarEdicionButton = document.getElementById(
  "cancelar-edicion-button",
);

// Estado del cliente
const estadoDescripcion = document.getElementById("estado-descripcion");
const darBajaButton = document.getElementById("dar-baja-button");
const reactivarButton = document.getElementById("reactivar-button");

// Historial
const historialForm = document.getElementById("historial-form");
const historialObservacion = document.getElementById("historial-observacion");
const historialMessage = document.getElementById("historial-message");
const totalHistorial = document.getElementById("total-historial");
const historialTbody = document.getElementById("historial-tbody");

// Navegación
const volverButton = document.getElementById("volver-button");

// ==========================================================
// VARIABLES DE ESTADO
// ==========================================================

// ID del cliente obtenido de la URL
const parametrosURL = new URLSearchParams(window.location.search);
const idCliente = Number(parametrosURL.get("id"));

// Datos del cliente cargado
let clienteActual = null;

// Indica si estamos editando los datos
let modoEdicion = false;

// ==========================================================
// INICIALIZACIÓN
// ==========================================================

document.addEventListener("DOMContentLoaded", initializeFichaCliente);

/**
 * Inicializa la ficha del cliente.
 */
async function initializeFichaCliente() {
  // Comprobar que se ha recibido un ID válido
  if (!Number.isInteger(idCliente) || idCliente <= 0) {
    showClienteMessage(
      "No se ha indicado un identificador de cliente válido.",
      "error",
    );

    return;
  }

  // Comprobar que existe una sesión activa
  const token = getToken();

  if (!token) {
    showClienteMessage(
      "La sesión ha caducado. Inicia sesión nuevamente.",
      "error",
    );

    return;
  }

  // Configurar eventos
  initializeEvents();

  // Cargar información del cliente
  await loadCliente();
}

// ==========================================================
// EVENTOS
// ==========================================================

/**
 * Configura los eventos de los botones y formularios.
 */
function initializeEvents() {
  // Volver al listado de clientes
  if (volverButton) {
    volverButton.addEventListener("click", () => {
      window.location.href = "clientes.html";
    });
  }

  // Activar modo edición
  if (editarClienteButton) {
    editarClienteButton.addEventListener("click", enableEditMode);
  }

  // Guardar cambios
  if (guardarClienteButton) {
    guardarClienteButton.addEventListener("click", updateCliente);
  }

  // Cancelar edición
  if (cancelarEdicionButton) {
    cancelarEdicionButton.addEventListener("click", cancelEditMode);
  }

  // Dar de baja al cliente
  if (darBajaButton) {
    darBajaButton.addEventListener("click", darDeBajaCliente);
  }

  // Reactivar cliente
  if (reactivarButton) {
    reactivarButton.addEventListener("click", reactivarCliente);
  }

  // El historial todavía no dispone de endpoints en la API.
  // Evitamos que el formulario intente enviarse.
  if (historialForm) {
    historialForm.addEventListener("submit", (event) => {
      event.preventDefault();

      showHistorialMessage(
        "El historial de clientes todavía no está disponible.",
        "error",
      );
    });
  }
}

// ==========================================================
// CARGAR CLIENTE
// ==========================================================

/**
 * Obtiene los datos del cliente desde la API.
 */
async function loadCliente() {
  try {
    const token = getToken();

    const result = await apiRequest(`/api/clientes/${idCliente}`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!result.success) {
      throw new Error(result.message || "No se ha podido obtener el cliente.");
    }

    clienteActual = result.cliente;

    if (!clienteActual) {
      throw new Error("La API no ha devuelto los datos del cliente.");
    }

    // Mostrar información
    renderCliente();

    // Limpiar mensajes anteriores
    showClienteMessage("", "");
  } catch (error) {
    console.error("Error al cargar el cliente:", error);

    showClienteMessage(
      error.message || "Error al cargar los datos del cliente.",
      "error",
    );
  }
}

// ==========================================================
// MOSTRAR DATOS DEL CLIENTE
// ==========================================================

/**
 * Muestra los datos personales del cliente en el formulario.
 */
function renderCliente() {
  if (!clienteActual) {
    return;
  }

  inputNombre.value = clienteActual.Nombre || "";
  inputApellido1.value = clienteActual.Apellido1 || "";
  inputApellido2.value = clienteActual.Apellido2 || "";
  inputTelefono.value = clienteActual.Telefono || "";
  inputEmail.value = clienteActual.Email || "";
  inputDireccion.value = clienteActual.Direccion || "";
  inputNotas.value = clienteActual.Notas || "";

  inputFechaAlta.value = formatDate(clienteActual.FechaAlta);

  // Mostrar el estado del cliente
  renderEstadoCliente();

  // Asegurar que el formulario empieza en modo lectura
  setEditMode(false);
}

// ==========================================================
// ESTADO DEL CLIENTE
// ==========================================================

/**
 * Actualiza los elementos visuales relacionados con el estado.
 */
function renderEstadoCliente() {
  const estaDeBaja = Boolean(Number(clienteActual.baja));

  if (estaDeBaja) {
    clienteEstado.textContent = "De baja";
    clienteEstado.className = "cliente-estado baja";

    estadoDescripcion.textContent = "Este cliente se encuentra dado de baja.";

    darBajaButton.hidden = true;
    reactivarButton.hidden = false;

    // No permitir editar los datos de un cliente de baja
    editarClienteButton.disabled = true;
  } else {
    clienteEstado.textContent = "Activo";
    clienteEstado.className = "cliente-estado activo";

    estadoDescripcion.textContent = "Este cliente se encuentra activo.";

    darBajaButton.hidden = false;
    reactivarButton.hidden = true;

    editarClienteButton.disabled = false;
  }
}

// ==========================================================
// MODO EDICIÓN
// ==========================================================

/**
 * Activa o desactiva el modo de edición.
 *
 * @param {boolean} activar
 */
function setEditMode(activar) {
  modoEdicion = activar;

  const camposEditables = [
    inputNombre,
    inputApellido1,
    inputApellido2,
    inputTelefono,
    inputEmail,
    inputDireccion,
    inputNotas,
  ];

  // Activar o desactivar los campos
  camposEditables.forEach((campo) => {
    campo.readOnly = !activar;
  });

  // Mostrar los botones correspondientes
  editarClienteButton.hidden = activar;
  guardarClienteButton.hidden = !activar;
  cancelarEdicionButton.hidden = !activar;

  // La fecha de alta nunca se puede modificar
  inputFechaAlta.readOnly = true;
  inputFechaAlta.disabled = true;
}

/**
 * Activa el modo de edición.
 */
function enableEditMode() {
  if (!clienteActual || Boolean(Number(clienteActual.baja))) {
    return;
  }

  // Guardar una copia de los datos originales
  renderCliente();

  setEditMode(true);

  showClienteMessage("", "");
}

/**
 * Cancela la edición y recupera los datos originales.
 */
function cancelEditMode() {
  if (!clienteActual) {
    return;
  }

  // Restaurar los valores originales
  renderCliente();

  showClienteMessage("Se ha cancelado la edición.", "success");
}

// ==========================================================
// ACTUALIZAR CLIENTE
// ==========================================================

/**
 * Envía los cambios del cliente a la API.
 */
async function updateCliente() {
  if (!modoEdicion || !clienteActual) {
    return;
  }

  // Recoger los datos del formulario
  const datosCliente = {
    Nombre: inputNombre.value.trim(),
    Apellido1: inputApellido1.value.trim(),
    Apellido2: inputApellido2.value.trim(),
    Telefono: inputTelefono.value.trim(),
    Email: inputEmail.value.trim(),
    Direccion: inputDireccion.value.trim(),
    Notas: inputNotas.value.trim(),
  };

  // Validaciones básicas
  if (!datosCliente.Nombre) {
    showClienteMessage("El nombre del cliente es obligatorio.", "error");

    inputNombre.focus();
    return;
  }

  if (!datosCliente.Apellido1) {
    showClienteMessage("El primer apellido es obligatorio.", "error");

    inputApellido1.focus();
    return;
  }

  if (datosCliente.Email && !inputEmail.validity.valid) {
    showClienteMessage(
      "Introduce una dirección de correo electrónico válida.",
      "error",
    );

    inputEmail.focus();
    return;
  }

  // Convertir campos opcionales vacíos a null
  datosCliente.Apellido2 = datosCliente.Apellido2 || null;
  datosCliente.Telefono = datosCliente.Telefono || null;
  datosCliente.Email = datosCliente.Email || null;
  datosCliente.Direccion = datosCliente.Direccion || null;
  datosCliente.Notas = datosCliente.Notas || null;

  try {
    guardarClienteButton.disabled = true;

    const token = getToken();

    const result = await apiRequest(`/api/clientes/${idCliente}`, {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(datosCliente),
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se han podido guardar los cambios.",
      );
    }

    // Actualizar los datos locales
    clienteActual = {
      ...clienteActual,
      ...datosCliente,
    };

    // Volver al modo lectura
    renderCliente();

    showClienteMessage(
      result.message ||
        "Los datos del cliente se han actualizado correctamente.",
      "success",
    );
  } catch (error) {
    console.error("Error al actualizar el cliente:", error);

    showClienteMessage(
      error.message || "Error al actualizar los datos del cliente.",
      "error",
    );
  } finally {
    guardarClienteButton.disabled = false;
  }
}

// ==========================================================
// DAR DE BAJA AL CLIENTE
// ==========================================================

/**
 * Realiza la baja lógica de un cliente.
 */
async function darDeBajaCliente() {
  if (!clienteActual || Boolean(Number(clienteActual.baja))) {
    return;
  }

  const confirmar = window.confirm(
    `¿Estás seguro de que deseas dar de baja a ${clienteActual.Nombre} ${clienteActual.Apellido1}?`,
  );

  if (!confirmar) {
    return;
  }

  try {
    darBajaButton.disabled = true;

    const token = getToken();

    const result = await apiRequest(`/api/clientes/${idCliente}/baja`, {
      method: "PATCH",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido dar de baja al cliente.",
      );
    }

    // Actualizar el estado local
    clienteActual.baja = true;

    // Actualizar la interfaz
    renderEstadoCliente();

    showClienteMessage(
      result.message || "El cliente ha sido dado de baja correctamente.",
      "success",
    );
  } catch (error) {
    console.error("Error al dar de baja al cliente:", error);

    showClienteMessage(
      error.message || "Error al dar de baja al cliente.",
      "error",
    );
  } finally {
    darBajaButton.disabled = false;
  }
}

// ==========================================================
// REACTIVAR CLIENTE
// ==========================================================

/**
 * Reactiva un cliente que se encuentra de baja.
 */
async function reactivarCliente() {
  if (!clienteActual || !Boolean(Number(clienteActual.baja))) {
    return;
  }

  const confirmar = window.confirm(
    `¿Deseas reactivar a ${clienteActual.Nombre} ${clienteActual.Apellido1}?`,
  );

  if (!confirmar) {
    return;
  }

  try {
    reactivarButton.disabled = true;

    const token = getToken();

    const result = await apiRequest(`/api/clientes/${idCliente}/reactivar`, {
      method: "PATCH",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido reactivar al cliente.",
      );
    }

    // Actualizar el estado local
    clienteActual.baja = false;

    // Actualizar la interfaz
    renderEstadoCliente();

    showClienteMessage(
      result.message || "El cliente ha sido reactivado correctamente.",
      "success",
    );
  } catch (error) {
    console.error("Error al reactivar al cliente:", error);

    showClienteMessage(
      error.message || "Error al reactivar al cliente.",
      "error",
    );
  } finally {
    reactivarButton.disabled = false;
  }
}

// ==========================================================
// MENSAJES
// ==========================================================

/**
 * Muestra mensajes relacionados con la ficha del cliente.
 *
 * @param {string} mensaje
 * @param {string} tipo
 */
function showClienteMessage(mensaje, tipo) {
  if (!clienteMessage) {
    return;
  }

  clienteMessage.textContent = mensaje;
  clienteMessage.hidden = !mensaje;

  clienteMessage.classList.remove("success", "error");

  if (tipo) {
    clienteMessage.classList.add(tipo);
  }
}

/**
 * Muestra mensajes relacionados con el historial.
 *
 * @param {string} mensaje
 * @param {string} tipo
 */
function showHistorialMessage(mensaje, tipo) {
  if (!historialMessage) {
    return;
  }

  historialMessage.textContent = mensaje;
  historialMessage.hidden = !mensaje;

  historialMessage.classList.remove("success", "error");

  if (tipo) {
    historialMessage.classList.add(tipo);
  }
}

// ==========================================================
// FORMATO DE FECHAS
// ==========================================================

/**
 * Convierte una fecha a formato español.
 *
 * Evita desplazamientos de fecha cuando la API devuelve
 * fechas SQL sin información de zona horaria.
 *
 * @param {string|null} fecha
 * @returns {string}
 */
function formatDate(fecha) {
  if (!fecha) {
    return "—";
  }

  const fechaTexto = String(fecha).substring(0, 10);
  const partes = fechaTexto.split("-");

  if (partes.length !== 3) {
    return fechaTexto;
  }

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}
