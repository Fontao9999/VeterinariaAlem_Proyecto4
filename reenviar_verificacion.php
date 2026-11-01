<?php
// ============================================================
// REENVIAR CORREO DE VERIFICACIÓN
// Por privacidad, la respuesta es siempre la misma exista o no el correo.
// ============================================================
require "includes/auth.php";
require "includes/captcha.php";
require "includes/mailer.php";
require "conexion.php";

if (usuario_logueado()) {
    header("Location: index.php");
    exit;
}

$error = "";
$listo = false;
$enlace_demo = "";
$email = trim($_GET["email"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!validar_captcha()) {
        $error = MENSAJE_CAPTCHA;
    } elseif (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Escribe un correo válido.";
    } else {
        $stmt = $conexion->prepare(
            "SELECT id, nombre, email_verificado, activo,
                    (verificacion_enviada IS NOT NULL AND verificacion_enviada > DATE_SUB(NOW(), INTERVAL 60 SECOND)) AS reciente
             FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if ($u && !$u["email_verificado"] && $u["activo"] && !$u["reciente"]) {
            $r = enviar_verificacion($conexion, $u["id"], $u["nombre"], $email);
            if ($r["resultado"]["ok"] && $r["resultado"]["modo"] === "demo") {
                $enlace_demo = $r["enlace"];
            }
        }
        $listo = true;
    }
}

nuevo_captcha();
require "includes/header.php";
?>
<div class="login-box form-card">
    <div class="hero-kicker">Verificación de correo</div>
    <h2>Reenviar enlace</h2>

    <?php if ($listo): ?>
        <div class="alert success">
            Si el correo corresponde a una cuenta pendiente de verificar, te enviamos un nuevo enlace.
            (Por seguridad, solo se puede pedir uno por minuto.)
        </div>
        <?php if ($enlace_demo): ?>
            <div class="info-box"><strong>Modo demostración:</strong>
                <a href="<?= h($enlace_demo) ?>">Verificar mi correo ahora</a></div>
        <?php endif; ?>
        <div class="form-actions"><a class="btn secondary" href="login.php">Volver</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" name="email" value="<?= h($email) ?>" required>
            </div>
            <br>
            <?= captcha_html() ?>
            <div class="form-actions">
                <button class="btn" type="submit">Enviar enlace</button>
                <a class="btn secondary" href="login.php">Volver</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require "includes/footer.php"; ?>
