<?php
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "veterinariaalem"; // Asegúrate de que coincida con la de phpMyAdmin

// Detectar si estamos en producción o local (ejemplo básico)
if ($_SERVER['HTTP_HOST'] != 'localhost' && $_SERVER['HTTP_HOST'] != '127.0.0.1') {
    // Credenciales de InfinityFree (Producción)
    $host = "sql311.infinityfree.com";
    $usuario = "tu_usuario_remoto";
    $password = "tu_contraseña_remota";
    $base_datos = "tu_base_remota";
}

$conexion = new mysqli($host, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión con MySQL: " . $conexion->connect_error);
}
$conexion->set_charset("utf8");
?>
<?php
// Detectar automáticamente si estamos en local o en producción
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// Si estás en local con subcarpeta, puedes adaptarlo o usar rutas relativas puras
// Las rutas relativas puras (ej: href="login.php" o href="../login.php") son la mejor opción porque funcionan en cualquier lado sin importar el dominio.
?>