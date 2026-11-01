<?php
// Cierra la sesión del usuario.
session_start();
session_unset();
session_destroy();

header("Location: index.php");
exit;
?>

