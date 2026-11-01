<?php
// ============================================================
// ELIMINAR REGISTRO
// Solo el administrador puede eliminar.
// Se pide CAPTCHA antes de ejecutar DELETE.
// ============================================================
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_login();

$permitidas = [
    "productos","servicios","empleados","clientes",
    "mascotas","citas","historial","usuarios"
];

$tabla = $_GET["tabla"] ?? "";
$id = (int)($_GET["id"] ?? 0);
$error = "";

if (!in_array($tabla, $permitidas, true) || $id < 1) {
    die("Datos inválidos.");
}

if (!puede($tabla, "baja")) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } elseif ($tabla === "usuarios" && $id === (int)$_SESSION["usuario_id"]) {
        $error = "No puedes eliminar tu propio usuario mientras estás conectado.";
    } else {
        $stmt = $conexion->prepare("DELETE FROM `$tabla` WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $destino = $tabla === "usuarios" ? "usuarios.php" : $tabla . ".php";
            header("Location: " . $destino);
            exit;
        }

        $error = "No se pudo eliminar. Puede existir información relacionada con este registro.";
    }
}

$cap = nuevo_captcha();

require "../includes/header.php";
?>

<section class="hero">
    <div class="hero-kicker"><?= es_admin() ? "Administración" : "Empleado" ?> · Seguridad</div>
    <h2>Confirmar eliminación</h2>
    <p>Esta acción elimina el registro de <strong><?= htmlspecialchars($tabla) ?></strong>.</p>
</section>

<div class="form-card">
    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="info-box">
        ⚠️ La eliminación puede fallar si existen otros registros relacionados
        mediante claves foráneas.
    </div>

    <form method="post">
        <?= captcha_html() ?>

        <div class="form-actions">
            <button class="btn danger" type="submit">Eliminar registro</button>
            <a class="btn secondary" href="<?= $tabla === "usuarios" ? "usuarios.php" : htmlspecialchars($tabla) . ".php" ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
