# CineVerse — API

La API propia de CineVerse está desarrollada en PHP y se utiliza para las funciones que trabajan con datos locales de la aplicación: cuentas, autenticación, perfil y reseñas.

La información de películas y personas se consulta directamente desde TMDB en el frontend y no pasa por esta API.

## URL en desarrollo

El backend registra rutas como:

```text
/login
/profile
/reviews
```

Durante el desarrollo, el frontend utiliza `/api` como prefijo. Vite reenvía esas solicitudes a `http://cineverse.local` y elimina `/api` antes de entregarlas a Apache.

Por ejemplo:

```text
/api/user
    ↓
http://cineverse.local/user
```

## Formato de respuestas

Las respuestas del backend utilizan JSON con una estructura común. Ejemplos:

Respuesta exitosa:

```json
{
  "success": true,
  "code": "REVIEW_CREATED"
}
```

Cuando una operación devuelve información adicional, se incluye `data`:

```json
{
  "success": true,
  "code": "AUTHENTICATED_USER",
  "data": {
    "user": {},
    "csrf_token": "..."
  }
}
```

Respuesta de error:

```json
{
  "success": false,
  "code": "INVALID_CREDENTIALS"
}
```

`code` identifica de forma estable el resultado o la causa del error para que el frontend pueda interpretarlo.

## Sesión y CSRF

CineVerse utiliza sesiones PHP. La cookie de sesión es administrada por el navegador y permite asociar las solicitudes posteriores con el usuario autenticado.

Las operaciones protegidas que modifican datos requieren además la cabecera:

```text
X-CSRF-Token: <token>
```

El token CSRF se obtiene al iniciar sesión y también puede recuperarse mediante `GET /user`.

Las rutas de solo lectura protegidas requieren sesión, pero no CSRF. Las rutas públicas no requieren ninguno de los dos.

## Autenticación y cuenta

| Método | Ruta | Sesión | CSRF | Entrada | Función |
|---|---|---:|---:|---|---|
| `POST` | `/register` | No | No | `username`, `email`, `password`, `language` | Registrar una cuenta |
| `POST` | `/login` | No | No | `email`, `password` | Iniciar sesión |
| `GET` | `/verify-email` | No | No | `token` por query | Verificar el correo |
| `POST` | `/resend-verification-email` | No | No | `email`, `language` | Reenviar el correo de verificación |
| `POST` | `/forgot-password` | No | No | `email`, `language` | Solicitar recuperación de contraseña |
| `GET` | `/reset-password/validate` | No | No | `token` por query | Validar un token de recuperación |
| `POST` | `/reset-password` | No | No | `token`, `password` | Restablecer la contraseña |
| `POST` | `/logout` | Sí | Sí | — | Cerrar la sesión |
| `GET` | `/user` | Sí | No | — | Recuperar el usuario autenticado y el token CSRF |

## Perfil

| Método | Ruta | Sesión | CSRF | Entrada | Función |
|---|---|---:|---:|---|---|
| `GET` | `/profile` | Sí | No | — | Obtener el resumen del perfil del usuario |

## Reseñas

| Método | Ruta | Sesión | CSRF | Entrada | Función |
|---|---|---:|---:|---|---|
| `GET` | `/reviews` | Sí | No | `page` opcional | Obtener las reseñas propias |
| `POST` | `/reviews` | Sí | Sí | `movie_id`, `movie_title`, `rating`, `comment` | Crear una reseña |
| `PUT` | `/reviews/{id}` | Sí | Sí | `rating`, `comment` | Editar una reseña propia |
| `DELETE` | `/reviews/{id}` | Sí | Sí | — | Eliminar una reseña propia |
| `GET` | `/movies/{movieId}/reviews` | No | No | `page` opcional | Obtener las reseñas de una película |

En las rutas paginadas, `page` vale `1` por defecto. Las dos consultas paginadas devuelven hasta 12 reseñas por página.

En `GET /movies/{movieId}/reviews`, `user_review` solo se devuelve con contenido en la primera página.

`GET /movies/{movieId}/reviews` es público. Si existe una sesión válida, el backend puede identificar al usuario y devolver su reseña propia separada de las reseñas de la comunidad.

## Estados HTTP principales

| Estado | Uso general en CineVerse |
|---:|---|
| `200` | Operación completada correctamente |
| `400` | Solicitud o token inválido |
| `401` | Falta autenticación o las credenciales son incorrectas |
| `403` | CSRF inválido, cuenta no verificada u operación no permitida sobre una reseña ajena |
| `404` | Ruta o recurso inexistente |
| `409` | Conflicto con el estado existente, por ejemplo una reseña duplicada |
| `422` | Error de validación de los datos recibidos |
| `429` | Límite temporal de intentos de inicio de sesión |
| `500` | Error interno del servidor |
| `503` | Base de datos MySQL no disponible |

Algunos códigos internos utilizados por la API son:

```text
AUTH_REQUIRED
INVALID_CREDENTIALS
EMAIL_NOT_VERIFIED
INVALID_CSRF_TOKEN
ROUTE_NOT_FOUND
REVIEW_NOT_FOUND
NOT_REVIEW_OWNER
REVIEW_ALREADY_EXISTS
LOGIN_TEMPORARILY_BLOCKED
INTERNAL_SERVER_ERROR
DATABASE_UNAVAILABLE
```

## Implementación

Las rutas se encuentran en:

```text
routes/api.php
```

El Router utiliza la configuración de cada endpoint para determinar el Controller correspondiente y aplicar, cuando corresponda, autenticación y validación CSRF antes de ejecutar la operación.

La documentación técnica completa de CineVerse desarrolla en profundidad el funcionamiento de la API, los Controllers, Services, Validators, Models, sesiones, seguridad y manejo de errores.
