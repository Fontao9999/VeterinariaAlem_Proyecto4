<?php
// ============================================================
// FACTURA ELECTRÓNICA - genera el HTML del comprobante.
// Usa estilos en línea para que se vea igual en pantalla,
// en el correo y en el archivo descargado.
// ============================================================

function factura_html($f, $items) {
    $filas = "";
    foreach ($items as $i) {
        $sub = $i["precio"] * $i["cantidad"];
        $filas .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #dbe5e2">' . h($i["nombre"]) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #dbe5e2;text-align:center">' . (int)$i["cantidad"] . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #dbe5e2;text-align:right">' . dinero($i["precio"]) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #dbe5e2;text-align:right">' . dinero($sub) . '</td>'
            . '</tr>';
    }

    return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:720px;margin:auto;color:#263238;'
        . 'border:1px solid #dbe5e2;border-radius:12px;padding:26px;background:#fff">'
        . '<table style="width:100%;border-collapse:collapse"><tr>'
        . '<td><div style="font-size:22px;font-weight:bold;color:#0e5c4f">🐾 ' . h(VET_NOMBRE) . '</div>'
        . '<div style="font-size:13px;color:#607d8b">Tel. ' . h(VET_TELEFONO) . '<br>' . h(VET_EMAIL_SOPORTE) . '</div></td>'
        . '<td style="text-align:right"><div style="font-size:13px;color:#607d8b">FACTURA ELECTRÓNICA</div>'
        . '<div style="font-size:22px;font-weight:bold">' . h($f["numero"]) . '</div>'
        . '<div style="font-size:13px;color:#607d8b">' . h(date("d/m/Y H:i", strtotime($f["fecha"]))) . '</div></td>'
        . '</tr></table>'
        . '<hr style="border:none;border-top:1px solid #dbe5e2;margin:18px 0">'
        . '<div style="font-size:14px"><strong>Cliente:</strong> ' . h($f["cliente_nombre"]) . '<br>'
        . ($f["cliente_documento"] !== "" && $f["cliente_documento"] !== null ? '<strong>Documento:</strong> ' . h($f["cliente_documento"]) . '<br>' : '')
        . ($f["cliente_email"] ? '<strong>Correo:</strong> ' . h($f["cliente_email"]) . '<br>' : '')
        . ($f["cliente_direccion"] ? '<strong>Dirección:</strong> ' . h($f["cliente_direccion"]) . '<br>' : '')
        . '<strong>Forma de pago:</strong> ' . h($f["metodo_pago"]) . '</div>'
        . '<table style="width:100%;border-collapse:collapse;margin-top:18px;font-size:14px">'
        . '<tr style="background:#e9f7f3"><th style="padding:8px;text-align:left">Producto</th>'
        . '<th style="padding:8px">Cant.</th><th style="padding:8px;text-align:right">Precio</th>'
        . '<th style="padding:8px;text-align:right">Importe</th></tr>'
        . $filas . '</table>'
        . '<table style="margin-left:auto;margin-top:14px;font-size:14px">'
        . '<tr><td style="padding:3px 10px">Subtotal (sin IVA)</td><td style="text-align:right">' . dinero($f["subtotal"]) . '</td></tr>'
        . '<tr><td style="padding:3px 10px">IVA ' . (int)IVA_PORCENTAJE . '%</td><td style="text-align:right">' . dinero($f["iva"]) . '</td></tr>'
        . '<tr><td style="padding:6px 10px;font-weight:bold;font-size:16px">TOTAL</td>'
        . '<td style="text-align:right;font-weight:bold;font-size:16px">' . dinero($f["total"]) . '</td></tr></table>'
        . '<p style="font-size:11px;color:#78909c;margin:22px 0 0">Comprobante electrónico generado por el sistema de '
        . h(VET_NOMBRE) . '. Los precios incluyen IVA. Documento de demostración del proyecto escolar: '
        . 'no tiene validez fiscal ante la DGI.</p></div>';
}
