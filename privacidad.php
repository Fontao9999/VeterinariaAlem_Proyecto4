<?php
require "includes/auth.php";
require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Privacidad de los clientes</div>
    <h2>Política de privacidad 🔒</h2>
    <p>Cuidamos los datos de nuestros clientes y de sus mascotas. Aquí explicamos qué guardamos, para qué y cuáles son tus derechos.</p>
</section>

<div class="form-card texto-legal">
    <h3>1. Qué datos guardamos</h3>
    <ul>
        <li><strong>Cuenta:</strong> nombre, correo electrónico y contraseña (la contraseña se guarda cifrada; nadie, ni el personal, puede leerla).</li>
        <li><strong>Perfil:</strong> cédula/RUT, teléfono y dirección, solo si tú los completas.</li>
        <li><strong>Mascotas y salud:</strong> datos de tus mascotas, citas e historial clínico.</li>
        <li><strong>Compras:</strong> pedidos y facturas electrónicas de la tienda.</li>
    </ul>

    <h3>2. Para qué los usamos</h3>
    <p>Para atender a tus mascotas, gestionar citas, vender productos, emitir facturas y comunicarnos contigo
    (verificación de correo, confirmación de citas y envío de facturas). No usamos tus datos para publicidad ni los vendemos o cedemos a terceros.</p>

    <h3>3. Quién puede ver tu información</h3>
    <ul>
        <li>Cada cliente ve <strong>únicamente</strong> sus propios datos: su perfil, sus mascotas, su historial, sus citas y sus facturas. Un cliente no puede ver información de otro.</li>
        <li>El personal administrador de la veterinaria accede a los datos solo para brindar el servicio.</li>
    </ul>

    <h3>4. Seguridad</h3>
    <ul>
        <li>Contraseñas almacenadas con cifrado (hash).</li>
        <li>Verificación del correo electrónico al crear la cuenta.</li>
        <li>Protección «No soy un robot» en formularios sensibles.</li>
        <li>Las facturas y los datos personales solo se muestran a su titular o al administrador.</li>
    </ul>

    <h3>5. Correos que enviamos</h3>
    <p>Solo mensajes del servicio: verificación de cuenta, citas y facturas. Los correos se envían desde
    <?= h(MAIL_FROM) ?> mediante un proveedor de correo.</p>

    <h3>6. Cookies</h3>
    <p>Usamos únicamente una cookie de sesión, necesaria para mantener tu inicio de sesión y tu carrito. No usamos cookies de seguimiento ni publicidad.</p>

    <h3>7. Conservación</h3>
    <p>Conservamos tus datos mientras tengas cuenta. Las facturas emitidas se guardan el tiempo que exijan las normas contables y fiscales aplicables.</p>

    <h3>8. Tus derechos</h3>
    <p>Puedes pedir acceso, corrección, actualización o eliminación de tus datos personales (derechos reconocidos por la
    Ley N.º 18.331 de Protección de Datos Personales de Uruguay). Muchos datos puedes corregirlos tú misma/o en <em>Mi perfil</em>.
    Para el resto, o para eliminar tu cuenta, escríbenos a
    <a href="mailto:<?= h(VET_EMAIL_SOPORTE) ?>"><?= h(VET_EMAIL_SOPORTE) ?></a> o llama al <?= h(VET_TELEFONO) ?>.</p>

    <p class="muted">Este sitio es un proyecto educativo. Última actualización: <?= date("d/m/Y") ?>.</p>
</div>
<?php require "includes/footer.php"; ?>
