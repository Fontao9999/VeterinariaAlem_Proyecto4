<?php
require "includes/auth.php";
require "includes/header.php";
$wa = "https://wa.me/" . ltrim(VET_TELEFONO_LINK, "+");
?>
<section class="hero">
    <div class="hero-kicker">Contacto</div>
    <h2>Comunícate con la veterinaria 📞</h2>
    <p>Estamos para ayudarte con consultas, urgencias y cambios de turno.</p>
</section>

<section class="grid grid-3">
    <article class="card">
        <div class="feature-icon">📞</div>
        <h3>Llámanos</h3>
        <p><a class="telefono-grande" href="tel:<?= h(VET_TELEFONO_LINK) ?>"><?= h(VET_TELEFONO) ?></a></p>
        <p>Consultas, urgencias y citas por teléfono.</p>
    </article>

    <article class="card">
        <div class="feature-icon">💬</div>
        <h3>WhatsApp</h3>
        <p><a class="btn small" href="<?= h($wa) ?>" target="_blank" rel="noopener">Escribir por WhatsApp</a></p>
        <p>Mismo número: <?= h(VET_TELEFONO) ?></p>
    </article>

    <article class="card">
        <div class="feature-icon">🛟</div>
        <h3>Soporte del sitio</h3>
        <p><a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>"><?= h(VET_EMAIL_SOPORTE) ?></a></p>
        <p>Problemas con tu cuenta, compras o facturas. <a href="soporte.php">Ver más</a></p>
    </article>
</section>

<div class="info-box section-gap">
    📅 <strong>Horario de citas:</strong> <?= h(HORA_APERTURA) ?> a <?= h(HORA_CIERRE) ?>, de lunes a sábado.
    Puedes <a href="citas.php">agendar tu turno online</a> en cualquier momento.
</div>
<?php require "includes/footer.php"; ?>
