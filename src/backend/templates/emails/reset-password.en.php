<?php

/* ########################################################################
 * Plantilla HTML en inglés del correo de restablecimiento de contraseña.
 *
 * Este archivo genera el contenido del email que recibe el usuario cuando
 * solicita recuperar el acceso a su cuenta.
 *
 * Incluye:
 * - Mensaje introductorio en inglés
 * - Botón de restablecimiento de contraseña
 * - URL completa como alternativa
 * - Información sobre la validez del enlace (1 hora)
 * - Aviso de seguridad si la solicitud no fue realizada por el usuario
 * ######################################################################## */

/** @var string $resetUrl */

?>

<p style="margin:0 0 8px 0;font-size:34px;">
    🔐
</p>

<h1 style="margin-top:0;">
    Reset your password - CineVerse
</h1>

<p>
    We received a request to reset your account password.
</p>

<p>
    If this was you, click the button below:
</p>

<p>
    <a
        href="<?= $resetUrl ?>"
        style="
            display:inline-block;
            padding:12px 20px;
            background:#6d28d9;
            color:#ffffff;
            text-decoration:none;
            border-radius:8px;
        "
    >
        Reset password
    </a>
</p>

<p>
    Or copy and paste this link into your browser:
</p>

<p>
    <a
        href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>"
        style="color:#6d28d9;word-break:break-all;"
    >
        <?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>
    </a>
</p>

<p>
    This link will remain valid for the next
    <strong>1 hour</strong>.
</p>

<p>
    If you didn't request a password reset, you can safely ignore this email.
    Your password will remain unchanged.
</p>

<hr>

<p>
    CineVerse Team
</p>
