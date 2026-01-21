<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'EasyVente') }} - Gestion Multi-Commerce</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: float 8s ease-in-out infinite;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            bottom: -50px;
            left: -100px;
            animation-delay: 2s;
        }

        /* Device Mockups */
        .device-laptop {
            position: relative;
            width: 100%;
            max-width: 600px;
        }

        @media (max-width: 768px) {
            .device-laptop {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .device-laptop {
                max-width: 100%;
            }
        }

        .laptop-frame {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px 16px 0 0;
            padding: 12px 12px 0 12px;
            border: 2px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .laptop-screen {
            background: #0f172a;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            aspect-ratio: 16/10;
            position: relative;
        }

        .laptop-screen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        .laptop-screen-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .laptop-base {
            background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
            height: 16px;
            border-radius: 0 0 4px 4px;
            margin: 0 -20px;
            position: relative;
        }

        .laptop-base::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: #475569;
            border-radius: 0 0 4px 4px;
        }

        .laptop-stand {
            background: linear-gradient(180deg, #475569 0%, #334155 100%);
            height: 8px;
            margin: 0 auto;
            width: 40%;
            border-radius: 0 0 8px 8px;
        }

        .device-phone {
            position: absolute;
            right: -30px;
            bottom: -20px;
            width: 140px;
            z-index: 10;
        }

        .phone-frame {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 24px;
            padding: 8px;
            border: 2px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .phone-screen {
            background: #0f172a;
            border-radius: 18px;
            overflow: hidden;
            aspect-ratio: 9/19;
            position: relative;
        }

        .phone-screen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        .phone-screen-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .phone-notch {
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 20px;
            background: #0f172a;
            border-radius: 12px;
            z-index: 10;
        }

        .mockup-container {
            position: relative;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .mockup-container {
                padding: 10px;
            }
        }

        @media (max-width: 768px) {
            .mockup-container {
                padding: 10px;
            }
        }

        .mockup-glow {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .floating {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        .floating-delay {
            animation: floating 6s ease-in-out infinite;
            animation-delay: 1s;
        }

        .blob-3 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        @media (max-width: 640px) {
            .blob {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .blob {
                display: none;
            }
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.15);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(30, 41, 59, 0.6);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-5px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.5);
        }

        .btn-secondary {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .logo-text {
            background: linear-gradient(135deg, #6366f1, #a855f7, #6366f1);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shine 3s linear infinite;
        }

        @keyframes shine {
            to { background-position: 200% center; }
        }

        .pulse-dot {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen text-white overflow-x-hidden">
    <!-- Contact Form Component -->
    @livewire('contact-form')

    <!-- Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Navigation -->
    <nav class="relative z-10 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 flex items-center justify-center">
                    <img src="{{ asset('icon.png') }}" alt="{{ config('app.name', 'EasyVente') }}" class="w-full h-full object-contain rounded-xl">
                </div>
                <span class="text-2xl font-bold logo-text">{{ config('app.name', 'EasyVente') }}</span>
            </div>

            <!-- Auth Buttons -->
            <div class="flex items-center gap-4">
                <!-- Pricing Link -->
                <a href="#pricing" class="hidden md:flex items-center gap-1 px-4 py-2 text-slate-300 hover:text-indigo-400 transition-colors font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tarifs
                </a>

                <!-- Contact Button -->
                <button
                    onclick="Livewire.dispatch('openContactModal')"
                    class="btn-secondary px-6 py-2.5 rounded-lg font-medium flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="hidden sm:inline">Contacter</span>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn-secondary px-6 py-2.5 rounded-lg font-medium">
                        Tableau de bord
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary px-6 py-2.5 rounded-lg font-medium">
                        Connexion
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-primary px-6 py-2.5 rounded-lg font-medium shadow-lg">
                            Créer un compte
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-8 sm:pt-12 lg:pt-16 pb-12 sm:pb-16 lg:pb-24">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20">
                        <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></span>
                        <span class="text-sm text-indigo-300">Version {{ app()->version() ?? '1.0' }} disponible</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                        Gérez votre
                        <span class="logo-text">commerce</span>
                        <br>en toute simplicité
                    </h1>

                    <p class="text-base sm:text-lg lg:text-xl text-slate-400 leading-relaxed max-w-lg">
                        EasyVente est la solution complète pour la gestion multi-boutiques.
                        Stocks, ventes, employés et statistiques en temps réel.
                    </p>

                    <div class="flex flex-wrap gap-3 sm:gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary px-4 sm:px-6 lg:px-8 py-3 sm:py-4 rounded-xl font-semibold text-sm sm:text-base lg:text-lg shadow-xl flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                                Accéder au tableau de bord
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-primary px-4 sm:px-6 lg:px-8 py-3 sm:py-4 rounded-xl font-semibold text-sm sm:text-base lg:text-lg shadow-xl flex items-center gap-2">
                                <span class="hidden sm:inline">Commencer gratuitement</span>
                                <span class="sm:hidden">Commencer</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                            <a href="{{ route('login') }}" class="btn-secondary px-4 sm:px-6 lg:px-8 py-3 sm:py-4 rounded-xl font-semibold text-sm sm:text-base lg:text-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Se connecter
                            </a>
                        @endauth
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-6 sm:pt-8">
                        <div class="stat-card p-3 sm:p-4 rounded-xl text-center">
                            <div class="text-2xl sm:text-3xl font-bold text-indigo-400">∞</div>
                            <div class="text-xs sm:text-sm text-slate-400 mt-1">Boutiques</div>
                        </div>
                        <div class="stat-card p-3 sm:p-4 rounded-xl text-center">
                            <div class="text-2xl sm:text-3xl font-bold text-purple-400">100%</div>
                            <div class="text-xs sm:text-sm text-slate-400 mt-1">Sécurisé</div>
                        </div>
                        <div class="stat-card p-3 sm:p-4 rounded-xl text-center">
                            <div class="text-2xl sm:text-3xl font-bold text-cyan-400">24/7</div>
                            <div class="text-xs sm:text-sm text-slate-400 mt-1">Disponible</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Device Mockups -->
                <div class="mockup-container">
                    <div class="mockup-glow"></div>

                    <!-- Laptop Mockup -->
                    <div class="device-laptop floating">
                        <div class="laptop-frame">
                            <div class="laptop-screen">
                                {{-- Remplacer par votre capture d'écran desktop --}}
                                @if(file_exists(public_path('images/mockup-desktop.png')))
                                    <img src="{{ asset('images/mockup-desktop.png') }}" alt="EasyVente Dashboard">
                                @else
                                    <div class="laptop-screen-placeholder">
                                        <div class="text-center p-4">
                                            <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                                <span class="text-white font-bold text-2xl">SF</span>
                                            </div>
                                            <p class="text-slate-400 text-sm">Dashboard Preview</p>
                                            <p class="text-slate-500 text-xs mt-1">mockup-desktop.png</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="laptop-base"></div>
                        <div class="laptop-stand"></div>

                        <!-- Phone Mockup -->
                        <div class="device-phone floating-delay hidden md:block">
                            <div class="phone-frame">
                                <div class="phone-notch"></div>
                                <div class="phone-screen">
                                    {{-- Remplacer par votre capture d'écran mobile --}}
                                    @if(file_exists(public_path('images/mockup-mobile.png')))
                                        <img src="{{ asset('images/mockup-mobile.png') }}" alt="EasyVente Mobile">
                                    @else
                                        <div class="phone-screen-placeholder">
                                            <div class="text-center p-2">
                                                <div class="w-10 h-10 mx-auto mb-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <span class="text-white font-bold text-sm">SF</span>
                                                </div>
                                                <p class="text-slate-400 text-[10px]">Mobile</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Section - Accessible en haut -->
        <div id="pricing" class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="text-center mb-8 sm:mb-12">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tarifs transparents
                </span>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4">Choisissez votre plan</h2>
                <p class="text-base sm:text-lg lg:text-xl text-slate-400 max-w-2xl mx-auto">
                    Des offres adaptées à la taille de votre entreprise. Évoluez à votre rythme.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
                @foreach($plans as $slug => $plan)
                    <div class="pricing-card glass-card rounded-2xl p-6 relative group hover:scale-105 transition-transform duration-300
                        @if($plan['is_popular'] ?? false) ring-2 ring-indigo-500 @endif">

                        @if($plan['is_popular'] ?? false)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold shadow-lg">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    Populaire
                                </span>
                            </div>
                        @endif

                        <div class="space-y-5 @if($plan['is_popular'] ?? false) pt-1 @endif">
                            <div>
                                <h3 class="text-lg font-semibold @if($plan['is_popular'] ?? false) text-white @else text-slate-300 @endif">
                                    {{ $plan['name'] }}
                                </h3>
                                <p class="text-slate-500 text-sm mt-1">
                                    @if($slug === 'free')
                                        Pour tester la plateforme
                                    @elseif($slug === 'starter')
                                        Pour les petits commerces
                                    @elseif($slug === 'professional')
                                        Pour les PME
                                    @else
                                        Pour les grandes structures
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold">{{ number_format($plan['price'], 0, ',', ' ') }}</span>
                                <span class="text-slate-400">{{ $currency }}/mois</span>
                            </div>

                            <ul class="space-y-2.5 text-sm">
                                @foreach($plan['features'] ?? [] as $feature)
                                    <li class="flex items-center gap-2 @if($plan['is_popular'] ?? false) text-slate-200 @else text-slate-300 @endif">
                                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @if($slug === 'enterprise')
                                <button
                                    onclick="Livewire.dispatch('openContactModal')"
                                    class="block w-full py-2.5 px-4 rounded-xl border border-slate-600 text-center font-medium text-sm hover:bg-slate-700 transition-colors"
                                >
                                    Nous contacter
                                </button>
                            @elseif($plan['is_popular'] ?? false)
                                <a href="{{ route('register') }}" class="block w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-center font-medium text-sm hover:opacity-90 transition-opacity shadow-lg shadow-indigo-500/25">
                                    Choisir {{ $plan['name'] }}
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="block w-full py-2.5 px-4 rounded-xl border border-slate-600 text-center font-medium text-sm hover:bg-slate-700 transition-colors">
                                    @if($slug === 'free')
                                        Commencer
                                    @else
                                        Choisir {{ $plan['name'] }}
                                    @endif
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Trust badges -->
            <div class="mt-12 text-center">
                <div class="flex flex-wrap items-center justify-center gap-8 text-slate-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Paiement sécurisé</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Annulation à tout moment</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>14 jours d'essai gratuit</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Preview Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
            <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-12">
                <div class="grid lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-12 items-center">
                    <!-- Left - Features List -->
                    <div class="space-y-4 sm:space-y-6">
                        <h2 class="text-2xl sm:text-3xl font-bold">Une interface intuitive</h2>
                        <p class="text-slate-400 text-base sm:text-lg">
                            Découvrez une expérience utilisateur optimisée pour la productivité
                        </p>

                        <div class="space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-1">Design responsive</h4>
                                    <p class="text-slate-400 text-sm">Accédez à votre tableau de bord depuis n'importe quel appareil</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-1">Ultra rapide</h4>
                                    <p class="text-slate-400 text-sm">Performance optimisée pour une navigation fluide</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold mb-1">Mode sombre</h4>
                                    <p class="text-slate-400 text-sm">Interface élégante et agréable pour les yeux</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            @guest
                                <a href="{{ route('register') }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold">
                                    Essayer gratuitement
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                            @endguest
                        </div>
                    </div>

                    <!-- Right - Auth Card -->
                    <div class="space-y-6">
                        <div class="text-center space-y-2">
                            <h3 class="text-2xl font-bold">Prêt à commencer ?</h3>
                            <p class="text-slate-400">Connectez-vous ou créez votre compte</p>
                        </div>

                        <div class="space-y-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="w-full btn-primary py-4 rounded-xl font-semibold text-center block">
                                    Accéder à mon espace
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="w-full btn-primary py-4 rounded-xl font-semibold text-center block">
                                    Se connecter
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="w-full btn-secondary py-4 rounded-xl font-semibold text-center block">
                                        Créer un compte
                                    </a>
                                @endif
                            @endauth
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-slate-600/50"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-slate-800/80 text-slate-400 rounded">Fonctionnalités incluses</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Multi-boutiques</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Stocks temps réel</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Point de vente</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Statistiques</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Gestion équipe</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Rapports PDF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="max-w-7xl mx-auto px-6 py-24">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">Tout ce dont vous avez besoin</h2>
                <p class="text-xl text-slate-400 max-w-2xl mx-auto">
                    Une suite complète d'outils pour gérer efficacement votre activité commerciale
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Multi-Boutiques</h3>
                    <p class="text-slate-400">Gérez plusieurs points de vente depuis une seule interface centralisée.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Gestion des Stocks</h3>
                    <p class="text-slate-400">Suivez vos inventaires en temps réel avec alertes automatiques.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Point de Vente</h3>
                    <p class="text-slate-400">Interface POS intuitive pour des ventes rapides et efficaces.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Statistiques</h3>
                    <p class="text-slate-400">Tableaux de bord avec KPIs et rapports détaillés.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Gestion d'Équipe</h3>
                    <p class="text-slate-400">Rôles et permissions personnalisables par boutique.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card rounded-2xl p-6 space-y-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Sécurité</h3>
                    <p class="text-slate-400">Protection des données avec authentification avancée.</p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-700/50">
        <div class="max-w-7xl mx-auto px-6 py-12">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 flex items-center justify-center">
                        <img src="{{ asset('icon.png') }}" alt="{{ config('app.name', 'EasyVente') }}" class="w-full h-full object-contain rounded-lg">
                    </div>
                    <span class="text-lg font-semibold">{{ config('app.name', 'EasyVente') }}</span>
                </div>

                <div class="flex items-center gap-6 text-slate-400 text-sm">
                    <button
                        onclick="Livewire.dispatch('openContactModal')"
                        class="hover:text-indigo-400 transition-colors flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Nous contacter
                    </button>
                    <span>•</span>
                    <span>© {{ date('Y') }} {{ config('app.name', 'EasyVente') }}</span>
                </div>

                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500">Développé par</span>
                    <a href="{{ config('app.developer_url', '#') }}" target="_blank" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                        {{ config('app.developer_name', 'Africa Go Tech') }}
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
