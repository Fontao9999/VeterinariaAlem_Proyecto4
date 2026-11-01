<?php
// ============================================================
// PRUEBA DE ENVÍO DE CORREO (solo administrador)
// Muestra el estado de la configuración y envía un correo de ejemplo.
// ============================================================
require "../includes/auth.php";
require "../includes/mailer.php";
exigir_admin();

$resultado = null;
$para = trim($_POST["para"] ?? MAIL_FROM);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_validar()) {
        $resultado = ["ok" => false, "modo" => "-", "error" => "La sesión venció. Intenta de nuevo."];
    } else {
        $resultado = enviar_correo($para, "Prueba de correo - " . VET_NOMBRE,
            '<p>🐾 Si ves este mensaje, el envío de correos de <strong>' . h(VET_NOMBRE) . '</strong> funciona correctamente.</p>');
    }
}

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Diagnóstico</div>
    <h2>Probar envío de correo 📧</h2>
    <p>Comprueba si el sistema puede enviar correos (verificación de cuenta, facturas y citas).</p>
</section>

<div class="table-card">
    <h3>Estado de la configuración</h3>
    <div class="table-wrap"><table>
        <tr><td>Modo</td><td><?= correo_modo_demo()
            ? "⚠ <strong>DEMOSTRACIÓN</strong> — SMTP_CLAVE está vacía en config.php; no se envía nada a internet"
            : "✅ Envío real por SMTP" ?></td></tr>
        <tr><td>Remitente</td><td><?= h(MAIL_FROM) ?></td></tr>
        <tr><td>Servidor</td><td><?= h(SMTP_HOST) ?>:<?= (int)SMTP_PORT ?></td></tr>
        <tr><td>Usuario SMTP</td><td><?= h(SMTP_USUARIO) ?></td></tr>
        <tr><td>Contraseña de aplicación</td><td><?= SMTP_CLAVE === "" ? "❌ No configurada" : "✅ Configurada (" . strlen(SMTP_CLAVE) . " caracteres" . (strlen(SMTP_CLAVE) !== 16 ? " — las de Google tienen 16, sin espacios" : "") . ")" ?></td></tr>
        <tr><td>Extensión openssl</td><td><?= extension_loaded("openssl") ? "✅ Activa" : "❌ Inactiva (necesaria para Gmail)" ?></td></tr>
    </table></div>
</div>

<div class="form-card">
    <h3>Enviar correo de prueba</h3>
    <?php if ($resultado): ?>
        <?php if ($resultado["ok"] && $resultado["modo"] === "smtp"): ?>
            <div class="alert success">✅ Enviado a <?= h($para) ?>. Revisa la bandeja de entrada y el spam.</div>
        <?php elseif ($resultado["ok"]): ?>
            <div class="info-box">Modo demostración: el correo se guardó en la carpeta <code>correos_enviados</code>, pero <strong>no salió a internet</strong>. Completa SMTP_CLAVE en config.php.</div>
        <?php else: ?>
            <div class="alert">❌ Error: <?= h($resultado["error"]) ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label>Enviar a</label><input type="email" name="para" value="<?= h($para) ?>" required></div>
        <div class="form-actions"><button class="btn" type="submit">Enviar prueba</button>
            <a class="btn secondary" href="index.php">Volver</a></div>
    </form>
    <p class="muted">Errores comunes: «Username and Password not accepted» = contraseña de aplicación incorrecta
    (o falta la verificación en 2 pasos); «No se pudo conectar» = sin internet o puerto bloqueado por antivirus/firewall.</p>
</div>
<?php require "../includes/footer.php"; ?>
