<?php
require "includes/auth.php";
require "includes/header.php";
$asunto = rawurlencode("Soporte - Veterinaria Alem");
?>
<section class="hero">
    <div class="hero-kicker">Ayuda</div>
    <h2>Soporte 🛟</h2>
    <p>¿Tienes un problema con el sitio, tu cuenta, una compra o una factura? Escríbenos y te respondemos lo antes posible.</p>
    <div class="hero-actions">
        <a class="btn" href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>?subject=<?= $asunto ?>">✉ <?= h(VET_EMAIL_SOPORTE) ?></a>
        <a class="btn secondary" href="tel:<?= h(VET_TELEFONO_LINK) ?>">📞 <?= h(VET_TELEFONO) ?></a>
    </div>
</section>

<div class="info-box">
    Para ayudarte más rápido, cuéntanos tu <strong>correo de registro</strong> y, si es por una compra,
    el <strong>número de factura</strong>. Nunca te pediremos tu contraseña.
</div>

<div class="section-title"><h2>Preguntas frecuentes</h2></div>

<div class="faq">
    <details open><summary>No me llegó el correo de verificación</summary>
        <p>Revisa la carpeta de spam. Si no está, puedes <a href="reenviar_verificacion.php">pedir un nuevo enlace</a>
        (se puede pedir uno por minuto). El enlace dura 24 horas.</p></details>
    <details><summary>¿Cómo agendo un turno?</summary>
        <p>Inicia sesión, registra a tu mascota en <em>Mascotas</em> y entra a <em>citas</em>: busca un horario libre
        por servicio, veterinario o fecha y toca <em>Reservar</em>.</p></details>
    <details><summary>¿Cómo cancelo o cambio un turno?</summary>
        <p>En <em>citas &gt; Mis citas</em> puedes cancelar los citas futuros. Para cambiarlo, cancela y reserva uno nuevo,
        o llámanos al <?= h(VET_TELEFONO) ?>.</p></details>
    <details><summary>¿Dónde veo mi factura?</summary>
        <p>En <a href="facturas.php">Facturas</a> encuentras todas tus compras y puedes descargarlas. También te llega por correo.</p></details>
    <details><summary>Quiero borrar mi cuenta y mis datos</summary>
        <p>Escríbenos a <a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>?subject=Eliminar%20mi%20cuenta"><?= h(VET_EMAIL_SOPORTE) ?></a>.
        Lee la <a href="privacidad.php">política de privacidad</a> para conocer tus derechos.</p></details>
</div>
<?php require "includes/footer.php"; ?>
