<?php

/* ########################################################################
 * Validador de datos de autenticación.
 *
 * Se encarga de validar los datos de entrada relacionados con la capa de
 * autenticación del sistema, incluyendo:
 *  - Registro de usuarios
 *  - Inicio de sesión
 *  - Solicitudes de recuperación y restablecimiento de contraseña
 *  - Reenvío de verificación de email
 *  - Validación de idioma para correos electrónicos
 *
 * Valida formato, campos obligatorios y reglas de seguridad (como
 * complejidad de contraseñas y formato de usuario/email).
 *
 * No contiene lógica de negocio ni acceso a base de datos.
 * Lanza InvalidArgumentException con códigos de error específicos.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

class AuthValidator
{
    private const USERNAME_MIN_LENGTH = 3;
    private const USERNAME_MAX_LENGTH = 20;
    private const EMAIL_MAX_LENGTH = 80;    
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 32;

    /* ==================================================================================
     * Valida los datos necesarios para registrar un usuario.
     *
     * Valida:
     *  - username
     *  - email
     *  - password
     *
     * Lanza una InvalidArgumentException si alguno de los datos
     * no cumple las reglas de validación.
     * ================================================================================== */
    public static function validateRegister(
        string $username,
        string $email,
        string $password
    ): void {

        self::validateUsername($username);
        self::validateEmail($email);
        self::validatePassword($password);
    }

    /* ==================================================================================
     * Valida los datos necesarios para iniciar sesión.
     *
     * Valida:
     *  - email
     *  - password
     *
     * Lanza una InvalidArgumentException si alguno de los datos
     * no cumple las reglas de validación.
     * ================================================================================== */
    public static function validateLogin(
        string $email,
        string $password
    ): void {

        self::validateEmail($email);
        self::validateLoginPassword($password);
    }

    /* ==================================================================================
     * Valida la nueva contraseña utilizada durante el proceso de
     * restablecimiento de contraseña.
     *
     * Valida:
     *  - password
     *
     * Lanza una InvalidArgumentException si la contraseña
     * no cumple las reglas de validación.
     * ================================================================================== */
    public static function validateResetPassword(
        string $password
    ): void {

        self::validatePassword($password);
    }

    /* ==================================================================================
     * Valida el mail para reenviar el correo de verificación.
     *
     * Valida:
     *  - email
     *
     * Lanza una InvalidArgumentException si el email no cumple las reglas de validación.
     * ================================================================================== */
    public static function validateEmailRequest(
        string $email
    ): void {

        self::validateEmail($email);
    }

    /* ==================================================================================
     * Valida el idioma solicitado para generar el contenido del correo electrónico.
     *
     * Reglas:
     *  - obligatorio
     *  - solo se permiten los idiomas soportados por la aplicación
     * ================================================================================== */
    public static function validateLanguage(
        string $language
    ): void {

        if (!in_array($language, ["es", "en"], true)) {
            throw new InvalidArgumentException(
                "INVALID_LANGUAGE"
            );
        }
    }

    /* ==================================================================================
     * Valida el nombre de usuario.
     *
     * Reglas:
     *  - obligatorio
     *  - entre 3 y 20 caracteres
     *  - solo letras, números y guion bajo (_)
     * ================================================================================== */
    private static function validateUsername(
        string $username
    ): void {

        if ($username === "") {
            throw new InvalidArgumentException(
                "USERNAME_REQUIRED"
            );
        }

        if (mb_strlen($username) < self::USERNAME_MIN_LENGTH) {
            throw new InvalidArgumentException(
                "USERNAME_TOO_SHORT"
            );
        }

        if (mb_strlen($username) > self::USERNAME_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "USERNAME_TOO_LONG"
            );
        }

        // Permite letras mayúsculas, minúsculas, números y guion bajo
        if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            throw new InvalidArgumentException(
                "USERNAME_INVALID"
            );
        }
    }

    /* ==================================================================================
     * Valida la dirección de correo electrónico.
     *
     * Reglas:
     *  - obligatorio
     *  - máximo 80 caracteres
     *  - formato de email válido
     * ================================================================================== */
    private static function validateEmail(
        string $email
    ): void {

        if ($email === "") {
            throw new InvalidArgumentException(
                "EMAIL_REQUIRED"
            );
        }

        if (mb_strlen($email) > self::EMAIL_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "EMAIL_TOO_LONG"
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                "INVALID_EMAIL"
            );
        }
    }

    /* ==================================================================================
     * Valida la contraseña del registro.
     *
     * Reglas:
     *  - obligatoria
     *  - entre 8 y 32 caracteres
     *  - al menos una letra mayúscula
     *  - al menos una letra minúscula
     *  - al menos un número
     *  - al menos un carácter especial
     * ================================================================================== */
    private static function validatePassword(
        string $password
    ): void {

        if ($password === "") {
            throw new InvalidArgumentException(
                "PASSWORD_REQUIRED"
            );
        }

        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new InvalidArgumentException(
                "PASSWORD_TOO_SHORT"
            );
        }

        if (mb_strlen($password) > self::PASSWORD_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "PASSWORD_TOO_LONG"
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException(
                "PASSWORD_UPPERCASE_REQUIRED"
            );
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException(
                "PASSWORD_LOWERCASE_REQUIRED"
            );
        }

        if (!preg_match('/\d/', $password)) {
            throw new InvalidArgumentException(
                "PASSWORD_NUMBER_REQUIRED"
            );
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException(
                "PASSWORD_SPECIAL_CHARACTER_REQUIRED"
            );
        }
    }

    /* ==================================================================================
     * Valida la contraseña recibida durante el inicio de sesión.
     *
     * Reglas:
     *  - obligatoria
     *  - máximo 32 caracteres
     *
     * No se valida la complejidad, ya que la contraseña ya fue
     * validada durante el registro. En el login únicamente se
     * comprueba que las credenciales sean correctas.
     * ================================================================================== */
    private static function validateLoginPassword(
        string $password
    ): void {

        if ($password === "") {
            throw new InvalidArgumentException(
                "PASSWORD_REQUIRED"
            );
        }

        if (mb_strlen($password) > self::PASSWORD_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "PASSWORD_TOO_LONG"
            );
        }
    }
}