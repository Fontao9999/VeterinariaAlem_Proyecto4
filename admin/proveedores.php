<?php
require "../includes/auth.php";
require "../conexion.php";
exigir_admin();

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST["codigo_proveedor"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    if ($codigo !== "" && $nombre !== "") {
        $stmt = $conexion->prepare("INSERT INTO proveedores (codigo_proveedor,nombre,correo,direccion,telefono) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $codigo, $nombre, $correo, $direccion, $telefono);
        $mensaje = $stmt->execute() ? "Proveedor agregado correctamente." : "No se pudo agregar. Verifica que el código no esté repetido.";
    } else {
        $mensaje = "El código y el nombre son obligatorios.";
    }
}
if (isset($_GET["eliminar"])) {
    $id = (int) $_GET["eliminar"];
    $stmt = $conexion->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: proveedores.php");
    exit;
}
$proveedores = $conexion->query("SELECT * FROM proveedores ORDER BY nombre");
require "../includes/header.php";
?>
<section class="hero"><div class="hero-kicker">Administración</div><h2>Proveedores</h2><p>Gestiona los proveedores de productos e insumos.</p></section>
<?php if ($mensaje): ?><div class="info-box"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<section class="card">
<h3>Agregar proveedor</h3>
<form method="post" class="form-grid">
<label>Código de proveedor<input name="codigo_proveedor" required></label>
<label>Nombre<input name="nombre" required></label>
<label>Correo<input type="email" name="correo"></label>
<label>Dirección<input name="direccion"></label>
<label>Teléfono<input name="telefono"></label>
<button class="btn" type="submit">Guardar proveedor</button>
</form></section>
<section class="card"><h3>Listado de proveedores</h3><div class="table-wrap"><table><thead><tr><th>Código</th><th>Nombre</th><th>Correo</th><th>Dirección</th><th>Teléfono</th><th>Acción</th></tr></thead><tbody>
<?php while ($fila = $proveedores->fetch_assoc()): ?><tr>
<td><?= htmlspecialchars($fila["codigo_proveedor"]) ?></td><td><?= htmlspecialchars($fila["nombre"]) ?></td><td><?= htmlspecialchars($fila["correo"] ?? "") ?></td><td><?= htmlspecialchars($fila["direccion"] ?? "") ?></td><td><?= htmlspecialchars($fila["telefono"] ?? "") ?></td>
<td><a href="?eliminar=<?= (int)$fila["id"] ?>" onclick="return confirm('¿Eliminar proveedor?')">Eliminar</a></td></tr><?php endwhile; ?>
</tbody></table></div></section>
<?php require "../includes/footer.php"; ?>
