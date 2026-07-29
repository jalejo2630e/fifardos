# Handoff: Landing FIFARDOS (rediseño gamer/esports)

## Overview
Rediseño completo de la landing pública de **FIFARDOS** (fifardos.com), una app gratuita para organizar torneos de FIFA / EA Sports FC con amigos: grupos, resultados, tabla de posiciones en vivo, eliminatorias automáticas y goleador.

Objetivo del rediseño: sacarle el look "plantilla de IA / SaaS genérico" y llevarlo a una estética **esports / gaming**: fondo casi negro, naranja de marca saturado, tipografía condensada en mayúsculas, cortes diagonales en botones, y **el producto real a la vista** (tabla en vivo y bracket como piezas visuales, no ilustraciones decorativas).

Idioma: **español rioplatense / LatAm** (locale `es_CO` con alternate `es_AR`). El tono es de grupo de amigos, no corporativo. No traducir ni "neutralizar" el copy.

## About the Design Files
Los archivos de este bundle son **referencias de diseño hechas en HTML** — prototipos que muestran el look y el comportamiento buscado, **no código de producción para copiar tal cual**. El `.dc.html` incluido usa un runtime propietario de streaming (`<x-dc>`, `<sc-for>`, `renderVals()`); **no lo intentes ejecutar ni portar su sintaxis**. Sirve como fuente de verdad visual: layout, colores exactos, tipografía, copy y datos de ejemplo.

La tarea es **recrear este diseño en el entorno del codebase existente** (Laravel + Blade + Tailwind, React, Vue, Astro — lo que ya use el proyecto), siguiendo sus patrones y librerías. La landing actual de fifardos.com está servida por Laravel (hay `csrf-token` en el `<head>`), así que lo más probable es una vista Blade + Tailwind. Si no hay entorno definido, elegir el más apropiado e implementarlo ahí.

## Fidelity
**High-fidelity (hifi).** Colores, tipografía, espaciados y copy son finales. Recrear pixel-perfect. Todos los valores de esta guía están tomados del prototipo.

Lo único que falta y hay que resolver en implementación: **las animaciones on-scroll** (ver sección "Interactions & Behavior" → *Scroll animations*), que son parte explícita del pedido y NO están en el prototipo.

---

## Design Tokens

### Colores
| Token | Hex | Uso |
|---|---|---|
| `bg-base` | `#08080a` | Fondo principal de la página |
| `bg-alt` | `#0b0b0d` | Fondo de secciones alternas (features, quotes, ticker, footer) |
| `bg-card` | `#0e0e11` | Tarjetas (pasos, tabla en vivo, bracket) |
| `bg-card-2` | `#131317` | Cajas internas de partidos del bracket |
| `bg-card-hover` | `#0f0d0c` | Hover de tarjetas de features |
| `accent` | `#ff5f00` | Naranja de marca (ya es el `theme-color` del sitio actual) |
| `accent-hover` | `#ff7a26` | Hover de botones sólidos |
| `accent-soft` | `#ff8a3d` | Texto naranja secundario (badges, labels) |
| `accent-link-hover` | `#ffb37a` | Hover de links de texto |
| `lime` | `#b6ff2e` | Acento secundario, sólo para estados "en vivo" y label "Eliminatorias" |
| `text-primary` | `#f2f2f0` | Texto principal |
| `text-secondary` | `#a8a8a3` | Párrafos de hero / CTA |
| `text-muted` | `#8f8f8b` | Párrafos de cuerpo en tarjetas y FAQ |
| `text-dim` | `#7a7a76` | Labels de stats, atribuciones |
| `text-dimmer` | `#6d6d69` | Metadatos, footer legal |
| `text-ticker` | `#5c5c58` | Texto del marquee |
| `hairline` | `rgba(255,255,255,.08)` | Bordes de sección |
| `border-card` | `rgba(255,255,255,.1)` | Bordes de tarjetas |
| `border-card-hover` | `rgba(255,95,0,.5)` | Borde de tarjeta en hover |
| `chip-bg` | `rgba(255,255,255,.07)` | Avatar/iniciales en la tabla |
| `row-divider` | `rgba(255,255,255,.06)` | Separador de filas de la tabla |
| `numeral-ghost` | `rgba(255,255,255,.13)` | Numerales gigantes 01–04 |

