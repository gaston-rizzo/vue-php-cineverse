# CineVerse

CineVerse es una aplicación web para buscar y consultar películas. La información cinematográfica se obtiene desde **The Movie Database (TMDB)** y, desde el detalle de cada película, también es posible consultar su reparto, los perfiles de los actores y sus filmografías.

La aplicación permite además crear cuentas de usuario, guardar películas favoritas en el navegador y publicar y gestionar reseñas.

El proyecto está dividido en un frontend desarrollado con **Vue 3**, **TypeScript** y **Vite**, y un backend desarrollado en **PHP puro**. Los datos propios de CineVerse se almacenan en **MySQL 8**.

## Qué permite hacer CineVerse

- Consultar tendencias, películas populares, en cartelera, próximas y mejor valoradas.
- Buscar, filtrar y ordenar películas.
- Consultar detalles, reparto, director, trailers y títulos relacionados.
- Consultar perfiles de actores y sus filmografías desde el reparto de una película.
- Registrarse, verificar el correo, iniciar y cerrar sesión y recuperar la contraseña.
- Crear, editar y eliminar reseñas propias.
- Consultar reseñas y la actividad del usuario desde el perfil.
- Guardar hasta 20 películas favoritas en el navegador.
- Utilizar la interfaz en español e inglés.

## Tecnologías principales

### Frontend

- Vue 3
- TypeScript
- Vite
- Node.js 20.19+ o 22.12+
- Vue Router
- Pinia
- TanStack Vue Query
- Axios
- vue-i18n

### Backend

- PHP 8.1+
- PDO
- Composer
- PHPMailer
- Monolog
- phpdotenv

### Datos y servicios

- MySQL 8
- TMDB API v3

El backend está desarrollado en PHP puro y no utiliza Laravel ni otro framework PHP.

## Estructura del repositorio

```text
cineverse/
├── README-ES.md
├── README-EN.md
├── database/
├── docs/
├── src/
└── video-sitio.mp4
```

- `database/`: contiene el archivo SQL con la estructura y los datos de prueba.
- `docs/`: contiene la documentación técnica y archivos Markdown (`.md`) con información complementaria y resultados de prueba.
- `src/`: contiene el frontend Vue y el backend PHP.
- `video-sitio.mp4`: muestra el sitio web en funcionamiento.

## Puesta en marcha

### Base de datos

El archivo:

```text
database/cineverse.sql
```

está preparado para crear la base `cineverse`, construir las tablas utilizadas por el backend y cargar los datos de prueba.

El script está preparado para reconstruir un entorno de prueba desde cero, por lo que no debe ejecutarse sobre una base cuyos datos se quieran conservar.

El conjunto incluido contiene **5.000 usuarios** y **24.322 reseñas**.

### Backend

Desde `src/backend/`:

```bash
composer install
```

Creá el archivo `.env` a partir de `.env.example` y configurá la conexión a MySQL y, si querés probar los flujos de correo, los datos SMTP.

En el entorno local documentado, Apache sirve el backend mediante:

```text
http://cineverse.local
```

Para utilizar ese dominio en Windows, agregá al archivo `hosts`:

```text
127.0.0.1 cineverse.local
```

El VirtualHost de Apache debe apuntar a:

```text
cineverse/src/backend/public
```

Apache debe tener habilitado `mod_rewrite` y permitir `AllowOverride All`.

### Frontend

Desde `src/frontend/`:

```bash
npm ci
```

Creá el archivo `.env` a partir de `.env.example` y configurá:

```env
VITE_TMDB_BASE_URL="https://api.themoviedb.org/3"
VITE_TMDB_TOKEN="<API Read Access Token>"
VITE_BACKEND_URL="/api"
```

Después iniciá Vite:

```bash
npm run dev
```

Durante el desarrollo, las solicitudes a `/api` son reenviadas por el proxy de Vite hacia el backend PHP servido por Apache.

## Arquitectura

```text
Vue 3 + TypeScript
   |--------------------------> TMDB API
   |
   | /api
   v
Backend PHP
   |
   v
PDO
   |
   v
MySQL 8
```

El frontend consulta TMDB directamente para obtener información sobre películas y actores. El backend PHP gestiona los datos propios de CineVerse, como cuentas de usuario y reseñas.

## Documentación

La documentación técnica y los archivos complementarios se encuentran en:

```text
docs/
```

Allí se desarrolla en mayor detalle la arquitectura, TMDB, Vue, TypeScript, PHP, MySQL, autenticación, reseñas, seguridad, instalación, publicación y pruebas del proyecto.
