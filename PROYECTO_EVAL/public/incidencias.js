const apiUrl = "/api/"; // Ruta base para los endpoints de la API
let allIncidencias = []; // Variable para almacenar todas las incidencias
loadIncidenciasGoogle();
// Función para mostrar y ocultar secciones
function toggleSections(sectionToShow) {
  // Ocultar todas las secciones
  document.getElementById("incidencias").style.display = "none";
  document.getElementById("asignAulas").style.display = "none";
  document.getElementById("editarIncidenciaForm").style.display = "none";
  document.getElementById("detallesIncidencia").style.display = "none";
  document.getElementById("resolverIncidenciaForm").style.display = "none";

  // Mostrar solo la sección deseada
  if (sectionToShow === "incidencias") {
      document.getElementById("incidencias").style.display = "block";
  } else if (sectionToShow === "asignAulas") {
      document.getElementById("asignAulas").style.display = "block";
  } else if (sectionToShow === "editarIncidenciaForm") {
      document.getElementById("editarIncidenciaForm").style.display = "block";
  } else if (sectionToShow === "detallesIncidencia") {
      document.getElementById("detallesIncidencia").style.display = "block";
  } else if (sectionToShow === "resolverIncidenciaForm") {
      document.getElementById("resolverIncidenciaForm").style.display = "block";
  }
}

// Manejo de incidencias (crear)
if (document.getElementById("incidenciaForm")) {
  document
    .getElementById("incidenciaForm")
    .addEventListener("submit", async (event) => {
      event.preventDefault();
      const id_aula = document.getElementById("aula").value;
      const id_tipo = document.getElementById("tipoIncidencia").value;
      const id_puesto = document.getElementById("puesto").value;
      const descripcion = document.getElementById("descripcion").value;

      const response = await fetch(apiUrl + "incidencias.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + localStorage.getItem("token"),
        },
        body: JSON.stringify({ id_aula, id_tipo, id_puesto, descripcion }),
      });

      const result = await response.json();
      alert(result.message || result.error);
      loadIncidencias();
      limpiarCampos();
    });
}
async function limpiarCampos() {
  document.getElementById("aula").value = ""; // Limpiar el campo de aula
  document.getElementById("tipoIncidencia").value = ""; // Limpiar el campo de tipo de incidencia
  document.getElementById("puesto").value = ""; // Limpiar el campo de puesto
  document.getElementById("descripcion").value = ""; // Limpiar el campo de descripción
}
// Función para cargar todas las incidencias para usuarios de Google
async function loadIncidenciasGoogle() {
  const response = await fetch(apiUrl + "incidencias_google.php", {
    headers: { Authorization: "Bearer " + localStorage.getItem("token") },
  });

  // Verifica si la respuesta es correcta
  if (!response.ok) {
    const error = await response.text();
    console.error("Error al obtener las incidencias:", error);
    alert("Error al obtener las incidencias");
    return;
  }

  allIncidencias = await response.json(); // Almacena todas las incidencias

  // Verifica si incidencias es un array
  if (!Array.isArray(allIncidencias)) {
    console.error("Error: La respuesta no es un array de incidencias");
    alert("Error al obtener las incidencias");
    return;
  }

  displayIncidencias(allIncidencias); // Muestra todas las incidencias
}
// Cargar todas las incidencias
async function loadIncidencias() {
  const response = await fetch(apiUrl + "incidencias.php", {
    headers: { Authorization: "Bearer " + localStorage.getItem("token") },
  });

  // Verifica si la respuesta es correcta
  if (!response.ok) {
    const error = await response.json();
    console.error("Error al cargar incidencias:", error);
    alert("Error al cargar las incidencias: " + error.message);
    return;
  }

  allIncidencias = await response.json(); // Almacena todas las incidencias

  // Verifica si incidencias es un array
  if (!Array.isArray(allIncidencias)) {
    console.error("La respuesta no es un array:", allIncidencias);
    alert("Error: La respuesta no es un array.");
    return;
  }
  
  // Verifica que cada incidencia tenga las propiedades id_tipo y id_aula
  allIncidencias.forEach((incidencia, index) => {
    if (incidencia.id_tipo === undefined || incidencia.id_aula === undefined) {
      console.error(`Incidencia ${index + 1} no tiene id_tipo o id_aula:`, incidencia);
    }
  });

  displayIncidencias(allIncidencias); // Muestra todas las incidencias
  limpiarCampos();
}

