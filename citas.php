<?php
// ============================================================
// citas (clientes)
// - Buscar horarios libres por veterinario / fecha / servicio.
// - Agendar un turno para una de sus mascotas.
// - Ver y cancelar sus propios citas.
// Cada cliente ve SOLO sus citas (privacidad).
// ============================================================
require "includes/auth.php";
require "includes/agenda.php";
require "includes/mailer.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/citas.php");
    exit;
}
if (es_empleado()) {
    header("Location: empleado/citas.php");
    exit;
}

$id_cliente = dueno_id_actual($conexion);
$error = "";
$msg = "";

// ---------- Acciones (POST) ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";

    } elseif ($accion === "cancelar") {
        $id = (int)($_POST["id_turno"] ?? 0);
        $stmt = $conexion->prepare(
            "UPDATE citas SET estado = 'Cancelado'
             WHERE id = ? AND id_cliente = ? AND estado IN ('Pendiente','Confirmado')
               AND TIMESTAMP(fecha, hora) > NOW()"
        );
        $stmt->bind_param("ii", $id, $id_cliente);
        $stmt->execute();
        if ($stmt->affected_rows === 1) $msg = "El turno fue cancelado.";
        else $error = "No se pudo cancelar ese turno.";

    } elseif ($accion === "agendar") {
        $id_mascota = (int)($_POST["id_mascota"] ?? 0);
        $id_vet = (int)($_POST["id_empleado"] ?? 0);
        $id_serv = (int)($_POST["id_servicio"] ?? 0);
        $fecha = $_POST["fecha"] ?? "";
        $hora = $_POST["hora"] ?? "";
        $motivo = mb_substr(trim($_POST["motivo"] ?? ""), 0, 255);

        $conexion->begin_transaction();
        try {
            // la mascota debe ser del cliente
            $m = $conexion->prepare("SELECT nombre FROM mascotas WHERE id = ? AND id_cliente = ?");
            $m->bind_param("ii", $id_mascota, $id_cliente);
            $m->execute();
            $mascota = $m->get_result()->fetch_assoc();
            if (!$mascota) throw new RuntimeException("Elige una de tus mascotas.");

            $ex = $conexion->prepare("SELECT (SELECT nombre FROM servicios WHERE id = ?) AS s, (SELECT nombre FROM empleados WHERE id = ?) AS v");
            $ex->bind_param("ii", $id_serv, $id_vet);
            $ex->execute();
            $nombres = $ex->get_result()->fetch_assoc();
            if (!$nombres["s"] || !$nombres["v"]) throw new RuntimeException("Elige un servicio y un veterinario.");

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !in_array($hora, horas_del_dia(), true)
                || !dia_de_atencion($fecha) || $fecha < date("Y-m-d")
                || ($fecha === date("Y-m-d") && $hora <= date("H:i"))) {
                throw new RuntimeException("Ese día u horario no está disponible para citas.");
            }

            // bloquear al veterinario mientras se verifica, para que dos clientes no reserven a la vez
            $lk = $conexion->prepare("SELECT id FROM empleados WHERE id = ? FOR UPDATE");
            $lk->bind_param("i", $id_vet);
            $lk->execute();
            $lk->get_result();

            $c = $conexion->prepare(
                "SELECT count(*) AS n FROM citas
                 WHERE fecha = ? AND hora = ? AND estado <> 'Cancelado' AND (id_empleado = ? OR id_mascota = ?)"
            );
            $hora_sql = $hora . ":00";
            $c->bind_param("ssii", $fecha, $hora_sql, $id_vet, $id_mascota);
            $c->execute();
            if ((int)$c->get_result()->fetch_assoc()["n"] > 0) {
                throw new RuntimeException("Ese horario ya fue tomado. Elige otro.");
            }

            $i = $conexion->prepare(
                "INSERT INTO citas(id_cliente, id_mascota, id_empleado, id_servicio, fecha, hora, motivo, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente')"
            );
            $i->bind_param("iiiisss", $id_cliente, $id_mascota, $id_vet, $id_serv, $fecha, $hora_sql, $motivo);
            $i->execute();
            $conexion->commit();

            $msg = "¡Turno agendado para " . $mascota["nombre"] . " el " . nombre_dia($fecha) . " a las " . $hora . "!";

            // correo de confirmación (si falla, el turno igual queda registrado)
            $q = $conexion->prepare("SELECT email FROM usuarios WHERE id = ?");
            $uid = (int)$_SESSION["usuario_id"];
            $q->bind_param("i", $uid);
            $q->execute();
            $mail = $q->get_result()->fetch_assoc()["email"] ?? "";
            if ($mail) {
                enviar_correo($mail, "Turno agendado - " . VET_NOMBRE,
                    '<div style="font-family:Arial,sans-serif"><h2 style="color:#167a68">🐾 ' . h(VET_NOMBRE) . '</h2>'
                    . '<p>Tu turno quedó <strong>pendiente de confirmación</strong>:</p><ul>'
                    . '<li>Mascota: ' . h($mascota["nombre"]) . '</li>'
                    . '<li>Servicio: ' . h($nombres["s"]) . '</li>'
                    . '<li>Veterinario/a: ' . h($nombres["v"]) . '</li>'
                    . '<li>Fecha: ' . h(nombre_dia($fecha)) . ' a las ' . h($hora) . '</li></ul>'
                    . '<p>Si necesitas cambiarlo, llámanos al ' . h(VET_TELEFONO) . '.</p></div>');
            }
            $_GET = [];
        } catch (RuntimeException $e) {
            $conexion->rollback();
            $error = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            $error = "No se pudo agendar el turno. Intenta nuevamente.";
        }
    }
}