Reglas: **máximo 2 fondos** (`#08080a` y `#0b0b0d`) alternándose. El lime `#b6ff2e` aparece sólo 2 veces en toda la página — no expandirlo. Sin gradientes salvo los dos glows radiales especificados.

### Tipografía (Google Fonts)
```
Anton (400)                     → display / headlines / números
Barlow Condensed (500,600,700)  → botones, subtítulos de tarjeta, ticker
Chakra Petch (400,600,700 + italic) → cuerpo, UI, tablas
```
`<link>` usado:
`https://fonts.googleapis.com/css2?family=Anton&family=Chakra+Petch:ital,wght@0,400;0,600;0,700;1,600;1,700&family=Barlow+Condensed:wght@500;600;700&display=swap`

Escala tipográfica:
| Rol | Familia | Size / line-height / tracking |
|---|---|---|
| H1 hero | Anton | 84px / .89 / -1.5px, uppercase |
| H2 CTA final | Anton | 76px / .9 / -1.5px, uppercase |
| H2 sección | Anton | 52px / .95 / -.5px, uppercase |
| H2 bracket / FAQ | Anton | 48px / 44px, -.5px, uppercase |
| Stat number | Anton | 30px |
| Numeral de paso | Anton | 46px / 1 |
| Título de feature | Barlow Condensed 700 | 27px / 1.05 / .02em, uppercase |
| Título de paso | Barlow Condensed 700 | 23px / .04em, uppercase |
| Pregunta FAQ | Barlow Condensed 700 | 22px / .03em, uppercase |
| Botón grande | Barlow Condensed 700 | 21–24px / .08em, uppercase |
| Botón nav | Barlow Condensed 700 | 16px / .1em, uppercase |
| Ticker | Barlow Condensed 600 | 17px / .16em, uppercase |
| Párrafo hero/CTA | Chakra Petch 400 | 18px / 1.55 |
| Párrafo cuerpo | Chakra Petch 400 | 15–17px / 1.5–1.55 |
| Eyebrow / label | Chakra Petch 700 o Anton | 10–13px / .14em–.2em, uppercase |
| Nav link | Chakra Petch 600 | 13px / .12em, uppercase |

### Espaciado y geometría
- Contenedor: `max-width: 1240px`, padding lateral `24px`. FAQ usa `900px`; CTA final `1000px`.
- Padding vertical de sección: `92px` (hero `76px` top / `40px` bottom; quotes `76px`; CTA final `100px/110px`).
- Gaps de grid: `16px` entre tarjetas; `48–56px` entre columnas de secciones split.
- **Border radius: 0 en todo.** El lenguaje es de esquinas rectas + un corte diagonal en los botones sólidos.
- Corte diagonal del botón (esquina sup-izq y esquina inf-der):
  ```css
  clip-path: polygon(Npx 0, 100% 0, 100% calc(100% - Npx), calc(100% - Npx) 100%, 0 100%, 0 Npx);
  /* N = 10px botón nav · 14px botones hero · 16px CTA final */
  ```
- Sombra (sólo la tarjeta de tabla en vivo): `0 40px 90px -30px rgba(0,0,0,.9)`.
- Glows radiales (decorativos, `pointer-events:none`):
  - Hero: `620×620px`, `top:-120px; left:-160px`, `radial-gradient(circle, rgba(255,95,0,.16), transparent 62%)`.
  - CTA final: full-bleed, `radial-gradient(ellipse at 50% 120%, rgba(255,95,0,.22), transparent 60%)`.
- `::selection { background:#ff5f00; color:#08080a; }`
- Aplicar `text-wrap: pretty` a párrafos y `text-wrap: balance` a los H1/H2 grandes.

---

## Screens / Views

Una sola vista: la landing. Orden de secciones de arriba a abajo.

