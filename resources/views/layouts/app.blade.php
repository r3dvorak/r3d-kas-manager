<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R3D KAS Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/js/uikit-icons.min.js"></script>
    <style>
        body {
            background: #fff;
            color: #222;
        }
        header {
            padding: 10px 45px;
            border-bottom: 1px solid #e5e5e5; /* Unterstreichung wie r3d.de */
        }
        .uk-logo img {
            height: 32px;
        }
        .sidebar {
            padding-right: 20px;
        }
        .right-col {
            padding-left: 20px;
        }
        footer {
            background: #f7f7f7; /* grau wie r3d.de */
            border-top: 1px solid #e5e5e5;
            font-size: 0.875rem;
            color: #666;
            text-align: center;
            padding: 20px 45px;
            margin-top: 40px;
        }
        .sidebar a {
            color: #444;
        }
        .sidebar a:hover {
            color: #00833e; /* r3d green */
        }
    </style>
</head>
<body>

<header class="uk-flex uk-flex-between uk-flex-middle">
    <a href="/" class="uk-logo">
        <img src="https://www.r3d.de/images/svg/r3d-logo_green_ng.svg" alt="R3D Logo">
    </a>
    <div class="uk-flex uk-flex-middle">
        <form class="uk-search uk-search-default uk-margin-small-right">
            <span uk-search-icon></span>
            <input class="uk-search-input" type="search" placeholder="Suche...">
        </form>
        <a href="#" class="uk-button uk-button-text">ABMELDEN</a>
    </div>
</header>

<!-- Layout Grid -->
<div class="uk-grid uk-grid-small" uk-grid style="padding: 20px 45px;">

    <!-- Sidebar -->
    <aside class="sidebar uk-width-1-4@m uk-width-1-1">
        <ul class="uk-nav uk-nav-default">
            <li><a href="#">Startseite</a></li>
            <li><a href="#">Accounts</a></li>
            <li><a href="#">User</a></li>
            <li><a href="#">Doku</a></li>
            <li><a href="#">Stats</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="uk-width-expand@m uk-width-1-1">
        @yield('content')
    </main>

    <!-- Right Column -->
    <aside class="right-col uk-width-1-4@m uk-width-1-1">
        <h4 class="uk-heading-line"><span>Hinweise</span></h4>
        <p>Hier könnten Tipps oder Logs erscheinen.</p>
    </aside>
</div>

<footer>
    &copy; {{ date('Y') }} R3D Internet Dienstleistungen · 
    <a href="#">Impressum</a> · <a href="#">Datenschutz</a>
</footer>

</body>
</html>
