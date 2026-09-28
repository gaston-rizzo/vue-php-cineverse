/* ============================================================================
 * PUNTO DE ENTRADA (main.ts)
 * ============================================================================
 *
 * Este archivo es el punto de entrada de la aplicación.
 *
 * Se encarga de:
 * - Crear la instancia raíz de Vue
 * - Registrar plugins globales (Router, Pinia, Vue Query, i18n, etc.)
 * - Reconstruir el estado de autenticación al iniciar la aplicación
 * - Montar la aplicación en el DOM
 *
 * Acá se inicializa la arquitectura base de la SPA,
 * conectando navegación, estado global y manejo de datos.
 * ============================================================================ */
 
// Importamos la función principal para crear la app Vue
import { createApp } from "vue";
// Componente raíz de la aplicación
import App from "./App.vue";
// Importamos el router configurado (rutas y navegación)
import router from "./core/router";
// Importamos Pinia para manejo de estado global
import { createPinia } from "pinia";
// Plugin para persistir el estado (ej: favoritos) en localStorage
import piniaPluginPersistedState from "pinia-plugin-persistedstate";
// Plugin de Vue Query para manejo de fetch, cache y estado asíncrono
import { VueQueryPlugin } from "@tanstack/vue-query";
// Importamos tailwind
import "./assets/main.css";
// Importamos la librería de internacionalización (vue-i18n)
import i18n from "./core/i18n";
import { useAuthStore } from "./features/auth/stores/useAuthStore.ts";

// Creamos la instancia de Pinia para gestionar los datos compartidos entre los distintos componentes de la aplicación.
const pinia = createPinia();

// Aplicamos el plugin de persistencia al store
// Permite mantener datos aunque el usuario recargue la página
pinia.use(piniaPluginPersistedState);

// Creamos la instancia principal de la aplicación Vue a partir del
// componente raíz (App.vue). Sobre esta instancia se registran los
// plugins globales y, finalmente, se monta la aplicación en el DOM.
const app = createApp(App);

// Registramos Pinia en la aplicación para habilitar el acceso
// al estado compartido desde cualquier componente o store.
app.use(pinia);

// Obtenemos la instancia del store de autenticación.
const authStore = useAuthStore();

// Se dispara pero no se espera: la reconstrucción de sesión corre en
// paralelo al montaje de la app. Home (que no requiere auth) puede
// mostrarse sin depender de esta request. El router guard es quien
// espera esta misma promesa solo para las rutas protegidas.
authStore.initialize();

app
  // Registramos el router para habilitar navegación SPA
  .use(router)
  // Registramos Vue Query para manejo avanzado de requests y cache
  .use(VueQueryPlugin)
  // Registramos el plugin vue-i18n para habilitar traducciones globales
  .use(i18n)
  // Montamos la aplicación en el div con id="app" del index.html
  .mount("#app");

/**
 * Avisa al boot screen (index.html) que Vue ya montó y pintó su primer
 * frame. El doble rAF asegura que el navegador ya compuso ese frame en
 * pantalla antes de disparar el evento (un solo rAF puede dispararse
 * antes del paint real).
 */
function notifyAppReady() {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      document.dispatchEvent(new Event("app:ready"));
    });
  });
}

notifyAppReady();