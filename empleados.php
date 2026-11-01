<?php
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (($_SESSION["rol"] ?? "") === "admin") {
    header("Location: admin/empleados.php");
    exit;
}

require "includes/header.php";
$r = $conexion->query("SELECT * FROM empleados ORDER BY id DESC");
?>
<section class="hero">
    <div class="hero-kicker">Equipo profesional</div>
    <h2>empleados</h2>
    <p>Profesionales registrados en la veterinaria.</p>
</section>

<div class="grid">
    <?php while ($x = $r->fetch_assoc()): ?>
        <article class="card">
            <div class="feature-icon">👨‍⚕️</div>
            <h3><?= htmlspecialchars($x["nombre"]) ?></h3>
            <p><strong>Especialidad:</strong> <?= htmlspecialchars($x["especialidad"]) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($x["telefono"]) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($x["email"]) ?></p>
        </article>
    <?php endwhile; ?>
</div>
<?php require "includes/footer.php"; ?>