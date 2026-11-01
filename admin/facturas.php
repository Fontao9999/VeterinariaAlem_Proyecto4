<?php
require "../includes/auth.php";
require "../conexion.php";
exigir_admin();

$r = $conexion->query(
    "SELECT f.id, f.numero, f.fecha, f.cliente_nombre, f.cliente_email, f.total, f.metodo_pago
     FROM facturas f
     ORDER BY f.fecha DESC, f.id DESC"
);

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Ventas</div>
    <h2>Facturas electrónicas</h2>
    <p>Listado de todas las compras realizadas en la tienda online. Las facturas no se pueden modificar ni eliminar.</p>
</section>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Número</th><th>Fecha</th><th>Cliente</th><th>Correo</th><th>Pago</th><th>Total</th><th></th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= h($x["numero"]) ?></td>
                    <td><?= h(date("d/m/Y H:i", strtotime($x["fecha"]))) ?></td>
                    <td><?= h($x["cliente_nombre"]) ?></td>
                    <td><?= h($x["cliente_email"]) ?></td>
                    <td><?= h($x["metodo_pago"]) ?></td>
                    <td><?= dinero($x["total"]) ?></td>
                    <td class="actions"><a class="btn small" href="../factura.php?id=<?= (int)$x["id"] ?>">Ver</a></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
