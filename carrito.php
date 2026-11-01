<?php
// ============================================================
// CARRITO Y COMPRA
// Al confirmar: se descuenta el stock, se registra el pedido y se
// genera la factura electrónica (también se envía por correo).
// El pago es SIMULADO (no se conecta con ningún banco ni tarjeta).
// ============================================================
require "includes/auth.php";
require "includes/mailer.php";
require "includes/factura_html.php";
require "conexion.php";
exigir_login();

if (es_admin()) {
    header("Location: admin/productos.php");
    exit;
}
if (es_empleado()) {
    header("Location: empleado/index.php");
    exit;
}

$error = "";
$msg = "";
$metodos = ["Efectivo al retirar en el local", "Transferencia bancaria", "Tarjeta (pago simulado)"];

if (!isset($_SESSION["carrito"]) || !is_array($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

$id_cliente = dueno_id_actual($conexion);

// Datos del cliente para la factura
$stmt = $conexion->prepare("SELECT nombre, cedula, email, direccion FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$dueno = $stmt->get_result()->fetch_assoc() ?: ["nombre" => $_SESSION["nombre"], "cedula" => "", "email" => "", "direccion" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_validar()) {
        $error = "La sesión venció. Intenta de nuevo.";
    } else {
        $accion = $_POST["accion"] ?? "";

        if ($accion === "actualizar") {
            foreach (($_POST["cant"] ?? []) as $id => $c) {
                $id = (int)$id; $c = (int)$c;
                if (isset($_SESSION["carrito"][$id])) {
                    if ($c <= 0) unset($_SESSION["carrito"][$id]);
                    else $_SESSION["carrito"][$id] = min(99, $c);
                }
            }
            $msg = "Carrito actualizado.";
        } elseif ($accion === "quitar") {
            unset($_SESSION["carrito"][(int)($_POST["id_producto"] ?? 0)]);
            $msg = "Producto quitado.";
        } elseif ($accion === "vaciar") {
            $_SESSION["carrito"] = [];
            $msg = "Carrito vaciado.";
        } elseif ($accion === "comprar") {
            $nombre = trim($_POST["f_nombre"] ?? "");
            $doc = trim($_POST["f_documento"] ?? "");
            $dir = trim($_POST["f_direccion"] ?? "");
            $metodo = $_POST["metodo_pago"] ?? "";

            if (!$_SESSION["carrito"]) {
                $error = "Tu carrito está vacío.";
            } elseif ($nombre === "") {
                $error = "Completa el nombre para la factura.";
            } elseif (!in_array($metodo, $metodos, true)) {
                $error = "Elige una forma de pago.";
            } elseif (($_POST["acepto_datos"] ?? "") !== "1") {
                $error = "Debes aceptar el uso de tus datos para emitir la factura.";
            } else {
                $id_usuario = (int)$_SESSION["usuario_id"];
                $email_cli = "";
                $q = $conexion->prepare("SELECT email FROM usuarios WHERE id = ?");
                $q->bind_param("i", $id_usuario);
                $q->execute();
                $email_cli = $q->get_result()->fetch_assoc()["email"] ?? "";

                $conexion->begin_transaction();
                try {
                    // 1) bloquear y validar productos (precio y stock se leen de la base, no del navegador)
                    $items = [];
                    $total = 0.0;
                    $ids = array_keys($_SESSION["carrito"]);
                    sort($ids);
                    foreach ($ids as $id) {
                        $cant = (int)$_SESSION["carrito"][$id];
                        $s = $conexion->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ? FOR UPDATE");
                        $s->bind_param("i", $id);
                        $s->execute();
                        $p = $s->get_result()->fetch_assoc();
                        if (!$p) throw new RuntimeException("Un producto del carrito ya no existe.");
                        if ($cant > (int)$p["stock"]) {
                            throw new RuntimeException("No hay stock suficiente de «" . $p["nombre"] . "» (disponible: " . (int)$p["stock"] . ").");
                        }
                        $items[] = ["id" => (int)$p["id"], "nombre" => $p["nombre"], "precio" => (float)$p["precio"], "cantidad" => $cant];
                        $total += $p["precio"] * $cant;
                    }
                    $total = round($total, 2);

                    // 2) pedido
                    $pe = $conexion->prepare("INSERT INTO pedidos(id_usuario, total, metodo_pago, estado) VALUES (?, ?, ?, 'Pagado')");
                    $pe->bind_param("ids", $id_usuario, $total, $metodo);
                    $pe->execute();
                    $id_pedido = $conexion->insert_id;

                    // 3) items + descuento de stock
                    foreach ($items as $it) {
                        $ii = $conexion->prepare("INSERT INTO pedido_items(id_pedido, id_producto, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)");
                        $ii->bind_param("iisdi", $id_pedido, $it["id"], $it["nombre"], $it["precio"], $it["cantidad"]);
                        $ii->execute();
                        $us = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                        $us->bind_param("ii", $it["cantidad"], $it["id"]);
                        $us->execute();
                    }

                    // 4) factura (los precios ya incluyen IVA)
                    $subtotal = round($total / (1 + IVA_PORCENTAJE / 100), 2);
                    $iva = round($total - $subtotal, 2);
                    $temporal = "T" . bin2hex(random_bytes(8));
                    $fa = $conexion->prepare(
                        "INSERT INTO facturas(numero, id_pedido, id_usuario, cliente_nombre, cliente_documento,
                                              cliente_email, cliente_direccion, subtotal, iva, total, metodo_pago)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $fa->bind_param("siissssddds", $temporal, $id_pedido, $id_usuario, $nombre, $doc, $email_cli, $dir, $subtotal, $iva, $total, $metodo);
                    $fa->execute();
                    $id_factura = $conexion->insert_id;
                    $numero = FACTURA_SERIE . "-" . str_pad((string)$id_factura, 6, "0", STR_PAD_LEFT);
                    $un = $conexion->prepare("UPDATE facturas SET numero = ? WHERE id = ?");
                    $un->bind_param("si", $numero, $id_factura);
                    $un->execute();

                    // 5) guardar datos de facturación en la ficha del cliente (si estaban vacíos)
                    $ud = $conexion->prepare(
                        "UPDATE clientes SET cedula = IF(cedula IS NULL OR cedula = '', ?, cedula),
                                           direccion = IF(direccion IS NULL OR direccion = '', ?, direccion)
                         WHERE id = ?"
                    );
                    $ud->bind_param("ssi", $doc, $dir, $id_cliente);
                    $ud->execute();

                    $conexion->commit();
                } catch (RuntimeException $e) {
                    $conexion->rollback();
                    $error = $e->getMessage();
                } catch (mysqli_sql_exception $e) {
                    $conexion->rollback();
                    $error = "No se pudo completar la compra. Intenta nuevamente.";
                }

                if ($error === "") {
                    $_SESSION["carrito"] = [];

                    // 6) enviar la factura por correo (si falla, igual queda disponible en "Facturas")
                    if ($email_cli !== "") {
                        $f = ["numero" => $numero, "fecha" => date("Y-m-d H:i:s"), "cliente_nombre" => $nombre,
                              "cliente_documento" => $doc, "cliente_email" => $email_cli, "cliente_direccion" => $dir,
                              "subtotal" => $subtotal, "iva" => $iva, "total" => $total, "metodo_pago" => $metodo];
                        $cuerpo = '<p>Gracias por tu compra en ' . h(VET_NOMBRE) . '. Esta es tu factura electrónica:</p>'
                                . factura_html($f, $items);
                        enviar_correo($email_cli, "Tu factura " . $numero . " - " . VET_NOMBRE, $cuerpo);
                    }

                    header("Location: factura.php?id=" . $id_factura . "&nueva=1");
                    exit;
                }
            }
        }
    }
}

// Cargar el carrito con datos actuales de la base
$lineas = [];
$total = 0.0;
foreach ($_SESSION["carrito"] as $id => $cant) {
    $s = $conexion->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");
    $id = (int)$id;
    $s->bind_param("i", $id);
    $s->execute();
    $p = $s->get_result()->fetch_assoc();
    if (!$p) { unset($_SESSION["carrito"][$id]); continue; }
    $p["cantidad"] = (int)$cant;
    $p["importe"] = $p["precio"] * $cant;
    $total += $p["importe"];
    $lineas[] = $p;
}

require "includes/header.php";
?>
<section class="hero">
    <div class="hero-kicker">Tienda veterinaria</div>
    <h2>Carrito de compras 🛒</h2>
    <p>Revisa tu pedido y finaliza la compra. Se generará una factura electrónica y te la enviamos por correo.</p>
</section>

<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>

<?php if (!$lineas): ?>
    <div class="form-card">
        <p>Tu carrito está vacío.</p>
        <a class="btn" href="productos.php">Ir a la tienda</a>
    </div>
<?php else: ?>
    <div class="table-card">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="actualizar">
            <div class="table-wrap">
                <table>
                    <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Importe</th><th></th></tr>
                    <?php foreach ($lineas as $l): ?>
                        <tr>
                            <td><?= h($l["nombre"]) ?></td>
                            <td><?= dinero($l["precio"]) ?></td>
                            <td><input class="cant" type="number" name="cant[<?= (int)$l["id"] ?>]" value="<?= (int)$l["cantidad"] ?>" min="0" max="<?= min(99, (int)$l["stock"]) ?>"></td>
                            <td><?= dinero($l["importe"]) ?></td>
                            <td>
                                <button class="btn danger small" type="submit" form="quitar-<?= (int)$l["id"] ?>">Quitar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td colspan="3"><strong>Total (IVA incluido)</strong></td><td colspan="2"><strong><?= dinero($total) ?></strong></td></tr>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn secondary" type="submit">Actualizar cantidades</button>
            </div>
        </form>
        <?php foreach ($lineas as $l): ?>
            <form id="quitar-<?= (int)$l["id"] ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="quitar">
                <input type="hidden" name="id_producto" value="<?= (int)$l["id"] ?>">
            </form>
        <?php endforeach; ?>
    </div>

    <div class="form-card">
        <h3>Finalizar compra</h3>
        <p class="muted">Estos datos aparecerán en tu factura electrónica.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="comprar">
            <div class="form-grid">
                <div class="field"><label>Nombre o razón social</label>
                    <input name="f_nombre" value="<?= h($_POST["f_nombre"] ?? $dueno["nombre"]) ?>" required maxlength="100"></div>
                <div class="field"><label>Cédula / RUT (opcional)</label>
                    <input name="f_documento" value="<?= h($_POST["f_documento"] ?? $dueno["cedula"]) ?>" maxlength="30"></div>
                <div class="field full"><label>Dirección (opcional)</label>
                    <input name="f_direccion" value="<?= h($_POST["f_direccion"] ?? $dueno["direccion"]) ?>" maxlength="200"></div>
                <div class="field full"><label>Forma de pago</label>
                    <select name="metodo_pago" required>
                        <?php foreach ($metodos as $m): ?>
                            <option <?= ($_POST["metodo_pago"] ?? "") === $m ? "selected" : "" ?>><?= h($m) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <br>
            <label class="check-line">
                <input type="checkbox" name="acepto_datos" value="1" required>
                <span>Acepto que se usen mis datos para emitir la factura, según la
                    <a href="privacidad.php" target="_blank">política de privacidad</a>.</span>
            </label>
            <div class="form-actions">
                <button class="btn" type="submit">Confirmar compra · <?= dinero($total) ?></button>
                <a class="btn secondary" href="productos.php">Seguir comprando</a>
            </div>
        </form>
        <form method="post" class="form-actions">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="vaciar">
            <button class="btn danger small" type="submit">Vaciar carrito</button>
        </form>
    </div>
<?php endif; ?>
<?php require "includes/footer.php"; ?>
