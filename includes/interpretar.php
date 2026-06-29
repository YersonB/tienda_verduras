<?php
// includes/interpretar.php
// Motor para interpretar una lista de compras en texto y cruzarla con el inventario.
// Lo usan el cotizador del personal y el cotizador público del cliente.

function cot_normalizar(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}

function cot_parsear_linea(string $linea): array {
    $l = cot_normalizar($linea);
    $l = str_replace(',', '.', $l);
    $cantidad = null;

    if (preg_match('#(\d+)\s*/\s*(\d+)#', $l, $m)) {                 // fracción 1/2
        $cantidad = ((int)$m[2] !== 0) ? (float)$m[1] / (float)$m[2] : 1;
        $l = preg_replace('#(\d+)\s*/\s*(\d+)#', ' ', $l, 1);
    } elseif (preg_match('/(\d+(?:\.\d+)?)/', $l, $m)) {             // entero o decimal
        $cantidad = (float)$m[1];
        $l = preg_replace('/(\d+(?:\.\d+)?)/', ' ', $l, 1);
    } elseif (preg_match('/\b(medio|media)\b/', $l)) {               // medio kilo
        $cantidad = 0.5;
    }
    if (preg_match('/\bdocena\b/', $l)) $cantidad = ($cantidad ?? 1) * 12;
    if ($cantidad === null) $cantidad = 1;

    $stop = ['kg','kilo','kilos','kilogramo','kilogramos','gramos','gr','g','unidad','unidades','und','u',
        'atado','atados','de','del','la','el','los','las','un','una','y','medio','media','docena','docenas',
        'bolsa','bolsas','paquete','paquetes','litro','litros','lt','par','pares','porfa','porfavor','quiero','x'];
    $palabras = array_filter(preg_split('/\s+/', trim($l)), function ($w) use ($stop) {
        return $w !== '' && !in_array($w, $stop, true) && !is_numeric($w);
    });
    return [$cantidad, trim(implode(' ', $palabras))];
}

function cot_buscar_coincidencias(string $keyword, array $productos): array {
    $k = trim($keyword);
    if ($k === '') return [];
    $kwWords = array_values(array_filter(explode(' ', $k), fn($w) => mb_strlen($w) >= 2));
    if (!$kwWords) return [];

    $scored = [];
    foreach ($productos as $p) {
        $pWords = array_filter(explode(' ', $p['_norm']), fn($w) => mb_strlen($w) >= 2);
        $score = 0;
        if (mb_strpos($p['_norm'], $k) !== false || mb_strpos($k, $p['_norm']) !== false) $score += 5;
        foreach ($kwWords as $kw) {
            foreach ($pWords as $pw) {
                if ($kw === $pw) {
                    $score += 3;
                } elseif (mb_strlen($kw) >= 4 && mb_strlen($pw) >= 4
                          && (str_starts_with($kw, $pw) || str_starts_with($pw, $kw))) {
                    $score += 2;
                } elseif (mb_strlen($kw) >= 4
                          && (mb_strpos($pw, $kw) !== false || mb_strpos($kw, $pw) !== false)) {
                    $score += 1;
                }
            }
        }
        if ($score > 0) $scored[] = ['p' => $p, 's' => $score];
    }
    usort($scored, fn($a, $b) => $b['s'] <=> $a['s']);
    return array_map(fn($x) => [
        'id' => $x['p']['id'], 'nombre' => $x['p']['nombre'],
        'precio_venta' => $x['p']['precio_venta'], 'unidad_medida' => $x['p']['unidad_medida'],
    ], array_slice($scored, 0, 6));
}

/**
 * Interpreta el texto completo y devuelve las líneas con sus coincidencias.
 * Puede lanzar PDOException (que el llamador maneja).
 */
function cot_interpretar(PDO $pdo, string $texto): array {
    $texto = trim($texto);
    if ($texto === '') return [];

    $productos = $pdo->query("SELECT id, nombre, precio_venta, unidad_medida FROM productos")->fetchAll();
    foreach ($productos as &$p) { $p['_norm'] = cot_normalizar($p['nombre']); }
    unset($p);

    $resultados = [];
    foreach (preg_split('/\r\n|\r|\n/', $texto) as $linea) {
        $linea = trim($linea);
        if ($linea === '') continue;
        $limpia = ltrim($linea, "-*•·.\t ");
        [$cantidad, $keyword] = cot_parsear_linea($limpia);
        $resultados[] = [
            'linea'    => $linea,
            'cantidad' => $cantidad,
            'keyword'  => $keyword,
            'matches'  => cot_buscar_coincidencias($keyword, $productos),
        ];
    }
    return $resultados;
}