// ---------- Datos para los formularios ----------
$mascotas = $conexion->prepare("SELECT id, nombre, especie FROM mascotas WHERE id_cliente = ? ORDER BY nombre");
$mascotas->bind_param("i", $id_cliente);
$mascotas->execute();
$mascotas = $mascotas->get_result()->fetch_all(MYSQLI_ASSOC);

$vets = $conexion->query("SELECT id, nombre, especialidad FROM empleados ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$servicios = $conexion->query("SELECT id, nombre, precio FROM servicios ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// ---------- Búsqueda de horarios (GET) ----------
$b_vet = (int)($_GET["id_empleado"] ?? 0);
$b_serv = (int)($_GET["id_servicio"] ?? 0);
$b_masc = (int)($_GET["id_mascota"] ?? 0);
$b_fecha = $_GET["fecha"] ?? "";
$b_motivo = $_GET["motivo"] ?? "";
$buscando = isset($_GET["buscar"]);
$resultados = [];   // cada uno: fecha, hora, id_vet, vet
$aviso_busqueda = "";

if ($buscando) {
    if ($b_serv <= 0) {
        $aviso_busqueda = "Elige un servicio.";
    } elseif ($b_fecha !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $b_fecha)) {
        $aviso_busqueda = "La fecha no es válida.";
    } else {
        $lista = array_values(array_filter($vets, fn($v) => $b_vet === 0 || (int)$v["id"] === $b_vet));
        if ($b_fecha === "") {
            $resultados = proximos_horarios($conexion, $lista, 12);
            if (!$resultados) $aviso_busqueda = "No hay horarios libres en los próximos " . DIAS_BUSQUEDA . " días. Llámanos al " . VET_TELEFONO . ".";
        } else {
            foreach ($lista as $v) {
                foreach (horarios_libres($conexion, (int)$v["id"], $b_fecha) as $h) {
                    $resultados[] = ["fecha" => $b_fecha, "hora" => $h, "id_vet" => (int)$v["id"], "vet" => $v["nombre"]];
                }
            }
            if (!$resultados) {
                $aviso_busqueda = dia_de_atencion($b_fecha)
                    ? "No quedan horarios libres ese día. Prueba con otra fecha o deja la fecha vacía para ver los próximos libres."
                    : "Ese día no atendemos. Atendemos de " . HORA_APERTURA . " a " . HORA_CIERRE . " (lunes a sábado).";
            }
        }
    }
}

// ---------- Mis citas ----------
$filtro = $_GET["ver"] ?? "proximos";
$sql = "SELECT t.*, m.nombre AS mascota, v.nombre AS veterinario, s.nombre AS servicio,
               (TIMESTAMP(t.fecha, t.hora) > NOW()) AS futuro
        FROM citas t
        JOIN mascotas m ON m.id = t.id_mascota
        JOIN empleados v ON v.id = t.id_empleado
        JOIN servicios s ON s.id = t.id_servicio
        WHERE t.id_cliente = ?";
if ($filtro === "proximos") $sql .= " AND TIMESTAMP(t.fecha, t.hora) >= NOW() AND t.estado <> 'Cancelado' ORDER BY t.fecha, t.hora";
else $sql .= " ORDER BY t.fecha DESC, t.hora DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$mis = $stmt->get_result();

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Agenda</div>
    <h2>citas</h2>
    <p>Busca un horario libre y agenda tu turno online. También puedes llamarnos al
        <a href="tel:<?= h(VET_TELEFONO_LINK) ?>"><?= h(VET_TELEFONO) ?></a>.
    </p>
</section>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

