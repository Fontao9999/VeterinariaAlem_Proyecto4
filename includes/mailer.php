<?php
// ============================================================
// ENVÍO DE CORREOS
// - Con SMTP_CLAVE configurada: envía por SMTP (Gmail, etc.).
// - Sin SMTP_CLAVE: MODO DEMOSTRACIÓN, guarda el correo como archivo
//   .eml en /correos_enviados (no sale nada a internet).
// Devuelve un arreglo: ["ok" => bool, "modo" => "smtp"|"demo", "error" => string]
// ============================================================
require_once __DIR__ . "/../config.php";

function correo_modo_demo() {
    return SMTP_CLAVE === "";
}

function enviar_correo($para, $asunto, $html) {
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return ["ok" => false, "modo" => "smtp", "error" => "Correo de destino inválido."];
    }
    $texto = trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/tr|\/h\d)>/i', "\n", $html)), ENT_QUOTES, "UTF-8"));
    $mensaje = construir_mensaje($para, $asunto, $html, $texto);

    if (correo_modo_demo()) {
        $dir = __DIR__ . "/../correos_enviados";
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . "/.htaccess", "Require all denied\n");
        $archivo = $dir . "/" . date("Ymd_His") . "_" . bin2hex(random_bytes(3)) . ".eml";
        $ok = @file_put_contents($archivo, $mensaje) !== false;
        return ["ok" => $ok, "modo" => "demo", "error" => $ok ? "" : "No se pudo escribir en /correos_enviados."];
    }

    $destinatarios = [$para];
    if (MAIL_COPIA_A !== "" && filter_var(MAIL_COPIA_A, FILTER_VALIDATE_EMAIL) && MAIL_COPIA_A !== $para) {
        $destinatarios[] = MAIL_COPIA_A;
    }
    $error = "";
    $ok = smtp_enviar($destinatarios, $mensaje, $error);
    return ["ok" => $ok, "modo" => "smtp", "error" => $error];
}

function construir_mensaje($para, $asunto, $html, $texto) {
    $limite = "=_alem_" . bin2hex(random_bytes(8));
    $enc = function ($s) { return "=?UTF-8?B?" . base64_encode($s) . "?="; };
    $h = [];
    $h[] = "Date: " . date("r");
    $h[] = "From: " . $enc(MAIL_FROM_NOMBRE) . " <" . MAIL_FROM . ">";
    $h[] = "To: <" . $para . ">";
    $h[] = "Reply-To: <" . VET_EMAIL_SOPORTE . ">";
    $h[] = "Subject: " . $enc($asunto);
    $h[] = "Message-ID: <" . bin2hex(random_bytes(10)) . "@veterinariaalem.local>";
    $h[] = "MIME-Version: 1.0";
    $h[] = "Content-Type: multipart/alternative; boundary=\"$limite\"";
    $cuerpo  = "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $cuerpo .= chunk_split(base64_encode($texto)) . "\r\n";
    $cuerpo .= "--$limite\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $cuerpo .= chunk_split(base64_encode($html)) . "\r\n";
    $cuerpo .= "--$limite--\r\n";
    return implode("\r\n", $h) . "\r\n\r\n" . $cuerpo;
}

// ---------- Cliente SMTP mínimo (sin librerías externas) ----------
function smtp_leer($fp) {
    $texto = "";
    while (($linea = fgets($fp, 1024)) !== false) {
        $texto .= $linea;
        if (strlen($linea) < 4 || $linea[3] === " ") break;
    }
    return [(int)substr($texto, 0, 3), trim($texto)];
}

function smtp_cmd($fp, $cmd, $esperados) {
    if ($cmd !== null) fwrite($fp, $cmd . "\r\n");
    [$codigo, $texto] = smtp_leer($fp);
    if (!in_array($codigo, (array)$esperados, true)) {
        throw new Exception("SMTP respondió: " . $texto);
    }
    return $texto;
}

