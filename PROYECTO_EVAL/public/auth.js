const apiUrl = "/api/"; // Ruta base para los endpoints de la API

let clientId;
fetch(apiUrl + "get_oauth_id.php") // Cargar clientId desde el backend
  .then((response) => response.json())
  .then((data) => {
    clientId = data.clientId;
    if (clientId) {
      console.log("Client ID cargado exitosamente:", clientId);
      if (document.getElementById("google-signin")) {
        initializeGoogleLogin(); // Solo si el botón de Google existe
      }
    } else {
      console.error("Error: OAUTH_ID no está definido.");
    }
  })
  .catch((error) => console.error("Error obteniendo clientId:", error));

// Manejo del registro del usuario
if (document.getElementById("registerForm")) {
  document
    .getElementById("registerForm")
    .addEventListener("submit", async (event) => {
      event.preventDefault();
      const nombre = document.getElementById("nombre").value;
      const apellidos = document.getElementById("apellidos").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;

      const emailPattern = /^[a-zA-Z0-9._%+-]+@educa\.madrid\.org$/;
      if (!emailPattern.test(email)) {
        alert("❌ Solo se permiten correos que terminen en @educa.madrid.org");
        return;
      }

      const response = await fetch(apiUrl + "register.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ nombre, apellidos, email, password }),
      });

      const result = await response.json();
      alert(result.message || result.error);
      if (result.message) registerForm.reset();
    });
}

// Manejo del login del usuario
if (document.getElementById("loginForm")) {
  document
    .getElementById("loginForm")
    .addEventListener("submit", async (event) => {
      event.preventDefault();
      const email = document.getElementById("loginEmail").value;
      const password = document.getElementById("loginPassword").value;

      const response = await fetch(apiUrl + "login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const result = await response.json();
      console.log(result); // Agrega esta línea para depurar
      if (result.token) {
        localStorage.setItem("token", result.token);
        localStorage.setItem("userId", result.userId);
        // Redirigir a incidencias.html
        window.location.href = "incidencias.html"; 
      }

      alert(result.message || result.error);
      loginForm.reset();
    });
}

// Inicializar login de Google
function initializeGoogleLogin() {
  if (!clientId) {
    console.error("Error: OAUTH_ID no está disponible.");
    return;
  }

  google.accounts.id.initialize({
    client_id: clientId,
    callback: handleCredentialResponse,
  });

  if (document.getElementById("google-signin")) {
    google.accounts.id.renderButton(document.getElementById("google-signin"), {
      theme: "outline",
      size: "large",
    });
  }

  if (!localStorage.getItem("token")) {
    google.accounts.id.prompt();
  }
}

function handleCredentialResponse(response) {
  const jwt = response.credential;
  fetch(apiUrl + "login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ token: jwt }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log(data); // Agrega esta línea para depurar
      if (data.token) {
        let isGoogleUser = true;
        localStorage.setItem("token", data.token);
        localStorage.setItem("userId", data.userId);
        localStorage.setItem("isGoogleUser", isGoogleUser);
        alert("Inicio de sesión exitoso");
        window.location.href = "incidencias.html"; // Redirigir a incidencias.html
      } else {
        alert("Error en autenticación con Google");
      }
    })
    .catch((error) => {
      console.error("Error al autenticar con Google:", error);
      alert("Hubo un error al intentar autenticarte.");
    });
}

// Borrar el userId del localStorage al cargar la página
document.addEventListener("DOMContentLoaded", function () {
  localStorage.removeItem("userId");
  localStorage.removeItem("isGoogleUser");
  // Obtener el campo userId y limpiarlo
  const userIdField = document.getElementById("userId");
  if (userIdField) {
    userIdField.value = ""; // Limpiar el campo
    userIdField.setAttribute("readonly", true); // Hacerlo no editable
  }
});