# CineVerse — Frontend

El frontend de CineVerse es una SPA desarrollada con Vue 3 y TypeScript. Se encarga de la interfaz, la navegación, la búsqueda y visualización de contenido cinematográfico, de agregar películas a favoritos y de interactuar con las funciones de cuenta, perfil y reseñas.

La información de películas y personas se consulta directamente desde **TMDB**. Las cuentas, sesiones, perfiles y reseñas se comunican con el backend PHP de CineVerse.

## Tecnologías principales

- Vue 3.
- TypeScript 5.9.
- Vite 7.
- Vue Router.
- Pinia.
- `pinia-plugin-persistedstate`.
- TanStack Vue Query.
- Axios.
- `vue-i18n`.
- Swiper.
- CSS propio, con Tailwind CSS 4 integrado mediante Vite.

## Estructura

```text
frontend/
├── docs/
│   ├── integracion-apis.md
├── public/
├── src/
│   ├── assets/
│   ├── core/
│   │   ├── api/
│   │   ├── composables/
│   │   ├── i18n/
│   │   └── router/
│   ├── features/
│   │   ├── auth/
│   │   ├── home/
│   │   ├── movies/
│   │   ├── person/
│   │   └── profile/
│   ├── layouts/
│   ├── shared/
│   ├── views/
│   ├── App.vue
│   └── main.ts
├── .env.example
├── package.json
├── tsconfig.json
└── vite.config.ts
```

Las carpetas principales se organizan de la siguiente manera:

- **core**: configuración compartida de APIs, router, internacionalización y composables generales.
- **features**: funcionalidades agrupadas por dominio.
- **layouts**: componentes persistentes de la interfaz.
- **shared**: componentes, tipos y composables reutilizables.
- **views**: vistas generales que no pertenecen a una feature concreta.

## Requisitos

Para ejecutar el frontend se necesita:

- Node.js ^20.19.0 o >=22.12.0.
- npm.
- un token Bearer de TMDB;
- el backend PHP de CineVerse disponible si se desean utilizar autenticación, perfil y reseñas.

## Configuración del entorno

Crear `.env` a partir de `.env.example`:

```bash
cp .env.example .env
```

Las variables principales son:

```env
VITE_TMDB_BASE_URL="https://api.themoviedb.org/3"
VITE_TMDB_TOKEN="token"
VITE_BACKEND_URL="/api"
```

- `VITE_TMDB_BASE_URL`: URL base de la API v3 de TMDB.
- `VITE_TMDB_TOKEN`: token Bearer utilizado para consultar TMDB.
- `VITE_BACKEND_URL`: URL base utilizada por el cliente Axios del backend.

## Instalación

Desde la carpeta `frontend`, instalar las dependencias:

```bash
npm install
```

## Ejecución

Iniciar el servidor de desarrollo:

```bash
npm run dev
```

En el entorno local, Vite sirve normalmente la aplicación en:

```text
http://localhost:5173
```

Generar el frontend para producción:

```bash
npm run build
```

Previsualizar localmente el build generado:

```bash
npm run preview
```

## Comunicación con APIs

CineVerse separa las consultas cinematográficas de los datos propios de la aplicación:

```text
Frontend Vue
├── TMDB API
│   └── películas, personas, géneros, imágenes y contenido relacionado
│
└── Backend PHP
    └── registro, login, sesión, perfil y reseñas
```

Los clientes principales se encuentran en:

```text
src/core/api/tmdbApi.ts
src/core/api/backendApi.ts
```

El backend PHP no funciona como proxy de TMDB.

## Navegación e idiomas

Vue Router administra la navegación de la SPA mediante URLs localizadas.

Algunos ejemplos son:

```text
/es
/en
/es/movies
/en/movies
/es/movies/{id}
/en/person/{id}
/es/favorites
/es/profile
```

CineVerse está preparado para español e inglés mediante `vue-i18n`. El segmento `en` o `es` de la URL se sincroniza con el idioma activo.

Las rutas de perfil y del historial de reseñas requieren una sesión autenticada. Login, registro y solicitud de recuperación de contraseña son exclusivas para usuarios sin sesión activa.

## Estado y datos

El frontend reparte las responsabilidades entre distintas herramientas:

- **Pinia** mantiene estado compartido, como autenticación y favoritos.
- **pinia-plugin-persistedstate** conserva los favoritos en el navegador.
- **Vue Query** gestiona consultas remotas, caché y estados de carga.
- **Axios** realiza las solicitudes HTTP hacia TMDB y hacia el backend.

Al iniciar la aplicación, el store de autenticación intenta reconstruir la sesión mediante el backend. Las rutas protegidas y las exclusivas para invitados esperan esa comprobación antes de decidir la navegación.

## Comunicación con el backend

En desarrollo, el frontend utiliza:

```env
VITE_BACKEND_URL="/api"
```

Vite reenvía las solicitudes bajo `/api` a:

```text
http://cineverse.local
```

y elimina el prefijo antes de entregarlas a Apache.

Por ejemplo:

```text
/api/login
    ↓
http://cineverse.local/login
```

La referencia de endpoints del backend se encuentra en:

[`../backend/API.md`](../backend/API.md)

## Documentación

La documentación técnica completa de CineVerse desarrolla en profundidad la arquitectura del frontend, navegación, estado, internacionalización, integración con TMDB, comunicación con el backend y funcionamiento de las distintas vistas.
