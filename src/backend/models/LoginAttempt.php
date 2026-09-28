<?php

/* ########################################################################
 * Modelo que gestiona los intentos fallidos de inicio de sesión asociados
 * a una combinación de dirección IP y correo electrónico.
 *
 * Responsabilidades principales:
 *  - Registrar intentos fallidos de login.
 *  - Consultar intentos existentes por IP y email.
 *  - Incrementar el contador de intentos.
 *  - Reiniciar los intentos tras un inicio de sesión exitoso.
 *  - Eliminar registros expirados para mantener la tabla limpia.
 *
 * Este modelo forma parte del mecanismo de rate limiting que limita los
 * intentos consecutivos de inicio de sesión para reducir ataques de
 * fuerza bruta.
 *
 * Todas las operaciones se realizan mediante consultas preparadas con PDO,
 * garantizando protección frente a inyección SQL.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Models;

use PDO;

class LoginAttempt
{
    /* ==================================================================================
     * Busca el registro de intentos de inicio de sesión asociado
     * a una combinación de IP y email.
     *
     * Se utiliza para comprobar si ya existen intentos fallidos
     * registrados antes de actualizar el contador.
     *
     * Devuelve:
     *  - Array asociativo con la información del registro si existe.
     *  - false si no se encontró ningún registro.
     * ================================================================================== */
    public static function find(
        PDO $pdo,
        string $ip,
        string $email
    ): array|false {
        $stmt = $pdo->prepare("
            SELECT id, attempts, last_attempt
            FROM login_attempts
            WHERE ip = :ip
            AND email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":ip" => $ip,
            ":email" => $email
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Registra el primer intento fallido de inicio de sesión para una
     * combinación de IP y email.
     *
     * Se utiliza cuando todavía no existe un registro previo en la
     * tabla login_attempts.
     * 
     * No se insertan los campos attempts ni last_attempt porque la base
     * de datos los inicializa automáticamente mediante sus valores por
     * defecto:      
     *  - attempts comienza en 1.
     *  - last_attempt se establece con la fecha y hora actuales.
     * ================================================================================== */
    public static function create(
        PDO $pdo,
        string $ip,
        string $email
    ): void {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (ip, email)
            VALUES (:ip, :email)
        ");

        $stmt->execute([
            ":ip" => $ip,
            ":email" => $email
        ]);
    }

    /* ==================================================================================
     * Incrementa el contador de intentos fallidos para un registro existente.
     *
     * Además de aumentar el número de intentos, la columna last_attempt se
     * actualiza automáticamente con la fecha y hora actuales gracias a la
     * configuración ON UPDATE CURRENT_TIMESTAMP definida en la tabla.
     *
     * No se actualiza last_attempt desde este método porque MySQL lo hace
     * automáticamente cada vez que la fila es modificada mediante UPDATE.
     * ================================================================================== */
    public static function increment(
        PDO $pdo,
        int $id
    ): void {
        $stmt = $pdo->prepare("
            UPDATE login_attempts
            SET attempts = attempts + 1
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);
    }

    /* ==================================================================================
     * Elimina el registro de intentos fallidos asociado a una combinación
     * de IP y email.
     *
     * Se utiliza cuando el inicio de sesión es correcto para reiniciar el
     * contador de intentos fallidos. Si no existe un registro, la operación
     * no produce ningún efecto.
     * ================================================================================== */
    public static function reset(
        PDO $pdo,
        int $id
    ): void {
        $stmt = $pdo->prepare("
            DELETE FROM login_attempts
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $id
        ]);
    }

    /* ==================================================================================
     * Elimina los registros de intentos fallidos cuyo período de bloqueo
     * ha expirado.
     *
     * Se utiliza para evitar que la tabla crezca indefinidamente eliminando
     * los registros cuyo último intento ocurrió hace más de 15 minutos.
     * ================================================================================== */
    public static function deleteExpired(PDO $pdo): void
    {
        $stmt = $pdo->prepare("
            DELETE FROM login_attempts
            WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");

        $stmt->execute();
    }
}