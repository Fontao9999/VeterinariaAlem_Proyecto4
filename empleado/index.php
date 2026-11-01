<?php
// ============================================================
// PANEL DEL EMPLEADO
// Resumen de su jornada: citas de hoy, próximas y pacientes.
// ============================================================
require "../includes/auth.php";
require "../conexion.php";
exigir_empleado();

$id_emp = es_empleado() ? empleado_id_actual($conexion) : 0;

function contar($conexion, $sql, $id_emp) {
    $stmt = $conexion->prepare($sql);
    if ($id_emp > 0) $stmt->bind_param("i", $id_emp);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

$filtro = $id_emp > 0 ? " AND id_empleado = ?" : "";
$hoy = contar($conexion, "SELECT COUNT(*) FROM citas WHERE fecha = CURDATE() AND estado <> 'Cancelado'" . $filtro, $id_emp);
$prox = contar($conexion, "SELECT COUNT(*) FROM citas WHERE TIMESTAMP(fecha, hora) >= NOW() AND estado IN ('Pendiente','Confirmado')" . $filtro, $id_emp);
$pend = contar($conexion, "SELECT COUNT(*) FROM citas WHERE estado = 'Pendiente'" . $filtro, $id_emp);
$hist = contar($conexion, "SELECT COUNT(*) FROM historial WHERE 1=1" . $filtro, $id_emp);

$sql = "SELECT t.hora, t.motivo, t.estado, m.nombre AS mascota, c.nombre AS cliente, s.nombre AS servicio
        FROM citas t
        JOIN mascotas m ON m.id = t.id_mascota
        JOIN clientes c ON c.id = t.id_cliente
        JOIN servicios s ON s.id = t.id_servicio
        WHERE t.fecha = CURDATE() AND t.estado <> 'Cancelado'" . ($id_emp > 0 ? " AND t.id_empleado = ?" : "") . "
        ORDER BY t.hora";
$stmt = $conexion->prepare($sql);
if ($id_emp > 0) $stmt->bind_param("i", $id_emp);
$stmt->execute();
$agenda = $stmt->get_result();

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Empleado</div>
    <h2>Panel de empleado 🩺</h2>
    <p>Hola, <?= h($_SESSION["nombre"]) ?>. Desde aquí consultas tus citas y gestionas mascotas, historial clínico, servicios y productos.</p>
</section>

<?php if (es_empleado() && $id_emp === 0): ?>
    <div class="alert">
        Tu cuenta todavía no está vinculada a una ficha de empleado, por eso no se muestran citas.
        Pídele al administrador que la vincule desde <em>Usuarios</em> o <em>Empleados</em>.
    </div>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card"><div class="stat-label">📅 Citas de hoy</div><div class="stats"><?= $hoy ?></div></article>
    <article class="stat-card"><div class="stat-label">⏭ Próximas citas</div><div class="stats"><?= $prox ?></div></article>
    <article class="stat-card"><div class="stat-label">⏳ Pendientes</div><div class="stats"><?= $pend ?></div></article>
    <article class="stat-card"><div class="stat-label">🩺 Historias registradas</div><div class="stats"><?= $hist ?></div></article>
</section>

<div class="section-title">
    <h2>Agenda de hoy</h2>
    <p><?= date("d/m/Y") ?></p>
</div>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Hora</th><th>Mascota</th><th>Dueño</th><th>Servicio</th><th>Motivo</th><th>Estado</th></tr>
            <?php if ($agenda->num_rows > 0): ?>
                <?php while ($x = $agenda->fetch_assoc()): ?>
                    <tr>
                        <td><?= h(substr($x["hora"], 0, 5)) ?></td>
                        <td><?= h($x["mascota"]) ?></td>
                        <td><?= h($x["cliente"]) ?></td>
                        <td><?= h($x["servicio"]) ?></td>
                        <td><?= h($x["motivo"]) ?></td>
                        <td><span class="badge"><?= h($x["estado"]) ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No hay citas para hoy.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="section-title">
    <h2>Mis módulos</h2>
    <p>Selecciona qué quieres hacer.</p>
</div>

<section class="grid">
    <article class="card">
        <div class="feature-icon">📅</div>
        <h3>Mis citas</h3>
        <p>Consulta tu agenda de citas.</p><br>
        <a class="btn" href="citas.php">Abrir módulo</a>
    </article>
    <article class="card">
        <div class="feature-icon">🐾</div>
        <h3>Mascotas</h3>
        <p>Alta, baja, modificación y listado de mascotas.</p><br>
        <a class="btn" href="../admin/mascotas.php">Abrir módulo</a>
    </article>
    <article class="card">
        <div class="feature-icon">🩺</div>
        <h3>Historial clínico</h3>
        <p>Alta, baja, modificación y listado de historias.</p><br>
        <a class="btn" href="../admin/historial.php">Abrir módulo</a>
    </article>
    <article class="card">
        <div class="feature-icon">✂️</div>
        <h3>Servicios</h3>
        <p>Alta, baja, modificación y listado de servicios.</p><br>
        <a class="btn" href="../admin/servicios.php">Abrir módulo</a>
    </article>
    <article class="card">
        <div class="feature-icon">🛒</div>
        <h3>Tienda · Productos</h3>
        <p>Alta, modificación y listado de productos.</p><br>
        <a class="btn" href="../admin/productos.php">Abrir módulo</a>
    </article>
    <article class="card">
        <div class="feature-icon">👤</div>
        <h3>Perfil</h3>
        <p>Modifica tus datos de contacto.</p><br>
        <a class="btn" href="perfil.php">Abrir módulo</a>
    </article>
</section>

<?php require "../includes/footer.php"; ?>
