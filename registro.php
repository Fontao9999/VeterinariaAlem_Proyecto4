<?php
// ============================================================
// REGISTRO
// Crea una cuenta de cliente + su ficha de dueño.
// - CAPTCHA "No soy un robot" (PHP + sesiones).
// - Envía un correo de verificación; la cuenta no permite iniciar
//   sesión hasta confirmar el correo.
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
$enviado = null;   // datos del envío cuando el registro fue correcto
$nombre = $email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $pass = $_POST["password"] ?? "";
    $pass2 = $_POST["password2"] ?? "";

    if (!validar_captcha()) {
        $error = MENSAJE_CAPTCHA;
    } elseif ($nombre === "" || $email === "") {
        $error = "Completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } elseif (strlen($pass) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } elseif (($_POST["acepto_privacidad"] ?? "") !== "1") {
        $error = "Debes aceptar la política de privacidad para crear la cuenta.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $conexion->begin_transaction();
        try {
            // 1) ficha del cliente (dueño de mascotas)
            $d = $conexion->prepare("INSERT INTO clientes(nombre, email) VALUES (?, ?)");
            $d->bind_param("ss", $nombre, $email);
            $d->execute();
            $id_cliente = $conexion->insert_id;

            // 2) cuenta de usuario, sin verificar
            $u = $conexion->prepare(
                "INSERT INTO usuarios(nombre, email, password, rol, activo, id_cliente, email_verificado)
                 VALUES (?, ?, ?, 'cliente', 1, ?, 0)"
            );
            $u->bind_param("sssi", $nombre, $email, $hash, $id_cliente);
            $u->execute();
            $id_usuario = $conexion->insert_id;
            $conexion->commit();

            // 3) correo de verificación
            $enviado = enviar_verificacion($conexion, $id_usuario, $nombre, $email);
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            $error = "No se pudo registrar. Es posible que el correo ya exista.";
        }
    }
}

nuevo_captcha();
require "includes/header.php";
?>

<?php if ($enviado): ?>
<div class="login-box form-card">
    <div class="hero-kicker">¡Casi listo!</div>
    <h2>Revisa tu correo 📬</h2>

    <?php if ($enviado["resultado"]["ok"] && $enviado["resultado"]["modo"] === "smtp"): ?>
        <div class="alert success">
            Enviamos un correo de verificación a <strong><?= h($email) ?></strong>.
            Abre el mensaje y toca <em>«Verificar mi correo»</em> para activar tu cuenta.
            Si no lo ves, revisa la carpeta de spam.
        </div>
    <?php elseif ($enviado["resultado"]["ok"]): ?>
        <div class="info-box">
            <strong>Modo demostración:</strong> todavía no se configuró el servidor de correo
            (<code>SMTP_CLAVE</code> en <code>config.php</code>), por eso el mensaje no salió a internet;
            quedó guardado en la carpeta <code>correos_enviados</code>.<br><br>
            Para continuar con la prueba usa este enlace:<br>
            <a href="<?= h($enviado["enlace"]) ?>">Verificar mi correo ahora</a>
        </div>
    <?php else: ?>
        <div class="alert">
            Tu cuenta se creó, pero no pudimos enviar el correo (<?= h($enviado["resultado"]["error"]) ?>).
            Puedes pedir un nuevo envío desde <a href="reenviar_verificacion.php">aquí</a>
            o escribir a <a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>"><?= h(VET_EMAIL_SOPORTE) ?></a>.
        </div>
    <?php endif; ?>

    <p class="muted">El enlace vence en 24 horas.</p>
    <div class="form-actions"><a class="btn secondary" href="login.php">Ir a iniciar sesión</a></div>
</div>

<?php else: ?>
<div class="login-box form-card">
    <div class="hero-kicker">Nuevo usuario</div>
    <h2>Crear cuenta</h2>
    <p class="muted">
        Las cuentas creadas desde aquí son de cliente. Te enviaremos un correo para
        verificar tu dirección de email.
    </p>

    <?php if ($error): ?>
        <div class="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label for="nombre">Nombre completo</label>
            <input id="nombre" name="nombre" value="<?= h($nombre) ?>" required maxlength="100">
        </div>

        <br>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="<?= h($email) ?>" required maxlength="150">
        </div>

        <br>

        <div class="field">
            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
        </div>

        <br>

        <div class="field">
            <label for="password2">Repetir contraseña</label>
            <input id="password2" type="password" name="password2" required minlength="8" autocomplete="new-password">
        </div>

        <br>

        <label class="check-line">
            <input type="checkbox" name="acepto_privacidad" value="1" required>
            <span>Leí y acepto la <a href="privacidad.php" target="_blank">política de privacidad</a>.</span>
        </label>

        <br>

        <?= captcha_html() ?>

        <div class="form-actions">
            <button class="btn" type="submit">Registrarme</button>
            <a class="btn secondary" href="login.php">Volver</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php require "includes/footer.php"; ?>