// Función para aplicar filtros para usuarios de Google
async function applyFiltersGoogle() {
  const estado = document.getElementById("filterEstado").value;
  const tipo = document.getElementById("filterTipo").value;
  const aula = document.getElementById("filterAula").value;
  const fechaDesde = document.getElementById("filterDesde").value;
  const fechaHasta = document.getElementById("filterHasta").value;

  const filters = {
    estado,
    tipo,
    aula,
    fechaDesde,
    fechaHasta
  };

  const response = await fetch(apiUrl + "filtrar_incidencias_google.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: "Bearer " + localStorage.getItem("token"),
    },
    body: JSON.stringify(filters),
  });

  if (!response.ok) {
    const error = await response.text();
    console.error("Error al aplicar filtros:", error);
    alert("Error al aplicar filtros");
    return;
  }

  const filteredIncidencias = await response.json();

  // Verifica si incidencias es un array
  if (!Array.isArray(filteredIncidencias)) {
    console.error("Error: La respuesta no es un array de incidencias");
    alert("Error al aplicar filtros");
    return;
  }

  displayIncidencias(filteredIncidencias); // Muestra las incidencias filtradas
}
// Función para restablecer los filtros
function resetFilter() {
  document.getElementById("filterEstado").value = "";
  document.getElementById("filterTipo").value = "";
  document.getElementById("filterAula").value = "";
  document.getElementById("filterDesde").value = "";
  document.getElementById("filterHasta").value = "";

  // Mostrar todas las incidencias
  displayIncidencias(allIncidencias);
}

// Función para mostrar incidencias
function displayIncidencias(incidencias) {
  const incidenciasContainer = document.getElementById("incidenciasContainer");
  incidenciasContainer.innerHTML = "";

  incidencias.forEach((incidencia) => {
    const li = document.createElement("li");
    li.classList.add("list-group-item");
    if(localStorage.getItem("isGoogleUser")) {
      li.innerHTML = `
      <strong>Aula:</strong> ${incidencia.nombre_aula} |
      <strong>Tipo:</strong> ${incidencia.nombre_tipo} |
      <strong>Puesto:</strong> ${incidencia.nombre_puesto} |
      <strong>Estado:</strong> 
      <select id="sel1" disabled>)">
        <option value="Pendiente" ${incidencia.estado === "Pendiente" ? "selected" : ""}>Pendiente</option>
        <option value="En proceso" ${incidencia.estado === "En proceso" ? "selected" : ""}>En proceso</option>
        <option value="Resuelto" ${incidencia.estado === "Resuelto" ? "selected" : ""}>Resuelto</option>
        <option value="Cerrado" ${incidencia.estado === "Cerrado" ? "selected" : ""}>Cerrado</option>
        <option value="Cancelado" ${incidencia.estado === "Cancelado" ? "selected" : ""}>Cancelado</option>
      </select>
    `;
    } else {
    li.innerHTML = `
      <strong>Aula:</strong> ${incidencia.nombre_aula} |
      <strong>Tipo:</strong> ${incidencia.nombre_tipo} |
      <strong>Puesto:</strong> ${incidencia.nombre_puesto} |
      <strong>Estado:</strong> 
      <select onchange="updateIncidencia(${incidencia.id_incidencia}, this.value)">
        <option value="Pendiente" ${incidencia.estado === "Pendiente" ? "selected" : ""}>Pendiente</option>
        <option value="En proceso" ${incidencia.estado === "En proceso" ? "selected" : ""}>En proceso</option>
        <option value="Resuelto" ${incidencia.estado === "Resuelto" ? "selected" : ""}>Resuelto</option>
        <option value="Cerrado" ${incidencia.estado === "Cerrado" ? "selected" : ""}>Cerrado</option>
        <option value="Cancelado" ${incidencia.estado === "Cancelado" ? "selected" : ""}>Cancelado</option>
      </select> |
      <button id="mod1-${incidencia.id_incidencia}" class="btn btn-warning btn-sm">Modificar</button> |
      <button id="info1-${incidencia.id_incidencia}" class="btn btn-info btn-sm">Más Información</button>
    `;}
    incidenciasContainer.appendChild(li);
      if(!localStorage.getItem("isGoogleUser")) {
        document
      .getElementById(`mod1-${incidencia.id_incidencia}`)
      .addEventListener("click", () => {
        toggleSections("editarIncidenciaForm"); // Ocultar incidencias y mostrar el formulario de edición
        cargarDatosModificarIncidencia(incidencia.id_incidencia); // Cargar los datos de la incidencia para modificar
      });

    // Asignar evento al botón "Más Información"
    document
      .getElementById(`info1-${incidencia.id_incidencia}`)
      .addEventListener("click", () => {
        toggleSections("detallesIncidencia");
        cargarDatosIncidencia(incidencia.id_incidencia); // Cargar los datos de la incidencia para ver más detalles
      });
  
      }
  });
}

