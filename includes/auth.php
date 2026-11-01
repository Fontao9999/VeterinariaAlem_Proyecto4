<?php
// ============================================================
// AUTENTICACIÓN Y PERMISOS
// Utiliza sesiones de PHP. No utiliza JavaScript.
// ============================================================

require_once __DIR__ . "/../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}

require_once __DIR__ . "/helpers.php";

function usuario_logueado() {
    return isset($_SESSION["usuario_id"]);
}

function es_admin() {
    return ($_SESSION["rol"] ?? "") === "admin";
}

function es_empleado() {
    return ($_SESSION["rol"] ?? "") === "empleado";
}

// Personal de la veterinaria = administrador o empleado.
function es_personal() {
    return es_admin() || es_empleado();
}

function exigir_login() {
    if (!usuario_logueado()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function exigir_admin() {
    exigir_login();

    if (!es_admin()) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

// Panel del empleado: entra el empleado (y el admin, que puede ver todo).
function exigir_empleado() {
    exigir_login();

    if (!es_personal()) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

// ------------------------------------------------------------
// MATRIZ DE PERMISOS DEL EMPLEADO (A = alta, B = baja,
// M = modificación, L = listar). El administrador puede todo.
//   Mascotas  A B M L      Historial  A B M L
//   Servicios A B M L      Productos  A M L   (sin baja)
// ------------------------------------------------------------
function permisos_empleado() {
    return [
        "mascotas"  => ["alta", "baja", "modificar", "listar"],
        "historial" => ["alta", "baja", "modificar", "listar"],
        "servicios" => ["alta", "baja", "modificar", "listar"],
        "productos" => ["alta", "modificar", "listar"],
    ];
}

function puede($tabla, $accion) {
    if (es_admin()) return true;
    if (!es_empleado()) return false;
    $p = permisos_empleado();
    return isset($p[$tabla]) && in_array($accion, $p[$tabla], true);
}

function exigir_permiso($tabla, $accion) {
    exigir_login();

    if (!puede($tabla, $accion)) {
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}
