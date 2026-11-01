<?php
// ============================================================
// TIENDA (clientes): ver productos y agregarlos al carrito.
// ============================================================
require "includes/auth.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/productos.php");
    exit;
}
if (es_empleado()) {
    header("Location: admin/productos.php");
    exit;
}

$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["id_producto"] ?? 0);
    $cant = max(1, min(99, (int)($_POST["cantidad"] ?? 1)));

    if (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";
    } else {
        $stmt = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();

        if (!$p) {
            $error = "El producto no existe.";
        } else {
            $en_carrito = (int)($_SESSION["carrito"][$id] ?? 0);
            $nuevo = $en_carrito + $cant;
            if ($nuevo > (int)$p["stock"]) {
                $error = "No hay stock suficiente de «" . $p["nombre"] . "» (disponible: " . (int)$p["stock"] . ").";
            } else {
                $_SESSION["carrito"][$id] = $nuevo;
                $msg = "«" . $p["nombre"] . "» se agregó al carrito.";
            }
        }
    }
}

$categoria = trim($_GET["categoria"] ?? "");
$buscar = trim($_GET["q"] ?? "");

$cats = $conexion->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria");

$sql = "SELECT * FROM productos WHERE 1=1";
$tipos = "";
$params = [];
if ($categoria !== "") { $sql .= " AND categoria = ?"; $tipos .= "s"; $params[] = $categoria; }
if ($buscar !== "")    { $sql .= " AND nombre LIKE ?"; $tipos .= "s"; $params[] = "%" . $buscar . "%"; }
$sql .= " ORDER BY nombre";
$stmt = $conexion->prepare($sql);
if ($params) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$r = $stmt->get_result();

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Tienda veterinaria</div>
    <h2>Productos</h2>
    <p>Compra online: elige tus productos, finaliza la compra en el carrito y recibe tu factura electrónica por correo.</p>
</section>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert success"><?= h($msg) ?> <a href="carrito.php">Ver carrito</a></div><?php endif; ?>

<form method="get" class="form-card filtros">
    <div class="field"><label for="q">Buscar</label>
        <input id="q" name="q" value="<?= h($buscar) ?>" placeholder="Nombre del producto"></div>
    <div class="field"><label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="">Todas</option>
            <?php while ($c = $cats->fetch_assoc()): ?>
                <option <?= $c["categoria"] === $categoria ? "selected" : "" ?>><?= h($c["categoria"]) ?></option>
            <?php endwhile; ?>
        </select></div>
    <div class="field"><label>&nbsp;</label><button class="btn" type="submit">Filtrar</button></div>
</form>

<div class="grid">
    <?php while ($x = $r->fetch_assoc()): ?>
        <article class="card">
            <div class="feature-icon">📦</div>
            <h3><?= h($x["nombre"]) ?></h3>
            <p><?= h($x["categoria"]) ?></p>
            <p><strong><?= dinero($x["precio"]) ?></strong> · IVA incluido</p>
            <?php if ((int)$x["stock"] > 0): ?>
                <p class="muted">Stock: <?= (int)$x["stock"] ?></p>
                <form method="post" class="comprar-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_producto" value="<?= (int)$x["id"] ?>">
                    <input type="number" name="cantidad" value="1" min="1" max="<?= min(99, (int)$x["stock"]) ?>" aria-label="Cantidad">
                    <button class="btn small" type="submit">Agregar al carrito</button>
                </form>
            <?php else: ?>
                <p><span class="badge block">Sin stock</span></p>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
</div>
<?php require "includes/footer.php"; ?>
