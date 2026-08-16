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
| `models/stand-demo.glb` | Stand dummy, 2.8 KB, generado sin dependencias |
| `tools/make-stand-glb.php` | Genera el GLB de arriba. Medidas y colores editables al final |
| `vendor/model-viewer.min.js` | Librería de Google, servida local (sin CDN) |

El stand dummy mide 3 × 2 × 2.4 m, mira hacia **+Z** y apoya en **Y=0**. Esas
dos convenciones son las que hay que respetar en los modelos reales para que
no salgan girados o enterrados en el piso.

## Pendientes conocidos

- **iOS no usa GLB.** Safari abre AR con Quick Look, que pide `.usdz`. Sin ese
  gemelo, los iPhone ven el 3D pero no el botón de AR. Android (Scene Viewer)
  sí lee el `.glb` directo.
- **Los GLB tienen que ser alcanzables sin sesión.** Scene Viewer los descarga
  desde fuera del navegador, sin las cookies del login. Modelos reales detrás
  de autenticación no van a cargar tal cual.
- Falta la hoja imprimible con el QR.
