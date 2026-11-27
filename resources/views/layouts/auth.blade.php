<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Kanri</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --kanri-navy: #24247f;
            --kanri-blue: #1857b6;
            --kanri-blue-dark: #1b3761;
            --kanri-white: #ffffff;
            --kanri-gray-900: #0f172a;
            --kanri-gray-700: #334155;
            --kanri-gray-500: #64748b;
            --kanri-gray-400: #94a3b8;
            --kanri-gray-200: #e2e8f0;
            --kanri-gray-50: #f8fafc;
            --kanri-danger: #dc2626;
            --kanri-body: rgba(37, 42.92, 54.02, 0.95);
            --kanri-muted: rgba(37, 42.92, 54.02, 0.681);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            line-height: 1.5;
            color: var(--kanri-body);
            -webkit-text-size-adjust: 100%;
        }

        h1, h2, p { margin-top: 0; }

        .font-poppins { font-family: 'Poppins', sans-serif; }
        .font-inter { font-family: 'Inter', sans-serif; }

        .login-page {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #17175a 0%, var(--kanri-navy) 45%, var(--kanri-blue-dark) 100%);
        }

        .login-page::before,
        .login-page::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .login-page::before {
            background:
                radial-gradient(circle at 78% 18%, rgba(93, 135, 255, 0.28), transparent 38%),
                radial-gradient(circle at 12% 30%, rgba(255, 255, 255, 0.07), transparent 35%),
                radial-gradient(circle at 60% 95%, rgba(24, 87, 182, 0.45), transparent 45%);
        }

        .login-page::after {
            background-image: radial-gradient(rgba(255, 255, 255, 0.09) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: linear-gradient(115deg, transparent 35%, #000 70%, transparent 100%);
            mask-image: linear-gradient(115deg, transparent 35%, #000 70%, transparent 100%);
        }

        .login-monogram {
            position: absolute;
            left: 2vw;
            bottom: -6vh;
            width: min(620px, 46vw);
            opacity: 0.05;
            filter: brightness(0) invert(1);
            clip-path: inset(0 0 17% 0);
            pointer-events: none;
            user-select: none;
        }

        .login-waves {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .login-waves path { fill: rgba(255, 255, 255, 0.035); }

        .login-content {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
            max-width: 1280px;
            margin: 0 auto;
            padding: 3rem 4.5rem;
        }

        .login-brand {
            display: flex;
            flex-direction: column;
            max-width: 560px;
            color: var(--kanri-white);
        }

        .login-brand h1 {
            margin: 0 0 1.1rem;
            font-size: 2.75rem;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: -0.4px;
        }

        .login-brand p {
            margin: 0;
            max-width: 470px;
            font-size: 0.95rem;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.75);
        }

        .login-brand-logo {
            width: 200px;
            margin-bottom: 3rem;
        }

        .login-brand-line {
            display: block;
            width: 70px;
            height: 4px;
            margin-top: 2.1rem;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--kanri-white), rgba(255, 255, 255, 0.2));
        }

        .login-card-wrapper {
            width: 100%;
            max-width: 450px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-card {
            width: 100%;
            padding: 2.6rem 2.75rem 2.25rem;
            border-radius: 18px;
            background: var(--kanri-white);
            box-shadow: 0 30px 60px rgba(7, 10, 40, 0.45);
        }

        .login-card h2 {
            margin: 0 0 0.25rem;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--kanri-gray-900);
        }

        .login-card-head { margin-bottom: 1.5rem; }

        .login-card-head p {
            margin: 0;
            color: var(--kanri-muted);
        }

        .login-card-logo {
            display: none;
            width: 150px;
            margin: 0 auto 1.75rem;
        }

        .login-copy {
            position: absolute;
            z-index: 1;
            left: 0;
            right: 0;
            bottom: 1.25rem;
            margin: 0;
            text-align: center;
            font-size: 0.72rem;
            letter-spacing: 0.4px;
            color: rgba(255, 255, 255, 0.45);
        }

        .login-link {
            font-size: 0.875em;
            font-weight: 500;
            color: var(--kanri-blue);
            text-decoration: none;
        }

        .login-link:hover { text-decoration: underline; }

        .login-field { margin-bottom: 1rem; }
        .login-field.last { margin-bottom: 1.5rem; }

        .login-field-top {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: baseline;
            column-gap: 1rem;
        }

        .login-field-top .login-link { margin-bottom: 0.5rem; }

        .form-label {
            display: inline-block;
            margin-bottom: 0.5rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--kanri-gray-700);
            transition: color 0.25s ease;
        }

        .login-field:focus-within .form-label { color: var(--kanri-blue); }

        .login-input {
            position: relative;
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            width: 100%;
            border: 1px solid var(--kanri-gray-200);
            border-radius: 8px;
            background: var(--kanri-gray-50);
        }

        .login-input::before {
            content: '';
            position: absolute;
            inset: -1px;
            z-index: 0;
            border: 1.5px solid var(--kanri-blue);
            border-radius: 8px;
            background: var(--kanri-white);
            box-shadow: 0 0 0 4px rgba(24, 87, 182, 0.12);
            clip-path: inset(-6px calc(100% + 6px) -6px -6px round 12px);
            transition: clip-path 0.45s cubic-bezier(0.65, 0, 0.35, 1);
            pointer-events: none;
        }

        .login-input:focus-within::before { clip-path: inset(-6px -6px -6px -6px round 12px); }

        .login-input .input-group-text,
        .login-input .form-control {
            position: relative;
            z-index: 1;
            height: 44px;
            border: 0;
            background: transparent;
            box-shadow: none;
            outline: 0;
        }

        .login-input .input-group-text {
            display: flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            color: var(--kanri-gray-500);
            transition: color 0.35s ease;
        }

        .login-input .form-control {
            display: block;
            flex: 1 1 auto;
            width: 1%;
            min-width: 0;
            padding: 0.375rem 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--kanri-body);
            -webkit-appearance: none;
            appearance: none;
        }

        .login-input .form-control::placeholder { color: var(--kanri-muted); opacity: 0.8; }

        .login-input > :not(:first-child) { margin-left: -1px; }

        .login-input .input-group-text:last-child { color: var(--kanri-gray-400); }

        .login-input:focus-within .input-group-text:first-child { color: var(--kanri-blue); }

        .login-input .cursor-pointer { cursor: pointer; }

        .login-input .login-at {
            max-width: 0;
            padding: 0;
            overflow: hidden;
            opacity: 0;
            font-weight: 600;
            color: var(--kanri-blue);
            transition: max-width 0.35s ease, opacity 0.35s ease;
        }

        .login-input:focus-within .login-at,
        .login-input.has-value .login-at {
            max-width: 1.2rem;
            opacity: 1;
        }

        .login-input:focus-within .login-at + .form-control,
        .login-input.has-value .login-at + .form-control { padding-left: 0.2rem; }

        .login-input.input-group-error {
            border-color: var(--kanri-danger);
            box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.25);
        }

        .field-error {
            margin-top: 0.25rem;
            font-size: 0.875em;
            font-weight: 500;
            color: var(--kanri-danger);
        }

        .btn-login {
            width: 100%;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--kanri-navy);
            border-radius: 8px;
            background: var(--kanri-navy);
            color: var(--kanri-white);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(36, 36, 127, 0.28);
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }

        .btn-login:hover { background: #1d1d6b; border-color: #1d1d6b; }
        .btn-login:active { background: #17175a; border-color: #17175a; }
        .btn-login:disabled { opacity: 0.8; cursor: default; }

        .btn-login svg { width: 1em; height: 1em; }

        .btn-login > span {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .btn-login .spinner { margin-right: 0; }

        .btn-login-outline {
            background: var(--kanri-white);
            color: var(--kanri-navy);
            box-shadow: none;
        }

        .btn-login-outline:hover { background: var(--kanri-gray-50); color: var(--kanri-navy); border-color: var(--kanri-navy); }
        .btn-login-outline:active { background: var(--kanri-gray-200); color: var(--kanri-navy); border-color: var(--kanri-navy); }

        .data-box {
            margin-bottom: 0.75rem;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--kanri-gray-200);
            border-radius: 8px;
            background: var(--kanri-gray-50);
            font-size: 0.9rem;
            color: var(--kanri-gray-900);
            word-break: break-all;
        }

        .data-box span {
            display: block;
            margin-bottom: 0.15rem;
            font-size: 0.75rem;
            color: var(--kanri-muted);
        }

        .scope-title {
            margin: 1rem 0 0.25rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--kanri-gray-700);
        }

        .scope-list {
            margin: 0;
            padding-left: 1.15rem;
            font-size: 0.9rem;
            color: var(--kanri-gray-700);
        }

        .button-row {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .button-row form { flex: 1; }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            vertical-align: -0.15em;
            border: 0.15em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-turn 0.75s linear infinite;
        }

        @keyframes spinner-turn { to { transform: rotate(360deg); } }

        @media (max-width: 991.98px) {
            .login-content {
                justify-content: center;
                padding: 2.5rem 1rem 4rem;
            }

            .login-brand { display: none; }

            .login-card-logo { display: block; }

            .login-monogram {
                left: 50%;
                top: 50%;
                bottom: auto;
                width: max(1400px, 175vw);
                transform: translate(-50%, -42%);
                opacity: 0.08;
            }

            .login-card { padding: 2.75rem 2.25rem 2.5rem; }

            .login-card-head { text-align: center; }
        }

        @media (max-width: 575.98px) {
            .login-card { padding: 2.5rem 1.5rem 2.25rem; }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <svg class="login-waves" viewBox="0 0 1280 800" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 600 C260 540 520 690 820 610 C1000 560 1140 620 1280 590 L1280 800 L0 800 Z" />
            <path d="M0 660 C280 610 560 760 860 680 C1040 630 1170 690 1280 665 L1280 800 L0 800 Z" />
            <path d="M0 725 C300 685 600 800 900 740 C1070 705 1190 750 1280 735 L1280 800 L0 800 Z" />
        </svg>
        <img src="{{ route('brand.image', ['file' => 'logo-b'], false) }}" alt="" class="login-monogram" aria-hidden="true">

        <div class="login-content">
            <div class="login-brand">
                <img src="{{ route('brand.image', ['file' => 'logo-kanri'], false) }}" alt="Kanri" class="login-brand-logo">
                <h1 class="font-poppins">
                    Toda la organización,
                    <br>
                    en un solo lugar.
                </h1>
                <p class="font-inter">
                    Kanri reúne la gestión de Organización Bless en una sola plataforma, con la información
                    siempre al día.
                </p>
                <span class="login-brand-line"></span>
            </div>

            <div class="login-card-wrapper">
                <div class="login-card">
                    <img src="{{ route('brand.image', ['file' => 'logo-b'], false) }}" alt="Kanri" class="login-card-logo">
                    @yield('card')
                </div>
            </div>
        </div>

        <p class="login-copy font-inter">© Organización Bless · Kanri</p>
    </div>
</body>
</html>
