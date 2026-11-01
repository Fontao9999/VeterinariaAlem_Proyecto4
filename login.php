<?php
require "includes/auth.php";
require "conexion.php";

if (usuario_logueado()) {
    header("Location: index.php");
    exit;
}

$error = "";
$sin_verificar = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass = $_POST["password"] ?? "";

    $stmt = $conexion->prepare(
        "SELECT id, nombre, email, password, rol, activo, email_verificado
         FROM usuarios
         WHERE email = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();

    if ($usuario && $usuario["activo"] && password_verify($pass, $usuario["password"])) {
        if (!$usuario["email_verificado"]) {
            $sin_verificar = $email;
            $error = "Debes verificar tu correo electrónico antes de iniciar sesión. Revisa tu bandeja de entrada (y spam).";
        }
    }

    if ($error === "" && $usuario && $usuario["activo"] && password_verify($pass, $usuario["password"])) {
        session_regenerate_id(true);
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];

        header("Location: " . ($usuario["rol"] === "empleado" ? "empleado/index.php" : "index.php"));
        exit;
    }

    if ($error === "") {
        $error = "Correo o contraseña incorrectos, o usuario bloqueado.";
    }
}

require "includes/header.php";
?>

<div class="login-box form-card">
    <div class="hero-kicker">Acceso al sistema</div>
    <h2>Iniciar sesión</h2>
    <p class="muted">Ingresa tus datos para acceder a Veterinaria Alem.</p>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($sin_verificar): ?>
        <p><a class="btn small" href="reenviar_verificacion.php?email=<?= urlencode($sin_verificar) ?>">Reenviar correo de verificación</a></p>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" required>
        </div>

        <br>

        <div class="field">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Entrar</button>
            <a class="btn secondary" href="index.php">Volver</a>
        </div>
    </form>

    <p class="muted">
        ¿No tienes cuenta?
        <a href="registro.php">Registrarse</a>
    </p>
</div>

<?php require "includes/footer.php"; ?>
