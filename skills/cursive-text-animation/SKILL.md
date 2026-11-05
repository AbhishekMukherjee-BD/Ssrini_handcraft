---
name: cursive-text-animation
description: >
  Creates a premium SVG + GSAP cursive handwriting animation where letters are
  drawn stroke-by-stroke using real Google Font letterforms. Produces a
  self-contained standalone preview HTML page on a white canvas with a full
  interactive control HUD, plus an integration-ready version for embedding into
  existing pages with a transparent background. Activate when the user provides
  any text they want animated in cursive/script style, mentions "handwriting
  animation", "write this in cursive", "script animation", "as if someone is
  writing", or opens this folder and gives a phrase to animate.
argument-hint: "[\"text line 1\" | \"text line 2\" | font | color | speed]"
license: MIT
---

# Cursive Text Animation Skill

You are an expert in creating premium SVG-based cursive handwriting animations
using the **stroke-dasharray/dashoffset technique** with real Google Fonts and
GSAP. This skill produces the exact output as built for Ssrini Handicrafts:
beautiful, identifiable, stroke-by-stroke cursive lettering on an interactive
review canvas.

---

## CRITICAL RULE: Always Use Real Font Letterforms

**NEVER** draw manual SVG `<path>` curves to represent letters. That produces
unreadable abstract squiggles. **ALWAYS** use `<text>` SVG elements with a
real Google Font. The font engine renders correct, identifiable letterforms. You
then animate the stroke reveal using `stroke-dasharray` / `stroke-dashoffset`.

---

## Persistence

ACTIVE every response for the duration of the animation task. Use this skill
for both the **standalone preview** and the **transparent overlay integration**.

---

## How the Technique Works

```
1. Load Google Font via CDN link in <head>
2. Render SVG <text> elements with:
      fill: transparent
      stroke: <ink color>
      stroke-linecap: round
      stroke-linejoin: round
      font-family: '<Font Name>', cursive
3. On page load (after font renders), get path length:
      const len = el.getTotalLength()
4. Set initial state:
      el.style.strokeDasharray  = len + 2
      el.style.strokeDashoffset = len + 2
5. GSAP timeline: animate strokeDashoffset → 0
      ease: 'none'  (constant pen speed, feels natural)
6. On complete: fade in fill, reduce stroke to 0.3×
7. Gold pen-nib glow follows tip via getPointAtLength()
8. Subtitle tag animates in with opacity + translateY
```

The duration is calculated proportionally to path length:
```js
const dur = (len / 380) * 1.1;  // ~1 sec per 380px of path
```

---

## Workflow When User Gives You Text

1. **Parse the input**: Extract line 1, line 2 (split at natural sentence
   breaks), preferred font, color, and speed if mentioned.
