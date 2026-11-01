<?php
require "../includes/auth.php";
require "../conexion.php";
exigir_admin();

$tablas = [
    "usuarios" => "Usuarios",
    "clientes" => "Clientes",
    "mascotas" => "Mascotas",
    "empleados" => "Empleados",
    "citas" => "Citas",
    "historial" => "Historial",
    "productos" => "Productos",
    "proveedores" => "Proveedores",
    "servicios" => "Servicios",
    "facturas" => "Facturas"
];

$iconos = [
    "usuarios"=>"👥","clientes"=>"🧑‍💼","mascotas"=>"🐾","empleados"=>"👨‍⚕️",
    "citas"=>"📅","historial"=>"🩺","productos"=>"📦","proveedores"=>"🚚","servicios"=>"✂️","facturas"=>"🧾"
];

require "../includes/header.php";
?>

<section class="hero">
    <div class="hero-kicker">Administración</div>
    <h2>Panel de administrador ⚙️</h2>
    <p>
        Desde este panel se pueden realizar altas, bajas, modificaciones y listados.
        Las acciones de agregar, modificar y eliminar piden confirmar «No soy un robot».
    </p>
</section>

<section class="stats-grid">
    <?php foreach ($tablas as $tabla => $nombre): ?>
        <?php
        $consulta = $conexion->query("SELECT COUNT(*) AS cantidad FROM `$tabla`");
        $cantidad = $consulta->fetch_assoc()["cantidad"];
        ?>
        <article class="stat-card">
            <div class="stat-label"><?= $iconos[$tabla] ?> <?= htmlspecialchars($nombre) ?></div>
            <div class="stats"><?= htmlspecialchars($cantidad) ?></div>
        </article>
    <?php endforeach; ?>
</section>

<div class="section-title">
    <h2>Gestión de la veterinaria</h2>
    <p>Selecciona el módulo que quieres administrar.</p>
</div>

<div class="info-box">
    📧 ¿Los correos no llegan? <a href="probar_correo.php">Probar el envío de correo</a>
</div>

<section class="grid">
    <?php foreach ($tablas as $tabla => $nombre): ?>
        <article class="card">
            <div class="feature-icon"><?= $iconos[$tabla] ?></div>
            <h3><?= htmlspecialchars($nombre) ?></h3>
            <p>Administrar información de <?= strtolower($nombre) ?>.</p>
            <br>
            <a class="btn" href="<?= htmlspecialchars($tabla) === "usuarios" ? "usuarios.php" : htmlspecialchars($tabla).".php" ?>">
                Abrir módulo
            </a>
        </article>
    <?php endforeach; ?>
</section>

<?php require "../includes/footer.php"; ?>
