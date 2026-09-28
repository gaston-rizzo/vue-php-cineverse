<?php

/* ########################################################################
 * Configuración de rutas de la API.
 *
 * Este archivo devuelve un array asociativo que define todos
 * los endpoints disponibles de la aplicación.
 *
 * Un endpoint representa una URL de la API junto con el método
 * HTTP que debe utilizarse para acceder a ella.
 *
 * Cada combinación de método HTTP y ruta contiene la
 * configuración necesaria para procesar la petición.
 *
 * Cada ruta especifica contiene:
 *  - handler: controlador y método que ejecutarán la petición.
 *  - auth: indica si la ruta requiere un usuario autenticado.
 *  - csrf: indica si debe validarse el token CSRF.
 *
 * El array se organiza por método HTTP:
 *  - GET: consulta de recursos.
 *  - POST: creación de recursos y operaciones de autenticación.
 *  - PUT: actualización de recursos.
 *  - DELETE: eliminación de recursos.
 *
 * Ejemplos:
 *  - POST /register
 *    Registra un nuevo usuario.
 *
 *  - POST /reviews
 *    Crea una nueva review para una película
 *    (requiere autenticación y validación CSRF).
 *
 *  - GET /profile
 *    Obtiene un resumen del perfil del usuario autenticado.
 *
 * El Router carga este array para:
 *  - localizar la ruta solicitada;
 *  - aplicar validaciones de autenticación y CSRF;
 *  - instanciar el controlador correspondiente;
 *  - delegar la ejecución de la petición.
 * ######################################################################## */

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ReviewController;
use App\Controllers\ProfileController;

return [

    "POST" => [

        "/register" => [
            "handler" => [AuthController::class, "register"],
            "auth" => false,
            "csrf" => false
        ],

        "/login" => [
            "handler" => [AuthController::class, "login"],
            "auth" => false,
            "csrf" => false
        ],

        "/resend-verification-email" => [
            "handler" => [AuthController::class, "resendVerificationEmail"],
            "auth" => false,
            "csrf" => false
        ],

        "/forgot-password" => [
            "handler" => [AuthController::class, "forgotPassword"],
            "auth" => false,
            "csrf" => false
        ],

        "/reset-password" => [
            "handler" => [AuthController::class, "resetPassword"],
            "auth" => false,
            "csrf" => false
        ],

        "/logout" => [
            "handler" => [AuthController::class, "logout"],
            "auth" => true,
            "csrf" => true
        ],

        "/reviews" => [
            "handler" => [ReviewController::class, "create"],
            "auth" => true,
            "csrf" => true
        ]
    ],

    "GET" => [        

        "/reset-password/validate" => [
            "handler" => [AuthController::class, "validateResetToken"],
            "auth" => false,
            "csrf" => false
        ],

        "/verify-email" => [
            "handler" => [AuthController::class, "verifyEmail"],
            "auth" => false,
            "csrf" => false
        ],

        "/user" => [
            "handler" => [AuthController::class, "getAuthenticatedUser"],
            "auth" => true,
            "csrf" => false
        ],

        "/profile" => [
            "handler" => [ProfileController::class, "getProfile"],
            "auth" => true,
            "csrf" => false
        ],

        "/reviews" => [
            "handler" => [ReviewController::class, "getUserReviews"],
            "auth" => true,
            "csrf" => false
        ],

        "/movies/{movieId}/reviews" => [
            "handler" => [ReviewController::class, "getMovieReviews"],
            "auth" => false,
            "csrf" => false
        ]
    ],

    "PUT" => [

        "/reviews/{id}" => [
            "handler" => [ReviewController::class, "update"],
            "auth" => true,
            "csrf" => true
        ]
    ],

    "DELETE" => [

        "/reviews/{id}" => [
            "handler" => [ReviewController::class, "delete"],
            "auth" => true,
            "csrf" => true
        ]
    ]
];