2. **Generate**: Create `cursive-preview.html` inside the project's `landing/`
   directory (or wherever the project's static files live).
3. **Use GSAP from the project**: `src="js/gsap.min.js"` — already present in
   Ssrini Handicrafts. For other projects, use the CDN:
   `https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js`
4. **Open preview** in browser at `http://localhost:<port>/landing/cursive-preview.html`
5. **Report** to user: font used, color, controls available.

---

## Font Reference Table

| Font Name      | Google Fonts ID    | Character                          | Best For                  |
|----------------|--------------------|------------------------------------|---------------------------|
| Great Vibes    | `Great+Vibes`      | Tall ascenders, closest to "Blush" | Headlines, brand taglines |
| Alex Brush     | `Alex+Brush`       | Classic, heavier weight, elegant   | General cursive script    |
| Pinyon Script  | `Pinyon+Script`    | Ultra thin, delicate, romantic     | Luxury, premium branding  |
| Italianno      | `Italianno`        | Very thin, Italian calligraphy     | Fine art, editorial       |

**Google Fonts CDN Link (always include all four):**
```html
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Alex+Brush&family=Pinyon+Script&family=Italianno&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
```

---

## Ink Color Palette

| Name            | Hex       | Use Case                            |
|-----------------|-----------|-------------------------------------|
| Walnut Brown    | `#5c3d2e` | Default — matches reference "Blush" |
| Deep Charcoal   | `#1a1a1a` | Modern, high contrast               |
| Champagne Gold  | `#b8945a` | Luxury, premium brand               |
| Royal Navy      | `#1b3354` | Classic, formal                     |
| Forest Green    | `#4a6741` | Organic, artisan                    |

---

## Full Feature Set (Reproduce Every Time)

### Canvas Background Modes
| Mode        | Implementation                                           |
|-------------|----------------------------------------------------------|
| White       | `background: #faf9f8` with soft radial vignette         |
| Transparent | CSS repeating-conic-gradient checkerboard — confirms zero BG |
| Dark        | `background: #0c0d0f`, ink shifts to warm `#dfc394`     |

Toggle via JS class on `<body>`:
```js
function setBg(mode) {
    document.body.classList.remove('dark', 'check');
    if (mode === 'dark')  document.body.classList.add('dark');
    if (mode === 'check') document.body.classList.add('check');
}
```

CSS for transparent mode:
```css
body.check {
    background: #ccc repeating-conic-gradient(#bbb 0% 25%, #d4d4d4 0% 50%)
                0 0 / 20px 20px;
}
```

### Replay Button
```js
function replayAnimation() {
    if (tl) tl.kill();
    isPaused = false;
    // Reset dashoffset, rebuild GSAP timeline, restart
}
```

### Pause / Resume
```js
function togglePause() {
    if (isPaused) { tl.play(); }
    else          { tl.pause(); }
    isPaused = !isPaused;
}
```

### Speed Slider (0.4× → 3×)
```js
function onSpeed(v) {
    speed = parseFloat(v);
    if (tl) tl.timeScale(speed);
}
```
HTML: `<input type="range" min="0.4" max="3" step="0.1" value="1">`

### Ink Width Slider (0.8px → 6px)
```js
function onStroke(v) {
    strokeW = parseFloat(v);
    [line1El, line2El].forEach(el => el.style.strokeWidth = strokeW);
}
```

### Font Size Slider (48px → 120px)
Changing font size requires replaying the animation because path length changes:
```js
function onFontSize(v) {
    fontSize = parseInt(v);
    [line1El, line2El].forEach(el => el.setAttribute('font-size', fontSize));
    replayAnimation();  // MUST replay — new font size = new path length
}
```

### Font Picker Pills
Each pill sets `font-family` attribute on both SVG text elements, then replays:
```js
function setFont(btnEl) {
    const ff = `'${btnEl.dataset.font}', cursive`;
    [line1El, line2El].forEach(el => el.setAttribute('font-family', ff));
    setTimeout(() => replayAnimation(), 200); // 200ms delay for font render
}
```

### Color Swatches (5 dots)
```js
function setInk(hex, dotEl) {
    inkColor = hex;
    document.documentElement.style.setProperty('--ink', hex);
    [line1El, line2El].forEach(el => {
        el.style.stroke = hex;
        el.style.fill = el.classList.contains('filled') ? hex : 'transparent';
    });
}
```

### Custom Text Input (Two-line split)
```html
<input type="text" id="txt-line1" placeholder="Line 1">
<input type="text" id="txt-line2" placeholder="Line 2">
<button onclick="applyText()">Write ✦</button>
```
```js
function applyText() {
    line1El.textContent = document.getElementById('txt-line1').value.trim();
    line2El.textContent = document.getElementById('txt-line2').value.trim();
    replayAnimation();
}
```

### Gold Pen-Nib Glow
A fixed `<div id="nib">` follows the current stroke tip in screen coordinates:
```js
function movNib(el, progress, len) {
    const pt   = el.getPointAtLength(progress * len);
    const rect = svgEl.getBoundingClientRect();
    const vb   = svgEl.viewBox.baseVal;
    gsap.set(nibEl, {
        left: rect.left + pt.x * (rect.width  / vb.width),
        top:  rect.top  + pt.y * (rect.height / vb.height)
    });
}
```
CSS for the nib:
```css
#nib {
    position: fixed;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #d4af37;
    box-shadow: 0 0 14px 4px rgba(212,175,55,0.85);
    transform: translate(-50%,-50%);
    pointer-events: none;
    opacity: 0;
    z-index: 200;
}
```

### Fill Reveal on Completion
After writing finishes, the fill fades in and the stroke weight reduces:
```js
onComplete() {
    el.classList.add('filled');
    el.style.fill = inkColor;
    gsap.to(el, { strokeWidth: strokeW * 0.3, duration: 0.35 });
}
```

### Subtitle Tag Animation
```js
gsap.to(tagEl, { opacity: 1, y: 0, duration: 0.7, ease: 'power2.out' }, '-=0.1');
```

---

## SVG Layout Defaults

```html
<svg id="cursive-svg" viewBox="0 0 960 260" ...>
    <text id="line1" class="cursive-line"
        x="480" y="120"
        text-anchor="middle"
        dominant-baseline="middle"
        font-family="'Alex Brush', cursive"
        font-size="88">Line One Text</text>

    <text id="line2" class="cursive-line"
        x="480" y="218"
        text-anchor="middle"
        dominant-baseline="middle"
        font-family="'Alex Brush', cursive"
        font-size="88">Line Two Text</text>
</svg>
```

Rules:
- `viewBox="0 0 960 260"` for two lines. Adjust height for more lines.
- Both lines use same `x="480"` (centered). `text-anchor="middle"` handles alignment.
- Line spacing: y1 = ~120, y2 = ~218 at font-size 88. Adjust proportionally.
- For a **single line**, set `y="140"` and viewBox height to `180`.

---

## GSAP Timeline Construction

```js
function replayAnimation() {
    if (tl) tl.kill();
    isPaused = false;

    const lines = [line1El, line2El];

    lines.forEach(el => {
        el.classList.remove('filled');
        el.style.stroke      = inkColor;
        el.style.strokeWidth = strokeW;
        el.style.fill        = 'transparent';
    });

    gsap.set(tagEl,  { opacity: 0, y: 10 });
    gsap.set(nibEl,  { opacity: 0 });

    tl = gsap.timeline({ timeScale: speed });

    lines.forEach((el, idx) => {
        const len = el.getTotalLength ? el.getTotalLength() : 2000;

        el.style.strokeDasharray  = len + 2;
        el.style.strokeDashoffset = len + 2;

        const dur = (len / 380) * 1.1;

        tl.to(el, {
            strokeDashoffset: 0,
            duration: dur,
            ease: 'none',
            onStart()   { gsap.set(nibEl, { opacity: 1 }); },
            onUpdate()  { movNib(el, this.progress(), len); },
            onComplete(){ el.classList.add('filled'); el.style.fill = inkColor;
                          gsap.to(el, { strokeWidth: strokeW * 0.3, duration: 0.35 }); }
        }, idx === 0 ? 0 : '-=0.05');
    });

    tl.to(nibEl, { opacity: 0, duration: 0.3 }, '-=0.2');
    tl.to(tagEl, { opacity: 1, y: 0, duration: 0.7, ease: 'power2.out' }, '-=0.1');
}
```

**Important**: Always call `replayAnimation()` inside a `window.addEventListener('load', ...)` handler with a 400ms delay to ensure Google Fonts have rendered before `getTotalLength()` is called.

```js
window.addEventListener('load', () => {
    setTimeout(() => replayAnimation(), 400);
});
```

---

## HUD Structure (Floating Control Panel)

The HUD is always `position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%)`.
Use `backdrop-filter: blur(20px)` and a semi-transparent background.

Section order (left to right):
1. Replay + Pause buttons
2. Separator
3. Custom text inputs (Line 1, Line 2, Write button)
4. Separator
5. Speed slider
6. Ink width slider
7. Font size slider
8. Separator
9. Color swatches (5 dots)
10. Separator
11. Background mode buttons (White, Transparent, Dark)

---

## Transparent Integration (For Main Landing Page)

When user approves the preview and wants it on the main page:

```css
.cursive-overlay {
    position: absolute;           /* or fixed depending on context */
    top: <target Y position>;
    left: 50%;
    transform: translateX(-50%);
    background: transparent;      /* no background — only the ink strokes */
    pointer-events: none;         /* doesn't block scroll/click behind it */
    z-index: <layer above content>;
    width: 100%;
    max-width: 960px;
}
```

The SVG itself has `overflow: visible` so descenders/ascenders don't clip.
The body of the preview page must have `background: transparent` (remove white).

Trigger animation on scroll into view with GSAP ScrollTrigger:
```js
ScrollTrigger.create({
    trigger: '.cursive-overlay',
    start: 'top 80%',
    onEnter: () => replayAnimation()
});
```

Or trigger on a specific scroll position in the existing masterTimeline.

---

## File Naming Convention

Always create the preview file at:
```
<project-root>/landing/cursive-preview.html
```

When integrating into an existing page, inject the SVG + script block directly
into that page's HTML. No separate file needed.

---

## Quick Trigger Phrases (User says → you do)

| User says | Action |
|-----------|--------|
| "animate this in cursive: [text]" | Build cursive-preview.html with that text |
| "use Great Vibes font" | Set font-family attribute + replay |
| "make it gold" | setInk('#b8945a') |
| "slow it down" | Reduce speed slider default to 0.6 |
| "single line" | Use one `<text>` element, viewBox height 180 |
| "add it to the landing page" | Integration mode — transparent overlay |
| "dark canvas" | body.classList.add('dark') |
| "replay" | Rebuild GSAP timeline from scratch |

---

## Example Invocation

User: *"Use the cursive animation skill. Animate: 'Crafted With Love.' on one line. Great Vibes font, gold ink, dark canvas, slow speed."*

You produce `landing/cursive-preview.html` with:
- `line1El.textContent = "Crafted With Love."`
- `line2El` hidden or removed
- `font-family: 'Great Vibes', cursive`
- `inkColor = '#b8945a'`
- `body` starts with `.dark` class
- `speed` default = `0.6`
- All HUD controls working as documented above