// Función para aplicar filtros
async function applyFilters() {
  const estado = document.getElementById("filterEstado").value;
  const tipo = document.getElementById("filterTipo").value;
  const aula = document.getElementById("filterAula").value;
  const fechaDesde = document.getElementById("filterDesde").value;
  const fechaHasta = document.getElementById("filterHasta").value;

  const filters = {
    estado,
    tipo,
    aula,
    fechaDesde,
    fechaHasta
  };

  // Filtrar incidencias según los filtros aplicados
  const filteredIncidencias = allIncidencias.filter(incidencia => {
    const estadoMatch = filters.estado ? incidencia.estado === filters.estado : true;
    const tipoMatch = filters.tipo ? (incidencia.id_tipo && incidencia.id_tipo.toString() === filters.tipo) : true; // Asegúrate de que id_tipo esté presente en la incidencia
    const aulaMatch = filters.aula ? (incidencia.id_aula && incidencia.id_aula.toString() === filters.aula) : true; // Asegúrate de que id_aula esté presente en la incidencia
    const fechaDesdeMatch = filters.fechaDesde ? new Date(incidencia.fecha_creacion) >= new Date(filters.fechaDesde) : true;
    const fechaHastaMatch = filters.fechaHasta ? new Date(incidencia.fecha_creacion) <= new Date(filters.fechaHasta) : true;

    // Mostrar valores de id_tipo y id_aula en la consola
    console.log(`Incidencia ${incidencia.id_incidencia}:`, {
      estadoMatch,
      tipoMatch,
      aulaMatch,
      fechaDesdeMatch,
      fechaHastaMatch
    });

    return estadoMatch && tipoMatch && aulaMatch && fechaDesdeMatch && fechaHastaMatch;
  });

  // Mostrar las incidencias filtradas
  displayIncidencias(filteredIncidencias);
}

// Manejo del cierre de sesión
if (document.getElementById("logoutButton")) {
  document
    .getElementById("logoutButton")
    .addEventListener("click", async () => {
      const response = await fetch(apiUrl + "logout.php", { method: "POST" });
      const result = await response.json();
      document.getElementById("user-info").innerHTML = "";
      alert(result.message || result.error);

      localStorage.removeItem("token");
      localStorage.removeItem("userId");
      localStorage.removeItem("isGoogleUser");
      window.location.href = "index.html"; // Redirigir a index.html
    });
}


