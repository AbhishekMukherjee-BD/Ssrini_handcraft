<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursive Handwriting Preview – Ssrini Handicrafts</title>

    <!--
        TECHNIQUE: Real Google Font cursive → SVG stroke-dasharray animation
        The font provides actual identifiable letterforms.
        GSAP animates stroke-dashoffset 0→full to create the "writing" reveal.
        Fill fades in at the end for a polished finish.
    -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!--
        Font candidates matching the reference "Blush" style (thin, elegant, tall ascenders):
        - Great Vibes      → Very close to reference
        - Alex Brush       → Slightly heavier, classic
        - Pinyon Script    → Thin and delicate
        - Italianno        → Ultra-thin like the reference
    -->
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,300..700&family=Great+Vibes&family=Alex+Brush&family=Pinyon+Script&family=Italianno&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #faf9f8;
            --ink: #5c3d2e;
            --hud-bg: rgba(255,255,255,0.95);
            --hud-border: rgba(0,0,0,0.08);
            --text: #222;
            --accent: #b8945a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding-bottom: 130px;
            transition: background 0.3s;
        }

        body.dark  { --bg: #0c0d0f; --hud-bg: rgba(18,20,24,0.95); --hud-border: rgba(255,255,255,0.1); --text: #eee; }
        body.check { background: #ccc repeating-conic-gradient(#bbb 0% 25%, #d4d4d4 0% 50%) 0 0 / 20px 20px; }

        /* ── Stage ─────────────────────────────────────────────── */
        .stage {
            width: 100%;
            max-width: 1100px;
            padding: 40px 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }

        /* ── Font Picker Row ────────────────────────────────────── */
        .font-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .font-pill {
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid var(--hud-border);
            background: transparent;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            color: var(--text);
            transition: all 0.18s;
        }

        .font-pill:hover { border-color: var(--accent); }

        .font-pill.active {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        body.dark .font-pill.active { background: var(--accent); border-color: var(--accent); color: #111; }

        /* ── SVG Canvas ─────────────────────────────────────────── */
        .svg-wrap {
            width: 100%;
            max-width: 980px;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        #cursive-svg {
            width: 100%;
            height: auto;
            overflow: visible;
        }

        /* SVG text styling – stroke only initially; fill fades in */
        .cursive-line {
            fill: transparent;
            stroke: var(--ink);
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: fill 0.5s ease, stroke 0.3s ease;
        }

        .cursive-line.filled {
            fill: var(--ink);
        }

        /* Subtitle tag */
        .tag {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.32em;
            text-transform: uppercase;
            color: var(--ink);
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.6s ease, transform 0.6s ease, color 0.3s;
        }

        /* ── Floating HUD ────────────────────────────────────────── */
        .hud {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--hud-bg);
            border: 1px solid var(--hud-border);
            border-radius: 22px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: center;
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 99;
            max-width: 95vw;
        }

        .hud-sep { width: 1px; height: 22px; background: var(--hud-border); }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid var(--hud-border);
            background: #fff;
            color: #222;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.18s;
        }

        body.dark .btn { background: #1c1e22; color: #f0f0f0; border-color: rgba(255,255,255,0.1); }

        .btn:hover { border-color: var(--accent); transform: translateY(-1px); }

        .btn.primary { background: #1a1a1a; color: #fff; border-color: #1a1a1a; }
        body.dark .btn.primary { background: var(--accent); color: #111; border-color: var(--accent); }

        /* Sliders */
        .slider-set {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text);
        }

        .slider-set input[type=range] {
            width: 72px;
            accent-color: var(--ink);
            cursor: pointer;
        }

        /* Color Dots */
        .swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.15s, border-color 0.15s;
        }

        .swatch:hover { transform: scale(1.2); }
        .swatch.active { border-color: #333; transform: scale(1.15); }
        body.dark .swatch.active { border-color: #fff; }

        /* Text Input */
        .text-input-wrap {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .text-input-wrap input {
            border: 1px solid var(--hud-border);
            border-radius: 8px;
            padding: 6px 10px;
            font-family: inherit;
            font-size: 13px;
            background: transparent;
            color: var(--text);
            outline: none;
            width: 240px;
            transition: border-color 0.18s;
        }

        .text-input-wrap input:focus { border-color: var(--accent); }

        /* Pen nib glow */
        #nib {
            position: fixed;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d4af37;
            box-shadow: 0 0 14px 4px rgba(212,175,55,0.85);
            transform: translate(-50%,-50%);
            pointer-events: none;
            opacity: 0;
            z-index: 200;
        }

        /* Status pill */
        .status {
            position: fixed;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--hud-bg);
            border: 1px solid var(--hud-border);
            border-radius: 30px;
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(16px);
            z-index: 99;
        }

        .dot-live {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 8px rgba(34,197,94,0.7);
            animation: blink 2s infinite;
        }

        @keyframes blink {
            0%,100% { opacity: 0.7; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.2); }
        }
    </style>
</head>
<body>

    <div class="status">
        <span class="dot-live"></span>
        <span>Cursive Preview — White Canvas</span>
    </div>

    <div id="nib"></div>

    <div class="stage">
        <!-- Font family picker -->
        <div class="font-row" id="font-row">
            <button class="font-pill" data-font="Montagu Slab" onclick="setFont(this)">Montagu Slab <small style="opacity:.55;font-size:10px">— Slab Serif</small></button>
            <button class="font-pill" data-font="Great Vibes" onclick="setFont(this)">Great Vibes  <small style="opacity:.55;font-size:10px">— Closest to Blush</small></button>
            <button class="font-pill" data-font="Pinyon Script" onclick="setFont(this)">Pinyon Script  <small style="opacity:.55;font-size:10px">— Ultra fine</small></button>
            <button class="font-pill active" data-font="Alex Brush" onclick="setFont(this)">Alex Brush  <small style="opacity:.55;font-size:10px">— Classic elegance</small></button>
            <button class="font-pill" data-font="Italianno" onclick="setFont(this)">Italianno  <small style="opacity:.55;font-size:10px">— Very thin</small></button>
        </div>

        <!-- SVG artboard -->
        <div class="svg-wrap">
            <svg id="cursive-svg" viewBox="0 0 960 260" xmlns="http://www.w3.org/2000/svg">
                <!--
                    Real font text rendered as SVG → stroke animated via GSAP
                    Text is split into two lines for layout breathing room.
                -->
                <text id="line1" class="cursive-line"
                    x="480" y="120"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    font-family="'Alex Brush', cursive"
                    font-size="88">Where Every Piece</text>

                <text id="line2" class="cursive-line"
                    x="480" y="218"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    font-family="'Alex Brush', cursive"
                    font-size="88">Tells A Story.</text>
            </svg>
        </div>

        <div class="tag" id="tag">Ssrini Handicrafts &nbsp;·&nbsp; Timeless Handcraft Heritage</div>
    </div>

    <!-- Floating HUD -->
    <div class="hud">
        <!-- Replay / Pause -->
        <div style="display:flex;gap:6px;align-items:center">
            <button class="btn primary" onclick="replayAnimation()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                Replay
            </button>
            <button class="btn" onclick="togglePause()">
                <svg id="pause-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                <span id="pause-label">Pause</span>
            </button>
        </div>

        <div class="hud-sep"></div>

        <!-- Custom text input -->
        <div class="text-input-wrap">
            <input type="text" id="txt-line1" placeholder="Line 1" value="Where Every Piece">
            <input type="text" id="txt-line2" placeholder="Line 2" value="Tells A Story.">
            <button class="btn" onclick="applyText()">Write ✦</button>
        </div>

        <div class="hud-sep"></div>

        <!-- Speed -->
        <div class="slider-set">
            Speed:
            <input type="range" id="speed" min="0.4" max="3" step="0.1" value="1" oninput="onSpeed(this.value)">
            <span id="speed-val">1.0×</span>
        </div>

        <!-- Stroke width -->
        <div class="slider-set">
            Ink:
            <input type="range" id="ink-w" min="0.8" max="6" step="0.2" value="1.8" oninput="onStroke(this.value)">
            <span id="ink-val">1.8px</span>
        </div>

        <!-- Font size -->
        <div class="slider-set">
            Size:
            <input type="range" id="fsize" min="48" max="120" step="4" value="88" oninput="onFontSize(this.value)">
            <span id="fsize-val">88px</span>
        </div>

        <div class="hud-sep"></div>

        <!-- Color swatches -->
        <div style="display:flex;gap:6px;align-items:center">
            <div class="swatch active" style="background:#5c3d2e" title="Walnut Brown" onclick="setInk('#5c3d2e',this)"></div>
            <div class="swatch" style="background:#1a1a1a" title="Charcoal Black" onclick="setInk('#1a1a1a',this)"></div>
            <div class="swatch" style="background:#b8945a" title="Champagne Gold" onclick="setInk('#b8945a',this)"></div>
            <div class="swatch" style="background:#1b3354" title="Royal Navy" onclick="setInk('#1b3354',this)"></div>
            <div class="swatch" style="background:#4a6741" title="Forest Green" onclick="setInk('#4a6741',this)"></div>
        </div>

        <div class="hud-sep"></div>

        <!-- Canvas mode -->
        <div style="display:flex;gap:6px">
            <button class="btn" onclick="setBg('white')">White</button>
            <button class="btn" onclick="setBg('check')">Transparent</button>
            <button class="btn" onclick="setBg('dark')">Dark</button>
        </div>
    </div>

    <!-- GSAP -->
    <script src="js/gsap.min.js"></script>
    <script>
    /* ── State ─────────────────────────────────────────────────── */
    let tl        = null;
    let inkColor  = '#5c3d2e';
    let strokeW   = 1.8;
    let speed     = 1.0;
    let fontSize  = 88;
    let isPaused  = false;
    let activeFont = 'Alex Brush';

    const line1El = document.getElementById('line1');
    const line2El = document.getElementById('line2');
    const tagEl   = document.getElementById('tag');
    const nibEl   = document.getElementById('nib');
    const svgEl   = document.getElementById('cursive-svg');

    /* ── Boot ──────────────────────────────────────────────────── */
    window.addEventListener('load', () => {
        // Small delay so fonts are definitely rendered
        setTimeout(() => replayAnimation(), 400);
    });

    /* ── Core Animation ────────────────────────────────────────── */
    function replayAnimation() {
        if (tl) tl.kill();
        isPaused = false;
        document.getElementById('pause-label').textContent = 'Pause';

        const lines = [line1El, line2El];

        // Reset styles
        lines.forEach(el => {
            el.classList.remove('filled');
            el.style.stroke      = inkColor;
            el.style.strokeWidth = strokeW;
            el.style.fill        = 'transparent';
        });

        gsap.set(tagEl,   { opacity: 0, y: 10 });
        gsap.set(nibEl,   { opacity: 0 });

        tl = gsap.timeline({ timeScale: speed });

        lines.forEach((el, idx) => {
            const len = el.getTotalLength ? el.getTotalLength() : 2000;

            // Initialise dasharray/dashoffset
            el.style.strokeDasharray  = len + 2;
            el.style.strokeDashoffset = len + 2;

            const dur = (len / 380) * 1.1; // proportional to path length

            tl.to(el, {
                strokeDashoffset: 0,
                duration: dur,
                ease: 'none',
                onStart() {
                    gsap.set(nibEl, { opacity: 1 });
                },
                onUpdate() {
                    movNib(el, this.progress(), len);
                },
                onComplete() {
                    // Smooth fill fade in
                    el.classList.add('filled');
                    el.style.fill = inkColor;
                    // Slight stroke fade so fill dominates
                    gsap.to(el, { strokeWidth: strokeW * 0.3, duration: 0.35 });
                }
            }, idx === 0 ? 0 : '-=0.05');
        });

        // Nib hide + subtitle reveal
        tl.to(nibEl, { opacity: 0, duration: 0.3 }, '-=0.2');
        tl.to(tagEl, { opacity: 1, y: 0, duration: 0.7, ease: 'power2.out' }, '-=0.1');
    }

    /* Move pen nib to current tip of stroke */
    function movNib(el, progress, len) {
        if (!el.getPointAtLength) return;
        try {
            const pt   = el.getPointAtLength(progress * len);
            const rect = svgEl.getBoundingClientRect();
            const vb   = svgEl.viewBox.baseVal;
            const sx   = rect.width  / vb.width;
            const sy   = rect.height / vb.height;
            gsap.set(nibEl, {
                left: rect.left + pt.x * sx,
                top:  rect.top  + pt.y * sy
            });
        } catch (e) {}
    }

    /* ── Controls ──────────────────────────────────────────────── */
    function togglePause() {
        if (!tl) return;
        if (isPaused) {
            tl.play();
            isPaused = false;
            document.getElementById('pause-label').textContent = 'Pause';
        } else {
            tl.pause();
            isPaused = true;
            document.getElementById('pause-label').textContent = 'Resume';
        }
    }

    function onSpeed(v) {
        speed = parseFloat(v);
        document.getElementById('speed-val').textContent = speed.toFixed(1) + '×';
        if (tl) tl.timeScale(speed);
    }

    function onStroke(v) {
        strokeW = parseFloat(v);
        document.getElementById('ink-val').textContent = strokeW + 'px';
        [line1El, line2El].forEach(el => el.style.strokeWidth = strokeW);
    }

    function onFontSize(v) {
        fontSize = parseInt(v);
        document.getElementById('fsize-val').textContent = fontSize + 'px';
        [line1El, line2El].forEach(el => el.setAttribute('font-size', fontSize));
        replayAnimation();
    }

    function setInk(hex, dotEl) {
        inkColor = hex;
        document.documentElement.style.setProperty('--ink', hex);
        [line1El, line2El].forEach(el => {
            el.style.stroke = hex;
            el.style.fill   = el.classList.contains('filled') ? hex : 'transparent';
        });
        tagEl.style.color = hex;
        document.querySelectorAll('.swatch').forEach(d => d.classList.remove('active'));
        if (dotEl) dotEl.classList.add('active');
    }

    function setFont(btnEl) {
        activeFont = btnEl.dataset.font;
        document.querySelectorAll('.font-pill').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
        const ff = `'${activeFont}', cursive`;
        [line1El, line2El].forEach(el => el.setAttribute('font-family', ff));
        setTimeout(() => replayAnimation(), 200);
    }

    function applyText() {
        const t1 = document.getElementById('txt-line1').value.trim() || 'Where Every Piece';
        const t2 = document.getElementById('txt-line2').value.trim() || 'Tells A Story.';
        line1El.textContent = t1;
        line2El.textContent = t2;
        replayAnimation();
    }

    function setBg(mode) {
        document.body.classList.remove('dark','check');
        if (mode === 'dark')  document.body.classList.add('dark');
        if (mode === 'check') document.body.classList.add('check');
    }
    </script>
</body>
</html>
