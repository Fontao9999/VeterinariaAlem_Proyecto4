<?php
require "includes/auth.php";
require "includes/header.php";
?>

<section class="hero">
    <div class="hero-kicker">Sistema de gestión veterinaria</div>
    <h1>Bienvenido a Veterinaria Alem 🐾</h1>
    

    <div class="hero-actions">
        <?php if (!usuario_logueado()): ?>
            <a class="btn" href="login.php">Iniciar sesión</a>
            <a class="btn secondary" href="registro.php">Crear cuenta</a>
            <a class="btn secondary" href="contacto.php">Contacto</a>
        <?php else: ?>
            <?php if (es_empleado()): ?>
                <a class="btn" href="empleado/index.php">Ir al panel de empleado</a>
            <?php else: ?>
                <a class="btn" href="citas.php">Agendar / ver citas</a>
            <?php endif; ?>
            <?php if (!es_personal()): ?><a class="btn secondary" href="productos.php">Ir a la tienda</a><?php endif; ?>
            <?php if (($_SESSION["rol"] ?? "") === "admin"): ?>
                <a class="btn secondary" href="admin/index.php">Ir al administrador</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<div class="section-title">
    <h2>Funciones principales</h2>
    <p>Todo lo necesario para representar una veterinaria en un proyecto escolar.</p>
</div>

<section class="grid">
    <article class="card">
        <div class="feature-icon">📅</div>
        <h3>citas</h3>
        <p>Organiza las consultas, fechas, horarios, servicios y empleados.</p>
    </article>

    <article class="card">
        <div class="feature-icon">🐶</div>
        <h3>Mascotas</h3>
        <p>Guarda los datos de las mascotas y relaciónalas con sus Clientes.</p>
    </article>

    <article class="card">
        <div class="feature-icon">🩺</div>
        <h3>Historial clínico</h3>
        <p>Registra diagnósticos, tratamientos y observaciones.</p>
    </article>

    <article class="card">
        <div class="feature-icon">💊</div>
        <h3>Productos</h3>
        <p>Controla productos, categorías, precios y cantidad disponible.</p>
    </article>
</section>

<section class="grid section-gap">
    <article class="card">
        <div class="feature-icon">🛒</div>
        <h3>Tienda online</h3>
        <p>Compra productos desde el sitio y recibe tu factura electrónica por correo.</p>
    </article>

    <article class="card">
        <div class="feature-icon">👨‍⚕️</div>
        <h3>empleados</h3>
        <p>Consulta profesionales, especialidades, teléfonos y correos.</p>
    </article>

    <article class="card">
        <div class="feature-icon">🧑‍💼</div>
        <h3>Clientes</h3>
        <p>Administra los datos de los clientes de la veterinaria.</p>
    </article>

    <article class="card">
        <div class="feature-icon">✂️</div>
        <h3>Servicios</h3>
        <p>Consulta servicios como consulta, vacunación y peluquería.</p>
    </article>

    <article class="card">
        <div class="feature-icon">🔐</div>
        <h3>Usuarios y permisos</h3>
        <p>El administrador gestiona el sistema y el empleado atiende sus citas y pacientes.</p>
    </article>
</section>

<section class="contacto-franja">
    <div>
        <strong>¿Necesitas comunicarte con la veterinaria?</strong><br>
        📞 <a href="tel:<?= h(VET_TELEFONO_LINK) ?>"><?= h(VET_TELEFONO) ?></a>
        &nbsp;·&nbsp; 🛟 Soporte: <a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>"><?= h(VET_EMAIL_SOPORTE) ?></a>
    </div>
    <a class="btn small" href="contacto.php">Ver contacto</a>
</section>

<?php require "includes/footer.php"; ?>
