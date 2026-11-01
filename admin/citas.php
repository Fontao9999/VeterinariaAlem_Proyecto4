<?php
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_admin();

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "agregar") {
    if (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $id_cliente = (int)($_POST["id_cliente"] ?? 0);
        $id_mascota = (int)($_POST["id_mascota"] ?? 0);
        $id_empleado = (int)($_POST["id_empleado"] ?? 0);
        $id_servicio = (int)($_POST["id_servicio"] ?? 0);
        $fecha = $_POST["fecha"] ?? "";
        $hora = $_POST["hora"] ?? "";
        $motivo = trim($_POST["motivo"] ?? "");
        $estado = $_POST["estado"] ?? "Pendiente";

        $stmt = $conexion->prepare(
            "INSERT INTO citas
            (id_cliente, id_mascota, id_empleado, id_servicio, fecha, hora, motivo, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iiiissss",
            $id_cliente, $id_mascota, $id_empleado, $id_servicio,
            $fecha, $hora, $motivo, $estado
        );

        if ($stmt->execute()) $ok = "Turno agregado correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$d = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre");
$m = $conexion->query("SELECT id, nombre FROM mascotas ORDER BY nombre");
$v = $conexion->query("SELECT id, nombre FROM empleados ORDER BY nombre");
$s = $conexion->query("SELECT id, nombre FROM servicios ORDER BY nombre");

$r = $conexion->query(
    "SELECT t.*, m.nombre AS mascota, v.nombre AS veterinario, s.nombre AS servicio
     FROM citas t
     JOIN mascotas m ON m.id = t.id_mascota
     JOIN empleados v ON v.id = t.id_empleado
     JOIN servicios s ON s.id = t.id_servicio
     ORDER BY t.fecha DESC, t.hora DESC"
);

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Agenda</div>
    <h2>citas</h2>
    <p>Programa consultas y relaciona cada turno con mascota, dueño, veterinario y servicio.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Nuevo turno</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-grid">
            <div class="field"><label>Dueño</label>
                <select name="id_cliente" required>
                    <?php while ($x = $d->fetch_assoc()): ?>
                        <option value="<?= $x["id"] ?>"><?= htmlspecialchars($x["nombre"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

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

            <div class="field"><label>Servicio</label>
                <select name="id_servicio" required>
                    <?php while ($x = $s->fetch_assoc()): ?>
                        <option value="<?= $x["id"] ?>"><?= htmlspecialchars($x["nombre"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="field"><label>Fecha</label><input type="date" name="fecha" required></div>
            <div class="field"><label>Hora</label><input type="time" name="hora" required></div>

            <div class="field"><label>Estado</label>
                <select name="estado">
                    <option>Pendiente</option>
                    <option>Confirmado</option>
                    <option>Atendido</option>
                    <option>Cancelado</option>
                </select>
            </div>

            <div class="field full"><label>Motivo</label><textarea name="motivo"></textarea></div>
        </div>

        <br>
        <?= captcha_html() ?>

        <div class="form-actions"><button class="btn" type="submit">Agregar turno</button></div>
    </form>
</div>

<div class="table-card">
    <h3>citas registrados</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Fecha</th><th>Hora</th><th>Mascota</th><th>Veterinario</th><th>Servicio</th><th>Estado</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["fecha"]) ?></td>
                    <td><?= htmlspecialchars($x["hora"]) ?></td>
                    <td><?= htmlspecialchars($x["mascota"]) ?></td>
                    <td><?= htmlspecialchars($x["veterinario"]) ?></td>
                    <td><?= htmlspecialchars($x["servicio"]) ?></td>
                    <td><span class="badge"><?= htmlspecialchars($x["estado"]) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=citas&id=<?= $x["id"] ?>">Modificar</a>
                        <a class="btn danger small" href="eliminar.php?tabla=citas&id=<?= $x["id"] ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
