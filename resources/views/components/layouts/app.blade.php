<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EasyVente') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Alpine.js x-cloak style (must be before Alpine loads) -->
    <style>[x-cloak] { display: none !important; }</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-navigation-dynamic/>
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <x-header />

            <!-- Page Header Section -->
            @if (isset($header))
                <div class="bg-white border-b border-gray-200 px-6 py-4">
                    {{ $header }}
                </div>
            @endif

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Global Search Modal -->
    @livewire('global-search')

    <!-- Payment Modal - Shows when organization payment is pending -->
    @auth
        <livewire:organization.payment-modal />
    @endauth

    <!-- Toast Notifications -->
    <x-toast />

    @stack('scripts')

    <!-- Handle session flash messages -->
    @if (session()->has('success') || session()->has('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if (session()->has('success'))
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: @js(session('success')), type: 'success' }
                    }));
                @endif

                @if (session()->has('error'))
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: @js(session('error')), type: 'error' }
                    }));
                @endif
            });
        </script>
    @endif
</body>
</html>
