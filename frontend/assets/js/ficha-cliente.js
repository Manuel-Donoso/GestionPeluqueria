/**
 * ==========================================================
 * FICHA DE CLIENTE
 * ==========================================================
 *
 * Gestiona:
 * - Carga de datos personales.
 * - Edición de datos.
 * - Baja y reactivación.
 * - Consulta del historial profesional.
 * - Registro de nuevas observaciones.
 */

// ==========================================================
// ELEMENTOS DEL DOM
// ==========================================================

// Información general
const clienteEstado = document.getElementById("cliente-estado");
const clienteMessage = document.getElementById("cliente-message");

// Formulario de datos personales
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
const guardarHistorialButton = document.getElementById(
  "guardar-historial-button",
);

// Navegación
const volverButton = document.getElementById("volver-button");

// ==========================================================
// VARIABLES DE ESTADO
// ==========================================================

// Obtener ID del cliente desde la URL
const parametrosURL = new URLSearchParams(window.location.search);
const idCliente = Number(parametrosURL.get("id"));

// Datos del cliente
let clienteActual = null;

// Estado de edición
let modoEdicion = false;

// ==========================================================
// INICIALIZACIÓN
// ==========================================================

document.addEventListener("DOMContentLoaded", initializeFichaCliente);

/**
 * Inicializa la ficha del cliente.
 */
async function initializeFichaCliente() {
  if (!Number.isInteger(idCliente) || idCliente <= 0) {
    showClienteMessage(
      "No se ha indicado un identificador de cliente válido.",
      "error",
    );
    return;
  }

  if (!getToken()) {
    showClienteMessage(
      "La sesión ha caducado. Inicia sesión nuevamente.",
      "error",
    );
    return;
  }

  initializeEvents();

  // Cargar los datos del cliente
  await loadCliente();

  // Cargar el historial profesional
  await loadHistorial();
}

// ==========================================================
// EVENTOS
// ==========================================================

/**
 * Configura los eventos de los botones y formularios.
 */
function initializeEvents() {
  // Volver al listado
  if (volverButton) {
    volverButton.addEventListener("click", () => {
      window.location.href = "clientes.html";
    });
  }

  // Edición de datos
  if (editarClienteButton) {
    editarClienteButton.addEventListener("click", enableEditMode);
  }

  if (guardarClienteButton) {
    guardarClienteButton.addEventListener("click", updateCliente);
  }

  if (cancelarEdicionButton) {
    cancelarEdicionButton.addEventListener("click", cancelEditMode);
  }

  // Baja y reactivación
  if (darBajaButton) {
    darBajaButton.addEventListener("click", darDeBajaCliente);
  }

  if (reactivarButton) {
    reactivarButton.addEventListener("click", reactivarCliente);
  }

  // Registro de observaciones
  if (historialForm) {
    historialForm.addEventListener("submit", crearEntradaHistorial);
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
    const result = await apiRequest(`/api/clientes/${idCliente}`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${getToken()}`,
      },
    });

    if (!result.success) {
      throw new Error(result.message || "No se ha podido obtener el cliente.");
    }

    clienteActual = result.cliente;

    if (!clienteActual) {
      throw new Error("La API no ha devuelto los datos del cliente.");
    }

    renderCliente();

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
 * Muestra los datos personales del cliente.
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

  renderEstadoCliente();

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

  camposEditables.forEach((campo) => {
    campo.readOnly = !activar;
  });

  editarClienteButton.hidden = activar;
  guardarClienteButton.hidden = !activar;
  cancelarEdicionButton.hidden = !activar;

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

  const datosCliente = {
    Nombre: inputNombre.value.trim(),
    Apellido1: inputApellido1.value.trim(),
    Apellido2: inputApellido2.value.trim(),
    Telefono: inputTelefono.value.trim(),
    Email: inputEmail.value.trim(),
    Direccion: inputDireccion.value.trim(),
    Notas: inputNotas.value.trim(),
  };

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

    const result = await apiRequest(`/api/clientes/${idCliente}`, {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${getToken()}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(datosCliente),
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se han podido guardar los cambios.",
      );
    }

    clienteActual = {
      ...clienteActual,
      ...datosCliente,
    };

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

    const result = await apiRequest(`/api/clientes/${idCliente}/baja`, {
      method: "PATCH",
      headers: {
        Authorization: `Bearer ${getToken()}`,
      },
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido dar de baja al cliente.",
      );
    }

    clienteActual.baja = true;

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

    const result = await apiRequest(`/api/clientes/${idCliente}/reactivar`, {
      method: "PATCH",
      headers: {
        Authorization: `Bearer ${getToken()}`,
      },
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido reactivar al cliente.",
      );
    }

    clienteActual.baja = false;

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
// HISTORIAL: CARGAR OBSERVACIONES
// ==========================================================

/**
 * Obtiene el historial profesional del cliente.
 */
async function loadHistorial() {
  try {
    historialTbody.innerHTML = `
            <tr>
                <td colspan="3">Cargando historial...</td>
            </tr>
        `;

    const result = await apiRequest(`/api/clientes/${idCliente}/historial`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${getToken()}`,
      },
    });

    if (!result.success) {
      throw new Error(result.message || "No se ha podido cargar el historial.");
    }

    const historial = result.historial || [];

    renderHistorial(historial);
  } catch (error) {
    console.error("Error al cargar el historial:", error);

    historialTbody.innerHTML = `
            <tr>
                <td colspan="3">No se ha podido cargar el historial.</td>
            </tr>
        `;

    showHistorialMessage(
      error.message || "Error al cargar el historial.",
      "error",
    );
  }
}

