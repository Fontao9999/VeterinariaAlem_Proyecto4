<?php
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (($_SESSION["rol"] ?? "") === "admin") {
    header("Location: admin/historial.php");
    exit;
}
if (es_empleado()) {
    header("Location: admin/historial.php");
    exit;
}

require "includes/header.php";

// Cada cliente ve SOLO el historial de sus propias mascotas (privacidad).
$id_cliente = dueno_id_actual($conexion);

$sql = "SELECT h.*, m.nombre AS mascota, v.nombre AS veterinario
        FROM historial h
        JOIN mascotas m ON m.id = h.id_mascota
        JOIN empleados v ON v.id = h.id_empleado
        WHERE m.id_cliente = ?
        ORDER BY h.fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$r = $stmt->get_result();
?>
<section class="hero">
    <div class="hero-kicker">Información médica</div>
    <h2>Historial clínico</h2>
    <p>Registro de consultas, diagnósticos y tratamientos de tus mascotas. Solo tú puedes verlo.</p>
</section>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Fecha</th><th>Mascota</th><th>Veterinario</th><th>Diagnóstico</th><th>Tratamiento</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["fecha"]) ?></td>
                    <td><?= htmlspecialchars($x["mascota"]) ?></td>
                    <td><?= htmlspecialchars($x["veterinario"]) ?></td>
                    <td><?= htmlspecialchars($x["diagnostico"]) ?></td>
                    <td><?= htmlspecialchars($x["tratamiento"]) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "includes/footer.php"; ?>