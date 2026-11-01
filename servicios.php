<?php
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (es_personal()) {
    header("Location: admin/servicios.php");
    exit;
}

require "includes/header.php";
$r = $conexion->query("SELECT * FROM servicios ORDER BY id DESC");
?>
<section class="hero">
    <div class="hero-kicker">Atención veterinaria</div>
    <h2>Servicios</h2>
    <p>Servicios disponibles en Veterinaria Alem.</p>
</section>

<div class="grid">
    <?php while ($x = $r->fetch_assoc()): ?>
        <article class="card">
            <div class="feature-icon">🩺</div>
            <h3><?= htmlspecialchars($x["nombre"]) ?></h3>
            <p><?= htmlspecialchars($x["descripcion"]) ?></p>
            <p><strong>Precio: $<?= htmlspecialchars($x["precio"]) ?></strong></p>
        </article>
    <?php endwhile; ?>
</div>
<?php require "includes/footer.php"; ?>