function smtp_enviar($destinatarios, $mensaje, &$error) {
    $puerto = (int)SMTP_PORT;
    if (!extension_loaded("openssl")) {
        $error = "Falta la extensión openssl de PHP. En XAMPP: php.ini > quitar el ';' de extension=openssl y reiniciar Apache.";
        return false;
    }
    $destino = ($puerto === 465 ? "ssl://" : "tcp://") . SMTP_HOST . ":" . $puerto;
    $fp = @stream_socket_client($destino, $errno, $errstr, 15);
    if (!$fp) {
        $error = "No se pudo conectar con el servidor de correo ($errstr).";
        return false;
    }
    stream_set_timeout($fp, 20);
    try {
        smtp_cmd($fp, null, 220);
        $ehlo = smtp_cmd($fp, "EHLO localhost", 250);
        if ($puerto !== 465) {
            if (stripos($ehlo, "STARTTLS") !== false) {
                smtp_cmd($fp, "STARTTLS", 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("No se pudo establecer la conexión cifrada (TLS).");
                }
                smtp_cmd($fp, "EHLO localhost", 250);
            } elseif (!in_array(SMTP_HOST, ["localhost", "127.0.0.1"], true)) {
                throw new Exception("El servidor no ofrece conexión cifrada; se cancela el envío.");
            }
        }
        smtp_cmd($fp, "AUTH LOGIN", 334);
        smtp_cmd($fp, base64_encode(SMTP_USUARIO), 334);
        smtp_cmd($fp, base64_encode(SMTP_CLAVE), 235);
        smtp_cmd($fp, "MAIL FROM:<" . MAIL_FROM . ">", 250);
        foreach ($destinatarios as $d) {
            smtp_cmd($fp, "RCPT TO:<" . $d . ">", [250, 251]);
        }
        smtp_cmd($fp, "DATA", 354);
        fwrite($fp, $mensaje . "\r\n.\r\n");
        smtp_cmd($fp, null, 250);
        @smtp_cmd($fp, "QUIT", 221);
        fclose($fp);
        return true;
    } catch (Exception $e) {
        $error = $e->getMessage();
        @fclose($fp);
        return false;
    }
}

// ---------- Correo de verificación de cuenta ----------
// Crea un token nuevo, lo guarda (solo su hash) y envía el enlace.
// Devuelve ["resultado" => ..., "enlace" => ...]
function enviar_verificacion($conexion, $id_usuario, $nombre, $email) {
    $token = bin2hex(random_bytes(32));
    $hash = hash("sha256", $token);
    $stmt = $conexion->prepare(
        "UPDATE usuarios
         SET token_verificacion = ?, token_expira = DATE_ADD(NOW(), INTERVAL 24 HOUR),
             verificacion_enviada = NOW()
         WHERE id = ?"
    );
    $stmt->bind_param("si", $hash, $id_usuario);
    $stmt->execute();

    $enlace = url_absoluta("verificar.php?token=" . $token);
    $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#263238">'
        . '<h2 style="color:#167a68">🐾 ' . h(VET_NOMBRE) . '</h2>'
        . '<p>Hola <strong>' . h($nombre) . '</strong>,</p>'
        . '<p>Gracias por registrarte. Para activar tu cuenta confirma tu correo electrónico:</p>'
        . '<p><a href="' . h($enlace) . '" style="background:#167a68;color:#fff;padding:12px 20px;'
        . 'border-radius:8px;text-decoration:none;display:inline-block">Verificar mi correo</a></p>'
        . '<p style="font-size:13px;color:#607d8b">Si el botón no funciona, copia este enlace en tu navegador:<br>'
        . h($enlace) . '</p>'
        . '<p style="font-size:13px;color:#607d8b">El enlace vence en 24 horas. Si no creaste esta cuenta, ignora este mensaje.</p>'
        . '<p style="font-size:13px;color:#607d8b">Consultas: ' . h(VET_TELEFONO) . ' · ' . h(VET_EMAIL_SOPORTE) . '</p>'
        . '</div>';
    $r = enviar_correo($email, "Verifica tu correo - " . VET_NOMBRE, $html);
    return ["resultado" => $r, "enlace" => $enlace];
}
