<?php
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_permiso("historial", "listar");

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "agregar") {
    if (!puede("historial", "alta")) {
        $error = "No tienes permiso para agregar.";
    } elseif (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $id_mascota = (int)($_POST["id_mascota"] ?? 0);
        $id_empleado = (int)($_POST["id_empleado"] ?? 0);
        if (es_empleado()) $id_empleado = empleado_id_actual($conexion);
        $fecha = $_POST["fecha"] ?? "";
        $diagnostico = trim($_POST["diagnostico"] ?? "");
        $tratamiento = trim($_POST["tratamiento"] ?? "");
        $observaciones = trim($_POST["observaciones"] ?? "");

        $stmt = $conexion->prepare(
            "INSERT INTO historial
            (id_mascota, id_empleado, fecha, diagnostico, tratamiento, observaciones)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iissss",
            $id_mascota, $id_empleado, $fecha,
            $diagnostico, $tratamiento, $observaciones
        );

        if ($stmt->execute()) $ok = "Historia clínica agregada correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$m = $conexion->query("SELECT id, nombre FROM mascotas ORDER BY nombre");
// El empleado solo puede registrar historias a su propio nombre.
$id_propio = es_empleado() ? empleado_id_actual($conexion) : 0;
$v = $conexion->query("SELECT id, nombre FROM empleados" . (es_empleado() ? " WHERE id = " . (int)$id_propio : "") . " ORDER BY nombre");
$r = $conexion->query(
    "SELECT h.*, m.nombre AS mascota, v.nombre AS veterinario
     FROM historial h
     JOIN mascotas m ON m.id = h.id_mascota
     JOIN empleados v ON v.id = h.id_empleado
     ORDER BY h.fecha DESC"
);

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker"><?= es_admin() ? "Administración" : "Empleado" ?> · Salud</div>
    <h2>Historial clínico</h2>
    <p>Registra diagnósticos, tratamientos y observaciones de cada mascota.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Nueva historia clínica</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">

        <div class="form-grid">
            <div class="field"><label>Mascota</label>
                <select name="id_mascota" required>
                    <?php while ($x = $m->fetch_assoc()): ?>
                        <option value="<?= $x["id"] ?>"><?= htmlspecialchars($x["nombre"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field"><label>Veterinario</label>
                <select name="id_empleado" required>
                    <?php while ($x = $v->fetch_assoc()): ?>
                        <option value="<?= $x["id"] ?>"><?= htmlspecialchars($x["nombre"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field"><label>Fecha</label><input type="date" name="fecha" required></div>
            <div class="field full"><label>Diagnóstico</label><textarea name="diagnostico" required></textarea></div>
            <div class="field full"><label>Tratamiento</label><textarea name="tratamiento"></textarea></div>
            <div class="field full"><label>Observaciones</label><textarea name="observaciones"></textarea></div>
        </div>

        <br>
        <?= captcha_html() ?>

        <div class="form-actions"><button class="btn" type="submit">Guardar historia</button></div>
    </form>
</div>

<div class="table-card">
    <h3>Historias registradas</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Fecha</th><th>Mascota</th><th>Veterinario</th><th>Diagnóstico</th><th>Tratamiento</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["fecha"]) ?></td>
                    <td><?= htmlspecialchars($x["mascota"]) ?></td>
                    <td><?= htmlspecialchars($x["veterinario"]) ?></td>
                    <td><?= htmlspecialchars($x["diagnostico"]) ?></td>
                    <td><?= htmlspecialchars($x["tratamiento"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=historial&id=<?= $x["id"] ?>">Modificar</a>
                        <?php if (puede("historial", "baja")): ?><a class="btn danger small" href="eliminar.php?tabla=historial&id=<?= $x["id"] ?>">Eliminar</a><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
