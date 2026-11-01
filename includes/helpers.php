<?php
// ============================================================
// FUNCIONES COMUNES (escape de HTML, dinero, CSRF, dueño actual)
// ============================================================

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function dinero($n) {
    return "$ " . number_format((float)$n, 2, ",", ".");
}

function url_absoluta($ruta) {
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) $host = "localhost";
    return ($https ? "https://" : "http://") . $host . BASE_URL . "/" . ltrim($ruta, "/");
}

// ---- Protección CSRF para formularios nuevos ----
function csrf_token() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_validar() {
    return hash_equals($_SESSION["csrf"] ?? "", (string)($_POST["csrf"] ?? ""));
}

// ---- Dueño (cliente) asociado al usuario que inició sesión ----
// Cada cuenta de cliente tiene su ficha en la tabla "clientes".
// Si por algún motivo no existe (cuentas viejas), se crea acá.
function dueno_id_actual($conexion) {
    $uid = (int)($_SESSION["usuario_id"] ?? 0);
    if ($uid <= 0) return 0;

    $stmt = $conexion->prepare("SELECT nombre, email, id_cliente FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if (!$u) return 0;
    if (!empty($u["id_cliente"])) return (int)$u["id_cliente"];

    $ins = $conexion->prepare("INSERT INTO clientes(nombre, email) VALUES (?, ?)");
    $ins->bind_param("ss", $u["nombre"], $u["email"]);
    $ins->execute();
    $id = (int)$conexion->insert_id;

    $upd = $conexion->prepare("UPDATE usuarios SET id_cliente = ? WHERE id = ?");
    $upd->bind_param("ii", $id, $uid);
    $upd->execute();
    return $id;
}

// ---- Ficha de empleado asociada al usuario que inició sesión ----
// La tabla "empleados" se vincula con "usuarios" mediante id_usuario.
// Si aún no está vinculada pero el correo coincide, se vincula acá.
// Devuelve 0 si el usuario no tiene ficha de empleado.
function empleado_id_actual($conexion) {
    $uid = (int)($_SESSION["usuario_id"] ?? 0);
    if ($uid <= 0) return 0;

    $stmt = $conexion->prepare("SELECT id FROM empleados WHERE id_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    if ($e) return (int)$e["id"];

    $stmt = $conexion->prepare(
        "SELECT e.id FROM empleados e
         JOIN usuarios u ON u.email = e.email
         WHERE u.id = ? AND e.id_usuario IS NULL LIMIT 1"
    );
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    if (!$e) return 0;

    $upd = $conexion->prepare("UPDATE empleados SET id_usuario = ? WHERE id = ?");
    $upd->bind_param("ii", $uid, $e["id"]);
    $upd->execute();
    return (int)$e["id"];
}
