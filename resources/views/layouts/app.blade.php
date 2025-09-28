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

                <div class="uk-navbar-right uk-visible@m">
                    {{-- Suche --}}
                    <form class="uk-search uk-search-default uk-margin-right" style="font-size: 0.85rem;">
                        <span uk-search-icon></span>
                        <input class="uk-search-input" type="search" placeholder="Suche...">
                    </form>

                    {{-- Logout --}}
                    @if(Auth::guard('kas_client')->check())
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="uk-button uk-button-text">Abmelden</button>
                        </form>
                    @elseif(Auth::guard('web')->check())
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="uk-button uk-button-text">Abmelden</button>
                        </form>
                    @endif
                </div>
            </nav>
        </div>
    </header>

    {{-- Content --}}
    <main class="uk-section uk-section-default">
        <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
            <div class="uk-grid-large" uk-grid>

                {{-- Sidebar links --}}
                <aside class="uk-width-1-6@m uk-visible@m uk-border-right">
                    <ul class="uk-nav uk-nav-default">
                        
                        @if(Auth::guard('kas_client')->check())
                            <li><a href="{{ route('client.dashboard') }}">Dashboard</a></li>
                            <li><a href="{{ route('client.domains.index') }}">Domains</a></li>
                            <li><a href="{{ route('client.mailboxes.index') }}">Mailkonten</a></li>
                            <li><a href="{{ route('client.dns.index') }}">DNS</a></li>
                            <li><a href="{{ route('client.recipes.index') }}">Rezepte</a></li>
                        
                        @elseif(Auth::guard('web')->check())
                            <li><a href="{{ route('dashboard') }}">Startseite</a></li>
                            <li><a href="{{ route('kas-clients.index') }}">Accounts</a></li>
                            <li><a href="{{ route('users.index') }}">User</a></li>
                            <li><a href="{{ route('docs') }}">Doku</a></li>
                            <li><a href="{{ route('stats') }}">Stats</a></li>
                        @endif
                    </ul>
                </aside>

                {{-- Hauptinhalt --}}
                <section id="content" class="uk-width-expand uk-padding-remove-left">
                    @yield('content')
                </section>

                {{-- Sidebar rechts --}}
                <aside class="uk-width-1-6@m uk-visible@m uk-border-left uk-padding-small">
                    <h4 class="uk-heading-line"><span>Hinweise</span></h4>
                    <p>Hier könnten Tipps oder Logs erscheinen.</p>
                </aside>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="uk-background-muted uk-padding-small uk-text-center" style="padding-left:45px; padding-right:45px;">
        <p>© 2025 R3D Internet Dienstleistungen · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></p>
    </footer>

</body>
</html>
