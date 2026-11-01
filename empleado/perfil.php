<?php
// ============================================================
// MI PERFIL (empleado): puede MODIFICAR sus datos de contacto.
// ============================================================
require "../includes/auth.php";
require "../conexion.php";
exigir_empleado();

if (!es_empleado()) {   // el administrador usa su propio módulo
    header("Location: ../admin/empleados.php");
    exit;
}

$id_emp = empleado_id_actual($conexion);
$uid = (int)$_SESSION["usuario_id"];
$error = "";
$msg = "";

if ($id_emp === 0) {
    $error = "Tu cuenta todavía no está vinculada a una ficha de empleado. Pídele al administrador que la vincule.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $tel = trim($_POST["telefono"] ?? "");
    $esp = trim($_POST["especialidad"] ?? "");

    if (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";
    } elseif ($nombre === "") {
        $error = "El nombre no puede estar vacío.";
    } else {
        $conexion->begin_transaction();
        $stmt = $conexion->prepare("UPDATE empleados SET nombre = ?, telefono = ?, especialidad = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $tel, $esp, $id_emp);
        $stmt->execute();
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $uid);
        $stmt->execute();
        $conexion->commit();
        $_SESSION["nombre"] = $nombre;
        $msg = "Perfil actualizado.";
    }
}

$e = ["nombre" => "", "telefono" => "", "especialidad" => "", "email" => ""];
if ($id_emp > 0) {
    $stmt = $conexion->prepare("SELECT nombre, telefono, especialidad, email FROM empleados WHERE id = ?");
    $stmt->bind_param("i", $id_emp);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
}

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Empleado · Cuenta</div>
    <h2>Mi perfil</h2>
    <p>Actualiza tus datos de contacto.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" value="<?= h($e["nombre"]) ?>" required maxlength="100"></div>
            <div class="field"><label>Teléfono</label><input name="telefono" value="<?= h($e["telefono"]) ?>" maxlength="30"></div>
            <div class="field"><label>Especialidad</label><input name="especialidad" value="<?= h($e["especialidad"]) ?>" maxlength="100"></div>
            <div class="field"><label>Correo (no editable)</label><input value="<?= h($e["email"]) ?>" disabled></div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn secondary" href="index.php">Volver al panel</a>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
