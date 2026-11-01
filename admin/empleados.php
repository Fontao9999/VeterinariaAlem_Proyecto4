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
        $especialidad = trim($_POST["especialidad"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $email = trim($_POST["email"] ?? "");

        $stmt = $conexion->prepare(
            "INSERT INTO empleados(nombre, especialidad, telefono, email)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $nombre, $especialidad, $telefono, $email);

        if ($stmt->execute()) $ok = "Veterinario agregado correctamente.";
        else $error = "No se pudo agregar: " . $stmt->error;
    }
}

$cap = nuevo_captcha();
$r = $conexion->query("SELECT * FROM empleados ORDER BY id DESC");
require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Personal</div>
    <h2>empleados</h2>
    <p>Registra profesionales y sus especialidades.</p>
</section>

<div class="form-card">
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <h3>Agregar veterinario</h3>
    <form method="post">
        <input type="hidden" name="accion" value="agregar">
        <div class="form-grid">
            <div class="field"><label>Nombre</label><input name="nombre" required></div>
            <div class="field"><label>Especialidad</label><input name="especialidad" required></div>
            <div class="field"><label>Teléfono</label><input name="telefono" required></div>
            <div class="field"><label>Email</label><input type="email" name="email" required></div>
        </div>
        <br>
        <?= captcha_html() ?>
        <div class="form-actions"><button class="btn" type="submit">Agregar veterinario</button></div>
    </form>
</div>

<div class="table-card">
    <h3>Listado</h3>
    <div class="table-wrap">
        <table>
            <tr><th>Nombre</th><th>Especialidad</th><th>Teléfono</th><th>Email</th><th>Acciones</th></tr>
            <?php while ($x = $r->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($x["nombre"]) ?></td>
                    <td><?= htmlspecialchars($x["especialidad"]) ?></td>
                    <td><?= htmlspecialchars($x["telefono"]) ?></td>
                    <td><?= htmlspecialchars($x["email"]) ?></td>
                    <td class="actions">
                        <a class="btn small" href="editar.php?tabla=empleados&id=<?= $x["id"] ?>">Modificar</a>
                        <a class="btn danger small" href="eliminar.php?tabla=empleados&id=<?= $x["id"] ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