// ==========================================================
// HISTORIAL: MOSTRAR OBSERVACIONES
// ==========================================================

/**
 * Representa las observaciones en la tabla.
 *
 * @param {Array} historial
 */
function renderHistorial(historial) {
  historialTbody.innerHTML = "";

  totalHistorial.textContent = historial.length;

  if (historial.length === 0) {
    historialTbody.innerHTML = `
            <tr>
                <td colspan="3">Este cliente todavía no tiene observaciones.</td>
            </tr>
        `;

    return;
  }

  historial.forEach((entrada) => {
    const fila = document.createElement("tr");

    // Identificador de la entrada
    const celdaId = document.createElement("td");
    celdaId.textContent = entrada.IDHistorial;

    // Observación
    const celdaObservacion = document.createElement("td");
    celdaObservacion.textContent = entrada.observaciones;

    // Peluquero que registró la observación
    const celdaPeluquero = document.createElement("td");
    celdaPeluquero.textContent = entrada.peluquero || "No disponible";

    fila.appendChild(celdaId);
    fila.appendChild(celdaObservacion);
    fila.appendChild(celdaPeluquero);

    historialTbody.appendChild(fila);
  });
}

// ==========================================================
// HISTORIAL: CREAR OBSERVACIÓN
// ==========================================================

/**
 * Registra una nueva observación en el historial.
 */
async function crearEntradaHistorial(event) {
  event.preventDefault();

  const observaciones = historialObservacion.value.trim();

  if (!observaciones) {
    showHistorialMessage("Escribe una observación antes de guardar.", "error");

    historialObservacion.focus();
    return;
  }

  try {
    guardarHistorialButton.disabled = true;

    const result = await apiRequest(`/api/clientes/${idCliente}/historial`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${getToken()}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        observaciones: observaciones,
      }),
    });

    if (!result.success) {
      throw new Error(
        result.message || "No se ha podido guardar la observación.",
      );
    }

    // Limpiar el campo de observaciones
    historialObservacion.value = "";

    showHistorialMessage(
      result.message || "La observación se ha añadido correctamente.",
      "success",
    );

    // Recargar el historial para mostrar la nueva entrada
    await loadHistorial();
  } catch (error) {
    console.error("Error al guardar la observación:", error);

    showHistorialMessage(
      error.message || "Error al guardar la observación.",
      "error",
    );
  } finally {
    guardarHistorialButton.disabled = false;
  }
}

// ==========================================================
// MENSAJES
// ==========================================================

/**
 * Muestra mensajes relacionados con la ficha del cliente.
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
 * Convierte una fecha SQL a formato español.
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