### 1. Header (sticky)
**Propósito:** navegación y CTA siempre a mano.
**Layout:** `position: sticky; top: 0; z-index: 40`, `backdrop-filter: blur(14px)`, fondo `rgba(8,8,10,.82)`, borde inferior hairline. Interior: flex, `align-items: center`, `gap: 32px`, padding `14px 24px`.
**Componentes:**
- **Logo**: `FIFAR` en `#f2f2f0` + `DOS` en `#ff5f00`, Anton 24px, `letter-spacing:-.5px`, `transform: skewX(-8deg)`. Sin espacio entre las dos partes (`gap: 2px`, baseline aligned).
- **Nav** (`margin-left:auto`, gap 26px): `Cómo va` → `#como`, `Modos` → `#modos`, `En vivo` → `#tabla`, `FAQ` → `#faq`. Color `#9a9a96`, hover `#f2f2f0`.
- **Botón `Crear torneo`**: fondo `#ff5f00`, texto `#08080a`, padding `10px 20px 9px`, clip-path 10px, hover `#ff7a26`.

### 2. Hero
**Layout:** grid `1.15fr .85fr`, gap 56px, `align-items:center`, padding `76px 24px 40px`. Glow radial detrás de la columna izquierda.
**Columna izquierda:**
- **Badge**: inline-flex, borde `1px solid rgba(255,95,0,.45)`, texto `#ff8a3d` 12px/700/.18em uppercase, padding `7px 13px`, `margin-bottom:26px`. Contiene un punto de 7px `#ff5f00` `border-radius:50%` con animación `fd-pulse` (opacidad 1→.25→1, 1.6s infinita). Texto: `Gratis · sin instalar nada`.
- **H1**: tres líneas — `Se armó` / `el torneo` (en `#ff5f00`) / `de la casa.` Anton 84px/.89.
- **Párrafo** (`max-width:480px`, `#a8a8a3`, 18px/1.55): "Grupos, resultados, tabla en vivo, eliminatorias automáticas y goleador. Vos jugás el FC, nosotros llevamos las cuentas — para que nadie discuta quién iba puntero."
- **Botones** (flex, gap 14px): primario `Armar mi torneo →` (sólido naranja, clip 14px); secundario `Ver una demo` (borde `1px solid rgba(255,255,255,.18)`, hover borde `#ff5f00` + texto `#ff8a3d`) → ancla a `#tabla`.
- **Stats row**: `margin-top:38px`, `padding-top:26px`, `border-top` hairline, flex gap 30px. Tres ítems, número en Anton 30px `#ff5f00` + label 12px/.14em uppercase `#7a7a76`:
  - `40 s` — en armar el fixture
  - `2–32` — jugadores por torneo
  - `$0` — para siempre

**Columna derecha — Card "Tabla en vivo"** (`id="tabla"`): fondo `#0e0e11`, borde `border-card`, padding 18px, sombra grande.
- Header de la card: punto lime 7px con `fd-pulse` 1.4s + label `TABLA EN VIVO` en `#b6ff2e` 11px/.2em; a la derecha `Grupo A · J4` en `#6d6d69`.
- Grid de columnas: `26px 1fr 34px 34px 40px`, gap `0 8px`. Cabecera `# / Jugador / PJ / DG / PTS` (11px/.14em uppercase `#6d6d69`, padding `0 8px 8px`; PJ y DG centrados, PTS a la derecha).
- Filas: padding `11px 8px`, `border-top: 1px solid rgba(255,255,255,.06)`. Posición en Anton 16px `#ff5f00`; iniciales en cuadrado 24×24 `chip-bg` Anton 11px `#cfcfca`; nombre 15px/600 con ellipsis; equipo 11px/.1em uppercase `#6d6d69`; PJ/DG en `#9a9a96`; PTS en Anton 17px.

  | # | Jugador | Equipo | PJ | DG | PTS |
  |---|---|---|---|---|---|
  | 1 | Nico (NI) | Real Madrid | 4 | +7 | 10 |
  | 2 | Maru (MA) | Inter | 4 | +4 | 9 |
  | 3 | El Chino (CH) | Liverpool | 4 | +1 | 6 |
  | 4 | Juanma (JU) | Boca | 4 | -3 | 3 |
  | 5 | Tincho (TI) | Newcastle | 4 | -9 | 1 |

