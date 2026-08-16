<?php
/**
 * Inspecciona un GLB y reporta su caja envolvente real.
 *
 * Sirve para confirmar la escala sin abrir un visor: en AR un modelo mal
 * escalado se ve plausible hasta que lo pones junto a algo conocido.
 *
 * Uso:  php inspect-glb.php ../models/stand-demo.glb
 */

$archivo = $argv[1] ?? __DIR__ . '/../models/stand-demo.glb';
$datos   = file_get_contents($archivo);

if ($datos === false) {
    fwrite(STDERR, "No se pudo leer $archivo\n");
    exit(1);
}

// Cabecera GLB: magic, version, longitud total.
[$magic, $version, $largo] = array_values(unpack('Vmagic/Vversion/Vlargo', substr($datos, 0, 12)));

if ($magic !== 0x46546C67) {
    fwrite(STDERR, "No es un GLB (magic incorrecto)\n");
    exit(1);
}

$largoJson = unpack('V', substr($datos, 12, 4))[1];
$gltf      = json_decode(substr($datos, 20, $largoJson), true);

// Cada pieza es el cubo unitario (de -0.5 a 0.5) con escala y traslacion, asi
// que la envolvente sale de los extremos de cada nodo sin tocar el binario.
$min = [INF, INF, INF];
$max = [-INF, -INF, -INF];

foreach ($gltf['nodes'] as $nodo) {
    $escala = $nodo['scale']       ?? [1, 1, 1];
    $centro = $nodo['translation'] ?? [0, 0, 0];
    for ($k = 0; $k < 3; $k++) {
        $min[$k] = min($min[$k], $centro[$k] - $escala[$k] / 2);
        $max[$k] = max($max[$k], $centro[$k] + $escala[$k] / 2);
    }
}

printf("Archivo   : %s (%d bytes)\n", basename($archivo), strlen($datos));
printf("Coherente : %s\n", $largo === strlen($datos) ? 'si, longitud declarada = real' : 'NO, longitud declarada ' . $largo);
printf("Version   : %d · %d nodos · %d materiales\n",
    $version, count($gltf['nodes']), count($gltf['materials'] ?? []));
printf("Ancho (X) : %.3f m\n", $max[0] - $min[0]);
printf("Alto  (Y) : %.3f m   (base en Y=%.3f)\n", $max[1] - $min[1], $min[1]);
printf("Fondo (Z) : %.3f m\n", $max[2] - $min[2]);
