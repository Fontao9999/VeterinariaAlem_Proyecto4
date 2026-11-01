<?php
require "../includes/auth.php";
require "../conexion.php";
exigir_admin();

// Filtros de búsqueda de usuarios (nombre, correo, rol y estado).
$busqueda = trim($_GET["buscar"] ?? "");
$rol = trim($_GET["rol"] ?? "");
$estado = trim($_GET["estado"] ?? "");

$sql = "SELECT id, nombre, email, rol, activo, email_verificado, creado_en
        FROM usuarios
        WHERE 1=1";
$tipos = "";
$parametros = [];

if ($busqueda !== "") {
    $sql .= " AND (nombre LIKE ? OR email LIKE ? )";
    $comodin = "%" . $busqueda . "%";
    $tipos .= "ss";
    $parametros[] = $comodin;
    $parametros[] = $comodin;
}

if (in_array($rol, ["admin", "empleado", "cliente"], true)) {
    $sql .= " AND rol = ?";
    $tipos .= "s";
    $parametros[] = $rol;
}

if ($estado === "activo" || $estado === "bloqueado") {
    $sql .= " AND activo = ?";
    $tipos .= "i";
    $parametros[] = $estado === "activo" ? 1 : 0;
}

$sql .= " ORDER BY id DESC";
$stmt = $conexion->prepare($sql);
if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$r = $stmt->get_result();
$total_resultados = $r->num_rows;

require "../includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Administración · Seguridad</div>
    <h2>Usuarios, roles y permisos</h2>
    <p>
        El administrador puede modificar el rol y bloquear/desbloquear usuarios.
        Las nuevas cuentas creadas desde el registro son clientes.
    </p>
</section>

<div class="info-box">
    <strong>Roles:</strong> administrador = gestiona el sistema;
    empleado = lista sus citas, gestiona mascotas, historial y servicios, agrega/modifica productos y edita su perfil;
    cliente = consulta la información disponible para usuarios.
</div>

<div class="form-card">
    <h3>Buscar y filtrar usuarios</h3>
    <form method="GET" action="usuarios.php">
        <div class="form-grid">
            <div>
                <label for="buscar">Nombre o correo electrónico</label>
                <input type="search" id="buscar" name="buscar"
                       placeholder="Ej.: Juan o correo@ejemplo.com"
                       value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <label for="rol">Tipo de usuario</label>
                <select id="rol" name="rol">
                    <option value="">Todos los roles</option>
                    <option value="admin" <?= $rol === "admin" ? "selected" : "" ?>>Administrador</option>
                    <option value="empleado" <?= $rol === "empleado" ? "selected" : "" ?>>Empleado</option>
                    <option value="cliente" <?= $rol === "cliente" ? "selected" : "" ?>>Cliente</option>
                </select>
            </div>
            <div>
                <label for="estado">Estado de la cuenta</label>
                <select id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?= $estado === "activo" ? "selected" : "" ?>>Activo</option>
                    <option value="bloqueado" <?= $estado === "bloqueado" ? "selected" : "" ?>>Bloqueado</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Buscar usuarios</button>
            <a class="btn secondary" href="usuarios.php">Limpiar filtros</a>
        </div>
    </form>
    <p class="muted">Resultados encontrados: <strong><?= $total_resultados ?></strong></p>
</div>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Correo verificado</th><th>Creado</th><th>Acciones</th></tr>
            <?php if ($total_resultados > 0): ?>
                <?php while ($x = $r->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($x["nombre"]) ?></td>
                        <td><?= htmlspecialchars($x["email"]) ?></td>
                        <td>
                            <span class="badge <?= $x["rol"] === "admin" ? "admin" : ($x["rol"] === "empleado" ? "empleado" : "") ?>">
                                <?= htmlspecialchars($x["rol"]) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= !$x["activo"] ? "block" : "" ?>">
                                <?= $x["activo"] ? "Activo" : "Bloqueado" ?>
                            </span>
                        </td>
                        <td><?= $x["email_verificado"] ? "✅ Sí" : "⏳ Pendiente" ?></td>
                        <td><?= htmlspecialchars($x["creado_en"]) ?></td>
                        <td class="actions">
                            <a class="btn small" href="editar.php?tabla=usuarios&id=<?= (int)$x["id"] ?>">Modificar</a>
                            <a class="btn danger small" href="eliminar.php?tabla=usuarios&id=<?= (int)$x["id"] ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No se encontraron usuarios con esos filtros.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php
$stmt->close();
require "../includes/footer.php";
?>
