<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CORRECTION 1 : Ajout du Jeton CSRF (ESSENTIEL pour la sécurité et la communication) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Polices -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- CORRECTION 2 : Lien vers Axios réparé (le vôtre était cassé) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center">
        <main class="w-full max-w-3xl p-4">
            @yield('content')
        </main>
    </div>

    <!-- CORRECTION 3 : Ajout de @stack('scripts') (ESSENTIEL pour que le script du scanner soit exécuté) -->
    @stack('scripts')
</body>
</html>