<div class="form-card">
    <h3>🔎 Buscar horario y agendar</h3>

    <?php if (!$mascotas): ?>
        <div class="info-box">Primero registra a tu mascota en <a href="mascotas.php">Mascotas</a> para poder agendar un cita.</div>
    <?php else: ?>
    <form method="get">
        <input type="hidden" name="buscar" value="1">
        <div class="form-grid">
            <div class="field"><label>Mascota</label>
                <select name="id_mascota" required>
                    <?php foreach ($mascotas as $m): ?>
                        <option value="<?= (int)$m["id"] ?>" <?= $b_masc === (int)$m["id"] ? "selected" : "" ?>><?= h($m["nombre"]) ?> (<?= h($m["especie"]) ?>)</option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Servicio</label>
                <select name="id_servicio" required>
                    <?php foreach ($servicios as $s): ?>
                        <option value="<?= (int)$s["id"] ?>" <?= $b_serv === (int)$s["id"] ? "selected" : "" ?>><?= h($s["nombre"]) ?> · <?= dinero($s["precio"]) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Empleado</label>
                <select name="id_empleado">
                    <option value="0">Cualquiera</option>
                    <?php foreach ($vets as $v): ?>
                        <option value="<?= (int)$v["id"] ?>" <?= $b_vet === (int)$v["id"] ? "selected" : "" ?>><?= h($v["nombre"]) ?> · <?= h($v["especialidad"]) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="field"><label>Fecha (déjala vacía para ver los próximos libres)</label>
                <input type="date" name="fecha" min="<?= date("Y-m-d") ?>" value="<?= h($b_fecha) ?>"></div>
            <div class="field full"><label>Motivo de la consulta (opcional)</label>
                <input name="motivo" maxlength="255" value="<?= h($b_motivo) ?>"></div>
        </div>
        <div class="form-actions"><button class="btn" type="submit">Buscar horarios libres</button></div>
    </form>

    <?php if ($buscando): ?>
        <hr class="sep">
        <?php if ($aviso_busqueda): ?>
            <div class="alert"><?= h($aviso_busqueda) ?></div>
        <?php else: ?>
            <h4>Horarios disponibles — elige uno para reservar</h4>
            <div class="slots">
                <?php foreach ($resultados as $r): ?>
                    <form method="post" class="slot">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agendar">
                        <input type="hidden" name="id_mascota" value="<?= $b_masc ?>">
                        <input type="hidden" name="id_servicio" value="<?= $b_serv ?>">
                        <input type="hidden" name="id_empleado" value="<?= (int)$r["id_vet"] ?>">
                        <input type="hidden" name="fecha" value="<?= h($r["fecha"]) ?>">
                        <input type="hidden" name="hora" value="<?= h($r["hora"]) ?>">
                        <input type="hidden" name="motivo" value="<?= h($b_motivo) ?>">
                        <div class="slot-info"><strong><?= h($r["hora"]) ?></strong><br>
                            <?= h(nombre_dia($r["fecha"])) ?><br><span class="muted"><?= h($r["vet"]) ?></span></div>
                        <button class="btn small" type="submit">Reservar</button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="table-card">
    <h3>Mis citas</h3>
    <p>
        <a class="btn small <?= $filtro === "proximos" ? "" : "secondary" ?>" href="citas.php?ver=proximos">Próximos</a>
        <a class="btn small <?= $filtro === "todos" ? "" : "secondary" ?>" href="citas.php?ver=todos">Todos (historial)</a>
    </p>
    <div class="table-wrap">
        <table>
            <tr><th>Fecha</th><th>Hora</th><th>Mascota</th><th>Veterinario</th><th>Servicio</th><th>Estado</th><th></th></tr>
            <?php $hay = false; while ($x = $mis->fetch_assoc()): $hay = true; ?>
                <tr>
                    <td><?= h(date("d/m/Y", strtotime($x["fecha"]))) ?></td>
                    <td><?= h(substr($x["hora"], 0, 5)) ?></td>
                    <td><?= h($x["mascota"]) ?></td>
                    <td><?= h($x["veterinario"]) ?></td>
                    <td><?= h($x["servicio"]) ?></td>
                    <td><span class="badge <?= $x["estado"] === "Cancelado" ? "block" : "" ?>"><?= h($x["estado"]) ?></span></td>
                    <td class="actions">
                        <?php if ($x["futuro"] && in_array($x["estado"], ["Pendiente", "Confirmado"], true)): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="cancelar">
                                <input type="hidden" name="id_turno" value="<?= (int)$x["id"] ?>">
                                <button class="btn danger small" type="submit">Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$hay): ?><tr><td colspan="7">No tienes citas <?= $filtro === "proximos" ? "próximos" : "registrados" ?>.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
<?php require "includes/footer.php"; ?>
