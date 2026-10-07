<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080808">
    <title>404 — File not found</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Gilda+Display');

        * { box-sizing: border-box; }
        html, body { min-height: 100%; margin: 0; }
        body {
            min-height: 100vh;
            overflow: hidden;
            display: grid;
            place-items: center;
            color: #fff;
            background: radial-gradient(ellipse at center, #171717 0%, #080808 72%);
            font-family: 'Gilda Display', Georgia, serif;
        }
        .static {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .055;
            background-image: repeating-linear-gradient(0deg, transparent 0 2px, rgba(255,255,255,.8) 3px, transparent 4px), repeating-linear-gradient(90deg, transparent 0 5px, rgba(255,255,255,.45) 6px, transparent 7px);
            mix-blend-mode: screen;
        }
        main { position: relative; z-index: 1; width: min(92vw, 680px); text-align: center; padding: 2rem 1rem; }
        .error {
            position: relative;
            display: inline-block;
            color: #f7f7f7;
            font-size: clamp(7rem, 24vw, 12rem);
            font-style: italic;
            line-height: .9;
            letter-spacing: -.08em;
            animation: flicker 2.8s linear infinite;
        }
        .error::before, .error::after {
            content: '404';
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }
        .error::before { color: #1d9692; animation: glitch-left 3s steps(1, end) infinite; }
        .error::after { color: #a01e22; animation: glitch-right 3s steps(1, end) infinite; }
        .info { display: block; margin-top: 1rem; font-size: clamp(1.1rem, 3vw, 1.5rem); font-style: italic; }
        .description { margin: .7rem 0 0; color: #a3a3a3; font: 400 .95rem/1.6 system-ui, sans-serif; }
        .actions { display: flex; justify-content: center; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; font: 600 .9rem/1 system-ui, sans-serif; }
        .actions a { padding: .8rem 1rem; border: 1px solid #525252; color: #f5f5f5; text-decoration: none; transition: border-color .2s, background .2s; }
        .actions a:hover { border-color: #1d9692; background: rgba(29,150,146,.12); }
        .actions a.primary { border-color: #1d9692; background: #1d9692; color: #fff; }
        .actions a.primary:hover { background: #167c79; }
        @keyframes flicker { 0%, 3%, 5%, 42%, 44%, 100% { opacity: 1; transform: scaleY(1); } 4% { opacity: .85; transform: scaleY(1.12); } 43% { opacity: .9; transform: scaleX(1.03); } }
        @keyframes glitch-left { 0%, 88%, 91%, 100% { opacity: 0; transform: none; } 89% { opacity: .6; transform: translate(-5px, 1px); clip-path: inset(22% 0 58%); } 90% { opacity: .45; transform: translate(3px, -1px); clip-path: inset(65% 0 15%); } }
        @keyframes glitch-right { 0%, 78%, 81%, 100% { opacity: 0; transform: none; } 79% { opacity: .55; transform: translate(5px, -1px); clip-path: inset(48% 0 34%); } 80% { opacity: .35; transform: translate(-3px, 1px); clip-path: inset(12% 0 71%); } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
    <div class="static" aria-hidden="true"></div>
    <main>
        <div class="error" aria-label="Error 404">404</div>
        <span class="info">File not found</span>
        <p class="description">The page may have moved, or the address may be incorrect.</p>
        <nav class="actions" aria-label="Page navigation">
            <a class="primary" href="{{ url('/login') }}">Go to sign in</a>
            <a href="{{ url('/') }}">Return to home</a>
        </nav>
    </main>
</body>
</html>