- Pie de card "Bota de oro": `margin-top:16px`, padding `13px 14px`, fondo `rgba(255,95,0,.09)`, `border-left: 3px solid #ff5f00`. Izquierda: `BOTA DE ORO` Anton 13px/.14em `#ff8a3d`. Derecha: `Nico — 11 goles` 15px/700.

### 3. Ticker (marquee)
Banda de `padding: 15px 0` sobre `#0b0b0d` con hairlines arriba y abajo, `overflow:hidden; white-space:nowrap`. Contenido: inline-flex con `gap:34px`, animación `fd-marquee` (`translateX(0)` → `translateX(-50%)`, 26s linear infinite). **La lista se duplica** (concat de sí misma) para que el loop sea continuo. Ítems en `#5c5c58` Barlow Condensed 600 17px/.16em uppercase, separados por un `✦` en `#ff5f00`:
`Tabla en vivo · Goleadores · Grupos automáticos · Eliminatorias · Link para compartir · Sin instalar nada · PS5 · Xbox · PC`

### 4. Cómo funciona (`id="como"`)
Header de sección en flex `align-items:flex-end`, gap 24px, `margin-bottom:44px`: H2 a la izquierda (`Cuatro pasos` / `y a jugar` en naranja), y a la derecha (`margin-left:auto`, `max-width:330px`, `text-align:right`, `#8f8f8b` 16px) — "Sin cuentas, sin app, sin planilla de Excel del primo que se ofende."
Grid de 4 columnas iguales, gap 16px. Cada tarjeta: `#0e0e11`, borde card, padding `24px 20px 26px`, hover borde `rgba(255,95,0,.5)`. Contiene numeral ghost (Anton 46px `numeral-ghost`, `margin-bottom:14px`), título y descripción.

1. **01 · Cargá los nombres** — Escribí quién juega y con qué equipo. Dos personas o treinta y dos.
2. **02 · Elegí el formato** — Liga, grupos + eliminatorias o mata-mata directo. Ida y vuelta opcional.
3. **03 · Anotá resultados** — Termina el partido, cargás el 3-2 y la tabla se acomoda sola.
4. **04 · Coroná al campeón** — Bracket, goleador y palmarés listos para el chat del grupo.

### 5. Features (`id="modos"`)
Sección `#0b0b0d` con hairlines. Grid de 3 columnas, gap 16px; el H2 ocupa `grid-column: span 3` (`Todo lo que` + `el grupo pide` en naranja). Tarjetas: fondo `#08080a`, borde card, padding `26px 22px 28px`, hover borde naranja + fondo `#0f0d0c`. Estructura: tag (Anton 12px/.2em uppercase `#ff5f00`, `margin-bottom:14px`) → título → descripción.

| Tag | Título | Descripción |
|---|---|---|
| Fixture | Grupos armados en segundos | Sorteo balanceado, fechas ordenadas y nadie repite rival dos veces seguidas. |
| Live | Tabla que se actualiza sola | Puntos, diferencia de gol y desempates calculados al instante, sin planillas. |
| Playoffs | Eliminatorias automáticas | Los clasificados se cruzan solos: cuartos, semis y final sin dibujar nada. |
| Stats | Bota de oro y récords | Goleador, mejor defensa, goleadas históricas y la racha del que no gana nunca. |
| Share | Un link para todo el grupo | Mandás el link y todos ven la tabla desde el celular. Sin cuentas ni claves. |
| Mobile | Se carga desde el sillón | Pensado para usarlo con una mano mientras el otro elige equipo. |

### 6. Bracket
Grid `.8fr 1.2fr`, gap 48px, `align-items:center`.
**Izquierda:** eyebrow `ELIMINATORIAS` en Anton 12px/.2em **lime `#b6ff2e`**; H2 `El bracket se` / `arma solo.`; párrafo `#8f8f8b` 17px — "Cuando termina la fase de grupos, FIFARDOS cruza los clasificados y genera las llaves. Cargás el resultado y el ganador avanza al toque."; link `Probarlo ahora` con `border-bottom: 2px solid #ff5f00`, Barlow Condensed 19px/.1em uppercase.
**Derecha:** card `#0e0e11` borde card, padding `28px 24px`, grid de 3 columnas iguales gap 18px, `align-items:center`.
- Col 1 (cuartos): 4 cajas en columna, gap 12px — `#131317`, borde card, padding `9px 11px`, flex `space-between`, nombre 14px/600 + score en Anton `#ff5f00`. Datos: Nico 3, Juanma 1, Maru 2, El Chino 0.
- Col 2 (semis): 2 cajas, gap 34px, borde `rgba(255,95,0,.4)`, padding 11px, peso 700. Datos: Nico 2, Maru 1.
- Col 3 (final): caja borde `#ff5f00`, fondo `rgba(255,95,0,.1)`, padding `18px 12px`, centrada — label `CAMPEÓN` 10px/.2em `#ff8a3d` + `Nico` en Anton 22px uppercase.

