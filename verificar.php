<?php
// ============================================================
// VERIFICACIÓN DE CORREO
// Se llega desde el enlace enviado por correo: verificar.php?token=...
// ============================================================
require "includes/auth.php";
require "conexion.php";

$token = (string)($_GET["token"] ?? "");
$ok = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $hash = hash("sha256", $token);
    $stmt = $conexion->prepare(
        "UPDATE usuarios
         SET email_verificado = 1, token_verificacion = NULL, token_expira = NULL
         WHERE token_verificacion = ? AND token_expira > NOW()"
    );
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
}

require "includes/header.php";
?>
<div class="login-box form-card">
    <?php if ($ok): ?>
        <div class="hero-kicker">Correo verificado</div>
        <h2>¡Cuenta activada! ✅</h2>
        <p class="muted">Ya puedes iniciar sesión, agendar citas y comprar en la tienda.</p>
        <div class="form-actions"><a class="btn" href="login.php">Iniciar sesión</a></div>
    <?php else: ?>
        <div class="hero-kicker">Enlace no válido</div>
        <h2>No pudimos verificar el correo</h2>
        <div class="alert">El enlace es incorrecto, ya fue usado o venció (dura 24 horas).</div>
        <div class="form-actions">
            <a class="btn" href="reenviar_verificacion.php">Pedir un nuevo enlace</a>
            <a class="btn secondary" href="login.php">Volver</a>
        </div>
    <?php endif; ?>
</div>
<?php require "includes/footer.php"; ?>
