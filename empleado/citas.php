<?php
// ============================================================
// MIS CITAS (empleado): solo LISTAR las citas que tiene asignadas.
// El administrador ve todas.
// ============================================================
require "../includes/auth.php";
require "../conexion.php";
exigir_empleado();

$id_emp = es_empleado() ? empleado_id_actual($conexion) : 0;
$ver = $_GET["ver"] ?? "proximas";
$sql = "SELECT t.id, t.fecha, t.hora, t.motivo, t.estado, m.nombre AS mascota, c.nombre AS cliente,
               c.telefono, s.nombre AS servicio
        FROM citas t
        JOIN mascotas m ON m.id = t.id_mascota
        JOIN clientes c ON c.id = t.id_cliente
        JOIN servicios s ON s.id = t.id_servicio
        WHERE 1=1";
if ($id_emp > 0) $sql .= " AND t.id_empleado = ?";
if ($ver === "todas") $sql .= " ORDER BY t.fecha DESC, t.hora DESC";
else $sql .= " AND TIMESTAMP(t.fecha, t.hora) >= CURDATE() ORDER BY t.fecha, t.hora";
$stmt = $conexion->prepare($sql);
if ($id_emp > 0) $stmt->bind_param("i", $id_emp);
$stmt->execute();
$r = $stmt->get_result();

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Empleado · Agenda</div>
    <h2>Mis citas</h2>
    <p>Listado de las citas que tienes asignadas.</p>
</section>

<div class="form-actions">
    <a class="btn <?= $ver === "todas" ? "secondary" : "" ?>" href="citas.php">Próximas</a>
    <a class="btn <?= $ver === "todas" ? "" : "secondary" ?>" href="citas.php?ver=todas">Todas</a>
    <a class="btn secondary" href="index.php">Volver al panel</a>
</div>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Fecha</th><th>Hora</th><th>Mascota</th><th>Dueño</th><th>Teléfono</th><th>Servicio</th><th>Motivo</th><th>Estado</th></tr>
            <?php if ($r->num_rows > 0): ?>
                <?php while ($x = $r->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($x["fecha"]) ?></td>
                        <td><?= h(substr($x["hora"], 0, 5)) ?></td>
                        <td><?= h($x["mascota"]) ?></td>
                        <td><?= h($x["cliente"]) ?></td>
                        <td><?= h($x["telefono"]) ?></td>
                        <td><?= h($x["servicio"]) ?></td>
                        <td><?= h($x["motivo"]) ?></td>
                        <td><span class="badge <?= $x["estado"] === "Cancelado" ? "block" : "" ?>"><?= h($x["estado"]) ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No hay citas para mostrar.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require "../includes/footer.php"; ?>
