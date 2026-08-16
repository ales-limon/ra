# StandHouse · visor AR de prueba

Demo provisional para ver stands en realidad aumentada desde el celular.
El cliente escanea un QR, se le abre esta página y planta el stand a tamaño
real en el piso de su oficina.

No lleva marcador impreso: el QR solo transporta el link. El rastreo lo hace
el AR nativo del teléfono, así que el modelo va a escala real y el papel deja
de importar en cuanto se escanea.

**En vivo:** <https://ales-limon.github.io/ra/> (GitHub Pages, rama `main`, raíz)

## Estado

Funciona y está probado en iPhone: se ve el stand y se planta en AR sobre una
mesa. Falta la hoja del QR y decidir si se agrega también la vía con marcador.

Esto es un demo aparte, **todavía sin conectar con StandHouse**. Los modelos
son un stand dummy generado aquí mismo, no los GLB reales del sistema.

## Cómo continuar en otra máquina

```
git clone git@github.com:ales-limon/ra.git
cd ra
php -S localhost:8080 -t .
```

Y abrir <http://localhost:8080>. En escritorio se ve el 3D pero no el botón de
AR: eso solo aparece en celular. Para probar la AR hay que entrar desde el
teléfono a la URL de Pages, porque necesita HTTPS.

Solo hace falta PHP, y únicamente para regenerar modelos: el sitio es estático
y la librería está versionada, así que no hay `npm install` ni build. Se
publica solo con hacer push a `main`.

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

## Gestos

Están invertidos entre un contexto y otro, y conviene tenerlo claro antes de
enseñárselo a alguien:

| | Girar | Mover | Escalar |
| --- | --- | --- | --- |
| En la página | un dedo | dos dedos | pellizco |
| Dentro de la AR | dos dedos en espiral | un dedo | pellizco |

El giro dentro de la AR es un gesto real pero incómodo: hay que rotar los dedos
uno alrededor del otro. Si se deslizan en paralelo se lee como paneo.

## Siguientes pasos

1. **Hoja imprimible con el QR.** Tamaño carta, QR apuntando a la URL de Pages,
   logo de Forjiato y espacio para notas. El QR se genera en el propio HTML,
   sin servicios externos.

2. **Decidir si se agrega la vía con marcador** (MindAR). Con marcador el
   modelo queda anclado al papel: girar la hoja gira el stand, que es mucho más
   intuitivo que el gesto de dos dedos. Dato que inclina la balanza: MindAR
   funciona en Safari iOS porque no usa WebXR, sino cámara y WebGL, así que
   sería un solo GLB para ambas plataformas y sin USDZ.

   A cambio, el modelo queda atado a la escala de la hoja (unos 20 cm), hay que
   mantenerla encuadrada, y si se raya o se dobla deja de rastrear. Las dos vías
   pueden convivir en la misma hoja.

   Requiere un paso manual: compilar el `.mind` desde el PNG del marcador con la
   herramienta web de MindAR.

3. **Conectar con StandHouse** (`stands.forjiato.com`). Sin empezar. Hace falta
   investigar primero, sobre el repo real, que no está en esta máquina: en qué
   carpeta viven los `.glb`, si se sirven por ruta directa o por un controlador
   PHP, si esa ruta exige sesión, cuánto pesan los más grandes y si hay
   `.htaccess` que bloquee estáticos.

## Pendientes conocidos

- **Los GLB tienen que ser alcanzables sin sesión.** Scene Viewer los descarga
  desde fuera del navegador, sin las cookies del login, así que los modelos
  detrás del login no van a cargar tal cual. Hay que resolverlo con URLs
  firmadas que caduquen, o abriendo esa ruta a propósito. **Decisión pendiente,
  no tocar sin acordarlo.**
- **Los modelos reales pueden venir en otra escala u orientación.** Este dummy
  mide 3 m, mira a +Z y apoya en Y=0. `tools/inspect-glb.php` sirve para
  detectar los que vengan en centímetros, que es el error más común y el más
  difícil de ver a ojo.
- **El USDZ se escribió a mano, con USD en texto.** Funciona en Quick Look. Si
  algún modelo futuro fallara, la sospecha primera es el formato de texto
  (`.usda`) frente al binario (`.usdc`).
