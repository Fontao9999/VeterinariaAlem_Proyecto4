<?php
// ============================================================
// VER / DESCARGAR UNA FACTURA ELECTRÓNICA
// Solo la puede ver su dueño o el administrador (privacidad).
// ============================================================
require "includes/auth.php";
require "includes/factura_html.php";
require "conexion.php";
exigir_login();

$id = (int)($_GET["id"] ?? 0);
$stmt = $conexion->prepare("SELECT * FROM facturas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$f = $stmt->get_result()->fetch_assoc();

if (!$f || (!es_admin() && (int)$f["id_usuario"] !== (int)$_SESSION["usuario_id"])) {
    http_response_code(404);
    require "includes/header.php";
    echo '<div class="form-card"><h2>Factura no encontrada</h2><a class="btn secondary" href="facturas.php">Volver</a></div>';
    require "includes/footer.php";
    exit;
}

$it = $conexion->prepare("SELECT nombre, precio, cantidad FROM pedido_items WHERE id_pedido = ? ORDER BY id");
$it->bind_param("i", $f["id_pedido"]);
$it->execute();
$items = $it->get_result()->fetch_all(MYSQLI_ASSOC);

// Descarga como archivo .html (sin JavaScript)
if (isset($_GET["descargar"])) {
    header("Content-Type: text/html; charset=UTF-8");
    header('Content-Disposition: attachment; filename="factura_' . $f["numero"] . '.html"');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Factura ' . h($f["numero"])
        . '</title></head><body style="background:#f4f8f7;padding:20px">' . factura_html($f, $items) . '</body></html>';
    exit;
}

require "includes/header.php";
?>
<?php if (isset($_GET["nueva"])): ?>
    <div class="alert success">
        ✅ ¡Compra realizada! Tu factura electrónica fue generada y enviada a tu correo
        (si el servidor de correo está configurado).
    </div>
<?php endif; ?>

<div class="acciones-factura">
    <a class="btn small" href="factura.php?id=<?= (int)$f["id"] ?>&descargar=1">⬇ Descargar factura</a>
    <a class="btn secondary small" href="<?= es_admin() ? "admin/facturas.php" : "facturas.php" ?>">Volver</a>
    <span class="muted">Para imprimirla usa Ctrl + P en tu navegador.</span>
</div>

<div class="factura-wrap"><?= factura_html($f, $items) ?></div>
<?php require "includes/footer.php"; ?>
