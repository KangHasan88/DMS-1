<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DMS KURMIGO') }} - Digitalisasi dan Otomasi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --auth-blue: #061a3f;
                --auth-blue-dark: #030d20;
                --auth-blue-soft: #e9eef8;
                --auth-orange: #ff7a00;
                --auth-gray-50: #f7faff;
                --auth-gray-100: #eef4ff;
                --auth-gray-200: #dce7f7;
                --auth-gray-500: #64748b;
                --auth-gray-700: #334155;
                --auth-gray-900: #0f172a;
                --auth-white: #ffffff;
                --auth-red: #dc2626;
                --auth-shadow: 0 24px 60px rgba(3, 13, 32, 0.14);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                color: var(--auth-gray-900);
                background:
                    radial-gradient(circle at 86% 12%, rgba(255, 122, 0, 0.08), transparent 18rem),
                    radial-gradient(circle at 12% 10%, rgba(6, 26, 63, 0.12), transparent 24rem),
                    var(--auth-gray-50);
            }

            .auth-shell {
                min-height: 100vh;
                display: grid;
                grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
            }

            .auth-brand-panel {
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: clamp(2rem, 5vw, 5rem);
                overflow: hidden;
            }

            .auth-brand-panel::before {
                content: '';
                position: absolute;
                inset: 8% 10% auto auto;
                width: 16rem;
                height: 16rem;
                border-radius: 999px;
                background: rgba(255, 122, 0, 0.08);
                filter: blur(6px);
            }

            .auth-brand-mark {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 0.9rem;
                margin-bottom: 2.2rem;
                text-decoration: none;
                color: inherit;
            }

            .auth-brand-mark img {
                width: 88px;
                height: auto;
                filter: drop-shadow(0 18px 26px rgba(3, 13, 32, 0.16));
            }

            .auth-brand-copy h1 {
                margin: 0;
                font-size: clamp(2.5rem, 5.5vw, 4.8rem);
                line-height: 0.95;
                font-weight: 800;
                letter-spacing: 0;
                color: var(--auth-blue);
            }

            .auth-brand-copy h1 span {
                color: var(--auth-orange);
            }

            .auth-brand-copy p {
                max-width: 34rem;
                margin: 1.25rem 0 0;
                color: var(--auth-gray-700);
                font-size: 1rem;
                line-height: 1.75;
            }

            .auth-proof {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                margin-top: 2rem;
            }

            .auth-proof-item {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.55rem 0.75rem;
                border-radius: 8px;
                background: var(--auth-white);
                border: 1px solid var(--auth-gray-200);
                color: var(--auth-blue);
                font-size: 0.78rem;
                font-weight: 700;
                box-shadow: 0 10px 28px rgba(3, 13, 32, 0.06);
            }

            .auth-card-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: clamp(1.25rem, 4vw, 3rem);
            }

            .auth-card {
                width: min(100%, 430px);
                padding: 2rem;
                background: rgba(255, 255, 255, 0.96);
                border: 1px solid var(--auth-gray-200);
                border-radius: 10px;
                box-shadow: var(--auth-shadow);
            }

            .auth-card-header {
                margin-bottom: 1.5rem;
            }

            .auth-card-header h2 {
                margin: 0;
                color: var(--auth-gray-900);
                font-size: 1.45rem;
                font-weight: 800;
                line-height: 1.2;
            }

            .auth-card-header p {
                margin: 0.45rem 0 0;
                color: var(--auth-gray-500);
                font-size: 0.86rem;
                line-height: 1.6;
            }

            .auth-field {
                margin-bottom: 1rem;
            }

            .auth-label {
                display: block;
                margin-bottom: 0.4rem;
                color: var(--auth-gray-700);
                font-size: 0.78rem;
                font-weight: 700;
            }

            .auth-input {
                width: 100%;
                height: 44px;
                border: 1px solid var(--auth-gray-200);
                border-radius: 8px;
                padding: 0 0.9rem;
                color: var(--auth-gray-900);
                font-size: 0.9rem;
                outline: none;
                background: var(--auth-white);
                transition: border-color 0.2s, box-shadow 0.2s;
            }

            .auth-input:focus {
                border-color: var(--auth-blue);
                box-shadow: 0 0 0 3px var(--auth-blue-soft);
            }

            .auth-error {
                margin-top: 0.35rem;
                color: var(--auth-red);
                font-size: 0.72rem;
                font-weight: 600;
            }

            .auth-session {
                margin-bottom: 1rem;
                padding: 0.75rem 0.85rem;
                border-radius: 8px;
                background: #e6f7e6;
                color: #15803d;
                font-size: 0.8rem;
                font-weight: 700;
            }

            .auth-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 1rem;
                flex-wrap: wrap;
            }

            .auth-check {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--auth-gray-500);
                font-size: 0.8rem;
            }

            .auth-check input {
                border-radius: 5px;
                color: var(--auth-blue);
            }

            .auth-link {
                color: var(--auth-blue);
                text-decoration: none;
                font-size: 0.78rem;
                font-weight: 700;
            }

            .auth-link:hover {
                color: var(--auth-orange);
            }

            .auth-actions {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                margin-top: 1.35rem;
            }

            .auth-button {
                min-height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                padding: 0.65rem 1.1rem;
                border: 0;
                border-radius: 8px;
                background: linear-gradient(135deg, var(--auth-blue-dark), var(--auth-blue));
                color: var(--auth-white);
                font-size: 0.82rem;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 12px 28px rgba(6, 26, 63, 0.22);
            }

            .auth-button:hover {
                background: linear-gradient(135deg, #020817, var(--auth-blue-dark));
            }

            @media (max-width: 920px) {
                .auth-shell {
                    grid-template-columns: 1fr;
                }

                .auth-brand-panel {
                    padding-bottom: 1rem;
                }

                .auth-card-panel {
                    align-items: flex-start;
                    padding-top: 0;
                }
            }

            @media (max-width: 560px) {
                .auth-brand-mark img {
                    width: 72px;
                }

                .auth-card {
                    padding: 1.25rem;
                }

                .auth-actions,
                .auth-button {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <main class="auth-shell">
            <section class="auth-brand-panel" aria-label="DMS KURMIGO">
                <a href="{{ route('login') }}" class="auth-brand-mark">
                    <img src="{{ asset('images/brand/kurmigo-robot.png') }}" alt="">
                    <div>
                        <strong style="display: block; color: var(--auth-blue); font-size: 1.1rem;">DMS KURMIGO</strong>
                        <span style="display: block; color: var(--auth-gray-500); font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;">Digitalisasi dan Otomasi</span>
                    </div>
                </a>

                <div class="auth-brand-copy">
                    <h1>Kelola Operasional <span>Lebih Rapi</span></h1>
                    <p>DMS KURMIGO membantu tim mengatur pesanan, stok, pembelian, pengiriman, dan laporan bisnis dalam satu alur kerja yang aman dan terstruktur.</p>
                </div>

                <div class="auth-proof" aria-label="Fitur utama">
                    <div class="auth-proof-item"><i class="bi bi-box-seam"></i> Inventory</div>
                    <div class="auth-proof-item"><i class="bi bi-truck"></i> Delivery</div>
                    <div class="auth-proof-item"><i class="bi bi-graph-up"></i> Reporting</div>
                </div>
            </section>

            <section class="auth-card-panel">
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </section>
        </main>
    </body>
</html>
