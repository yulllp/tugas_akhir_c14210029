<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yamdena Plaza</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qz-tray/2.2.4/qz-tray.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>
    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    <!-- PWA  -->
    <meta name="theme-color" content="#6777ef" />
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">
    <script>
        // So your frontend knows the public key:
        window.LARAVEL_PUSH_PUBLIC_KEY = "{{ config('webpush.vapid.public_key') }}";
    </script>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <x-side-bar>
        {{ $slot }}
    </x-side-bar>
    <script src="{{ asset('/sw.js') }}"></script>
    <script>
        if ("serviceWorker" in navigator) {
            // Register a service worker hosted at the root of the
            // site using the default scope.
            navigator.serviceWorker.register("/sw.js").then(
                (registration) => {
                    console.log("Service worker registration succeeded:", registration);
                },
                (error) => {
                    console.error(`Service worker registration failed: ${error}`);
                },
            );
        } else {
            console.error("Service workers are not supported.");
        }
    </script>
    @livewireScripts

    <script>
        $(document).ready(function() {
            // The URL of your polling endpoint
            const remindersUrl = "{{ route('credit.reminders.handle') }}";

            // Function to fetch & render reminders
            function fetchReminders() {
                $.ajax({
                    url: remindersUrl,
                    method: 'GET',
                    dataType: 'json',
                    success(response) {
                        console.log('test');
                    },
                    error(xhr, status, error) {
                        console.error('Gagal mengambil reminder kredit:', error);
                    }
                });
            }

            // Initial fetch
            fetchReminders();

            // Poll every 10 seconds (10000 ms)
            setInterval(fetchReminders, 10000);
        });
    </script>

</body>

</html>