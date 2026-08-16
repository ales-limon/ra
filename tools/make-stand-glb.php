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

$destino = $argv[1] ?? __DIR__ . '/../models/stand-demo.glb';

// ---------------------------------------------------------------------------
// Geometria: un cubo unitario centrado en el origen, reutilizado por todas las
// piezas via escala/traslacion de cada nodo.
// ---------------------------------------------------------------------------
$caras = [
    [[1, 0, 0],  [[.5,-.5,-.5], [.5,-.5,.5],  [.5,.5,.5],   [.5,.5,-.5]]],
    [[-1, 0, 0], [[-.5,-.5,.5], [-.5,-.5,-.5],[-.5,.5,-.5], [-.5,.5,.5]]],
    [[0, 1, 0],  [[-.5,.5,.5],  [.5,.5,.5],   [.5,.5,-.5],  [-.5,.5,-.5]]],
    [[0, -1, 0], [[-.5,-.5,-.5],[.5,-.5,-.5], [.5,-.5,.5],  [-.5,-.5,.5]]],
    [[0, 0, 1],  [[-.5,-.5,.5], [.5,-.5,.5],  [.5,.5,.5],   [-.5,.5,.5]]],
    [[0, 0, -1], [[.5,-.5,-.5], [-.5,-.5,-.5],[-.5,.5,-.5], [.5,.5,-.5]]],
];

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

// ---------------------------------------------------------------------------
// Materiales. Colores planos y opacos: en AR sobre hoja impresa, los materiales
// muy metalicos o muy oscuros se leen mal contra el fondo real.
// ---------------------------------------------------------------------------
$materiales = [
    ['nombre' => 'piso',      'color' => [0.16, 0.17, 0.19, 1.0], 'metal' => 0.10, 'rug' => 0.90],
    ['nombre' => 'muro',      'color' => [0.92, 0.91, 0.88, 1.0], 'metal' => 0.00, 'rug' => 0.85],
    ['nombre' => 'acento',    'color' => [0.78, 0.28, 0.05, 1.0], 'metal' => 0.25, 'rug' => 0.55],
    ['nombre' => 'estructura','color' => [0.42, 0.44, 0.47, 1.0], 'metal' => 0.85, 'rug' => 0.35],
];

// ---------------------------------------------------------------------------
// Piezas del stand: [material, escala (ancho, alto, fondo), centro (x, y, z)]
// ---------------------------------------------------------------------------
$piezas = [
    ['piso',       [3.00, 0.08, 2.00], [ 0.00, 0.04,  0.00]],
    ['muro',       [3.00, 2.40, 0.08], [ 0.00, 1.20, -0.96]],
    ['muro',       [0.08, 2.40, 2.00], [-1.46, 1.20,  0.00]],
    ['acento',     [2.20, 0.45, 0.06], [ 0.00, 2.00, -0.90]],  // letrero en el muro
    ['acento',     [1.20, 0.90, 0.50], [ 0.55, 0.45,  0.60]],  // mostrador
    ['estructura', [0.10, 2.40, 0.10], [ 1.46, 1.20,  0.96]],  // poste de esquina
];

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
            'baseColorFactor' => $m['color'],
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
