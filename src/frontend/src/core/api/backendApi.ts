/* ============================================================================
 * API: backendApi.ts
 * ============================================================================
 *
 * Instancia compartida de Axios utilizada para comunicarse con la API
 * del backend.
 *
 * Configura la URL base, las cabeceras por defecto y el envío de cookies
 * de sesión en todas las solicitudes HTTP.
 * ============================================================================ */

import axios from "axios";

/**
 * Instancia de Axios configurada para realizar requests al backend.
 *
 * Incluye la URL base definida mediante variables de entorno, el tipo
 * de contenido JSON y el envío automático de credenciales (cookies)
 * para mantener la sesión del usuario.
 */
const backendApi = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL,
  headers: {
    "Content-Type": "application/json"
  },
  withCredentials: true
});

// Exportamos la instancia para usarla en otros módulos
export default backendApi;