### 7. Quotes / prueba social
Sección `#0b0b0d` con hairlines, padding `76px 24px`, grid de 3 columnas gap 16px. Cada ítem: padding `26px 24px`, `border-left: 2px solid #ff5f00`; cita 18px/1.5/600 y atribución 12px/.16em uppercase `#7a7a76`.
1. “Se terminaron las discusiones de quién iba puntero. Está escrito.” — Torneo del barrio · 12 jugadores
2. “Lo armé en el entretiempo y ya estábamos jugando la fecha 1.” — Liga de oficina · 8 jugadores
3. “El bracket salió solo y quedó mejor que el del Mundial.” — Copa de vacaciones · 16 jugadores

> Nota: son testimonios de ejemplo. Reemplazar por reales antes de publicar, o quitar la sección.

### 8. FAQ (`id="faq"`)
`max-width:900px`. H2 `Preguntas` + `rápidas` en naranja. Cada fila: `border-top` `border-card`, padding `22px 0`, grid `1fr 1.3fr`, gap 28px — pregunta a la izquierda, respuesta a la derecha (`#8f8f8b` 16px/1.55). Siempre abiertas, sin acordeón.
1. **¿Hay que registrarse?** — No. Creás el torneo y compartís el link. Listo.
2. **¿Sirve para FIFA viejo?** — Sirve para cualquier edición: FIFA, EA Sports FC, y también para PES si insistís.
3. **¿Cuántos jugadores entran?** — De 2 a 32, con o sin fase de grupos, partidos de ida y vuelta opcionales.
4. **¿Es gratis de verdad?** — Sí. Sin límites de torneos ni funciones bloqueadas.

### 9. CTA final (`id="crear"`)
Full-bleed con glow radial inferior. `max-width:1000px`, padding `100px 24px 110px`, centrado.
H2 Anton 76px: `Dejá de discutir.` / `Jugá el torneo.` (segunda línea en `#ff5f00`).
Párrafo `#a8a8a3` 18px, `max-width:460px`: "Creá el torneo, mandá el link al grupo y que gane el mejor (o el que agarre al Madrid)."
Botón sólido `Crear torneo gratis`, Barlow Condensed 24px, padding `18px 44px 16px`, clip 16px.
Bajada 12px/.16em uppercase `#6d6d69`: `Sin registro · sin descargas · PS5, Xbox y PC`.

### 10. Footer
`#0b0b0d`, hairline superior, padding `30px 24px`, flex wrap gap 20px: logo skew 18px · disclaimer 13px `#6d6d69` ("Hecho por fanáticos, no por EA. FIFA y EA Sports FC son marcas de sus dueños.") · a la derecha links `X / Twitter`, `Instagram`, `Contacto` (12px/.14em uppercase `#8f8f8b`, hover `#ff5f00`).

---

## Interactions & Behavior

### Anclas
Nav y botones hacen scroll suave a `#como`, `#modos`, `#tabla`, `#faq`, `#crear`. Usar `scroll-behavior: smooth` en `html` + `scroll-margin-top: 80px` en las secciones con id (para compensar el header sticky). **No usar `scrollIntoView` con offsets manuales.**

### Hover states
- Botones sólidos: `#ff5f00` → `#ff7a26`.
- Botón outline: borde `rgba(255,255,255,.18)` → `#ff5f00`, texto → `#ff8a3d`.
- Tarjetas de pasos: borde → `rgba(255,95,0,.5)`.
- Tarjetas de features: borde → `rgba(255,95,0,.5)` y fondo → `#0f0d0c`.
- Nav links: `#9a9a96` → `#f2f2f0`. Links de footer: `#8f8f8b` → `#ff5f00`.
- Transición estándar: `transition: border-color .2s ease, background-color .2s ease, color .15s ease`.

