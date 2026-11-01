<?php
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_admin();

$error = "";
$ok = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "agregar") {
    if (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $nombre = trim($_POST["nombre"] ?? "");
        $cedula = trim($_POST["cedula"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        $stmt = $conexion->prepare(
            "INSERT INTO clientes(nombre, cedula, telefono, email, direccion)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $nombre, $cedula, $telefono, $email, $direccion);

        if ($stmt->execute()) $ok = "Dueño agregado correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$r = $conexion->query("SELECT * FROM clientes ORDER BY id DESC");
require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Clientes</div>
    <h2>Clientes / Clientes</h2>
    <p>Alta y listado de los Clientes de las mascotas.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Agregar dueño</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" required></div>
            <div class="field"><label>Cédula</label><input name="cedula" required></div>
            <div class="field"><label>Teléfono</label><input name="telefono" required></div>
            <div class="field"><label>Email</label><input type="email" name="email" required></div>
            <div class="field full"><label>Dirección</label><input name="direccion" required></div>
        </div>

        <br>
        <?= captcha_html() ?>
        <div class="form-actions"><button class="btn" type="submit">Agregar dueño</button></div>
    </form>
</div>

<div class="table-card">
    <h3>Listado de Clientes</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Email</th><th>Dirección</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["nombre"]) ?></td>
                    <td><?= htmlspecialchars($x["cedula"]) ?></td>
                    <td><?= htmlspecialchars($x["telefono"]) ?></td>
                    <td><?= htmlspecialchars($x["email"]) ?></td>
                    <td><?= htmlspecialchars($x["direccion"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=clientes&id=<?= $x["id"] ?>">Modificar</a>
                        <a class="btn danger small" href="eliminar.php?tabla=clientes&id=<?= $x["id"] ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
