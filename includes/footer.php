<?php $base_url = $base_url ?? BASE_URL; ?></main>

<footer>
    <div class="footer-links">
        <a href="<?= $base_url ?>/contacto.php">📞 Contacto: <?= h(VET_TELEFONO) ?></a>
        <a href="<?= $base_url ?>/soporte.php">🛟 Soporte: <?= h(VET_EMAIL_SOPORTE) ?></a>
        <a href="<?= $base_url ?>/privacidad.php">🔒 Privacidad de los clientes</a>
        <a href="<?= $base_url ?>/login.php">Iniciar sesión</a>
        <?php if (isset($_SESSION["usuario_id"])): ?>
            <a href="<?= $base_url ?>/facturas.php">🧾 Mis facturas</a>
        <?php endif; ?>
    </div>
    <strong>Veterinaria Alem</strong> · Sistema de gestión veterinaria · Proyecto educativo de 3.º de Bachillerato
</footer>

</body>
</html>
