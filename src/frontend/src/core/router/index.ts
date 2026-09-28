/* ============================================================================
 * ROUTER: Configuración de rutas
 * ============================================================================
 *
 * Configuración del router de la aplicación.
 *
 * Declara las rutas, configura Vue Router en modo history, conecta URLs con vistas, 
 * sincroniza el idioma de la URL con vue-i18n, define el comportamiento global del
 * scroll y gestiona las redirecciones y las rutas 404.
 * ============================================================================ */
 
// Importamos las funciones necesarias de Vue Router
import { createRouter, createWebHistory } from "vue-router";

import { useAuthStore } from "@/features/auth/stores/useAuthStore";

// Importamos las vistas (pages) principales de la aplicación
import HomeView from "@/features/home/views/HomeView.vue";
import RegisterView from "@/features/auth/views/RegisterView.vue";
import VerifyEmailView from "@/features/auth/views/VerifyEmailView.vue";
import LoginView from "@/features/auth/views/LoginView.vue";
import ForgotPasswordView from "@/features/auth/views/ForgotPasswordView.vue";
import ResetPasswordView from "@/features/auth/views/ResetPasswordView.vue";
import MoviesView from "@/features/movies/views/MoviesView.vue";
import MovieDetailView from "@/features/movies/views/MovieDetailView.vue";
import PersonDetailView from "@/features/person/views/PersonDetailView.vue";
import ProfileView from "@/features/profile/views/ProfileView.vue";
import MyReviewsView from "@/features/profile/views/MyReviewsView.vue";
import MoviesFavoritesView from "@/features/movies/views/MoviesFavoritesView.vue";
import NotFoundView from "@/views/NotFoundView.vue";

// Importamos la instancia de i18n para sincronizar idioma con la ruta
import i18n from "../i18n";

const router = createRouter({
  /**
   * Usamos history mode (sin hash #) para tener URLs limpias
   * como /en/movies en lugar de /#/en/movies.
   *
   * import.meta.env.BASE_URL define la raíz donde está montada la aplicación.
   *
   * Ejemplo en desarrollo:
   * - http://localhost:5173/
   * - BASE_URL = "/"
   * - Resultado: /en/movies funciona correctamente
   *
   * Ejemplo en producción (subcarpeta):
   * - https://dominio.com/cineverse/
   * - BASE_URL = "/cineverse/"
   * - Resultado: rutas como /cineverse/en/movies
   *
   * Sin esto, las rutas se romperían al cambiar el path base del deploy.
   */
  history: createWebHistory(import.meta.env.BASE_URL),
  /**
   * Controla la posición del scroll en cada navegación de Vue Router.
   *
   * Cada vez que cambia la ruta (ej: cambiar de película,
   * cambiar idioma, ir a otra vista, etc),
   * esta función se ejecuta automáticamente.
   *
   * return { top: 0 }:
   * - Hace scroll al inicio de la página (arriba de todo)
   * - Equivale a: window.scrollTo(0, 0)
   *
   * Problema que soluciona:
   * - Vue Router mantiene el scroll anterior por defecto
   * - Al entrar a una nueva vista, podés quedar en el medio de la página
   *
   * Resultado:
   * - Cada nueva vista empieza desde el top
   * - UX consistente y profesional
   */
  scrollBehavior() {    
    return { top: 0 };
  },
  // Rutas de la aplicación
  routes: [
    // Detecta idioma del navegador, si es español
    // traduce el sitio a español, sino a ingles
    // Solo se ejecuta cuando se ingresa a "/"
    // No afecta navegación interna
    {
      path: "/",
      redirect: () => {
        // Detecta idioma del navegador (ej: es-AR, en-US)
        const browserLang = navigator.language.toLowerCase();

        // Si empieza con "es" → va a español
        if (browserLang.startsWith("es")) {
          return "/es";
        }

        // En cualquier otro caso → inglés
        return "/en";
      },
    },
    // Ruta para Home por idioma
    // Valida que :lang sea solo 'en' o 'es'
    // Evita rutas inválidas como: /asdf/movies
    {
      path: "/:lang(en|es)",
      name: "Home",
      component: HomeView,
    },
    // Ruta para la pagina de registro
    {
      path: "/:lang(en|es)/register",
      name: "Register",
      component: RegisterView,
      meta: { requiresGuest: true }
    },
    // Ruta utilizada por el enlace enviado por email para verificar la cuenta.
    {
      path: "/:lang(en|es)/verify-email",
      name: "VerifyEmail",
      component: VerifyEmailView
    },
    // Ruta para la página de inicio de sesión
    {
      path: "/:lang(en|es)/login",
      name: "Login",
      component: LoginView,      
      meta: { requiresGuest: true }
    },
    // Ruta para solicitar la recuperación de contraseña.
    {
      path: "/:lang(en|es)/forgot-password",
      name: "ForgotPassword",
      component: ForgotPasswordView,
      meta: { requiresGuest: true }
    },
    // Ruta para restablecer la contraseña mediante el enlace recibido por correo electrónico.
    {
      path: "/:lang(es|en)/reset-password",
      name: "reset-password",
      component: ResetPasswordView
    },
    // Ruta para el listado de películas
    {
      path: "/:lang(en|es)/movies",
      name: "Movies",
      component: MoviesView,
    },
    // Ruta para ver el detalle de una película
    // (\\d+): Sólo acepta números
    {
      path: "/:lang(en|es)/movies/:id(\\d+)",
      name: "MovieDetail",
      component: MovieDetailView
    },            
    // Ruta para ver el detalle de una persona
    // (\\d+): Sólo acepta números
    {
      path: "/:lang(en|es)/person/:id(\\d+)",
      name: "Person",
      component: PersonDetailView
    },    
    // Ruta para ver los favoritos
    {
      path: "/:lang(en|es)/favorites",
      name: "Favorites",
      component: MoviesFavoritesView,
    },
    // Ruta para ver el perfil del usuario
    {
      path: "/:lang(en|es)/profile",
      name: "Profile",
      component: ProfileView,
      meta: { requiresAuth: true }
    },
    // Ruta para ver las reviews de un usuario
    {
      path: "/:lang(en|es)/profile/reviews",
      name: "MyReviews",
      component: MyReviewsView,
      meta: { requiresAuth: true }
    },
    // ==========================================
    // 404 - Ruta no encontrada con idioma
    // ==========================================
    // Esta ruta captura cualquier URL que:
    // - Empiece con /en o /es
    // - Pero no coincida con ninguna ruta existente
    //
    // Ejemplos:
    // /es/person/aaa
    // /en/movies/abc
    // /es/loquesea
    //
    // :lang(en|es) → mantiene el idioma en la URL
    // :pathMatch(.*)* → captura cualquier resto de la ruta
    //
    // Cuando matchea:
    // Vue Router renderiza la vista NotFoundView
    // dentro de <router-view>
    //
    // IMPORTANTE:
    // Debe ir después de todas las rutas válidas
    {
      path: "/:lang(en|es)/:pathMatch(.*)*",
      name: "NotFound",
      component: NotFoundView,
    },
    // ==========================================
    // 404 - Ruta no encontrada sin idioma
    // ==========================================
    // Esta ruta captura cualquier URL que:
    // - No tenga /en o /es al inicio
    // - No coincida con ninguna ruta existente
    //
    // Ejemplos:
    // /asdf
    // /123
    // /movies
    // /person/999
    //
    // :pathMatch(.*)* → catch-all global
    //
    // Usa el idioma actual guardado en i18n
    // (último idioma seleccionado por el usuario)
    //
    // Cuando matchea:
    // Vue Router renderiza la vista NotFoundView
    // dentro de <router-view>
    //
    // IMPORTANTE:
    // Esta ruta debe ir siempre última
    // porque captura absolutamente todo
    {
      path: "/:pathMatch(.*)*",
      name: "NotFoundNoLang",
      component: NotFoundView,
    }
  ],
});