// Función para cargar los datos de la incidencia para modificar
async function cargarDatosModificarIncidencia(idIncidencia) {
  try {
    console.log(`Cargando incidencia con ID: ${idIncidencia}`);

    const response = await fetch(apiUrl + `datos_incidencia.php?id=${idIncidencia}`, {
      headers: { Authorization: "Bearer " + localStorage.getItem("token") },
    });

    console.log("Estado de la respuesta:", response.status);

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }

    const data = await response.json();
    console.log("Respuesta de la API:", data);

    if (!data.incidencia) {
      alert("Error: No se encontraron los datos de la incidencia.");
      return;
    }

    // Obtener la incidencia correctamente
    const incidencia = data.incidencia;

    // Asignar valores a los inputs del formulario
    document.getElementById("incidenciaIdMod").value = incidencia.id_incidencia;
    document.getElementById("descripcionMod").value = incidencia.descripcion;
    document.getElementById("estadoMod").value = incidencia.estado;
    document.getElementById("editSolucionMod").value = incidencia.solucion || "";

    // Llenar las opciones de Aula, Tipo y Puesto
    const aulas = data.aulas;
    const tiposInci = data.tipos_incidencias;
    const puestos = data.puestos;
    const usuarios = data.usuarios;

    // Limpiar las opciones anteriores
    const aulaSelect = document.getElementById("aulaMod");
    aulaSelect.innerHTML = "";
    const tipoSelect = document.getElementById("tipoMod");
    tipoSelect.innerHTML = "";
    const puestoSelect = document.getElementById("puestoMod");
    puestoSelect.innerHTML = "";
    const usuarioSelect = document.getElementById("editUserIdMod");
    usuarioSelect.innerHTML = "";

    // Llenar las opciones del select Aula
    aulas.forEach(aula => {
      const option = document.createElement("option");
      option.value = aula.id_aula;
      option.textContent = aula.nombre;
      aulaSelect.appendChild(option);
    });
    document.getElementById("aulaMod").value = incidencia.id_aula;

    // Llenar las opciones del select Tipo de incidencia
    tiposInci.forEach(tipo => {
      const option = document.createElement("option");
      option.value = tipo.id_tipo;
      option.textContent = tipo.tipo;
      tipoSelect.appendChild(option);
    });
    document.getElementById("tipoMod").value = incidencia.id_tipo;

    // Llenar las opciones del select Puesto
    puestos.forEach(puesto => {
      const option = document.createElement("option");
      option.value = puesto.id_puesto;
      option.textContent = puesto.nombre;
      puestoSelect.appendChild(option);
    });
    document.getElementById("puestoMod").value = incidencia.id_puesto;

    // Llenar las opciones del select Usuario (Correo)
    usuarios.forEach(user => {
      const option = document.createElement("option");
      option.value = user.email;
      option.textContent = user.email;
      usuarioSelect.appendChild(option);
    });

    // Seleccionar el usuario actual
    usuarioSelect.value = incidencia.usuario_email; // Establecer el usuario actual que hizo la incidencia

    // Mostrar formulario de edición
    document.getElementById("editarIncidenciaForm").style.display = "block";

  } catch (error) {
    console.error("Error en cargarDatosModificarIncidencia:", error);
    alert("Hubo un problema al intentar cargar los datos de la incidencia. 1");
  }
  loadIncidencias();
}

// Función para cargar los datos de la incidencia para ver más detalles
async function cargarDatosIncidencia(idIncidencia) {
  try {
    const response = await fetch(apiUrl + `datos_incidencia.php?id=${idIncidencia}`, {
      headers: { Authorization: "Bearer " + localStorage.getItem("token") },
    });

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }

    const data = await response.json();

    if (!data.incidencia) {
      alert("Error: No se encontraron los datos de la incidencia.");
      return;
    }

    // Obtener la incidencia correctamente
    const incidencia = data.incidencia;

    // Llenar los detalles de la incidencia
    const detallesContenido = document.getElementById("detallesContenido");
    detallesContenido.innerHTML = `
      <p><strong>Aula:</strong> ${incidencia.nombre_aula}</p>
      <p><strong>Usuario:</strong> ${incidencia.usuario_email}</p>
      <p><strong>Tipo de Incidencia:</strong> ${incidencia.nombre_tipo}</p>
      <p><strong>Puesto:</strong> ${incidencia.nombre_puesto}</p>
      <p><strong>Descripción:</strong> ${incidencia.descripcion}</p>
      <p><strong>Estado:</strong> ${incidencia.estado}</p>
      <p><strong>Fecha de Creación:</strong> ${incidencia.fecha_creacion}</p>
      <p><strong>Fecha de Cierre:</strong> ${incidencia.fecha_cierre || "N/A"}</p>
      <p><strong>Solución:</strong> ${incidencia.solucion || "N/A"}</p>
    `;

    // Mostrar la sección de detalles
    toggleSections("detallesIncidencia");

  } catch (error) {
    console.error("Error en cargarDatosIncidencia:", error);
    alert("Hubo un problema al intentar cargar los datos de la incidencia. 2");
  }
}

