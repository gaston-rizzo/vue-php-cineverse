/* ============================================================================
 * MAP: authFieldErrorMap
 * ============================================================================
 *
 * Mapea los códigos de error que puede devolver el backend durante el
 * registro e inicio de sesión, indicando el campo del formulario y la
 * clave de traducción (i18n) correspondiente para mostrar el mensaje
 * adecuado en la UI.
 * ============================================================================ */

export const authFieldErrorMap = {

    // El nombre de usuario es obligatorio
    USERNAME_REQUIRED: {
        field: "username",
        error: "required"
    },

    // El nombre de usuario no cumple con la longitud mínima
    USERNAME_TOO_SHORT: {
        field: "username",
        error: "usernameMin"
    },

    // El nombre de usuario supera la longitud máxima permitida
    USERNAME_TOO_LONG: {
        field: "username",
        error: "usernameMax"
    },

    // El nombre de usuario contiene caracteres no permitidos
    USERNAME_INVALID: {
        field: "username",
        error: "usernameInvalid"
    },

    // El email es obligatorio
    EMAIL_REQUIRED: {
        field: "email",
        error: "required"
    },

    // El email supera la longitud máxima permitida
    EMAIL_TOO_LONG: {
        field: "email",
        error: "emailMax"
    },

    // El formato del email no es válido
    INVALID_EMAIL: {
        field: "email",
        error: "invalidEmail"
    },

    // La contraseña es obligatoria
    PASSWORD_REQUIRED: {
        field: "password",
        error: "required"
    },

    // La contraseña no cumple con la longitud mínima
    PASSWORD_TOO_SHORT: {
        field: "password",
        error: "passwordMin"
    },

    // La contraseña supera la longitud máxima permitida
    PASSWORD_TOO_LONG: {
        field: "password",
        error: "passwordMax"
    },

    // La contraseña debe contener al menos una letra mayúscula
    PASSWORD_UPPERCASE_REQUIRED: {
        field: "password",
        error: "passwordUppercase"
    },

    // La contraseña debe contener al menos una letra minúscula
    PASSWORD_LOWERCASE_REQUIRED: {
        field: "password",
        error: "passwordLowercase"
    },

    // La contraseña debe contener al menos un número
    PASSWORD_NUMBER_REQUIRED: {
        field: "password",
        error: "passwordNumber"
    },

    // La contraseña debe contener al menos un carácter especial
    PASSWORD_SPECIAL_CHARACTER_REQUIRED: {
        field: "password",
        error: "passwordSpecialCharacter"
    },

    // El nombre de usuario ya está registrado
    USERNAME_ALREADY_EXISTS: {
        field: "username",
        error: "usernameAlreadyExists"
    },

    // El email ya está registrado
    EMAIL_ALREADY_EXISTS: {
        field: "email",
        error: "emailAlreadyExists"
    }

} as const;