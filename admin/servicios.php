<?php
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_permiso("servicios", "listar");

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "agregar") {
    if (!puede("servicios", "alta")) {
        $error = "No tienes permiso para agregar.";
    } elseif (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = (float)($_POST["precio"] ?? 0);

        $stmt = $conexion->prepare(
            "INSERT INTO servicios(nombre, descripcion, precio)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssd", $nombre, $descripcion, $precio);

        if ($stmt->execute()) $ok = "Servicio agregado correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$r = $conexion->query("SELECT * FROM servicios ORDER BY id DESC");
require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker"><?= es_admin() ? "Administración" : "Empleado" ?> · Atención</div>
    <h2>Servicios</h2>
    <p>Administra las prestaciones que ofrece la veterinaria.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Agregar servicio</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" required></div>
            <div class="field"><label>Precio</label><input type="number" step="0.01" name="precio" required></div>
            <div class="field full"><label>Descripción</label><textarea name="descripcion" required></textarea></div>
        </div>
        <br>
        <?= captcha_html() ?>
        <div class="form-actions"><button class="btn" type="submit">Agregar servicio</button></div>
    </form>
</div>

<div class="table-card">
    <h3>Listado de servicios</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["nombre"]) ?></td>
                    <td><?= htmlspecialchars($x["descripcion"]) ?></td>
                    <td>$<?= htmlspecialchars($x["precio"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=servicios&id=<?= $x["id"] ?>">Modificar</a>
                        <?php if (puede("servicios", "baja")): ?><a class="btn danger small" href="eliminar.php?tabla=servicios&id=<?= $x["id"] ?>">Eliminar</a><?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
