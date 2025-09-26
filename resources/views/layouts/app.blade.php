<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RIIID KAS Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/js/uikit-icons.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>

    {{-- Header --}}
    <header class="uk-background-muted" uk-sticky>
        <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
            <nav class="uk-navbar-container uk-navbar-transparent" uk-navbar>
                <div class="uk-navbar-left">
                    <a href="/" class="uk-logo uk-padding-small uk-padding-remove-horizontal">
                        <img src="https://www.r3d.de/images/svg/r3d-logo_green_ng.svg" alt="RIIID" height="32">
                    </a>
                </div>

                @auth
                <div class="uk-navbar-right uk-visible@m">
                    <form class="uk-search uk-search-default uk-margin-right" style="font-size: 0.85rem;">
                        <span uk-search-icon></span>
                        <input class="uk-search-input" type="search" placeholder="Suche...">
                    </form>
                    {{-- Logout --}}
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="uk-button uk-button-text">Abmelden</button>
                    </form>
                </div>

                {{-- Mobile toggle --}}
                <div class="uk-navbar-right uk-hidden@m">
                    <a class="uk-navbar-toggle" uk-navbar-toggle-icon href="#offcanvas-nav" uk-toggle></a>
                </div>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Offcanvas Mobile Nav --}}
    @auth
    <div id="offcanvas-nav" uk-offcanvas="mode: slide; overlay: true">
        <div class="uk-offcanvas-bar">
            <button class="uk-offcanvas-close" type="button" uk-close></button>
            <ul class="uk-nav uk-nav-default">
                <li><a href="{{ route('dashboard') }}">Startseite</a></li>
                <li><a href="{{ route('kas-clients.index') }}">Accounts</a></li>
                <li><a href="{{ route('users.index') }}">User</a></li>
                <li><a href="{{ route('docs') }}">Doku</a></li>
                <li><a href="{{ route('stats') }}">Stats</a></li>
                <li class="uk-nav-divider"></li>
                {{-- Logout --}}
                <li>
                    <a href="#"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Abmelden
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
            <form class="uk-search uk-search-default uk-margin-top" style="font-size: 0.85rem;">
                <span uk-search-icon></span>
                <input class="uk-search-input" type="search" placeholder="Suche...">
            </form>
        </div>
    </div>
    @endauth

    {{-- Content --}}
    <main class="uk-section uk-section-default">
        <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
            <div class="uk-grid-large" uk-grid>
                
                {{-- Sidebar links --}}
                @auth
                <aside class="uk-width-1-6@m uk-visible@m uk-border-right">
                    <ul class="uk-nav uk-nav-default">
                        <li><a href="{{ route('dashboard') }}">Startseite</a></li>
                        <li><a href="{{ route('kas-clients.index') }}">Accounts</a></li>
                        <li><a href="{{ route('users.index') }}">User</a></li>
                        <li><a href="{{ route('docs') }}">Doku</a></li>
                        <li><a href="{{ route('stats') }}">Stats</a></li>
                    </ul>
                </aside>
                @endauth

                {{-- Hauptinhalt --}}
                <section id="content" class="uk-width-expand uk-padding-remove-left">
                    @yield('content')
                </section>

                {{-- Sidebar rechts --}}
                @auth
                <aside class="uk-width-1-6@m uk-visible@m uk-border-left uk-padding-small">
                    <h4 class="uk-heading-line"><span>Hinweise</span></h4>
                    <p>Hier könnten Tipps oder Logs erscheinen.</p>
                </aside>
                @endauth
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="uk-background-muted uk-padding-small uk-text-center" style="padding-left:45px; padding-right:45px;">
        <p>© 2025 R3D Internet Dienstleistungen · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></p>
    </footer>

</body>
</html>