### Animaciones continuas (ya en el diseño)
```css
@keyframes fd-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
@keyframes fd-pulse   { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
```
- Marquee: 26s linear infinite. Pausar en `:hover` es opcional y bienvenido.
- Pulse: 1.6s en el badge del hero, 1.4s en el punto lime de la tabla.

### Scroll animations — **A IMPLEMENTAR** (pedido explícito)
Objetivo: que la página se sienta "viva" al scrollear, sin caer en el efecto plantilla (nada de que todo suba 60px con la misma duración).

**Motor.** `IntersectionObserver` + clases CSS, sin librerías. Si el codebase ya usa Framer Motion / GSAP / Alpine `x-intersect`, usar eso. Setup base:

```css
[data-reveal] {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity .6s cubic-bezier(.22,.61,.36,1),
              transform .6s cubic-bezier(.22,.61,.36,1);
  transition-delay: var(--reveal-delay, 0ms);
  will-change: opacity, transform;
}
[data-reveal].is-in { opacity: 1; transform: none; }

@media (prefers-reduced-motion: reduce) {
  [data-reveal] { opacity: 1; transform: none; transition: none; }
}
```
```js
const io = new IntersectionObserver((entries) => {
  entries.forEach((e) => {
    if (!e.isIntersecting) return;
    e.target.classList.add('is-in');
    io.unobserve(e.target); // one-shot: nunca revertir al scrollear hacia arriba
  });
}, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });

document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
```

**Reglas de aplicación, sección por sección:**

| Zona | Efecto | Detalle |
|---|---|---|
| Hero | **Sin scroll reveal.** Entra en load | Stagger de entrada: badge → H1 → párrafo → botones → stats, 70ms entre cada uno, `translateY(16px)` + fade, 500ms. La card de tabla entra desde `translateX(28px)` con 200ms de delay. |
| Hero (parallax) | Glow y card | Al scrollear, mover el glow radial a `translateY(scrollY * 0.12)` y la card a `scrollY * -0.04`. Sólo con `transform`, dentro de `requestAnimationFrame`. Desactivar bajo 900px de ancho. |
| Ticker | Se activa al entrar | Empezar la animación `fd-marquee` sólo cuando la banda es visible (`animation-play-state`), para no gastar CPU arriba. |
| Pasos 01–04 | Stagger horizontal | 4 tarjetas, `--reveal-delay` 0 / 90 / 180 / 270ms, `translateY(28px)` + fade. El numeral ghost además va de `opacity .04 → .13` en 800ms con 150ms extra de delay. |
| Features (6) | Stagger en grilla | Delay = `(row * 120ms) + (col * 80ms)`, `translateY(20px)` + `scale(.985)` → normal. |
| Bracket | Reveal secuencial de llaves | Cuartos (4 cajas) con delay 0/60/120/180ms desde `translateX(-16px)`; semis a 320/400ms desde `translateX(-12px)`; caja Campeón a 560ms con `scale(.9) → 1` (`cubic-bezier(.34,1.56,.64,1)`, leve overshoot) y el borde naranja pasando de `rgba(255,95,0,.25)` a `#ff5f00`. |
| Stats del hero + números | Count-up | Al entrar en viewport, animar `40 s` de 0→40 y `$0` fijo; en el bracket, los scores cuentan 0→valor. 700ms, `easeOutQuad`, respetando `prefers-reduced-motion` (mostrar valor final directo). |
| Quotes | Stagger suave | 0/110/220ms, sólo fade + `translateY(14px)`. El `border-left` crece de `scaleY(0)` a `scaleY(1)` (origen top, 500ms). |
| FAQ | Filas en cascada | 60ms entre filas, `translateY(12px)` + fade. |
| CTA final | Zoom-in mínimo del glow | El glow radial escala de `.85` a `1` y su opacidad de 0 a 1 en 900ms al entrar; el H2 hace fade + `translateY(20px)`. |
| Header | Cambio de estado al scrollear | A partir de 40px de scroll: `background: rgba(8,8,10,.94)` y sombra `0 10px 30px -18px rgba(0,0,0,.9)`. Transición 200ms. |
| Global | Barra de progreso (opcional) | Línea de 2px `#ff5f00` fija arriba del viewport, `transform: scaleX(scrollProgress)`, `transform-origin: left`. |

