<?php

declare(strict_types=1);

namespace App\Core\Middleware;

final class CorsMiddleware
{
    public static function handle(): void
    {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            http_response_code(204);
            exit;
        }
    }
}