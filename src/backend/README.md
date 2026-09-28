# CineVerse — Backend

El backend de CineVerse es una API desarrollada en **PHP puro** para gestionar las funciones propias de la aplicación: cuentas de usuario, autenticación, sesiones, verificación de correo, recuperación de contraseña, perfil y reseñas.

La información de películas y personas no pasa por este backend. El frontend consulta **TMDB directamente**, mientras que PHP trabaja con los datos propios de CineVerse almacenados en MySQL.

## Tecnologías principales

- PHP 8.1 o superior.
- Entorno utilizado en esta versión: PHP 8.2.12.
- MySQL 8.
- PDO para el acceso a la base de datos.
- Composer.
- `vlucas/phpdotenv` para variables de entorno.
- Monolog para logs.
- PHPMailer para correo SMTP.
- Apache con `mod_rewrite`.

## Estructura

```text
backend/
├── config/
├── controllers/
├── core/
├── database/
├── docs/
│   ├── API.md
│   └── datos-de-prueba.md
│   └── resultado-datos-de-prueba.md
├── models/
├── public/
│   ├── index.php
│   └── .htaccess
├── routes/
├── services/
├── storage/
│   └── logs/
├── templates/
├── validators/
├── composer.json
└── composer.lock
```

Las responsabilidades principales se separan por capas:

- **Controllers**: reciben la solicitud HTTP y construyen la respuesta.
- **Validators**: comprueban formato, longitud y rango de los datos recibidos.
- **Services**: aplican las reglas de negocio.
- **Models**: acceden a MySQL mediante PDO.
- **Middleware**: realiza comprobaciones previas, como sesión y CSRF.
- **Core**: reúne infraestructura compartida de HTTP, autenticación, logging, middleware y bootstrap.

## Instalación

Desde la carpeta `backend`, instalar las dependencias:

```bash
composer install
```

Crear `.env` a partir de `.env.example`:

```bash
cp .env.example .env
```

Después configurar en `.env` la conexión MySQL y los valores necesarios para el entorno:

```env
APP_ENV=local
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=cineverse
DB_USER=usuario
DB_PASSWORD=contraseña
DB_CHARSET=utf8mb4

CINEVERSE_LOG_PATH=storage/logs
```

Si se quieren probar los correos de verificación y recuperación de contraseña, también deben configurarse las variables SMTP y `FRONTEND_URL`.

## Base de datos

El repositorio incluye el archivo SQL de datos de prueba `cineverse.sql`.

El script crea la base `cineverse`, genera las tablas utilizadas por el backend y carga el conjunto de datos de prueba.

El backend utiliza tres tablas:

```text
users
reviews
login_attempts
```

El detalle del dataset se encuentra en:

- [`docs/datos-de-prueba.md`](docs/datos-de-prueba.md)
- [`docs/resultado-datos-prueba.md`](docs/resultado-datos-prueba.md)

## Ejecución local

En el entorno de desarrollo documentado, Apache de XAMPP sirve únicamente la carpeta:

```text
backend/public
```

El VirtualHost utiliza:

```text
http://cineverse.local
```

El archivo `hosts` de Windows debe asociar ese nombre con `127.0.0.1`, y Apache debe tener habilitado `mod_rewrite` para que `.htaccess` pueda enviar las rutas de la API a `public/index.php`.

El backend no se inicia mediante un comando propio de Composer: Apache es quien atiende las solicitudes PHP.

## Flujo de una solicitud

```text
Petición HTTP
    ↓
public/index.php
    ↓
bootstrap.php / PDO / Session / CORS
    ↓
routes/api.php
    ↓
Router
    ↓
Middleware
    ↓
Controller
    ↓
Request
    ↓
Validator
    ↓
Service
    ↓
Model
    ↓
PDO / MySQL
    ↓
Respuesta JSON
```

## Autenticación y seguridad

El backend utiliza sesiones PHP para mantener la autenticación.

También incorpora:

- protección CSRF en operaciones que modifican datos;
- CORS para la comunicación con el frontend;
- contraseñas almacenadas mediante hash;
- verificación de correo electrónico;
- recuperación de contraseña mediante token;
- limitación de intentos fallidos de inicio de sesión.

## API

Las rutas del backend se definen en:

```text
routes/api.php
```

El contrato HTTP, los endpoints disponibles y los requisitos de sesión y CSRF se documentan en:

[`API.md`](API.md)

## Logs

Los errores técnicos del backend se registran mediante Monolog.

La carpeta se configura con:

```env
CINEVERSE_LOG_PATH=storage/logs
```

## Documentación

La carpeta `docs/` contiene documentación complementaria sobre los datos de prueba.

El repositorio incluye además la documentación técnica completa de CineVerse, donde se desarrollan en profundidad la arquitectura, autenticación, reseñas, seguridad, logs, instalación, ejecución y publicación del proyecto.
