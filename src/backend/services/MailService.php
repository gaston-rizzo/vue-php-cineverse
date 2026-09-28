<?php

/* ########################################################################
 * Servicio encargado de generar y enviar los correos electrónicos del sistema.
 *
 * Construye el contenido de los mensajes a partir de plantillas HTML,
 * adaptándolas al idioma solicitado por el usuario, y los envía mediante
 * un servidor SMTP utilizando PHPMailer.
 *
 * Actualmente soporta:
 * - verificación de cuentas;
 * - recuperación de contraseña.
 *
 * La estructura del servicio permite incorporar fácilmente nuevos tipos
 * de correos electrónicos en el futuro.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class MailService
{
    /* ==================================================================================
     * Genera y envía el correo electrónico de verificación de cuenta.
     *
     * Construye la URL de verificación utilizando el token recibido y el idioma
     * solicitado, carga la plantilla HTML correspondiente y envía el mensaje al
     * usuario.
     *
     * Lanza:
     *  - RuntimeException cuando no existe la plantilla del idioma solicitado.
    * ================================================================================== */
    public function generateVerificationEmail(
        string $email,
        string $token,
        string $language
    ): void {

        // Construye la URL que va a utilizar el usuario para verificar su cuenta.
        $verificationUrl =
            $_ENV["FRONTEND_URL"]
                . "/"
                . $language
                . "/verify-email?token="
                . urlencode($token);

        // Obtiene la ruta de la plantilla HTML correspondiente al idioma solicitado.
        $template =
            __DIR__
            . "/../templates/emails/verify-account."
            . $language
            . ".php";

        // Inicia el buffer de salida para capturar el HTML generado por la plantilla.
        ob_start();

        // Verifica que la plantilla exista antes de intentar cargarla.
        if (!file_exists($template)) {
            throw new \RuntimeException(
                "EMAIL_TEMPLATE_NOT_FOUND"
            );
        }

        // Ejecuta la plantilla y genera el contenido HTML del correo.
        require $template;

        // Recupera el HTML generado y limpia el buffer de salida.
        $html = (string) ob_get_clean();

        // Selecciona el asunto del correo según el idioma solicitado.
        $subject =
            $language === "en"
                ? "CineVerse Account Verification"
                : "Verificación de cuenta | CineVerse";                                

        // Envía el correo electrónico generado al destinatario.
        $this->send(
            $email,
            $subject,
            $html
        );
    }

   /* ==================================================================================
    * Genera y envía el correo electrónico para restablecer la contraseña.
    *
    * Construye la URL de recuperación utilizando el token recibido y el idioma
    * solicitado, carga la plantilla HTML correspondiente y envía el mensaje al
    * usuario.
    *
    * Lanza:
    *  - RuntimeException cuando no existe la plantilla del idioma solicitado.
    * ================================================================================== */
    public function generatePasswordResetEmail(
        string $email,
        string $token,
        string $language
    ): void {

        // Construye la URL que utilizará el usuario para restablecer su contraseña.
        $resetUrl =
            $_ENV["FRONTEND_URL"]
                . "/"
                . $language
                . "/reset-password?token="
                . urlencode($token);

        // Obtiene la ruta de la plantilla HTML correspondiente al idioma solicitado.
        $template =
            __DIR__
            . "/../templates/emails/reset-password."
            . $language
            . ".php";

        // Inicia el buffer de salida para capturar el HTML generado por la plantilla.
        ob_start();

        // Verifica que la plantilla exista antes de intentar cargarla.
        if (!file_exists($template)) {
            throw new \RuntimeException(
                "EMAIL_TEMPLATE_NOT_FOUND"
            );
        }

        // Ejecuta la plantilla y genera el contenido HTML del correo.
        require $template;

        // Recupera el HTML generado y limpia el buffer de salida.
        $html = (string) ob_get_clean();

        // Selecciona el asunto del correo según el idioma solicitado.
        $subject =
            $language === "en"
                ? "Reset your CineVerse password"
                : "Restablecer contraseña | CineVerse";

        // Envía el correo electrónico generado al destinatario.
        $this->send(
            $email,
            $subject,
            $html
        );
    }

    /* ==================================================================================
     * Envía un correo electrónico mediante SMTP.
     *
     * Recibe:
     *  - Dirección del destinatario.
     *  - Asunto del correo.
     *  - Contenido HTML del mensaje.
     *
     * La configuración SMTP se obtiene desde variables de entorno.
     * ================================================================================== */
    private function send(
        string $to,
        string $subject,
        string $html
    ): void {

        // Crea una nueva instancia de PHPMailer configurada para lanzar
        // excepciones cuando ocurra un error durante el envío.
        $mail = new PHPMailer(true);

        // Indica que el envío del correo se realizará mediante SMTP.
        // SMTP (Simple Mail Transfer Protocol) es el protocolo estándar utilizado
        // para enviar correos electrónicos entre clientes y servidores de correo.
        $mail->isSMTP();

        // Configura la conexión con el servidor SMTP.
        $mail->Host = $_ENV["MAIL_HOST"];
        $mail->Port = (int) $_ENV["MAIL_PORT"];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV["MAIL_USERNAME"];
        $mail->Password = $_ENV["MAIL_PASSWORD"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = "UTF-8";

        // Configura el remitente del correo.
        $mail->setFrom(
            $_ENV["MAIL_FROM"],
            $_ENV["MAIL_FROM_NAME"]
        );

        // Configura el destinatario y el contenido del mensaje.
        $mail->addAddress($to);

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $html;

        try {
            // Envía el correo electrónico.
            $mail->send();
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "EMAIL_DELIVERY_FAILED",
                0,
                $e
            );
        }
    }
}