**Restricciones:**
- Animar **sólo** `opacity` y `transform`. Nunca `height`, `top`, `margin` ni `filter` en scroll.
- Todo one-shot: nada re-anima al volver a subir.
- Respetar `prefers-reduced-motion: reduce` → todo visible, sin transiciones ni parallax ni count-up.
- Sin animar dentro de la primera pantalla en móvil salvo el stagger de entrada.
- Los listeners de scroll van con `{ passive: true }` y throttle vía `requestAnimationFrame`.

### Responsive
El prototipo está dibujado a desktop (~1240px). Breakpoints a implementar:
- **≥1120px:** como está descrito.
- **880–1119px:** hero pasa a 1 columna (card de tabla debajo, `max-width:520px`); pasos a 2×2; features a 2 columnas; bracket a 1 columna (card debajo); H1 a 64px; H2 a 42px.
- **<880px:** todo a 1 columna. H1 a 44–48px (line-height .95), H2 CTA a 40px, H2 de sección a 34px. Padding de sección a 60px; lateral a 20px. Nav links colapsan a menú (o se ocultan dejando sólo el logo + botón `Crear torneo`). Header de "Cómo funciona": el párrafo pasa a `text-align:left` debajo del H2. FAQ pasa a 1 columna (pregunta arriba, respuesta abajo, gap 8px). Stats del hero: wrap a 2 filas o scroll horizontal. Bracket: reducir a scroll horizontal con `min-width:520px` en la card. Botones a `width:100%` en el CTA final.
- **Hit targets mínimos de 44px** en todos los links y botones móviles (subir padding vertical del nav).

### Estados que faltan (definir en implementación)
La landing es estática; los CTAs deben apuntar al flujo real de creación de torneo del app. Si `Crear torneo` abre un formulario, definir: loading del botón (spinner o texto `Creando…` con el botón a `#ff7a26` y `cursor: wait`), y error inline en `#ff5f00`.

## State Management
Mínimo. No hay data fetching en la landing.
- `scrolled: boolean` — header compacto (>40px).
- `revealed: Set<Element>` — manejado por el IntersectionObserver (o clases en el DOM).
- `scrollProgress: number` — sólo si se implementa la barra de progreso.
- `tickerActive: boolean` — play/pause del marquee según visibilidad.
- Los datos de la tabla, bracket, features, pasos, quotes y FAQ son **constantes en el módulo** (ver el objeto que devuelve `renderVals()` en el `.dc.html`). Extraerlos a un archivo de contenido (`content.ts` / array en Blade) para que sean editables sin tocar el markup.

## Assets
- **Fuentes:** Anton, Barlow Condensed, Chakra Petch — Google Fonts. Preferible auto-hospedarlas (`@font-face` + woff2) para performance; con `font-display: swap`.
- **Iconografía:** ninguna librería de iconos. Los únicos glifos son `→` y `✦` (texto). Mantenerlo así — no meter un pack de iconos.
- **Imágenes:** el prototipo no usa ninguna. Toda la "imagen" es UI real (tabla, bracket). Si se quieren capturas reales del app o del juego, hay que proveerlas; los slots naturales son la columna derecha del hero y la sección de bracket.
- **OG image:** el sitio actual ya tiene `og-image.png` (1200×630). Actualizarla para que matchee la nueva identidad.
- **Marca:** no se usan assets de EA/FIFA. Mantener el disclaimer del footer.

## Files
- `FIFARDOS Landing.dc.html` — el prototipo hifi completo (referencia visual; runtime propietario, no portar la sintaxis). El markup está en la sección `<x-dc>`; los datos de ejemplo en `class Component extends DCLogic { renderVals() {...} }`.
- `README.md` — este documento. Autosuficiente: se puede implementar la landing sin ver el prototipo.
