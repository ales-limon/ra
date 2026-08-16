<?php
/**
 * Genera un GLB de prueba: un stand dummy para calibrar la experiencia AR.
 *
 * No descarga nada ni depende de librerias: escribe el binario glTF 2.0 a mano.
 * Uso:  php make-stand-glb.php  ../models/stand-demo.glb
 *
 * El modelo mide 3 m de ancho x 2 m de fondo x 2.4 m de alto, con el frente
 * mirando hacia +Z y la base apoyada en Y=0. Esas dos convenciones son las que
 * importan al montarlo sobre el marcador.
 */

require __DIR__ . '/stand-piezas.php';

$destino = $argv[1] ?? __DIR__ . '/../models/stand-demo.glb';

// Geometria: un cubo unitario centrado en el origen, reutilizado por todas las
// piezas via escala/traslacion de cada nodo.
$caras = stand_caras();

$posBin = '';
$norBin = '';
$idxBin = '';
$base   = 0;

foreach ($caras as [$normal, $vertices]) {
    foreach ($vertices as $v) {
        $posBin .= pack('ggg', $v[0], $v[1], $v[2]);
        $norBin .= pack('ggg', $normal[0], $normal[1], $normal[2]);
    }
    foreach ([0, 1, 2, 0, 2, 3] as $i) {
        $idxBin .= pack('v', $base + $i);
    }
    $base += 4;
}

$bin = $posBin . $norBin . $idxBin;           // 288 + 288 + 72 = 648 bytes
$offPos = 0;
$offNor = strlen($posBin);
$offIdx = $offNor + strlen($norBin);

// Segundo argumento: factor de escala. 1 = tamano real, 0.05 = maqueta 1:20.
$factor = isset($argv[2]) ? (float) $argv[2] : 1.0;

$materiales = stand_materiales();
$piezas     = stand_piezas($factor);

$gltf = [
    'asset'       => ['version' => '2.0', 'generator' => 'StandHouse dummy stand generator'],
    'scene'       => 0,
    'scenes'      => [['name' => 'stand-demo', 'nodes' => range(0, count($piezas) - 1)]],
    'nodes'       => [],
    'meshes'      => [],
    'materials'   => [],
    'buffers'     => [['byteLength' => strlen($bin)]],
    'bufferViews' => [
        ['buffer' => 0, 'byteOffset' => $offPos, 'byteLength' => strlen($posBin), 'target' => 34962],
        ['buffer' => 0, 'byteOffset' => $offNor, 'byteLength' => strlen($norBin), 'target' => 34962],
        ['buffer' => 0, 'byteOffset' => $offIdx, 'byteLength' => strlen($idxBin), 'target' => 34963],
    ],
    'accessors'   => [
        ['bufferView' => 0, 'componentType' => 5126, 'count' => 24, 'type' => 'VEC3',
         'min' => [-0.5, -0.5, -0.5], 'max' => [0.5, 0.5, 0.5]],
        ['bufferView' => 1, 'componentType' => 5126, 'count' => 24, 'type' => 'VEC3'],
        ['bufferView' => 2, 'componentType' => 5123, 'count' => 36, 'type' => 'SCALAR'],
    ],
];

// Un mesh por material: en glTF el material se asigna al primitivo, no al nodo.
$indicePorMaterial = [];
foreach ($materiales as $m) {
    $gltf['materials'][] = [
        'name' => $m['nombre'],
        'doubleSided' => true,
        'pbrMetallicRoughness' => [
            // glTF pide RGBA; la definicion compartida guarda solo RGB.
            'baseColorFactor' => array_merge($m['color'], [1.0]),
            'metallicFactor'  => $m['metal'],
            'roughnessFactor' => $m['rug'],
        ],
    ];
    $gltf['meshes'][] = [
        'name' => 'caja_' . $m['nombre'],
        'primitives' => [[
            'attributes' => ['POSITION' => 0, 'NORMAL' => 1],
            'indices'    => 2,
            'material'   => count($gltf['materials']) - 1,
        ]],
    ];
    $indicePorMaterial[$m['nombre']] = count($gltf['meshes']) - 1;
}

foreach ($piezas as $i => [$material, $escala, $centro]) {
    $gltf['nodes'][] = [
        'name'        => 'pieza_' . $i . '_' . $material,
        'mesh'        => $indicePorMaterial[$material],
        'translation' => $centro,
        'scale'       => $escala,
    ];
}

// ---------------------------------------------------------------------------
// Empaquetado GLB: cabecera + chunk JSON + chunk binario, cada uno alineado a 4.
// ---------------------------------------------------------------------------
$json = json_encode($gltf, JSON_UNESCAPED_SLASHES);
$json .= str_repeat(' ', (4 - strlen($json) % 4) % 4);
$bin  .= str_repeat("\0", (4 - strlen($bin) % 4) % 4);

$glb  = pack('VVV', 0x46546C67, 2, 12 + 8 + strlen($json) + 8 + strlen($bin));
$glb .= pack('VV', strlen($json), 0x4E4F534A) . $json;
$glb .= pack('VV', strlen($bin),  0x004E4942) . $bin;

if (!is_dir(dirname($destino))) {
    mkdir(dirname($destino), 0755, true);
}
file_put_contents($destino, $glb);

printf("GLB escrito: %s (%d bytes, %d nodos, %d materiales)\n",
    realpath($destino), strlen($glb), count($gltf['nodes']), count($gltf['materials']));