// ==========================================
// Sincronización automática Router ↔ i18n
// ==========================================

// beforeEach: Es una función que se ejecuta antes de cada cambio de ruta.

// Cada vez que el usuario:
//    Hace click en un <router-link>
//    Es redirigido
//    Escribe una URL nueva
//    Cambia de /en a /es
// beforeEach se ejecuta antes de que Vue cargue la nueva vista.

// Cada vez que cambia la URL:
// Lee :lang
// Si es "en" o "es"
// Cambia el idioma de vue-i18n

// Si vas a:
//     /en/movies
//     Idioma → inglés

// Si vas a:
//   /es/movies
//   Idioma → español

// ==========================================
// Auth: espera diferida (solo rutas protegidas)
// ==========================================

// authStore.initialize() se dispara en main.ts sin esperarse (no bloquea
// el mount de la app), para que rutas públicas como Home naveguen de
// inmediato sin depender del backend de auth.
//
// Acá, en el guard, es donde si se espera esa misma promesa (isInitialized),
// pero solo si la ruta destino requiere autenticación. Si initialize() ya
// terminó en segundo plano mientras el usuario navegaba, no hay espera real.

// Guard global que se ejecuta antes de cada navegación
// un guard global es una función que se ejecuta antes
//  o después de una navegación.
router.beforeEach(async (to) => {

  const lang = to.params.lang as string;

  if (lang === "en" || lang === "es") {
    i18n.global.locale.value = lang;
  }

  // Tanto las rutas protegidas (requiresAuth) como las exclusivas para
  // invitados (requiresGuest) necesitan saber con certeza si hay una 
  // sesión activa antes de decidir si dejan pasar la navegación. Por eso 
  // ambas comparten la misma espera sobre authStore.isInitialized.
  //
  // Rutas públicas (Home, Movies, etc.) no tienen ninguno de los dos
  // meta, así que no entran acá y navegan de inmediato sin esperar nada.
  if (to.meta.requiresAuth || to.meta.requiresGuest) {

    const authStore = useAuthStore();

    // Si initialize() todavía no terminó (puede seguir en curso desde
    // main.ts, o no haberse disparado nunca), esperamos acá a que la
    // sesión termine de reconstruirse antes de seguir. initialize()
    // reutiliza la misma promesa en curso, así que esto no dispara un
    // segundo fetch al backend aunque main.ts ya lo haya llamado antes.
    if (!authStore.isInitialized) {
      await authStore.initialize();
    }

    // Caso 1: ruta protegida (ej: Profile) sin sesión activa.
    // No dejamos entrar: redirige a Login manteniendo el idioma actual.
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return {
        name: "Login",
        params: { lang: lang || i18n.global.locale.value }
      };
    }

    // Caso 2: ruta exclusiva para invitados (ej: Login, Register,
    // ForgotPassword) pero el usuario ya tiene una sesión activa.
    // No tiene sentido mostrarle el formulario de login/registro estando
    // ya logueado, así que lo mandamos directo a Home.
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
      return {
        name: "Home",
        params: { lang: lang || i18n.global.locale.value }
      };
    }
  }

  return true;
    
});

// Exportamos el router para usarlo en main.ts
export default router;