// Función para cargar los datos de la incidencia para resolver
async function cargarDatosResolverIncidencia(idIncidencia) {
  try {
      const response = await fetch(apiUrl + `datos_solucion.php?id=${idIncidencia}`, {
          headers: { Authorization: "Bearer " + localStorage.getItem("token") },
      });

      if (!response.ok) {
          throw new Error("Error al obtener los datos de la incidencia");
      }

      const data = await response.json();

      if (!data.incidencia) {
          throw new Error("Incidencia no encontrada");
      }

      // Obtener la incidencia correctamente
      const incidencia = data.incidencia;

      // Llenar los detalles de la incidencia
      const resolverContenido = document.getElementById("resolverContenido");
      resolverContenido.innerHTML = `
          <p><strong>Aula:</strong> ${incidencia.nombre_aula}</p>
          <p><strong>Usuario:</strong> ${incidencia.usuario_email}</p>
          <p><strong>Tipo de Incidencia:</strong> ${incidencia.nombre_tipo}</p>
          <p><strong>Puesto:</strong> ${incidencia.nombre_puesto}</p>
          <p><strong>Descripción:</strong> ${incidencia.descripcion}</p>
          <p><strong>Estado:</strong> ${incidencia.estado}</p>
          <p><strong>Fecha de Creación:</strong> ${incidencia.fecha_creacion}</p>
          <p><strong>Fecha de Cierre:</strong> ${incidencia.fecha_cierre || "N/A"}</p>
      `;

      // Almacenar el ID de la incidencia en un atributo de datos
      resolverContenido.dataset.idIncidencia = idIncidencia;

      // Cargar la solución existente si la hay
      const solucionInput = document.getElementById("solucionInput");
      solucionInput.value = incidencia.solucion || "";

      // Mostrar la sección de resolver
      toggleSections("resolverIncidenciaForm");

  } catch (error) {
      console.error("Error en cargarDatosResolverIncidencia:", error);
      alert("Hubo un problema al intentar cargar los datos de la incidencia.");
      loadIncidencias();
  }
}

async function guardarSolucion() {
  const resolverContenido = document.getElementById("resolverContenido");
  const idIncidencia = resolverContenido.dataset.idIncidencia;
  const solucion = document.getElementById("solucionInput").value;

  if (!solucion) {
      alert("Por favor, escribe la solución antes de guardar.");
      return;
  }

  const requestBody = { solucion, estado: estadoTemporal };
  console.log("Request Body:", requestBody);

  try {
    const response = await fetch(apiUrl + "datos_solucion.php?id=" + idIncidencia, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + localStorage.getItem("token"),
        },
        body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || "Error desconocido");
    }

    const result = await response.json();
    console.log("Response:", result);

    alert(result.message || "Solución guardada correctamente");
    volverAIncidencias();
    loadIncidencias();
    resetFilter();
  } catch (error) {
    console.error("Error al guardar la solución:", error);
    alert("Hubo un problema al intentar guardar la solución: " + error.message);
  }
}


let estadoTemporal = null; // Variable temporal para almacenar el estado seleccionado

