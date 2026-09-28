<?php

/* ########################################################################
 * Plantilla HTML en inglés del correo de verificación de cuenta.
 *
 * Este archivo genera el contenido del email enviado al usuario cuando
 * crea una cuenta en CineVerse.
 *
 * Incluye:
 * - Mensaje de bienvenida
 * - Instrucciones para verificar el email
 * - Botón de verificación
 * - URL alternativa en texto plano
 * - Validez del enlace (24 horas)
 * - Aviso en caso de registro no solicitado
 *
 * Todo el contenido visible para el usuario está escrito en inglés.
 * ######################################################################## */

/** @var string $verificationUrl */

?>

<p style="margin:0 0 8px 0;font-size:34px;">
    🎬
</p>

<h1 style="margin-top:0;">
    Welcome to CineVerse
</h1>

<p>
    Thank you for creating your account.
</p>

<p>
    To complete your registration and activate your access,
    you need to verify your email address.
</p>

<p>
    Click the button below:
</p>

<p>
    <a
        href="<?= $verificationUrl ?>"
        style="
            display:inline-block;
            padding:12px 20px;
            background:#6d28d9;
            color:#ffffff;
            text-decoration:none;
            border-radius:8px;
        "
    >
        Verify account
    </a>
</p>

<p>
    Or copy and paste this link into your browser:
</p>

<p>
    <a
        href="<?= htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') ?>"
        style="color:#6d28d9;word-break:break-all;"
    >
        <?= htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') ?>
    </a>
</p>

<p>
    This link will be available for the next
    <strong>24 hours</strong>.
</p>

<p>
    If you did not create this account, you can ignore this email.
</p>

<hr>

<p>
    CineVerse Team
</p>
