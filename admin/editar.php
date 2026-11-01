<?php
// ============================================================
// EDITAR REGISTRO
// El administrador puede modificar registros.
// Antes de guardar se solicita un CAPTCHA.
// ============================================================
require "../includes/auth.php";
require "../includes/captcha.php";
require "../conexion.php";
exigir_login();

$permitidas = [
    "productos" => ["nombre", "categoria", "precio", "stock"],
    "servicios" => ["nombre", "descripcion", "precio"],
    "empleados" => ["nombre", "especialidad", "telefono", "email"],
    "clientes" => ["nombre", "cedula", "telefono", "email", "direccion"],
    "mascotas" => ["nombre", "especie", "raza", "sexo", "fecha_nacimiento", "peso", "id_cliente"],
    "citas" => ["id_cliente", "id_mascota", "id_empleado", "id_servicio", "fecha", "hora", "motivo", "estado"],
    "historial" => ["id_mascota", "id_empleado", "fecha", "diagnostico", "tratamiento", "observaciones"],
    "usuarios" => ["nombre", "email", "rol", "activo"]
];

$tabla = $_GET["tabla"] ?? "";
$id = (int)($_GET["id"] ?? 0);

if (!isset($permitidas[$tabla]) || $id < 1) {
    die("Datos inválidos.");
}

if (!puede($tabla, "modificar")) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$campos = $permitidas[$tabla];
$error = "";

