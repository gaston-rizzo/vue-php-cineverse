# CineVerse

CineVerse is a web application for searching and viewing movies. Film information is obtained from **The Movie Database (TMDB)** and, from each movie's detail page, users can also view the cast, actor profiles, and filmographies.

The application also allows users to create accounts, save favorite movies in the browser, and publish and manage reviews.

The project is divided into a frontend developed with **Vue 3**, **TypeScript**, and **Vite**, and a backend developed in **plain PHP**. CineVerse's own data is stored in **MySQL 8**.

## What CineVerse allows you to do

- Browse trending, popular, now playing, upcoming, and top-rated movies.
- Search, filter, and sort movies.
- View movie details, cast, director, trailers, and related titles.
- View actor profiles and filmographies from a movie's cast.
- Register, verify email, log in and out, and recover passwords.
- Create, edit, and delete your own reviews.
- View reviews and user activity from the profile.
- Save up to 20 favorite movies in the browser.
- Use the interface in Spanish and English.

## Main technologies

### Frontend

- Vue 3
- TypeScript
- Vite
- Node.js 20.19+ or 22.12+
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

### Data and services

- MySQL 8
- TMDB API v3

The backend is developed in plain PHP and does not use Laravel or any other PHP framework.

## Repository structure

```text
cineverse/
├── README-ES.md
├── README-EN.md
├── database/
├── docs/
├── src/
└── video-sitio.mp4
```

- `database/`: contains the SQL file with the database structure and test data.
- `docs/`: contains the technical documentation and Markdown (`.md`) files with complementary information and test results.
- `src/`: contains the Vue frontend and the PHP backend.
- `video-sitio.mp4`: shows the website in operation.

## Getting started

### Database

The file:

```text
database/cineverse.sql
```

is prepared to create the `cineverse` database, build the tables used by the backend, and load the test data.

The script is designed to rebuild a test environment from scratch, so it should not be run on a database containing data that must be preserved.

The included dataset contains **5,000 users** and **24,322 reviews**.

### Backend

From `src/backend/`:

```bash
composer install
```

Create the `.env` file from `.env.example` and configure the MySQL connection and, if you want to test email flows, the SMTP settings.

In the documented local environment, Apache serves the backend through:

```text
http://cineverse.local
```

To use that domain on Windows, add the following entry to the `hosts` file:

```text
127.0.0.1 cineverse.local
```

The Apache VirtualHost must point to:

```text
cineverse/src/backend/public
```

Apache must have `mod_rewrite` enabled and allow `AllowOverride All`.

### Frontend

From `src/frontend/`:

```bash
npm ci
```

Create the `.env` file from `.env.example` and configure:

```env
VITE_TMDB_BASE_URL="https://api.themoviedb.org/3"
VITE_TMDB_TOKEN="<API Read Access Token>"
VITE_BACKEND_URL="/api"
```

Then start Vite:

```bash
npm run dev
```

During development, requests to `/api` are forwarded by Vite's proxy to the PHP backend served by Apache.

## Architecture

```text
Vue 3 + TypeScript
   |--------------------------> TMDB API
   |
   | /api
   v
PHP backend
   |
   v
PDO
   |
   v
MySQL 8
```

The frontend queries TMDB directly to obtain information about movies and actors. The PHP backend manages CineVerse's own data, such as user accounts and reviews.

## Documentation

The technical documentation and complementary files are located in:

```text
docs/
```

They provide more detailed information about the architecture, TMDB, Vue, TypeScript, PHP, MySQL, authentication, reviews, security, installation, deployment, and project testing.