// Función para actualizar el estado de una incidencia
async function updateIncidencia(id_incidencia, nuevoEstado) {
  estadoTemporal = nuevoEstado; // Guardar el estado seleccionado en la variable temporal

  if (['Resuelto', 'Cerrado', 'Cancelado'].includes(nuevoEstado)) {
    cargarDatosResolverIncidencia(id_incidencia);
  } else {
    try {
      const response = await fetch(apiUrl + "incidencias.php", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + localStorage.getItem("token"),
        },
        body: JSON.stringify({ id_incidencia, estado: nuevoEstado, fecha_cierre: null }),
      });

      const result = await response.json();
      alert(result.message || result.error);
      loadIncidencias();
      resetFilter();
    } catch (error) {
      console.error("Error al actualizar la incidencia:", error);
      alert("Hubo un problema al intentar actualizar la incidencia.");
    }
  }
}



// Función para volver a la lista de incidencias
function volverAIncidencias() {
  toggleSections("incidencias");
}

// Función para volver a la lista de incidencias
function volverAIncidencias() {
  toggleSections("incidencias");
}

// Función para enviar la solicitud PUT para actualizar una incidencia
async function actualizarIncidencia(event) {
  event.preventDefault();

  const id_incidencia = document.getElementById("incidenciaIdMod").value;
  const descripcion = document.getElementById("descripcionMod").value;
  const estado = document.getElementById("estadoMod").value;
  const solucion = document.getElementById("editSolucionMod").value;
  const id_aula = document.getElementById("aulaMod").value;
  const id_tipo = document.getElementById("tipoMod").value;
  const id_puesto = document.getElementById("puestoMod").value;
  const email = document.getElementById("editUserIdMod").value;

  if (['Resuelto', 'Cerrado', 'Cancelado'].includes(estado) && !solucion) {
    alert("Por favor, proporciona una solución para el estado seleccionado.");
    return;
  }

  try {
    const response = await fetch(apiUrl + "datos_incidencia.php?id=" + id_incidencia, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + localStorage.getItem("token"),
        },
        body: JSON.stringify({ descripcion, estado, solucion, id_aula, id_tipo, id_puesto, email }),
    });

    if (!response.ok) {
      const error = await response.json();
      alert(error.error || "Error al actualizar la incidencia");
      return;
    }

    const result = await response.json();
    alert(result.message || "Incidencia actualizada correctamente");
    cerrarFormulario();
    toggleSections("incidencias");
    loadIncidencias();
  } catch (error) {
    console.error("Error al procesar la respuesta JSON:", error);
    alert("Hubo un problema al procesar la respuesta del servidor.");
  }
}

// Asignar el evento de envío al formulario de modificación de incidencias
document.getElementById("form-modificar-incidencia").addEventListener("submit", actualizarIncidencia);

// Función para cerrar el formulario de edición y volver a la lista
function cerrarFormulario() {
  document.getElementById("editarIncidenciaForm").style.display = "none";
  document.getElementById("incidencias").style.display = "block";
  resetFilter();
}

// Función para cargar los detalles de una incidencia
async function verDetallesIncidencia(idIncidencia) {
  try {
    const response = await fetch(apiUrl + `datos_incidencia.php?id=${idIncidencia}`, {
      headers: { Authorization: "Bearer " + localStorage.getItem("token") },
    });

    console.log("Estado de la respuesta:", response.status);

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }

    const data = await response.json();
    console.log("Respuesta de la API:", data);

    if (!data.incidencia) {
      alert("Error: No se encontraron los datos de la incidencia.");
      return;
    }

    // Obtener la incidencia correctamente
    const incidencia = data.incidencia;

    // Llenar los detalles de la incidencia
    const detallesContenido = document.getElementById("detallesContenido");
    detallesContenido.innerHTML = `
      <p><strong>Aula:</strong> ${incidencia.nombre_aula}</p>
      <p><strong>Usuario:</strong> ${incidencia.usuario_email}</p>
      <p><strong>Tipo de Incidencia:</strong> ${incidencia.nombre_tipo}</p>
      <p><strong>Puesto:</strong> ${incidencia.nombre_puesto}</p>
      <p><strong>Descripción:</strong> ${incidencia.descripcion}</p>
      <p><strong>Estado:</strong> ${incidencia.estado}</p>
      <p><strong>Fecha de Creación:</strong> ${incidencia.fecha_creacion}</p>
      <p><strong>Fecha de Cierre:</strong> ${incidencia.fecha_cierre || "N/A"}</p>
      <p><strong>Solución:</strong> ${incidencia.solucion || "N/A"}</p>
    `;

    // Mostrar la sección de detalles
    toggleSections("detallesIncidencia");

  } catch (error) {
    console.error("Error en verDetallesIncidencia:", error);
    alert("Hubo un problema al intentar cargar los datos de la incidencia. 4");
  }
}

