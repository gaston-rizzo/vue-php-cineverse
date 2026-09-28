# Integración con APIs

El frontend de CineVerse se comunica con dos APIs distintas según el tipo de dato que necesita.

Las películas, personas, géneros y demás información cinematográfica se consultan directamente desde **TMDB**. Las cuentas, sesiones, perfiles y reseñas utilizan la **API propia de CineVerse**, desarrollada en PHP.

## Visión general

```text
Frontend Vue
│
├── tmdbApi.ts
│   └── TMDB
│       ├── películas
│       ├── personas
│       ├── géneros
│       ├── créditos
│       └── contenido relacionado
│
└── backendApi.ts
    └── Backend PHP de CineVerse
        ├── cuentas
        ├── autenticación
        ├── sesión
        ├── perfil
        └── reseñas
            ↓
          MySQL
```

Las consultas a TMDB no pasan por el backend PHP. El backend propio se utiliza para cuentas, autenticación, sesiones, perfil y reseñas.

## Clientes HTTP

El frontend utiliza dos instancias de Axios configuradas por separado.

### `tmdbApi.ts`

Ubicación:

```text
src/core/api/tmdbApi.ts
```

Este cliente utiliza:

```text
VITE_TMDB_BASE_URL
VITE_TMDB_TOKEN
        ↓
Axios
        ↓
Authorization: Bearer <token>
        ↓
TMDB
```

`tmdbApi.ts` centraliza la URL base de TMDB y la cabecera `Authorization`. Los services que consultan contenido cinematográfico reutilizan esta instancia.

### `backendApi.ts`

Ubicación:

```text
src/core/api/backendApi.ts
```

Este cliente utiliza:

```text
VITE_BACKEND_URL
        ↓
Axios
withCredentials: true
        ↓
Backend PHP
```

`withCredentials: true` permite que el navegador envíe la cookie de sesión PHP cuando corresponde.

El token CSRF no se agrega de forma global: las operaciones protegidas lo incluyen explícitamente mediante la cabecera:

```text
X-CSRF-Token
```

## Services y destino

| Service | Cliente | Destino | Responsabilidad |
|---|---|---|---|
| `movies.service.ts` | `tmdbApi` | TMDB | Películas, búsqueda, filtros, géneros y detalle |
| `person.service.ts` | `tmdbApi` | TMDB | Personas y filmografía |
| `auth.service.ts` | `backendApi` | Backend PHP | Cuenta, autenticación, sesión y recuperación |
| `reviews.service.ts` | `backendApi` | Backend PHP | Crear, editar, eliminar y consultar reseñas |
| `profile.service.ts` | `backendApi` | Backend PHP | Resumen del perfil |

Los services reúnen operaciones relacionadas con cada fuente de datos y reutilizan los clientes Axios para no repetir configuración.

## Comunicación con TMDB

Cuando una vista necesita contenido cinematográfico, el recorrido habitual es:

```text
View / componente
        ↓
Composable
        ↓
movies.service.ts / person.service.ts
        ↓
tmdbApi.ts
        ↓
Axios
        ↓
TMDB
        ↓
JSON
        ↓
Vue Query / composable
        ↓
Interfaz
```

En este camino no intervienen PHP ni MySQL.

### Ejemplo: consultar una persona

```text
PersonDetailView
    ↓
usePersonDetail
    ↓
person.service.ts
    ↓
tmdbApi.ts
    ↓
Axios
    ↓
TMDB
```

## Comunicación con el backend de CineVerse

Las funciones de cuenta, autenticación, perfil y reseñas utilizan `backendApi.ts`.

### Ejemplo: iniciar sesión

```text
LoginView.vue
    ↓
auth.service.ts
    ↓
backendApi.ts
    ↓
POST /login
    ↓
Backend PHP
    ↓
MySQL
    ↓
LOGIN_SUCCESS
    ↓
user + csrf_token
    ↓
useAuthStore / Pinia
```

El navegador administra la cookie de sesión. El frontend conserva además el token CSRF recibido para utilizarlo en operaciones protegidas.

### Ejemplo: crear una reseña

```text
MovieReviews.vue
    ↓
reviews.service.ts
    ↓
backendApi.ts
    ↓
Cookie de sesión
    +
X-CSRF-Token
    ↓
POST /reviews
    ↓
Backend PHP
    ↓
MySQL
```

Las operaciones de creación, edición y eliminación de reseñas requieren sesión y CSRF.

## Proxy de desarrollo

Durante el desarrollo, el frontend utiliza:

```env
VITE_BACKEND_URL="/api"
```

Por eso Axios construye URLs como:

```text
/api/login
/api/user
/api/reviews
```

El servidor de desarrollo de Vite intercepta `/api`, reenvía la solicitud a:

```text
http://cineverse.local
```

y elimina el prefijo antes de entregarla a Apache.

Ejemplo:

```text
/api/login
    ↓
Vite
    ↓
http://cineverse.local/login
```

Por lo tanto:

```text
/api/login   ← ruta utilizada por el frontend en desarrollo
/login       ← ruta real registrada por el backend PHP
```

## Bearer, cookie y CSRF

CineVerse utiliza mecanismos distintos según la API.

| Elemento | Destino | Función |
|---|---|---|
| `Authorization: Bearer <token>` | TMDB | Identificar la aplicación frente a TMDB |
| Cookie de sesión PHP | Backend CineVerse | Relacionar el navegador con la sesión del usuario |
| `X-CSRF-Token` | Backend CineVerse | Proteger operaciones autenticadas que modifican estado |

Estos mecanismos no son intercambiables.

Por ejemplo:

```text
GET /profile
→ requiere sesión
→ no requiere CSRF

POST /reviews
→ requiere sesión
→ requiere X-CSRF-Token

GET /movies/{movieId}/reviews
→ es público
→ no requiere sesión ni CSRF
```

## Manejo de respuestas y errores

Los services normalmente devuelven `response.data` y no el objeto completo de Axios.

Cuando el backend responde con un estado HTTP de error, Axios rechaza la Promise y el frontend puede recuperar el código interno desde:

```text
error.response.data.code
```

Ese código se utiliza después para decidir qué mensaje o estado mostrar en la interfaz.

Los clientes Axios actuales no tienen un interceptor global de errores ni un timeout global. El manejo se realiza en los services, composables o vistas que ejecutan cada operación.

## Referencia de endpoints

La lista completa de endpoints del backend, junto con sus métodos HTTP y requisitos de sesión y CSRF, se encuentra en:

[`../../backend/API.md`](../../backend/API.md)

La documentación técnica completa de CineVerse desarrolla en mayor profundidad la comunicación entre Vue, Axios, TMDB, el backend PHP y MySQL.
