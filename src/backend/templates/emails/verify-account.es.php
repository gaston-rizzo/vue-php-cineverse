<?php

/* ########################################################################
 * Plantilla HTML en español del correo de verificación de cuenta.
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
    Bienvenido a CineVerse
</h1>

<p>
    Gracias por crear tu cuenta.
</p>

<p>
    Para completar el registro y activar tu acceso,
    necesitás verificar tu dirección de correo electrónico.
</p>

<p>
    Hacé click en el siguiente botón:
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
        Verificar cuenta
    </a>
</p>

<p>
    O copiá y pegá este enlace en tu navegador:
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
    Este enlace estará disponible durante las próximas
    <strong>24 horas</strong>.
</p>

<p>
    Si no creaste esta cuenta, podés ignorar este correo.
</p>

<hr>

<p>
    Equipo de CineVerse
</p>