$stmt = $conexion->prepare("SELECT * FROM `$tabla` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();

if (!$actual) {
    die("Registro no encontrado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validar_captcha($_POST["captcha"] ?? "")) {
        $error = MENSAJE_CAPTCHA;
    } else {
        $valores = [];

        foreach ($campos as $campo) {
            $valores[] = $_POST[$campo] ?? "";
        }

        $set = implode(" = ?, ", $campos) . " = ?";
        $sql = "UPDATE `$tabla` SET $set WHERE id = ?";

        $stmt = $conexion->prepare($sql);
        $tipos = str_repeat("s", count($valores)) . "i";
        $valores[] = $id;
        $stmt->bind_param($tipos, ...$valores);

        if ($stmt->execute()) {
            // Al pasar un usuario a "empleado" se le crea/vincula su ficha en la tabla empleados.
            if ($tabla === "usuarios" && ($_POST["rol"] ?? "") === "empleado") {
                $v = $conexion->prepare("SELECT id FROM empleados WHERE id_usuario = ? LIMIT 1");
                $v->bind_param("i", $id);
                $v->execute();
                if (!$v->get_result()->fetch_assoc()) {
                    $u = $conexion->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
                    $u->bind_param("i", $id);
                    $u->execute();
                    $us = $u->get_result()->fetch_assoc();

                    // ¿ya existe una ficha de empleado con ese correo sin vincular?
                    $b = $conexion->prepare("UPDATE empleados SET id_usuario = ? WHERE email = ? AND id_usuario IS NULL LIMIT 1");
                    $b->bind_param("is", $id, $us["email"]);
                    $b->execute();
                    if ($b->affected_rows === 0) {
                        $n = $conexion->prepare("INSERT INTO empleados(id_usuario, nombre, email) VALUES (?, ?, ?)");
                        $n->bind_param("iss", $id, $us["nombre"], $us["email"]);
                        $n->execute();
                    }
                }
            }
            $destino = $tabla === "usuarios" ? "usuarios.php" : $tabla . ".php";
            header("Location: " . $destino);
            exit;
        }

        $error = "No se pudieron guardar los cambios: " . $stmt->error;
    }
}

$cap = nuevo_captcha();

$clientes = null;
$mascotas = null;
$empleados = null;
$servicios = null;

if ($tabla === "mascotas" || $tabla === "citas") {
    $clientes = $conexion->query("SELECT id, nombre FROM clientes ORDER BY nombre");
}
if ($tabla === "citas" || $tabla === "historial") {
    $mascotas = $conexion->query("SELECT id, nombre FROM mascotas ORDER BY nombre");
    $empleados = $conexion->query("SELECT id, nombre FROM empleados ORDER BY nombre");
}
if ($tabla === "citas") {
    $servicios = $conexion->query("SELECT id, nombre FROM servicios ORDER BY nombre");
}

$etiquetas = [
    "nombre"=>"Nombre",
    "email"=>"Correo",
    "categoria"=>"Categoría",
    "precio"=>"Precio",
    "stock"=>"Stock",
    "descripcion"=>"Descripción",
    "especialidad"=>"Especialidad",
    "telefono"=>"Teléfono",
    "cedula"=>"Cédula",
    "direccion"=>"Dirección",
    "especie"=>"Especie",
    "raza"=>"Raza",
    "sexo"=>"Sexo",
    "fecha_nacimiento"=>"Fecha de nacimiento",
    "peso"=>"Peso (kg)",
    "id_cliente"=>"Dueño",
    "id_mascota"=>"Mascota",
    "id_empleado"=>"Veterinario",
    "id_servicio"=>"Servicio",
    "fecha"=>"Fecha",
    "hora"=>"Hora",
    "motivo"=>"Motivo",
    "estado"=>"Estado",
    "diagnostico"=>"Diagnóstico",
    "tratamiento"=>"Tratamiento",
    "observaciones"=>"Observaciones",
    "rol"=>"Rol",
    "activo"=>"Estado del usuario"
];

function campo_es_textarea($campo) {
    return in_array($campo, ["descripcion", "direccion", "motivo", "diagnostico", "tratamiento", "observaciones"], true);
}

require "../includes/header.php";
?>

<section class="hero">
    <div class="hero-kicker"><?= es_admin() ? "Administración" : "Empleado" ?> · Modificar</div>
    <h2>Modificar <?= htmlspecialchars(ucfirst($tabla)) ?></h2>
    <p>Actualiza los datos del registro seleccionado.</p>
</section>

<div class="form-card">
    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-grid">
            <?php foreach ($campos as $campo): ?>
                <?php $valor = $actual[$campo] ?? ""; ?>

                <div class="field <?= campo_es_textarea($campo) ? "full" : "" ?>">
                    <label for="<?= htmlspecialchars($campo) ?>">
                        <?= htmlspecialchars($etiquetas[$campo] ?? ucfirst($campo)) ?>
                    </label>

                    <?php if ($campo === "rol"): ?>
                        <select name="rol" id="rol">
                            <option value="cliente" <?= $valor === "cliente" ? "selected" : "" ?>>Cliente</option>
                            <option value="empleado" <?= $valor === "empleado" ? "selected" : "" ?>>Empleado</option>
                            <option value="admin" <?= $valor === "admin" ? "selected" : "" ?>>Administrador</option>
                        </select>

                    <?php elseif ($campo === "activo"): ?>
                        <select name="activo" id="activo">
                            <option value="1" <?= $valor ? "selected" : "" ?>>Activo</option>
                            <option value="0" <?= !$valor ? "selected" : "" ?>>Bloqueado</option>
                        </select>

                    <?php elseif ($campo === "id_cliente"): ?>
                        <select name="id_cliente" id="id_cliente" required>
                            <?php while ($o = $clientes->fetch_assoc()): ?>
                                <option value="<?= $o["id"] ?>" <?= (int)$valor === (int)$o["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($o["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                    <?php elseif ($campo === "id_mascota"): ?>
                        <select name="id_mascota" id="id_mascota" required>
                            <?php while ($o = $mascotas->fetch_assoc()): ?>
                                <option value="<?= $o["id"] ?>" <?= (int)$valor === (int)$o["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($o["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                    <?php elseif ($campo === "id_empleado"): ?>
                        <select name="id_empleado" id="id_empleado" required>
                            <?php while ($o = $empleados->fetch_assoc()): ?>
                                <option value="<?= $o["id"] ?>" <?= (int)$valor === (int)$o["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($o["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                    <?php elseif ($campo === "id_servicio"): ?>
                        <select name="id_servicio" id="id_servicio" required>
                            <?php while ($o = $servicios->fetch_assoc()): ?>
                                <option value="<?= $o["id"] ?>" <?= (int)$valor === (int)$o["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($o["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                    <?php elseif ($campo === "fecha" || $campo === "fecha_nacimiento"): ?>
                        <input type="date" name="<?= htmlspecialchars($campo) ?>" id="<?= htmlspecialchars($campo) ?>"
                               value="<?= htmlspecialchars($valor) ?>" required>

                    <?php elseif ($campo === "hora"): ?>
                        <input type="time" name="hora" id="hora"
                               value="<?= htmlspecialchars($valor) ?>" required>

                    <?php elseif (campo_es_textarea($campo)): ?>
                        <textarea name="<?= htmlspecialchars($campo) ?>" id="<?= htmlspecialchars($campo) ?>"><?= htmlspecialchars($valor) ?></textarea>

                    <?php else: ?>
                        <input
                            type="<?= $campo === "email" ? "email" : ($campo === "precio" || $campo === "peso" ? "number" : ($campo === "stock" ? "number" : "text")) ?>"
                            name="<?= htmlspecialchars($campo) ?>"
                            id="<?= htmlspecialchars($campo) ?>"
                            value="<?= htmlspecialchars($valor) ?>"
                            <?= in_array($campo, ["precio", "peso"], true) ? 'step="0.01"' : "" ?>
                            required
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <br>
        <?= captcha_html() ?>

        <div class="form-actions">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn secondary" href="<?= $tabla === "usuarios" ? "usuarios.php" : htmlspecialchars($tabla) . ".php" ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php require "../includes/footer.php"; ?>
