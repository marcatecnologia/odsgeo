<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Leaflet -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        @stack('scripts')
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="dark:text-gray-100">
                {{ $slot }}
            </main>
        </div>
        @livewireScripts
        <script>
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-message]');
            if (btn) {
                e.stopPropagation();
                try {
                    const data = btn.getAttribute('data-message');
                    if (!data) {
                        alert('Atributo data-message vazio!');
                        return;
                    }
                    const message = atob(data);
                    navigator.clipboard.writeText(message).then(function() {
                        if (window.Filament && Filament.Notifications) {
                            Filament.Notifications.Notification.make()
                                .title('Mensagem copiada!')
                                .success()
                                .send();
                        } else {
                            alert('Mensagem copiada!');
                        }
                    }, function(err) {
                        alert('Erro ao copiar: ' + err);
                    });
                } catch (e) {
                    alert('Erro JS: ' + e.message);
                }
            }
        });
        </script>
    </body>
</html>
