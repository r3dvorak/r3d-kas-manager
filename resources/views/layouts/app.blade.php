<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @php
        $isAdmin  = Auth::guard('web')->check();
        $isClient = Auth::guard('kas_client')->check();
        $mode     = $isAdmin ? 'ADMIN' : ($isClient ? 'KAS Client' : '');
    @endphp
    <title>{{ $mode ? $mode . ' | ' : '' }}RIIID KAS Manager</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.17.11/dist/js/uikit-icons.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>

<header class="uk-background-muted" uk-sticky>
    <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
        <nav class="uk-navbar-container uk-navbar-transparent" uk-navbar>

            <div class="uk-navbar-left">
                {{-- Logo --}}
                @php
                    use App\Models\AppSetting;
                    $logoUrl  = AppSetting::getValue('logo_url', 'https://www.r3d.de/images/svg/r3d-logo_green_ng.svg');
                    $siteName = AppSetting::getValue('site_name', 'R3D KAS Manager');
                @endphp

                <a href="/" class="uk-logo uk-padding-small uk-padding-remove-horizontal">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="32">
                </a>

                {{-- Role label (ADMIN / KAS Client) --}}
                @if($isAdmin)
                    <span class="uk-label uk-label-danger uk-margin-small-left">ADMIN</span>
                @elseif($isClient)
                    <span class="uk-label uk-label-success uk-margin-small-left">KAS Client</span>
                @endif
            </div>

            <div class="uk-navbar-right">
                {{-- Desktop search --}}
                <div class="uk-visible@m">
                    <form class="uk-search uk-search-default uk-margin-right uk-margin-large-right" style="font-size: 0.85rem;">
                        <span uk-search-icon></span>
                        <input class="uk-search-input" type="search" placeholder="Suche...">
                    </form>

                    {{-- Back to Admin --}}
                    @if(session('impersonate') && Auth::guard('kas_client')->check())
                        <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST" class="uk-display-inline">
                            @csrf
                            <button type="submit" class="uk-button uk-button-danger uk-button-small uk-margin-small-right">
                                ← Zurück zum Admin-Panel
                            </button>
                        </form>
                    @endif

                    {{-- Logout --}}
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="uk-button uk-button-text">Abmelden</button>
                    </form>
                </div>

                {{-- Burger toggle for mobile --}}
                <a class="uk-navbar-toggle uk-hidden@m" href="#offcanvas-nav" uk-toggle>
                    <span uk-navbar-toggle-icon></span>
                </a>
            </div>
        </nav>
    </div>
</header>

{{-- Offcanvas for mobile --}}
<div id="offcanvas-nav" uk-offcanvas="overlay: true">
    <div class="uk-offcanvas-bar">
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
                <li class="uk-nav-divider"></li>
                <li><a href="{{ route('config.index') }}"><span uk-icon="cog"></span> Einstellungen</a></li>
            @endif

            @if(session('impersonate') && Auth::guard('kas_client')->check())
                <li class="uk-nav-divider"></li>
                <li>
                    <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST">
                        @csrf
                        <button type="submit" class="uk-button uk-button-danger uk-button-small uk-width-1-1">
                            ← Zurück zum Admin-Panel
                        </button>
                    </form>
                </li>
            @endif

            <li class="uk-nav-divider"></li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="uk-button uk-button-text uk-text-danger">Abmelden</button>
                </form>
            </li>
        </ul>
    </div>
</div>

<main class="uk-section uk-section-default">
    <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
        <div class="uk-grid-large" uk-grid>
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
                        <li class="uk-nav-divider"></li>
                        <li><a href="{{ route('config.index') }}"><span uk-icon="cog"></span> Einstellungen</a></li>
                    @endif
                </ul>
            </aside>

            <section id="content" class="uk-width-expand uk-padding-remove-left">
                @yield('content')
            </section>

            <aside class="uk-width-1-6@m uk-visible@m uk-border-left uk-padding-small">
                <h4 class="uk-heading-line"><span>Hinweise</span></h4>
                <p>Hier könnten Tipps oder Logs erscheinen.</p>
            </aside>
        </div>
    </div>
</main>

<footer class="uk-background-muted uk-padding-small uk-text-center" style="padding-left:45px; padding-right:45px;">
    <p>© 2025 R3D Internet Dienstleistungen · <a href="#">Impressum</a> · <a href="#">Datenschutz</a></p>
</footer>

</body>
</html>
