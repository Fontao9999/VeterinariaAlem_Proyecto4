<?php
require_once __DIR__ . "/auth.php";

// Si cambias el nombre de la carpeta del proyecto en XAMPP,
// modifica solamente BASE_URL en config.php.
$base_url = BASE_URL;

$items_carrito = 0;
if (isset($_SESSION["usuario_id"]) && !es_personal() && !empty($_SESSION["carrito"])) {
    $items_carrito = array_sum($_SESSION["carrito"]);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veterinaria Alem</title>
    <link rel="stylesheet" href="<?= $base_url ?>/css/estilos.css">
    <link rel="stylesheet" href="css/estilos.css">.
<body>

<header class="topbar">
    <div class="nav-inner">
        <a class="brand" href="<?= $base_url ?>/index.php">
            <span class="brand-icon">🐾</span>
            <span>Veterinaria Alem</span>
        </a>

        <nav class="nav-links">
            <a href="<?= $base_url ?>/index.php">Inicio</a>

            <?php if (es_empleado()): ?>
                <a href="<?= $base_url ?>/empleado/citas.php">Mis citas</a>
                <a href="<?= $base_url ?>/admin/mascotas.php">Mascotas</a>
                <a href="<?= $base_url ?>/admin/historial.php">Historial</a>
                <a href="<?= $base_url ?>/admin/servicios.php">Servicios</a>
                <a href="<?= $base_url ?>/admin/productos.php">Tienda</a>
                <a href="<?= $base_url ?>/empleado/perfil.php">Perfil</a>
                <a class="empleado-link" href="<?= $base_url ?>/empleado/index.php">🩺 Panel empleado</a>
                <a class="logout-link" href="<?= $base_url ?>/logout.php">Salir</a>
            <?php elseif (isset($_SESSION["usuario_id"])): ?>
                <a href="<?= $base_url ?>/citas.php">citas</a>
                <a href="<?= $base_url ?>/mascotas.php">Mascotas</a>
                <a href="<?= $base_url ?>/historial.php">Historial</a>
                <a href="<?= $base_url ?>/servicios.php">Servicios</a>
                <a href="<?= $base_url ?>/empleados.php">empleados</a>
                <a href="<?= $base_url ?>/productos.php">Tienda</a>
                <?php if (!es_admin()): ?>
                    <a href="<?= $base_url ?>/carrito.php">🛒 Carrito<?= $items_carrito > 0 ? " (" . (int)$items_carrito . ")" : "" ?></a>
                <?php endif; ?>
                <a href="<?= $base_url ?>/facturas.php">Facturas</a>
                <a href="<?= $base_url ?>/clientes.php"><?= es_admin() ? "Clientes" : "Mi perfil" ?></a>
                <a href="<?= $base_url ?>/contacto.php">Contacto</a>

                <?php if (es_admin()): ?>
                    <a class="admin-link" href="<?= $base_url ?>/admin/index.php">⚙ Admin</a>
                <?php endif; ?>

                <a class="logout-link" href="<?= $base_url ?>/logout.php">Salir</a>
            <?php else: ?>
                <a href="<?= $base_url ?>/contacto.php">Contacto</a>
                <a href="<?= $base_url ?>/soporte.php">Soporte</a>
                <a href="<?= $base_url ?>/login.php">Iniciar sesión</a>
                <a class="btn" href="<?= $base_url ?>/registro.php">Registrarse</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
