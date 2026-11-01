<?php
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_permiso("productos", "listar");

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "agregar") {
    if (!puede("productos", "alta")) {
        $error = "No tienes permiso para agregar.";
    } elseif (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $nombre = trim($_POST["nombre"] ?? "");
        $categoria = trim($_POST["categoria"] ?? "");
        $precio = (float)($_POST["precio"] ?? 0);
        $stock = (int)($_POST["stock"] ?? 0);

        $stmt = $conexion->prepare(
            "INSERT INTO productos(nombre, categoria, precio, stock)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdi", $nombre, $categoria, $precio, $stock);

        if ($stmt->execute()) $ok = "Producto agregado correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$r = $conexion->query("SELECT * FROM productos ORDER BY id DESC");
require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker"><?= es_admin() ? "Administración" : "Empleado" ?> · Tienda</div>
    <h2>Productos</h2>
    <p>Control de productos, precios y stock de la veterinaria.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Agregar producto</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" required></div>
            <div class="field"><label>Categoría</label><input name="categoria" required></div>
            <div class="field"><label>Precio</label><input type="number" step="0.01" name="precio" required></div>
            <div class="field"><label>Stock</label><input type="number" name="stock" min="0" required></div>
        </div>
        <br>
        <?= captcha_html() ?>
        <div class="form-actions"><button class="btn" type="submit">Agregar producto</button></div>
    </form>
</div>

<div class="table-card">
    <h3>Listado de productos</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["nombre"]) ?></td>
                    <td><?= htmlspecialchars($x["categoria"]) ?></td>
                    <td>$<?= htmlspecialchars($x["precio"]) ?></td>
                    <td><?= htmlspecialchars($x["stock"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=productos&id=<?= $x["id"] ?>">Modificar</a>
                        <?php if (puede("productos", "baja")): ?><a class="btn danger small" href="eliminar.php?tabla=productos&id=<?= $x["id"] ?>">Eliminar</a><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
