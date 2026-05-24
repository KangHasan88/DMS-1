<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .panel {
            width: min(92vw, 560px);
            padding: 2rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
        }
        .code {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            color: #166534;
            margin-bottom: 0.75rem;
        }
        h1 {
            font-size: 1.35rem;
            margin: 0 0 0.5rem;
        }
        p {
            margin: 0 0 1.5rem;
            color: #64748b;
        }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 1rem;
            border-radius: 8px;
            background: #166534;
            color: #ffffff;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="panel">
        <div class="code">404</div>
        <h1>Halaman tidak ditemukan</h1>
        <p>URL yang kamu buka tidak tersedia atau sudah dipindahkan.</p>
        <a href="{{ route('home') }}">Kembali ke Home</a>
    </main>
</body>
</html>
