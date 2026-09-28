<?php

/* ########################################################################
 * Plantilla HTML en español del correo de restablecimiento de contraseña.
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
    Restablecer contraseña - CineVerse
</h1>

<p>
    Recibimos una solicitud para restablecer la contraseña de tu cuenta.
</p>

<p>
    Si fuiste vos, hacé click en el siguiente botón:
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
        Restablecer contraseña
    </a>
</p>

<p>
    O copiá y pegá este enlace en tu navegador:
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
    Este enlace estará disponible durante la próxima
    <strong>1 hora</strong>.
</p>

<p>
    Si no solicitaste este cambio, podés ignorar este correo.
    Tu contraseña permanecerá sin modificaciones.
</p>

<hr>

<p>
    Equipo de CineVerse
</p>
