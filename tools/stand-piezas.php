<?php
/**
 * Definicion unica del stand de prueba.
 *
 * La usan los dos generadores (GLB para Android/web, USDZ para iPhone). Si las
 * medidas vivieran duplicadas en cada uno, tarde o temprano un modelo saldria
 * distinto del otro y el bug seria dificil de ver: en AR ambos "se ven bien".
 *
 * Convenciones: metros, frente hacia +Z, base apoyada en Y=0.
 */

/** Cubo unitario centrado en el origen: 6 caras de 4 vertices, con su normal. */
function stand_caras(): array
{
    return [
        [[1, 0, 0],  [[.5,-.5,-.5], [.5,-.5,.5],  [.5,.5,.5],   [.5,.5,-.5]]],
        [[-1, 0, 0], [[-.5,-.5,.5], [-.5,-.5,-.5],[-.5,.5,-.5], [-.5,.5,.5]]],
        [[0, 1, 0],  [[-.5,.5,.5],  [.5,.5,.5],   [.5,.5,-.5],  [-.5,.5,-.5]]],
        [[0, -1, 0], [[-.5,-.5,-.5],[.5,-.5,-.5], [.5,-.5,.5],  [-.5,-.5,.5]]],
        [[0, 0, 1],  [[-.5,-.5,.5], [.5,-.5,.5],  [.5,.5,.5],   [-.5,.5,.5]]],
        [[0, 0, -1], [[.5,-.5,-.5], [-.5,-.5,-.5],[-.5,.5,-.5], [.5,.5,-.5]]],
    ];
}

/**
 * Materiales. Colores planos y opacos: en AR contra un fondo real, lo muy
 * metalico o muy oscuro se lee mal.
 */
function stand_materiales(): array
{
    return [
        ['nombre' => 'piso',       'color' => [0.16, 0.17, 0.19], 'metal' => 0.10, 'rug' => 0.90],
        ['nombre' => 'muro',       'color' => [0.92, 0.91, 0.88], 'metal' => 0.00, 'rug' => 0.85],
        ['nombre' => 'acento',     'color' => [0.78, 0.28, 0.05], 'metal' => 0.25, 'rug' => 0.55],
        ['nombre' => 'estructura', 'color' => [0.42, 0.44, 0.47], 'metal' => 0.85, 'rug' => 0.35],
    ];
}

/** Piezas del stand: [material, escala (ancho, alto, fondo), centro (x, y, z)]. */
function stand_piezas(): array
{
    return [
        ['piso',       [3.00, 0.08, 2.00], [ 0.00, 0.04,  0.00]],
        ['muro',       [3.00, 2.40, 0.08], [ 0.00, 1.20, -0.96]],
        ['muro',       [0.08, 2.40, 2.00], [-1.46, 1.20,  0.00]],
        ['acento',     [2.20, 0.45, 0.06], [ 0.00, 2.00, -0.90]],  // letrero
        ['acento',     [1.20, 0.90, 0.50], [ 0.55, 0.45,  0.60]],  // mostrador
        ['estructura', [0.10, 2.40, 0.10], [ 1.46, 1.20,  0.96]],  // poste
    ];
}
