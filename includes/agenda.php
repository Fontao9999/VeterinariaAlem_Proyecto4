<?php
// ============================================================
// AGENDA DE citas - cálculo de horarios disponibles
// Los horarios se configuran en config.php.
// ============================================================

// Todas las horas posibles de un día (ej: 08:00, 08:30, ... 18:30)
function horas_del_dia() {
    $horas = [];
    $t = strtotime("1970-01-01 " . HORA_APERTURA);
    $fin = strtotime("1970-01-01 " . HORA_CIERRE);
    while ($t + MINUTOS_POR_TURNO * 60 <= $fin) {
        $horas[] = date("H:i", $t);
        $t += MINUTOS_POR_TURNO * 60;
    }
    return $horas;
}

function dia_de_atencion($fecha) {
    return in_array((int)date("N", strtotime($fecha)), DIAS_ATENCION, true);
}

// Horarios libres de un veterinario en una fecha (sin citas cancelados, sin horas pasadas).
function horarios_libres($conexion, $id_vet, $fecha) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !dia_de_atencion($fecha)) return [];
    if ($fecha < date("Y-m-d")) return [];

    $stmt = $conexion->prepare(
        "SELECT time_format(hora, '%H:%i') AS h FROM citas
         WHERE id_empleado = ? AND fecha = ? AND estado <> 'Cancelado'"
    );
    $stmt->bind_param("is", $id_vet, $fecha);
    $stmt->execute();
    $ocupadas = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "h");

    $ahora = date("H:i");
    $libres = [];
    foreach (horas_del_dia() as $h) {
        if (in_array($h, $ocupadas, true)) continue;
        if ($fecha === date("Y-m-d") && $h <= $ahora) continue;
        $libres[] = $h;
    }
    return $libres;
}

// Si no se elige fecha: primeros horarios libres en los próximos días.
// Devuelve [["fecha"=>, "hora"=>, "id_vet"=>, "vet"=>], ...]
function proximos_horarios($conexion, $vets, $max = 12) {
    $res = [];
    for ($d = 0; $d < DIAS_BUSQUEDA && count($res) < $max; $d++) {
        $fecha = date("Y-m-d", strtotime("+$d day"));
        foreach ($vets as $v) {
            foreach (horarios_libres($conexion, (int)$v["id"], $fecha) as $h) {
                $res[] = ["fecha" => $fecha, "hora" => $h, "id_vet" => (int)$v["id"], "vet" => $v["nombre"]];
                if (count($res) >= $max) return $res;
                break; // un horario por veterinario y día, para dar variedad
            }
        }
    }
    return $res;
}

function nombre_dia($fecha) {
    $dias = [1 => "lunes", 2 => "martes", 3 => "miércoles", 4 => "jueves", 5 => "viernes", 6 => "sábado", 7 => "domingo"];
    return $dias[(int)date("N", strtotime($fecha))] . " " . date("d/m/Y", strtotime($fecha));
}