// Función para volver a la sección de incidencias
function volverAIncidencias() {
  toggleSections("incidencias");
}
// Cargar todas las incidencias al cargar la página
document.addEventListener("DOMContentLoaded", function () {
  
  if (localStorage.getItem("isGoogleUser")) {
    loadIncidenciasGoogle();
  } else {
    loadIncidencias();
  }
});



//---------------------------------------------------------------------------

// Cargar las aulas desde la base de datos
async function loadAulas() {
  const token = localStorage.getItem("token"); // Obtener el token del localStorage
  const response = await fetch(apiUrl + "obtener_datos.php", { // Usar el endpoint existente
      method: "GET",
      headers: {
          Authorization: `Bearer ${token}`, // Incluir el token en el encabezado
      },
  });

  if (!response.ok) {
      const error = await response.json();
      alert("Error al cargar las aulas: " + error.error);
      return;
  }

  const data = await response.json();
  const aulasContainer = document.getElementById("aulasContainer");
  aulasContainer.innerHTML = ""; // Limpiar opciones anteriores

  data.aulas.forEach((aula) => {
      const checkbox = document.createElement("div");
      checkbox.className = "form-check"; // Clase de Bootstrap para el estilo

      const input = document.createElement("input");
      input.type = "checkbox";
      input.className = "form-check-input";
      input.id = `aula-${aula.id_aula}`; // ID único para cada checkbox
      input.value = aula.id_aula; // Valor del checkbox
      input.checked = aula.is_selected === 1; // Marcar el checkbox si is_selected es 1

      const label = document.createElement("label");
      label.className = "form-check-label";
      label.htmlFor = `aula-${aula.id_aula}`; // Asociar el label con el checkbox
      label.textContent = aula.nombre; // Nombre del aula

      checkbox.appendChild(input);
      checkbox.appendChild(label);
      aulasContainer.appendChild(checkbox);
  });
}

// Cargar los tipos de incidencia y estados
async function loadFilters() {
  const token = localStorage.getItem("token"); // Obtener el token del localStorage
  const response = await fetch(apiUrl + "obtener_datos.php", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    const error = await response.json();
    alert("Error al cargar los filtros: " + error.error);
    return;
  }

  const data = await response.json();

  // Llenar el filtro de estado
  const filterEstado = document.getElementById("filterEstado");
  filterEstado.innerHTML = `<option value="">Todos</option>`;
  const estados = ["Pendiente", "En proceso", "Resuelto", "Cerrado", "Cancelado"];
  estados.forEach((estado) => {
    const option = document.createElement("option");
    option.value = estado;
    option.textContent = estado;
    filterEstado.appendChild(option);
  });

  // Llenar el filtro de tipos de incidencia
  const filterTipo = document.getElementById("filterTipo");
  filterTipo.innerHTML = `<option value="">Todos</option>`;
  data.tipos_incidencias.forEach((tipo) => {
    const option = document.createElement("option");
    option.value = tipo.id_tipo; // Asegúrate de que este valor coincida con el id_tipo en incidencias
    option.textContent = tipo.tipo;
    filterTipo.appendChild(option);
  });

  // Llenar el filtro de aulas
  const filterAula = document.getElementById("filterAula");
  filterAula.innerHTML = `<option value="">Todas</option>`;
  data.aulas.forEach((aula) => {
    const option = document.createElement("option");
    option.value = aula.id_aula; // Asegúrate de que este valor coincida con el id_aula en incidencias
    option.textContent = aula.nombre;
    filterAula.appendChild(option);
  });
}

