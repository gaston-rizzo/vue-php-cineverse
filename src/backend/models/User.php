<?php

/* ########################################################################
 * Modelo que gestiona las operaciones relacionadas con los usuarios
 * del sistema.
 *
 * Responsabilidades principales:
 *  - Comprobar la existencia de usuarios por username o email.
 *  - Registrar nuevos usuarios.
 *  - Obtener usuarios por id, email, token de verificación o token de
 *    recuperación de contraseña.
 *  - Gestionar el proceso de verificación mediante correo electrónico.
 *  - Gestionar la recuperación y el restablecimiento de contraseñas.
 *  - Activar cuentas y actualizar información relacionada con la
 *    autenticación.
 *
 * Este modelo agrupa las consultas de lectura y escritura sobre la
 * tabla users, incluyendo el registro de usuarios, la autenticación,
 * la verificación de cuentas y la recuperación de contraseñas.
 *
 * Todas las operaciones se realizan mediante consultas preparadas con PDO,
 * garantizando protección frente a inyección SQL.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    /* ==================================================================================
     * Comprueba si ya existe un usuario con el mismo username o email.
     *
     * Se utiliza durante el registro para evitar usuarios duplicados dentro
     * de la tabla users.
     *
     * Devuelve:
     *  - array con username/email si existe coincidencia
     *  - false si no existe ningún registro coincidente
     * ================================================================================== */
    public static function existsByUsernameOrEmail(
        PDO $pdo,
        string $username,
        string $email
    ): array|false {

        $stmt = $pdo->prepare("
            SELECT username, email
            FROM users
            WHERE username = :username OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":username" => $username,
            ":email" => $email
        ]);

        return $stmt->fetch() ?: false;
    }
    
    /* ==================================================================================
     * Crea un nuevo usuario en la base de datos.
     *
     * Recibe:
     *  - Username del usuario.
     *  - Email del usuario.
     *  - Contraseña almacenada previamente como hash.
     *  - Token de verificación de correo electrónico.
     *  - Fecha y hora de expiración del token de verificación.
     *
     * El usuario se crea inicialmente con la cuenta sin verificar
     * (is_verified = 0), permitiendo su activación una vez que confirme
     * su dirección de correo electrónico.
     * ================================================================================== */
    public static function create(
        PDO $pdo,
        string $username,
        string $email,
        string $passwordHash,
        string $verificationToken,
        string $verificationExpiresAt
    ): void {

        $stmt = $pdo->prepare("
            INSERT INTO users (
                username,
                email,
                password_hash,
                is_verified,
                verification_token,
                verification_expires_at
            )
            VALUES (
                :username,
                :email,
                :password_hash,
                :is_verified,
                :verification_token,
                :verification_expires_at
            )
        ");

        $stmt->execute([
            ":username" => $username,
            ":email" => $email,
            ":password_hash" => $passwordHash,
            ":is_verified" => 0,
            ":verification_token" => $verificationToken,
            ":verification_expires_at" => $verificationExpiresAt
        ]);
    }

    /* ==================================================================================
     * Busca un usuario mediante su id.
     *
     * Se utiliza para recuperar la información del usuario autenticado a partir
     * del user_id almacenado en la sesión.
     *
     * Devuelve:
     *  - Array asociativo con los datos del usuario si existe.
     *  - false si no se encontró ningún usuario.
     * ================================================================================== */
    public static function findById(
        PDO $pdo, 
        int $id
    ): array|false {
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Busca un usuario mediante su dirección de correo electrónico.
     *
     * Se utiliza durante el proceso de autenticación para obtener los datos
     * necesarios, comprobar la contraseña ingresada y verificar si la
     * cuenta ya fue confirmada mediante el correo electrónico.
     *
     * Devuelve:
     *  - Array asociativo con los datos del usuario si existe.
     *  - false si no se encontró ningún usuario.
     * ================================================================================== */
    public static function findByEmail(
        PDO $pdo,
        string $email
    ): array|false {

        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                email,
                password_hash,
                is_verified,
                created_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":email" => $email
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Busca un usuario mediante su token de verificación.
     *
     * Devuelve:
     *  - Los datos del usuario necesarios para validar el token si existe.
     *  - false si no existe ningún usuario con ese token.
     * ================================================================================== */
    public static function findByVerificationToken(
        PDO $pdo,
        string $token
    ): array|false {

        $stmt = $pdo->prepare("
            SELECT
                id,
                is_verified,
                verification_expires_at
            FROM users
            WHERE verification_token = :token
            LIMIT 1
        ");

        $stmt->execute([
            ":token" => $token
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Busca un usuario mediante su token de recuperación de contraseña.
     *
     * Devuelve:
     *  - Los datos del usuario necesarios para validar el token si existe.
     *  - false si no existe ningún usuario con ese token.
     * ================================================================================== */
    public static function findByPasswordResetToken(
        PDO $pdo,
        string $token
    ): array|false {

        $stmt = $pdo->prepare("
            SELECT
                id,
                password_reset_expires_at
            FROM users
            WHERE password_reset_token = :token
            LIMIT 1
        ");

        $stmt->execute([
            ":token" => $token
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Activa la cuenta de un usuario.
     *
     * Marca la cuenta como verificada e invalida el token de verificación
     * para impedir que vuelva a utilizarse.
     * ================================================================================== */
    public static function activateEmail(
        PDO $pdo,
        int $userId
    ): void {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                is_verified = 1,
                verification_token = NULL,
                verification_expires_at = NULL
            WHERE id = :id
        ");

        $stmt->execute([
            ":id" => $userId
        ]);
    }

    /* ==================================================================================
     * Actualiza el token de verificación de un usuario.
     *
     * Reemplaza el token anterior por uno nuevo y actualiza
     * la fecha de expiración del mismo.
     * ================================================================================== */
    public static function updateVerificationToken(
        PDO $pdo,
        int $userId,
        string $verificationToken,
        string $verificationExpiresAt
    ): void {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                verification_token = :verification_token,
                verification_expires_at = :verification_expires_at
            WHERE id = :id
        ");

        $stmt->execute([
            ":verification_token" => $verificationToken,
            ":verification_expires_at" => $verificationExpiresAt,
            ":id" => $userId
        ]);
    }

    /* ==================================================================================
     * Actualiza el token de recuperación de contraseña de un usuario.
     *
     * Reemplaza el token anterior por uno nuevo y actualiza
     * la fecha de expiración del mismo.
     * ================================================================================== */
    public static function updatePasswordResetToken(
        PDO $pdo,
        int $userId,
        string $token,
        string $expiresAt
    ): void {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                password_reset_token = :token,
                password_reset_expires_at = :expires_at
            WHERE id = :id
        ");

        $stmt->execute([
            ":token" => $token,
            ":expires_at" => $expiresAt,
            ":id" => $userId
        ]);
    }

    /* ==================================================================================
     * Actualiza la contraseña de un usuario.
     *
     * Reemplaza la contraseña almacenada por el nuevo hash recibido
     * e invalida el token de recuperación junto con su fecha de
     * expiración para impedir que pueda reutilizarse.
     * ================================================================================== */
    public static function updatePassword(
        PDO $pdo,
        int $userId,
        string $passwordHash
    ): void {
        
        $stmt = $pdo->prepare("
            UPDATE users
            SET
                password_hash = :password_hash,
                password_reset_token = NULL,
                password_reset_expires_at = NULL
            WHERE id = :id
        ");

        $stmt->execute([
            ":password_hash" => $passwordHash,
            ":id" => $userId
        ]);
    }
}