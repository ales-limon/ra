<?php
/**
 * Genera un USDZ del stand de prueba, para AR en iPhone (Quick Look).
 *
 * Safari no soporta WebXR, asi que en iOS el unico camino a la AR es Quick
 * Look, y Quick Look no lee GLB: pide USDZ. Este script lo arma sin Mac, sin
 * Blender y sin descargar nada.
 *
 * Un USDZ es un ZIP con dos reglas que no son opcionales:
 *   1. Todo almacenado SIN comprimir (metodo store).
 *   2. Los datos de cada archivo empiezan en un offset multiplo de 64.
 * Si alguna falla, Quick Look rechaza el archivo sin explicar el motivo.
 *
 * Uso:  php make-stand-usdz.php  ../models/stand-demo.usdz
 */

require __DIR__ . '/stand-piezas.php';

$destino = $argv[1] ?? __DIR__ . '/../models/stand-demo.usdz';

// ---------------------------------------------------------------------------
// Formato de numeros: USD es texto, y los flotantes largos inflan el archivo
// sin aportar precision util a esta escala.
// ---------------------------------------------------------------------------
function n(float $v): string
{
    $s = rtrim(rtrim(sprintf('%.5f', $v), '0'), '.');
    return $s === '' || $s === '-0' ? '0' : $s;
}

function vec(array $v): string
{
    return '(' . n($v[0]) . ', ' . n($v[1]) . ', ' . n($v[2]) . ')';
}

// ---------------------------------------------------------------------------
// USDA: el USD en texto plano. Se hornea la transformacion de cada pieza
// directamente en sus vertices, en vez de usar Xform anidados: menos supuestos
// sobre el orden en que cada visor aplica escala y traslacion.
// ---------------------------------------------------------------------------
$caras      = stand_caras();
$materiales = stand_materiales();
$piezas     = stand_piezas();

$usda  = "#usda 1.0\n(\n";
$usda .= "    defaultPrim = \"Stand\"\n";
$usda .= "    metersPerUnit = 1\n";
$usda .= "    upAxis = \"Y\"\n";
$usda .= ")\n\n";
$usda .= "def Xform \"Stand\" (\n    kind = \"component\"\n)\n{\n";

// --- Materiales (UsdPreviewSurface, que es lo que entiende Quick Look) ---
$usda .= "    def Scope \"Materiales\"\n    {\n";
foreach ($materiales as $m) {
    $id = $m['nombre'];
    $usda .= "        def Material \"$id\"\n        {\n";
    $usda .= "            token outputs:surface.connect = </Stand/Materiales/$id/PBR.outputs:surface>\n\n";
    $usda .= "            def Shader \"PBR\"\n            {\n";
    $usda .= "                uniform token info:id = \"UsdPreviewSurface\"\n";
    $usda .= "                color3f inputs:diffuseColor = " . vec($m['color']) . "\n";
    $usda .= "                float inputs:metallic = " . n($m['metal']) . "\n";
    $usda .= "                float inputs:roughness = " . n($m['rug']) . "\n";
    $usda .= "                token outputs:surface\n";
    $usda .= "            }\n        }\n";
}
$usda .= "    }\n\n";

// --- Mallas ---
foreach ($piezas as $i => [$material, $escala, $centro]) {
    $puntos   = [];
    $normales = [];
    $min = [INF, INF, INF];
    $max = [-INF, -INF, -INF];

    foreach ($caras as [$normal, $vertices]) {
        foreach ($vertices as $v) {
            $p = [
                $v[0] * $escala[0] + $centro[0],
                $v[1] * $escala[1] + $centro[1],
                $v[2] * $escala[2] + $centro[2],
            ];
            $puntos[]   = vec($p);
            // Las piezas son cajas alineadas a los ejes, asi que la escala no
            // desvia las normales: siguen siendo la normal de la cara.
            $normales[] = vec($normal);
            for ($k = 0; $k < 3; $k++) {
                $min[$k] = min($min[$k], $p[$k]);
                $max[$k] = max($max[$k], $p[$k]);
            }
        }
    }

    $nombre  = "pieza_{$i}_{$material}";
    $indices = implode(', ', range(0, count($puntos) - 1));
    $conteos = implode(', ', array_fill(0, count($caras), 4));

    $usda .= "    def Mesh \"$nombre\"\n    {\n";
    $usda .= "        uniform bool doubleSided = 1\n";
    $usda .= "        float3[] extent = [" . vec($min) . ", " . vec($max) . "]\n";
    $usda .= "        int[] faceVertexCounts = [$conteos]\n";
    $usda .= "        int[] faceVertexIndices = [$indices]\n";
    $usda .= "        normal3f[] normals = [" . implode(', ', $normales) . "] (\n";
    $usda .= "            interpolation = \"vertex\"\n        )\n";
    $usda .= "        point3f[] points = [" . implode(', ', $puntos) . "]\n";
    $usda .= "        rel material:binding = </Stand/Materiales/$material>\n";
    $usda .= "        uniform token subdivisionScheme = \"none\"\n";
    $usda .= "    }\n\n";
}

