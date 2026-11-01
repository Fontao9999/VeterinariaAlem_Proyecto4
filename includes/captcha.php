<?php
// ============================================================
// CAPTCHA "NO SOY UN ROBOT"
// Funciona solo con PHP + HTML + CSS (sin JavaScript):
//   1) casilla "No soy un robot" obligatoria,
//   2) campo trampa oculto (los robots lo completan, las personas no),
//   3) tiempo mínimo entre que se abre el formulario y se envía,
//   4) token de un solo uso guardado en la sesión.
// Uso: en el formulario  echo captcha_html();
//      al recibir el POST  if (!validar_captcha()) { ... }
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function nuevo_captcha() {
    $_SESSION["nr_token"] = bin2hex(random_bytes(16));
    $_SESSION["nr_inicio"] = time();
    return $_SESSION["nr_token"];
}

function captcha_html() {
    if (empty($_SESSION["nr_token"])) {
        nuevo_captcha();
    }
    $token = htmlspecialchars($_SESSION["nr_token"], ENT_QUOTES, "UTF-8");
    return '<div class="robot-box">'
        . '<label class="robot-check"><input type="checkbox" name="no_robot" value="1" required> '
        . '<span>No soy un robot</span></label>'
        . '<span class="robot-logo">🛡️ Verificación</span>'
        . '</div>'
        . '<input type="hidden" name="nr_token" value="' . $token . '">'
        . '<div class="trampa" aria-hidden="true"><label>Dejar vacío'
        . '<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>';
}

// El parámetro se ignora; se mantiene para no romper llamadas antiguas.
function validar_captcha($ignorado = null) {
    $token = $_SESSION["nr_token"] ?? "";
    $inicio = (int)($_SESSION["nr_inicio"] ?? 0);
    unset($_SESSION["nr_token"], $_SESSION["nr_inicio"]); // un solo uso

    if ($token === "" || !hash_equals($token, (string)($_POST["nr_token"] ?? ""))) return false;
    if (($_POST["no_robot"] ?? "") !== "1") return false;
    if (($_POST["sitio_web"] ?? "") !== "") return false;      // campo trampa
    $pasado = time() - $inicio;
    if ($pasado < 2 || $pasado > 7200) return false;           // muy rápido (robot) o vencido
    return true;
}

const MENSAJE_CAPTCHA = "Marca la casilla «No soy un robot» y vuelve a intentar.";
