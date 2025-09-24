<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'r3d_kas_manager') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.23.13/dist/css/uikit.min.css" />
</head>
<body>

    <nav class="uk-navbar-container" uk-navbar>
        <div class="uk-container uk-container-expand">
            <div class="uk-navbar-left">
                <a href="{{ url('/') }}" class="uk-navbar-item uk-logo">r3d_kas_manager</a>
            </div>
            <div class="uk-navbar-right">
                <ul class="uk-navbar-nav">
                    <li><a href="{{ route('kas-clients.index') }}">KAS Clients</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="uk-container uk-margin">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/uikit@3.23.13/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.23.13/dist/js/uikit-icons.min.js"></script>

</body>
</html>
