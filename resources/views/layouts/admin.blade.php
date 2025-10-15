<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Polices -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full">
<div class="min-h-full">
    <!-- Barre de navigation latérale -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex flex-col flex-grow pt-5 bg-gray-800 overflow-y-auto">
            <div class="flex items-center flex-shrink-0 px-4">
                <svg class="h-8 w-auto text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="ml-3 text-white text-lg font-semibold">Système Pointage</span>
            </div>
            <div class="mt-5 flex-1 flex flex-col">
                <nav class="flex-1 px-2 pb-4 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        Tableau de Bord
                    </a>
                    <a href="{{ route('admin.employees.index') }}" class="{{ request()->routeIs('admin.employees.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        Employés
                    </a>
                    <a href="{{ route('admin.attendances.index') }}" class="{{ request()->routeIs('admin.attendances.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        Feuilles de Présence
                    </a>
                    <a href="{{ route('admin.historique') }}" class="{{ request()->routeIs('admin.historique.*') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        historique Pointage
                    </a>
                </nav>
            </div>
             <!-- Section Utilisateur et Déconnexion -->
            <div class="flex-shrink-0 flex bg-gray-700 p-4">
                <div class="flex-shrink-0 w-full group block">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block h-9 w-9 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" alt="">
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            <!-- CORRECTION : Le formulaire doit pointer vers la route 'logout' -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-gray-300 group-hover:text-gray-200">Déconnexion</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Contenu Principal -->
    <div class="md:pl-64 flex flex-col flex-1">
        <main class="flex-1">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <h1 class="text-2xl font-semibold text-gray-900">@yield('title')</h1>
                </div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 mt-6">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>