$usda .= "}\n";

// ---------------------------------------------------------------------------
// Empaquetado ZIP con alineacion a 64 bytes.
//
// El relleno va en el campo "extra" de la cabecera local, que es justo para
// esto. Si hicieran falta menos de 4 bytes no cabria su encabezado, asi que en
// ese caso se suma un bloque completo de 64 mas.
// ---------------------------------------------------------------------------
function usdz_empaquetar(array $archivos, string $destino): array
{
    $ALINEACION = 64;
    $FECHA_DOS  = (44 << 9) | (1 << 5) | 1;   // 2024-01-01, fija y reproducible

    $cuerpo  = '';
    $central = '';
    $informe = [];

    foreach ($archivos as $nombre => $datos) {
        $crc    = crc32($datos) & 0xFFFFFFFF;
        $tam    = strlen($datos);
        $offset = strlen($cuerpo);

        // Donde caerian los datos si el campo extra estuviera vacio.
        $inicioDatos = $offset + 30 + strlen($nombre);
        $relleno     = ($ALINEACION - ($inicioDatos % $ALINEACION)) % $ALINEACION;
        if ($relleno > 0 && $relleno < 4) {
            $relleno += $ALINEACION;   // sitio para el encabezado del campo extra
        }

        $extra = '';
        if ($relleno > 0) {
            // Id 0xFFFF: reservado, ningun lector le atribuye significado.
            $extra = pack('vv', 0xFFFF, $relleno - 4) . str_repeat("\0", $relleno - 4);
        }

        $cuerpo .= "PK\x03\x04"
                 . pack('vvvvv', 10, 0, 0, 0, $FECHA_DOS)
                 . pack('VVV', $crc, $tam, $tam)
                 . pack('vv', strlen($nombre), strlen($extra))
                 . $nombre . $extra;

        $informe[] = [
            'nombre'    => $nombre,
            'offset'    => strlen($cuerpo),
            'tam'       => $tam,
            'alineado'  => strlen($cuerpo) % $ALINEACION === 0,
        ];

        $cuerpo .= $datos;

        $central .= "PK\x01\x02"
                  . pack('vvvvvv', 20, 10, 0, 0, 0, $FECHA_DOS)
                  . pack('VVV', $crc, $tam, $tam)
                  . pack('vvvvv', strlen($nombre), 0, 0, 0, 0)
                  . pack('V', 0)
                  . pack('V', $offset)
                  . $nombre;
    }

    $zip = $cuerpo . $central . "PK\x05\x06"
         . pack('vvvv', 0, 0, count($archivos), count($archivos))
         . pack('VV', strlen($central), strlen($cuerpo))
         . pack('v', 0);

    if (!is_dir(dirname($destino))) {
        mkdir(dirname($destino), 0755, true);
    }
    file_put_contents($destino, $zip);

    return $informe;
}

// El archivo USD debe ir primero dentro del paquete.
$informe = usdz_empaquetar(['stand-demo.usda' => $usda], $destino);

printf("USDZ escrito: %s (%d bytes)\n", realpath($destino), filesize($destino));
printf("USDA interno: %d bytes, %d mallas\n", strlen($usda), count($piezas));
foreach ($informe as $f) {
    printf("  %-20s datos en offset %6d  %s\n",
        $f['nombre'], $f['offset'], $f['alineado'] ? 'alineado a 64 OK' : 'DESALINEADO');
}
