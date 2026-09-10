document.addEventListener("DOMContentLoaded", () => {
    // Reset scroll position to top on page load/refresh
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);

    // -----------------------------------------------------
    // 1. ASSET SETUP & CANVASES
    // -----------------------------------------------------
    const totalLogoFrames = 144;
    const totalVideoFrames = 480;
    
    // Canvases
    const logoCanvas = document.getElementById("logo-canvas");
    const logoCtx = logoCanvas.getContext("2d");
    logoCanvas.width = 1080;
    logoCanvas.height = 1920;
    
    const heroCanvas = document.getElementById("hero-canvas");
    const heroCtx = heroCanvas.getContext("2d", { alpha: false });
    
    const indicatorEl = document.getElementById("idle-scroll-indicator");

    let idleTimer = null;
    let isVideoScrubComplete = false;
    let isPreloaderComplete = false;

    // Retina High-DPI Canvas Scaling for Hero
    const resizeHeroCanvas = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        heroCanvas.width = window.innerWidth * dpr;
        heroCanvas.height = window.innerHeight * dpr;
        heroCtx.imageSmoothingEnabled = true;
        heroCtx.imageSmoothingQuality = "high";
        renderHeroCanvasFrame();
    };
    window.addEventListener("resize", resizeHeroCanvas);

    // -----------------------------------------------------
    // 2. LOGO ASSET PRELOAD & DRAW
    // -----------------------------------------------------
    const logoFramesCache = new Array(totalLogoFrames);
    let lastDrawnLogoImg = null;

    const renderLogoFrame = (frameIndex) => {
        const img = logoFramesCache[frameIndex];
        if (img && img.complete && img.naturalWidth > 0) {
            lastDrawnLogoImg = img;
            logoCtx.clearRect(0, 0, logoCanvas.width, logoCanvas.height);
            logoCtx.drawImage(img, 0, 0, logoCanvas.width, logoCanvas.height);
        } else if (lastDrawnLogoImg) {
            logoCtx.clearRect(0, 0, logoCanvas.width, logoCanvas.height);
            logoCtx.drawImage(lastDrawnLogoImg, 0, 0, logoCanvas.width, logoCanvas.height);
        }
    };

    // Preload logo frames
    for (let i = 0; i < totalLogoFrames; i++) {
        const img = new Image();
        const num = i.toString().padStart(4, '0');
        img.src = `loading_frames/frame_${num}.png`;
        if (i === 0) {
            img.onload = () => renderLogoFrame(0);
            if (img.complete) renderLogoFrame(0);
        }
        logoFramesCache[i] = img;
    }

    // -----------------------------------------------------
    // 3. HERO VIDEO FRAMES PRELOAD
    // -----------------------------------------------------
    const videoFramesCache = new Array(totalVideoFrames + 1);
    let currentVideoFrame = 1;

    const getHeroFramePath = (index) => {
        const frameStr = index.toString().padStart(4, '0');
        return `hero_frames/frame_${frameStr}.jpg`;
    };

    const preloadVideoFrames = () => {
        // Eagerly preload first frame and final frame (frame 480: gold necklace on mannequin)
        const imgFirst = new Image();
        imgFirst.src = getHeroFramePath(1);
        videoFramesCache[1] = imgFirst;

        const imgLast = new Image();
        imgLast.src = getHeroFramePath(totalVideoFrames);
        videoFramesCache[totalVideoFrames] = imgLast;

        for (let i = 1; i <= Math.min(60, totalVideoFrames); i++) {
            if (!videoFramesCache[i]) {
                const img = new Image();
                img.src = getHeroFramePath(i);
                videoFramesCache[i] = img;
            }
        }

        let nextIndex = 61;
        const loadBatch = () => {
            const batchLimit = Math.min(nextIndex + 25, totalVideoFrames);
            for (let i = nextIndex; i <= batchLimit; i++) {
                if (!videoFramesCache[i]) {
                    const img = new Image();
                    img.src = getHeroFramePath(i);
                    videoFramesCache[i] = img;
                }
            }
            nextIndex = batchLimit + 1;
            if (nextIndex <= totalVideoFrames) {
                if (window.requestIdleCallback) {
                    window.requestIdleCallback(loadBatch);
                } else {
                    setTimeout(loadBatch, 30);
                }
            }
        };
        setTimeout(loadBatch, 200);
    };
    preloadVideoFrames();

    const renderHeroCanvasFrame = () => {
        const img = videoFramesCache[currentVideoFrame] || new Image();
        if (!img.src) {
            img.src = getHeroFramePath(currentVideoFrame);
            videoFramesCache[currentVideoFrame] = img;
        }

        const draw = () => {
            const canvasRatio = heroCanvas.width / heroCanvas.height;
            const imgRatio = img.width / img.height;
            let renderWidth, renderHeight, x, y;

            if (imgRatio > canvasRatio) {
                renderHeight = heroCanvas.height;
                renderWidth = img.width * (heroCanvas.height / img.height);
                x = (heroCanvas.width - renderWidth) / 2;
                y = 0;
            } else {
                renderWidth = heroCanvas.width;
                renderHeight = img.height * (heroCanvas.width / img.width);
                x = 0;
                y = (heroCanvas.height - renderHeight) / 2;
            }

            heroCtx.imageSmoothingEnabled = true;
            heroCtx.imageSmoothingQuality = "high";
            heroCtx.drawImage(img, x, y, renderWidth, renderHeight);
        };

        if (img.complete && img.naturalWidth > 0) {
            draw();
        } else {
            img.onload = draw;
        }
    };

    resizeHeroCanvas();

    // -----------------------------------------------------
    // 4. SMART IDLE INDICATOR (15s INACTIVITY TRIGGER)
    // -----------------------------------------------------
    const startIdleTimer = () => {
        if (isVideoScrubComplete || !isPreloaderComplete) return;
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            if (!isVideoScrubComplete && isPreloaderComplete) {
                indicatorEl.classList.add("visible");
            }
        }, 15000);
    };

    const handleUserActivity = () => {
        if (isVideoScrubComplete) return;
        indicatorEl.classList.remove("visible");
        startIdleTimer();
    };

    const disableIndicatorPermanently = () => {
        isVideoScrubComplete = true;
        clearTimeout(idleTimer);
        indicatorEl.classList.remove("visible");
        indicatorEl.classList.add("disabled");
    };

    // -----------------------------------------------------
    // 5. MASTER ANIMATION TIMELINE
    // -----------------------------------------------------
    const masterTimeline = gsap.timeline({ paused: false });

    // Step 1: Sequential Stacking In (1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7)
    const stackImages = [
        { id: "#img-1-container", time: 0.05 },
        { id: "#img-2-container", time: 0.30 },
        { id: "#img-3-container", time: 0.55 },
        { id: "#img-4-container", time: 0.80 },
        { id: "#img-5-container", time: 1.05 },
        { id: "#img-6-container", time: 1.30 },
        { id: "#img-7-container", time: 1.55 }
    ];

    stackImages.forEach((item) => {
        masterTimeline.to(item.id, {
            opacity: 1,
            scale: 1,
            duration: 0.45,
            ease: "power2.out"
        }, item.time);
    });

    // Step 2: Hold full stack (~1.9s to 2.4s)
    const peelStartTime = 2.4;

    // Step 3: Descending Reverse Peel (7 -> 6 -> 5 -> 4 -> 3 -> 2)
    const peelConfig = [
        { id: "#img-7-container", rot: -7, delay: 0.00 },
        { id: "#img-6-container", rot: 6,  delay: 0.12 },
        { id: "#img-5-container", rot: -5, delay: 0.24 },
        { id: "#img-4-container", rot: 7,  delay: 0.36 },
        { id: "#img-3-container", rot: -4, delay: 0.48 },
        { id: "#img-2-container", rot: 5,  delay: 0.60 }
    ];

    peelConfig.forEach((item) => {
        masterTimeline.to(item.id, {
            y: "140vh",
            rotation: item.rot,
            opacity: 0,
            scale: 0.95,
            duration: 0.65,
            ease: "power3.inOut"
        }, peelStartTime + item.delay);
    });

    // Step 4: Expand Image 1 with ZERO-BLUR Native Viewport Expansion
    const expandTime = peelStartTime + 0.75;

    masterTimeline.to("#img-1-container", {
        top: "0%",
        left: "0%",
        transform: "translate(0px, 0px)",
        width: "100vw",
        height: "100vh",
        borderRadius: "0px",
        boxShadow: "none",
        duration: 1.3,
        ease: "expo.inOut"
    }, expandTime);

    // Step 5: Smooth Parallax Shift of Logo to Extreme Top Left (Site Logo position)
    // Stays completely permanent and visible!
    masterTimeline.to("#logo-container", {
        top: "clamp(-45px, -3.5vh, -25px)",
        left: "clamp(0px, 1vw, 20px)",
        transform: "translate(0, 0)",
        width: "clamp(170px, 18vw, 225px)",
        duration: 1.3,
        ease: "power3.inOut"
    }, expandTime);

    masterTimeline.to("#main-navbar", {
        opacity: 1,
        duration: 0.9,
        ease: "power2.out",
        onStart: () => {
            const nav = document.getElementById("main-navbar");
            if (nav) nav.classList.add("visible");
        }
    }, expandTime + 0.3);

    // Step 6: Transition to Canvas & Enable Scrolling
    masterTimeline.to("#hero-container", {
        opacity: 1,
        duration: 0.2,
        onComplete: () => {
            document.getElementById("preloader").style.display = "none";
            document.body.classList.remove("loading");
            isPreloaderComplete = true;

            startIdleTimer();
            window.addEventListener("scroll", handleUserActivity, { passive: true });
            window.addEventListener("mousemove", handleUserActivity, { passive: true });
            window.addEventListener("touchstart", handleUserActivity, { passive: true });

            initScrollTriggerVideo();

            // ── Trigger Cursive Handwriting Animation ──────────
            // Starts exactly as the 1st frame has zoomed to full
            // and the scroll experience is ready.
            startCursiveAnimation();
        }
    }, expandTime + 1.25);


    // -----------------------------------------------------
    // 6. LOGO CANVAS ANIMATION (GSAP-driven, synced to masterTimeline)
    // -----------------------------------------------------
    const logoTracker = { frame: 0 };
    masterTimeline.to(logoTracker, {
        frame: totalLogoFrames - 1,
        duration: peelStartTime - 0.05,
        ease: "none",
        onUpdate: () => {
            const idx = Math.round(logoTracker.frame);
            renderLogoFrame(idx);
        },
        onComplete: () => {
            renderLogoFrame(totalLogoFrames - 1);
        }
    }, 0.05);


    // -----------------------------------------------------
    // 7. SCROLLTRIGGER VIDEO SCRUB
    // -----------------------------------------------------
    const initScrollTriggerVideo = () => {
        gsap.registerPlugin(ScrollTrigger);

        const frameTracker = { frame: 1 };

        gsap.to(frameTracker, {
            frame: totalVideoFrames,
            ease: "none",
            scrollTrigger: {
                trigger: "#scroll-track",
                start: "top top",
                end: "bottom bottom",
                scrub: 0.2,
                onUpdate: (self) => {
                    const mappedFrame = Math.max(1, Math.min(totalVideoFrames, Math.round(self.progress * (totalVideoFrames - 1) + 1)));
                    if (mappedFrame !== currentVideoFrame) {
                        currentVideoFrame = mappedFrame;
                        renderHeroCanvasFrame();
                    }

                    if (self.progress >= 0.98) {
                        disableIndicatorPermanently();
                    }
                }
            }
        });

        // Ensure hero canvas remains locked on frame 480 (gold necklace on mannequin) while collection-flow-wrapper rises from bottom of screen
        ScrollTrigger.create({
            trigger: ".collection-flow-wrapper",
            start: "top bottom",
            end: "top top",
            onUpdate: () => {
                const heroContainer = document.getElementById("hero-container");
                if (heroContainer) {
                    heroContainer.style.visibility = "visible";
                    heroContainer.style.opacity = "1";
                    heroContainer.style.pointerEvents = "none";
                }
                if (currentVideoFrame !== totalVideoFrames) {
                    currentVideoFrame = totalVideoFrames;
                    renderHeroCanvasFrame();
                }
            }
        });

        // Hide fixed hero canvas ONLY when the white collection slide has fully covered the viewport (top top)
        ScrollTrigger.create({
            trigger: ".collection-flow-wrapper",
            start: "top top",
            onEnter: () => {
                const heroContainer = document.getElementById("hero-container");
                if (heroContainer) {
                    heroContainer.style.visibility = "hidden";
                    heroContainer.style.opacity = "0";
                    heroContainer.style.pointerEvents = "none";
                }
            },
            onLeaveBack: () => {
                const heroContainer = document.getElementById("hero-container");
                if (heroContainer) {
                    heroContainer.style.visibility = "visible";
                    heroContainer.style.opacity = "1";
                    heroContainer.style.pointerEvents = "none";
                    currentVideoFrame = totalVideoFrames;
                    renderHeroCanvasFrame();
                }
            }
        });

        ScrollTrigger.refresh();
    };


    // -----------------------------------------------------
    // 8. TEXT ANIMATION (Amita)
    //    Stroke-dashoffset writing reveal with Amita.
    //    Timed purely in seconds (zero frame/scroll dependency).
    //    Completes in exactly 2.0s, then disappears within 2.0s.
    // -----------------------------------------------------
    const startCursiveAnimation = () => {
        const container = document.getElementById("cursive-container");
        const line1     = document.getElementById("cursive-line1");
        const line2     = document.getElementById("cursive-line2");

        if (!container || !line1 || !line2) return;

        const inkColor  = "#ffffff";
        const strokeW   = 6.0;

        const lines = [line1, line2];

        // Measure path length accurately for Amita glyphs
        const getLen = (el) => {
            try {
                if (el.getTotalLength && el.getTotalLength() > 0) {
                    return el.getTotalLength();
                }
            } catch (e) {}
            if (el.getComputedTextLength && el.getComputedTextLength() > 0) {
                return Math.round(el.getComputedTextLength() * 3.8);
            }
            return 14000;
        };

        // Reset state
        lines.forEach(el => {
            const len = getLen(el);
            el.classList.remove("filled");
            el.style.stroke           = inkColor;
            el.style.strokeWidth      = strokeW;
            el.style.fill             = "transparent";
            el.style.strokeDasharray  = `${len + 10} ${len + 10}`;
            el.style.strokeDashoffset = `${len + 10}`;
        });

        // Exactly 2.0 seconds writing animation, then disappears within 2 seconds
        const cursiveTl = gsap.timeline();

        // 1. Container fade-in to full opacity (0s -> 0.2s)
        cursiveTl.to(container, {
            opacity: 1,
            y: 0,
            duration: 0.2,
            ease: "power2.out"
        }, 0);

        // 2. Line 1 Stroke Reveal (0.05s -> 1.05s, duration 1.0s)
        cursiveTl.to(line1, {
            strokeDashoffset: 0,
            duration: 1.0,
            ease: "power1.inOut",
            onComplete() {
                line1.classList.add("filled");
                line1.style.fill = inkColor;
                line1.style.stroke = inkColor;
                gsap.to(line1, { strokeWidth: 4.5, duration: 0.2 });
            }
        }, 0.05);

        // 3. Line 2 Stroke Reveal (starts at 0.85s, completes at 1.85s, duration 1.0s)
        cursiveTl.to(line2, {
            strokeDashoffset: 0,
            duration: 1.0,
            ease: "power1.inOut",
            onComplete() {
                line2.classList.add("filled");
                line2.style.fill = inkColor;
                line2.style.stroke = inkColor;
                gsap.to(line2, { strokeWidth: 4.5, duration: 0.2 });
            }
        }, 0.85);

        // Line 2 fill transition completes writing fully by 2.0s!
        // 4. Timed Disappearance: As soon as animation finishes at 2.0s, it disappears within 2.0s
        cursiveTl.to(container, {
            opacity: 0,
            y: -35,
            duration: 2.0,
            ease: "power2.inOut",
            onComplete() {
                container.style.display = "none";
            }
        }, 2.0);

        // Instant graceful dismiss if the user begins scrolling before the 2s timer ends
        const onUserScrollDismiss = () => {
            if (window.scrollY > 25) {
                gsap.to(container, {
                    opacity: 0,
                    y: -35,
                    duration: 0.35,
                    ease: "power2.out",
                    onComplete() {
                        container.style.display = "none";
                    }
                });
                window.removeEventListener("scroll", onUserScrollDismiss);
            }
        };
        window.addEventListener("scroll", onUserScrollDismiss, { passive: true });
    };

});
