<?php
// ============================================================
// MIS FACTURAS (cliente) - solo muestra las facturas propias.
// ============================================================
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/facturas.php");
    exit;
}
if (es_empleado()) {
    header("Location: empleado/index.php");
    exit;
}

$uid = (int)$_SESSION["usuario_id"];
$stmt = $conexion->prepare("SELECT id, numero, fecha, total, metodo_pago FROM facturas WHERE id_usuario = ? ORDER BY fecha DESC, id DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$r = $stmt->get_result();

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Compras</div>
    <h2>Mis facturas 🧾</h2>
    <p>Aquí quedan guardadas las facturas electrónicas de tus compras en la tienda. Solo tú puedes verlas.</p>
</section>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Número</th><th>Fecha</th><th>Forma de pago</th><th>Total</th><th></th></tr>
            <?php $hay = false; while ($x = $r->fetch_assoc()): $hay = true; ?>
                <tr>
                    <td><?= h($x["numero"]) ?></td>
                    <td><?= h(date("d/m/Y H:i", strtotime($x["fecha"]))) ?></td>
                    <td><?= h($x["metodo_pago"]) ?></td>
                    <td><?= dinero($x["total"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="factura.php?id=<?= (int)$x["id"] ?>">Ver</a>
                        <a class="btn secondary small" href="factura.php?id=<?= (int)$x["id"] ?>&descargar=1">Descargar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$hay): ?>
                <tr><td colspan="5">Todavía no tienes compras. <a href="productos.php">Ir a la tienda</a></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php require "includes/footer.php"; ?>
