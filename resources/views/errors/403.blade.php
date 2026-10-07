<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#a7a7a2">
    <title>403 — Access denied</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        * { box-sizing: border-box; }
        html, body { min-height: 100%; margin: 0; }
        body {
            min-height: 100vh;
            overflow: hidden;
            display: grid;
            place-items: center;
            color: #161817;
            background: linear-gradient(160deg, #e4e4e1 0%, #9da3a2 55%, #727879 100%);
            font-family: 'Poppins', system-ui, sans-serif;
        }
        .cage {
            position: fixed;
            z-index: 2;
            inset: 0;
            pointer-events: none;
            background: repeating-linear-gradient(90deg, transparent 0 74px, rgba(37,42,43,.34) 75px 78px, rgba(12,14,15,.76) 79px 91px);
            animation: close 1.8s cubic-bezier(.2,.7,.2,1) both;
        }
        main { position: relative; z-index: 3; width: min(92vw, 680px); padding: 2rem 1.25rem; text-align: center; }
        .code {
            position: fixed;
            z-index: 1;
            inset: 0;
            display: grid;
            place-items: center;
            margin: 0;
            overflow: hidden;
            color: rgba(10,13,13,.88);
            font-size: clamp(13rem, 48vw, 42rem);
            font-weight: 600;
            letter-spacing: -.09em;
            line-height: .8;
            text-shadow: 0 2px rgba(255,255,255,.24), 0 8px 24px rgba(0,0,0,.12);
            user-select: none;
        }
        .code span { position: relative; }
        .code span::after {
            content: '403';
            position: absolute;
            top: 82%; left: 8%;
            transform: scaleY(-.48) skewX(-8deg);
            transform-origin: top;
            opacity: .17;
            filter: blur(1px);
        }
        .eyebrow { margin: 0 0 .65rem; color: #303736; font-size: .75rem; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(1.8rem, 5vw, 3rem); font-weight: 600; letter-spacing: -.04em; }
        .description { max-width: 28rem; margin: .8rem auto 0; color: #303736; font-size: .95rem; line-height: 1.65; }
        .actions { display: flex; justify-content: center; flex-wrap: wrap; gap: .75rem; margin-top: 1.7rem; }
        .actions a { min-width: 9rem; padding: .8rem 1rem; border: 1px solid rgba(20,25,24,.55); color: #171b1a; font-size: .85rem; font-weight: 600; text-decoration: none; transition: background .2s, color .2s; }
        .actions a:hover { color: #fff; background: #1d9692; border-color: #1d9692; }
        .actions a.primary { color: #fff; background: #1d9692; border-color: #1d9692; }
        .actions a.primary:hover { background: #167c79; }
        @keyframes close { from { transform: translateX(-75%); } to { transform: translateX(0); } }
        @media (max-width: 520px) { .cage { background-size: 100px 100%; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
    <div class="cage" aria-hidden="true"></div>
    <div class="code" aria-hidden="true"><span>403</span></div>
    
</body>
</html>