// Manejo del botón "Aplicar Filtros"
if (document.getElementById("applyFiltersButton")) {
  document.getElementById("applyFiltersButton").addEventListener("click", function() {
    if (localStorage.getItem("isGoogleUser")) {
      applyFiltersGoogle();
    } else {
      applyFilters();
    }
  });
}

// Manejo del botón "Guardar Cambios"
if (document.getElementById("guardarCambios")) {
  document.getElementById("guardarCambios").addEventListener("click", async () => {
    const selectedAulas = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
      .map(checkbox => checkbox.value); // Obtener los valores de los checkboxes seleccionados

    if (selectedAulas.length === 0) {
      alert("Por favor, seleccione al menos un aula.");
      return;
    }

    const response = await fetch(apiUrl + "guardar_cambios.php", { 
      method: "POST",
      headers: { 
        "Content-Type": "application/json", 
        Authorization: `Bearer ${localStorage.getItem("token")}` 
      },
      body: JSON.stringify({ aulasIds: selectedAulas }), // Enviar los IDs de las aulas seleccionadas
    });

    const result = await response.json();
    alert(result.message || result.error);
  });
}

// Manejo del botón "Volver a Incidencias"
if (document.getElementById("volverIncidencias")) {
  document.getElementById("volverIncidencias").addEventListener("click", () => {
    toggleSections("incidencias"); // Volver a la sección de incidencias
  });
}
// Manejo del botón "Mis Aulas"
if (document.getElementById("asignAul")) {
  document
    .getElementById("asignAul")
    .addEventListener("click", () => {
      toggleSections("asignAulas"); // Mostrar solo la sección de asignAulas
    });
}

// Manejo del botón "Mis Aulas"
if (document.getElementById("info1")) {
  document
    .getElementById("info1")
    .addEventListener("click", () => {
      const idIncidencia = event.target.dataset.id;
      verDetallesIncidencia(idIncidencia);
    });
}

// Cargar las aulas y filtros cuando se muestre la sección de "Asignar Aulas"
if (document.getElementById("asignAulas")) {
  window.addEventListener("load", () => {
    loadAulas(); // Cargar las aulas al mostrar la sección
    loadFilters(); // Cargar los filtros al mostrar la sección
  });
}

// Cargar todas las incidencias al cargar la página
document.addEventListener("DOMContentLoaded", function () {
  const token = localStorage.getItem("token"); // Token JWT guardado en localStorage
  
  if (!token) {
    alert("No hay token de autenticación");
    return;
  }

  fetch(apiUrl + "obtener_datos.php", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        alert("Error: " + data.error);
        return;
      }

      let selectAulas = document.getElementById("aula");
      let selectPuestos = document.getElementById("puesto");
      let selectTipos = document.getElementById("tipoIncidencia");

      selectAulas.innerHTML = `<option value="">Seleccione un aula</option>`;
      selectPuestos.innerHTML = `<option value="">Seleccione un puesto</option>`;
      selectTipos.innerHTML = `<option value="">Seleccione un tipo de incidencia</option>`;

      // Llenar aulas
      data.aulas.forEach((aula) => {
        let option = document.createElement("option");
        option.value = aula.id_aula;
        option.textContent = aula.nombre;
        selectAulas.appendChild(option);
      });

      // Llenar puestos
      data.puestos.forEach((puesto) => {
        let option = document.createElement("option");
        option.value = puesto.id_puesto;
        option.textContent = puesto.nombre;
        selectPuestos.appendChild(option);
      });

      // Llenar tipos de incidencias
      data.tipos_incidencias.forEach((tipo) => {
        let option = document.createElement("option");
        option.value = tipo.id_tipo;
        option.textContent = tipo.tipo;
        selectTipos.appendChild(option);
      });
      
      // Mostrar el email del usuario en un campo no editable
      const userIdField = document.getElementById("userId");
      if (data.email) {
        userIdField.value = data.email; // Asignar el email del usuario
        userIdField.setAttribute("readonly", true); // Hacer el campo no editable
      } else {
        userIdField.value = "Email no encontrado"; // Mensaje de error
      }
    })
    .catch((error) => {
      console.error("Error al obtener los datos:", error);
      alert("Error al cargar los datos");
    });
});