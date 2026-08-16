# StandHouse · visor AR de prueba

Demo provisional para ver stands en realidad aumentada desde el celular.
El cliente escanea un QR, se le abre esta página y planta el stand a tamaño
real en el piso de su oficina.

No lleva marcador impreso: el QR solo transporta el link. El rastreo lo hace
el AR nativo del teléfono, así que el modelo va a escala real y el papel deja
de importar en cuanto se escanea.

## Cómo probarlo en local

```
php -S localhost:8080 -t .
```

Y abrir <http://localhost:8080>. En escritorio se ve el 3D pero no el botón de
AR: eso solo aparece en celular.

## Estructura

| Ruta | Qué es |
| --- | --- |
| `index.html` | El visor. Recibe el modelo por `?src=ruta.glb` |
| `models/stand-demo.*` | Stand a tamaño real: 3 m de frente |
| `models/stand-maqueta.*` | El mismo stand a 1:20: 15 cm de frente |
| `tools/stand-piezas.php` | Medidas, materiales y piezas. Fuente única de los dos generadores |
| `tools/make-stand-glb.php` | Genera el GLB (Android y web) |
| `tools/make-stand-usdz.php` | Genera el USDZ (iPhone), incluido el ZIP alineado a 64 bytes |
| `tools/inspect-glb.php` | Reporta la caja envolvente de un GLB, para comprobar escalas |
| `vendor/model-viewer.min.js` | Librería de Google, servida local (sin CDN) |

## Escalas

El segundo argumento de los generadores es el factor. `1` da tamaño real y
`0.05` la maqueta 1:20:

```
php tools/make-stand-glb.php  models/stand-maqueta.glb  0.05
php tools/make-stand-usdz.php models/stand-maqueta.usdz 0.05
```

Son modelos distintos a propósito, no un escalado en el visor: al pasar a AR
el teléfono planta el modelo con las medidas que trae dentro del archivo, así
que encogerlo en pantalla no cambiaría nada.

Aparte, `ar-scale="auto"` deja que el usuario ajuste el tamaño con los dedos
dentro de la AR. Con `"fixed"` queda clavado a escala real.

El visor busca el `.usdz` solo: toma el `src`, le cambia la extensión y lo pasa
como `ios-src`. Si el gemelo no existe, el iPhone muestra el 3D sin botón de
AR. Se puede forzar otro con `?ios=ruta.usdz`.

El stand dummy mide 3 × 2 × 2.4 m, mira hacia **+Z** y apoya en **Y=0**. Esas
dos convenciones son las que hay que respetar en los modelos reales para que
no salgan girados o enterrados en el piso.

## Pendientes conocidos

- **El USDZ está sin probar en un iPhone real.** Cumple el formato (ZIP sin
  comprimir, alineado a 64 bytes, `#usda 1.0`) y lo abre un lector de ZIP
  estándar, pero Quick Look es exigente y cuando rechaza algo no dice por qué.
  Si no abriera, el siguiente paso es generar USD binario (`.usdc`) en vez de
  texto.
- **Los GLB tienen que ser alcanzables sin sesión.** Scene Viewer los descarga
  desde fuera del navegador, sin las cookies del login. Modelos reales detrás
  de autenticación no van a cargar tal cual.
- Falta la hoja imprimible con el QR.
