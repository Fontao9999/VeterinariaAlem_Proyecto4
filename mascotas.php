<?php
// ============================================================
// MASCOTAS (clientes): ver y registrar SOLO sus propias mascotas.
// ============================================================
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/mascotas.php");
    exit;
}
if (es_empleado()) {
    header("Location: admin/mascotas.php");
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
        $especie = trim($_POST["especie"] ?? "");
        $raza = trim($_POST["raza"] ?? "");
        $sexo = in_array($_POST["sexo"] ?? "", ["Macho", "Hembra"], true) ? $_POST["sexo"] : "";
        $nac = $_POST["fecha_nacimiento"] ?? "";
        $peso = $_POST["peso"] ?? "";

        if ($nombre === "" || $especie === "") {
            $error = "El nombre y la especie son obligatorios.";
        } elseif ($nac !== "" && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nac) || $nac > date("Y-m-d"))) {
            $error = "La fecha de nacimiento no es válida.";
        } elseif ($peso !== "" && (!is_numeric($peso) || $peso < 0 || $peso > 999)) {
            $error = "El peso no es válido.";
        } else {
            $nac_sql = $nac === "" ? null : $nac;
            $peso_sql = $peso === "" ? null : (float)$peso;
            $stmt = $conexion->prepare(
                "INSERT INTO mascotas(nombre, especie, raza, sexo, fecha_nacimiento, peso, id_cliente)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssssdi", $nombre, $especie, $raza, $sexo, $nac_sql, $peso_sql, $id_cliente);
            $stmt->execute();
            $msg = "Mascota registrada correctamente.";
        }
    }
}

$stmt = $conexion->prepare("SELECT * FROM mascotas WHERE id_cliente = ? ORDER BY nombre");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$r = $stmt->get_result();

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Mis pacientes</div>
    <h2>Mis mascotas</h2>
    <p>Registra a tus mascotas para poder agendar citas. Solo tú ves esta información.</p>
</section>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Nombre</th><th>Especie</th><th>Raza</th><th>Sexo</th><th>Nacimiento</th><th>Peso</th></tr>
            <?php $hay = false; while ($x = $r->fetch_assoc()): $hay = true; ?>
                <tr>
                    <td><?= h($x["nombre"]) ?></td>
                    <td><?= h($x["especie"]) ?></td>
                    <td><?= h($x["raza"]) ?></td>
                    <td><?= h($x["sexo"]) ?></td>
                    <td><?= $x["fecha_nacimiento"] ? h(date("d/m/Y", strtotime($x["fecha_nacimiento"]))) : "" ?></td>
                    <td><?= $x["peso"] !== null ? h($x["peso"]) . " kg" : "" ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$hay): ?><tr><td colspan="6">Todavía no registraste mascotas.</td></tr><?php endif; ?>
        </table>
    </div>
</div>

<div class="form-card">
    <h3>Registrar una mascota</h3>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" required maxlength="100"></div>
            <div class="field"><label>Especie (perro, gato...)</label><input name="especie" required maxlength="50"></div>
            <div class="field"><label>Raza</label><input name="raza" maxlength="80"></div>
            <div class="field"><label>Sexo</label>
                <select name="sexo"><option value="">—</option><option>Macho</option><option>Hembra</option></select></div>
            <div class="field"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" max="<?= date("Y-m-d") ?>"></div>
            <div class="field"><label>Peso (kg)</label><input type="number" step="0.01" min="0" name="peso"></div>
        </div>
        <div class="form-actions"><button class="btn" type="submit">Guardar mascota</button></div>
    </form>
</div>
<?php require "includes/footer.php"; ?>
