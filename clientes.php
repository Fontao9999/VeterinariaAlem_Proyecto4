<?php
// ============================================================
// MI PERFIL (clientes): cada cliente ve y edita SOLO sus datos.
// Los administradores son redirigidos al listado de Clientes.
// ============================================================
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/clientes.php");
    exit;
}
if (es_empleado()) {
    header("Location: empleado/perfil.php");
    exit;
}

$id_cliente = dueno_id_actual($conexion);
$error = "";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";
    } else {
        $nombre = trim($_POST["nombre"] ?? "");
        $cedula = trim($_POST["cedula"] ?? "");
        $tel = trim($_POST["telefono"] ?? "");
        $dir = trim($_POST["direccion"] ?? "");
        if ($nombre === "") {
            $error = "El nombre no puede estar vacío.";
        } else {
            $stmt = $conexion->prepare("UPDATE clientes SET nombre = ?, cedula = ?, telefono = ?, direccion = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre, $cedula, $tel, $dir, $id_cliente);
            $stmt->execute();
            $u = $conexion->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
            $uid = (int)$_SESSION["usuario_id"];
            $u->bind_param("si", $nombre, $uid);
            $u->execute();
            $_SESSION["nombre"] = $nombre;
            $msg = "Tus datos se guardaron correctamente.";
        }
    }
}

$stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();

$q = $conexion->prepare("SELECT email, email_verificado FROM usuarios WHERE id = ?");
$uid = (int)$_SESSION["usuario_id"];
$q->bind_param("i", $uid);
$q->execute();
$u = $q->get_result()->fetch_assoc();

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Mi cuenta</div>
    <h2>Mi perfil</h2>
    <p>Estos son tus datos personales. Nadie más que tú y el personal de la veterinaria puede verlos.</p>
</section>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

<div class="form-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label>Nombre completo</label><input name="nombre" value="<?= h($d["nombre"]) ?>" required maxlength="100"></div>
            <div class="field"><label>Correo electrónico (no editable)</label>
                <input value="<?= h($u["email"]) ?>" disabled>
                <span class="muted"><?= $u["email_verificado"] ? "✅ Verificado" : "⚠ Sin verificar" ?></span></div>
            <div class="field"><label>Cédula / RUT</label><input name="cedula" value="<?= h($d["cedula"]) ?>" maxlength="30"></div>
            <div class="field"><label>Teléfono</label><input name="telefono" value="<?= h($d["telefono"]) ?>" maxlength="30"></div>
            <div class="field full"><label>Dirección</label><input name="direccion" value="<?= h($d["direccion"]) ?>" maxlength="200"></div>
        </div>
        <div class="form-actions"><button class="btn" type="submit">Guardar cambios</button></div>
    </form>
</div>

<div class="info-box">
    🔒 <strong>Tus derechos:</strong> puedes consultar, corregir o pedir la eliminación de tus datos.
    Para eliminar tu cuenta escribe a <a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>?subject=Eliminar%20mi%20cuenta"><?= h(VET_EMAIL_SOPORTE) ?></a>.
    Más información en la <a href="privacidad.php">política de privacidad</a>.
</div>
<?php require "includes/footer.php"; ?>
