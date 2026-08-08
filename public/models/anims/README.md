# Clips de estirada del arquero (Mixamo)

El minijuego de penales carga clips de estirada **reales** desde esta carpeta y
los mezcla (crossfade) con la animación *Idle* del arquero. Si un archivo no
está, el arquero usa la estirada **procedural** de respaldo (no se rompe nada).

## Archivos que busca el juego

| Archivo | Cuándo se usa |
|---|---|
| `dive_left.glb`  | Estirada a la izquierda (tiros abajo/medio a ese lado) |
| `dive_right.glb` | Estirada a la derecha |
| `dive_left_high.glb`  | *(opcional)* estirada alta a la izquierda |
| `dive_right_high.glb` | *(opcional)* estirada alta a la derecha |

Con solo `dive_left.glb` y `dive_right.glb` ya funciona; las variantes `_high`
son un plus.

## Cómo exportarlos de Mixamo (gratis)

1. Entrá a **https://www.mixamo.com** (login gratis de Adobe).
2. Arriba a la izquierda, en **Upload Character**, subí el mismo modelo que usás
   como arquero (`public/models/keeper.glb`) para que la animación calce con su
   esqueleto. *(Si usás el placeholder, cualquier personaje Mixamo sirve: todos
   comparten el rig `mixamorig:*`.)*
3. Buscá una animación de **"Goalkeeper Dive"** (o "Dive", "Diving Save").
4. Ajustes recomendados en el panel derecho:
   - **In Place**: activado (la traslación la maneja el juego).
   - Trim/velocidad a gusto.
5. **Download** con estas opciones:
   - Format: **glTF Binary (.glb)**
   - Skin: **Without Skin** (solo animación, más liviano)
   - Frames per Second: 30 · Keyframe Reduction: none
6. Renombralo a `dive_left.glb` (y hacé el espejo o descargá la versión hacia el
   otro lado como `dive_right.glb`) y dejalos en esta carpeta.

No hay que tocar código: el juego detecta los archivos al iniciar la partida.

## Notas técnicas

- Se cargan como **animación pura** y se les quitan los *tracks de posición*
  (solo rotaciones), para que las unidades de Mixamo no muevan al modelo de lugar.
- La duración se comprime a ~0.5 s (`DIVE_DURATION` en `penaltyGame.js`) para
  sincronizar con el tiempo de vuelo del balón.
- Los nombres de huesos deben ser `mixamorig:*` (estándar de Mixamo).
