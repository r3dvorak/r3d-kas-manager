@php
    $sessionLocale = session('locale');
    $clientLocale = Auth::guard('kas_client')->check()
        ? (string) (Auth::guard('kas_client')->user()?->preferred_locale ?? '')
        : '';
    $effectiveLocale = in_array($sessionLocale, ['de', 'en'], true)
        ? $sessionLocale
        : (in_array($clientLocale, ['de', 'en'], true) ? $clientLocale : app()->getLocale());
    app()->setLocale($effectiveLocale);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    @php
        $isAdmin  = Auth::guard('web')->check();
        $isClient = Auth::guard('kas_client')->check();
        $clientUser = $isClient ? Auth::guard('kas_client')->user() : null;
        $mode     = $isAdmin ? __('ui.role.admin') : ($isClient ? __('ui.role.client') : '');
        $isLoginPage = request()->routeIs('login');
        $activeClass = static fn (array $patterns): string => request()->routeIs(...$patterns) ? 'nav-link-active' : '';
        $clientMenuEnabled = static fn (string $key): bool => $clientUser?->hasClientMenuItem($key) ?? false;
        $locale = app()->getLocale();
    @endphp
    <title>{{ $mode ? $mode . ' | ' : '' }}RIIID KAS Manager</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/css/uikit.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.25.11/dist/js/uikit-icons.min.js"></script>
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
                @if(!$isLoginPage && $isAdmin)
                    <span class="uk-label uk-label-danger uk-margin-small-left">ADMIN</span>
                @elseif(!$isLoginPage && $isClient)
                    <span class="uk-label uk-label-success uk-margin-small-left">KAS Client</span>
                @endif
            </div>

            <div class="uk-navbar-right">
                {{-- Desktop search --}}
                @if(!$isLoginPage)
                <div class="uk-visible@m">
                    <form class="uk-search uk-search-default uk-margin-right uk-margin-large-right" style="font-size: 0.85rem;">
                        <span uk-search-icon></span>
                        <input class="uk-search-input" type="search" placeholder="{{ __('ui.common.search') }}...">
                    </form>

                    {{-- Back to Admin --}}
                    @if(session('impersonate') && Auth::guard('kas_client')->check())
                        <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST" class="uk-display-inline">
                            @csrf
                            <button type="submit" class="uk-button uk-button-danger uk-button-small uk-margin-small-right">
                                ← {{ __('ui.common.back_to_admin') }}
                            </button>
                        </form>
                    @endif

                    {{-- Logout --}}
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="uk-button uk-button-text">{{ __('ui.common.logout') }}</button>
                    </form>
                </div>
                @endif

                {{-- Burger toggle for mobile --}}
                @if(!$isLoginPage)
                <a class="uk-navbar-toggle uk-hidden@m" href="#offcanvas-nav" uk-toggle>
                    <span uk-navbar-toggle-icon></span>
                </a>
                @endif
            </div>
        </nav>
    </div>
</header>

{{-- Offcanvas for mobile --}}
@if(!$isLoginPage)
<div id="offcanvas-nav" uk-offcanvas="overlay: true">
            <div class="uk-offcanvas-bar">
                <ul class="uk-nav uk-nav-default">
                    @if(Auth::guard('kas_client')->check())
                        @if($clientMenuEnabled('dashboard'))<li><a href="{{ route('client.dashboard') }}" class="{{ $activeClass(['client.dashboard']) }}"><span uk-icon="home" class="uk-margin-small-right"></span>{{ __('ui.nav.dashboard') }}</a></li>@endif
                        @if($clientMenuEnabled('domain'))<li><a href="{{ route('client.domains.index') }}" class="{{ $activeClass(['client.domains.*']) }}"><span uk-icon="world" class="uk-margin-small-right"></span>{{ __('ui.nav.domain') }}</a></li>@endif
                        @if($clientMenuEnabled('subdomain'))<li><a href="{{ route('client.subdomains.index') }}" class="{{ $activeClass(['client.subdomains.*']) }}"><span uk-icon="grid" class="uk-margin-small-right"></span>{{ __('ui.nav.subdomain') }}</a></li>@endif
                        @if($clientMenuEnabled('mailboxes'))<li><a href="{{ route('client.mailboxes.index') }}" class="{{ $activeClass(['client.mailboxes.*']) }}"><span uk-icon="mail" class="uk-margin-small-right"></span>{{ __('ui.nav.mailboxes') }}</a></li>@endif
                        @if($clientMenuEnabled('mailforwards'))<li><a href="{{ route('client.mailforwards.index') }}" class="{{ $activeClass(['client.mailforwards.*']) }}"><span uk-icon="reply" class="uk-margin-small-right"></span>{{ __('ui.nav.mailforwards') }}</a></li>@endif
                        @if($clientMenuEnabled('ftp'))<li><a href="{{ route('client.ftp.index') }}" class="{{ $activeClass(['client.ftp.*']) }}"><span uk-icon="folder" class="uk-margin-small-right"></span>{{ __('ui.nav.ftp') }}</a></li>@endif
                        @if($clientMenuEnabled('databases'))<li><a href="{{ route('client.databases.index') }}" class="{{ $activeClass(['client.databases.*']) }}"><span uk-icon="database" class="uk-margin-small-right"></span>{{ __('ui.nav.databases') }}</a></li>@endif
                        @if($clientMenuEnabled('dns'))<li><a href="{{ route('client.dns.index') }}" class="{{ $activeClass(['client.dns.*']) }}"><span uk-icon="settings" class="uk-margin-small-right"></span>{{ __('ui.nav.dns') }}</a></li>@endif
                        @if($clientMenuEnabled('ssl'))<li><a href="{{ route('client.ssl.index') }}" class="{{ $activeClass(['client.ssl.*']) }}"><span uk-icon="lock" class="uk-margin-small-right"></span>{{ __('ui.nav.ssl') }}</a></li>@endif
                        @if($clientMenuEnabled('statistics'))<li><a href="{{ route('client.statistics.index') }}" class="{{ $activeClass(['client.statistics.*']) }}"><span uk-icon="gitter" class="uk-margin-small-right"></span>{{ __('ui.nav.statistics') }}</a></li>@endif
                        @if($clientMenuEnabled('recipes'))<li><a href="{{ route('client.recipes.index') }}" class="{{ $activeClass(['client.recipes.*']) }}"><span uk-icon="nut" class="uk-margin-small-right"></span>{{ __('ui.nav.recipes') }}</a></li>@endif
                    @elseif(Auth::guard('web')->check())
                        <li><a href="{{ route('dashboard') }}" class="{{ $activeClass(['dashboard']) }}"><span uk-icon="home" class="uk-margin-small-right"></span>{{ __('ui.nav.startpage') }}</a></li>
                        <li><a href="{{ route('kas-clients.index') }}" class="{{ $activeClass(['kas-clients.*']) }}"><span uk-icon="thumbnails" class="uk-margin-small-right"></span>{{ __('ui.nav.accounts') }}</a></li>
                        <li><a href="{{ route('admin.mailboxes.index') }}" class="{{ $activeClass(['admin.mailboxes.*']) }}"><span uk-icon="mail" class="uk-margin-small-right"></span>{{ __('ui.nav.mailboxes') }}</a></li>
                        <li><a href="{{ route('admin.mailforwards.index') }}" class="{{ $activeClass(['admin.mailforwards.*']) }}"><span uk-icon="reply" class="uk-margin-small-right"></span>{{ __('ui.nav.mailforwards') }}</a></li>
                        <li><a href="{{ route('docs') }}" class="{{ $activeClass(['docs']) }}"><span uk-icon="file-text" class="uk-margin-small-right"></span>{{ __('ui.nav.docs') }}</a></li>
                        <li><a href="{{ route('stats') }}" class="{{ $activeClass(['stats']) }}"><span uk-icon="gitter" class="uk-margin-small-right"></span>{{ __('ui.nav.stats') }}</a></li>
                        <li><a href="{{ route('users.index') }}" class="{{ $activeClass(['users.*']) }}"><span uk-icon="users" class="uk-margin-small-right"></span>{{ __('ui.nav.user_management') }}</a></li>
            @endif
            <li class="uk-nav-divider"></li>
            <li class="uk-nav-header">{{ __('ui.common.language') }}</li>
            <li><a href="{{ route('locale.switch', 'de') }}" class="{{ $locale === 'de' ? 'nav-link-active' : '' }}"><span uk-icon="world" class="uk-margin-small-right"></span>Deutsch</a></li>
            <li><a href="{{ route('locale.switch', 'en') }}" class="{{ $locale === 'en' ? 'nav-link-active' : '' }}"><span uk-icon="world" class="uk-margin-small-right"></span>English</a></li>
            @if(Auth::guard('web')->check())
                <li><a href="{{ route('config.index') }}" class="{{ $activeClass(['config.*']) }}"><span uk-icon="settings" class="uk-margin-small-right"></span>{{ __('ui.nav.settings') }}</a></li>
            @endif

            @if(session('impersonate') && Auth::guard('kas_client')->check())
                <li class="uk-nav-divider"></li>
                <li>
                    <form action="{{ route('kas-clients.impersonate.leave') }}" method="POST">
                        @csrf
                        <button type="submit" class="uk-button uk-button-danger uk-button-small uk-width-1-1">
                            ← {{ __('ui.common.back_to_admin') }}
                        </button>
                    </form>
                </li>
            @endif

            <li class="uk-nav-divider"></li>
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="uk-button uk-button-text uk-text-danger">{{ __('ui.common.logout') }}</button>
                </form>
            </li>
        </ul>
    </div>
</div>
@endif

<main class="uk-section uk-section-default">
    <div class="uk-container uk-container-expand" style="padding-left:45px; padding-right:45px;">
        @if($isLoginPage)
        <section id="content">
            @yield('content')
        </section>
        @else
        <div class="uk-grid-large" uk-grid>
            <aside class="uk-width-1-6@m uk-visible@m uk-border-right">
                <ul class="uk-nav uk-nav-default">
                    @if(Auth::guard('kas_client')->check())
                        @if($clientMenuEnabled('dashboard'))<li><a href="{{ route('client.dashboard') }}" class="{{ $activeClass(['client.dashboard']) }}"><span uk-icon="home" class="uk-margin-small-right"></span>{{ __('ui.nav.dashboard') }}</a></li>@endif
                        @if($clientMenuEnabled('domain'))<li><a href="{{ route('client.domains.index') }}" class="{{ $activeClass(['client.domains.*']) }}"><span uk-icon="world" class="uk-margin-small-right"></span>{{ __('ui.nav.domain') }}</a></li>@endif
                        @if($clientMenuEnabled('subdomain'))<li><a href="{{ route('client.subdomains.index') }}" class="{{ $activeClass(['client.subdomains.*']) }}"><span uk-icon="grid" class="uk-margin-small-right"></span>{{ __('ui.nav.subdomain') }}</a></li>@endif
                        @if($clientMenuEnabled('mailboxes'))<li><a href="{{ route('client.mailboxes.index') }}" class="{{ $activeClass(['client.mailboxes.*']) }}"><span uk-icon="mail" class="uk-margin-small-right"></span>{{ __('ui.nav.mailboxes') }}</a></li>@endif
                        @if($clientMenuEnabled('mailforwards'))<li><a href="{{ route('client.mailforwards.index') }}" class="{{ $activeClass(['client.mailforwards.*']) }}"><span uk-icon="reply" class="uk-margin-small-right"></span>{{ __('ui.nav.mailforwards') }}</a></li>@endif
                        @if($clientMenuEnabled('ftp'))<li><a href="{{ route('client.ftp.index') }}" class="{{ $activeClass(['client.ftp.*']) }}"><span uk-icon="folder" class="uk-margin-small-right"></span>{{ __('ui.nav.ftp') }}</a></li>@endif
                        @if($clientMenuEnabled('databases'))<li><a href="{{ route('client.databases.index') }}" class="{{ $activeClass(['client.databases.*']) }}"><span uk-icon="database" class="uk-margin-small-right"></span>{{ __('ui.nav.databases') }}</a></li>@endif
                        @if($clientMenuEnabled('dns'))<li><a href="{{ route('client.dns.index') }}" class="{{ $activeClass(['client.dns.*']) }}"><span uk-icon="settings" class="uk-margin-small-right"></span>{{ __('ui.nav.dns') }}</a></li>@endif
                        @if($clientMenuEnabled('ssl'))<li><a href="{{ route('client.ssl.index') }}" class="{{ $activeClass(['client.ssl.*']) }}"><span uk-icon="lock" class="uk-margin-small-right"></span>{{ __('ui.nav.ssl') }}</a></li>@endif
                        @if($clientMenuEnabled('statistics'))<li><a href="{{ route('client.statistics.index') }}" class="{{ $activeClass(['client.statistics.*']) }}"><span uk-icon="gitter" class="uk-margin-small-right"></span>{{ __('ui.nav.statistics') }}</a></li>@endif
                        @if($clientMenuEnabled('recipes'))<li><a href="{{ route('client.recipes.index') }}" class="{{ $activeClass(['client.recipes.*']) }}"><span uk-icon="nut" class="uk-margin-small-right"></span>{{ __('ui.nav.recipes') }}</a></li>@endif
                    @elseif(Auth::guard('web')->check())
                        <li><a href="{{ route('dashboard') }}" class="{{ $activeClass(['dashboard']) }}"><span uk-icon="home" class="uk-margin-small-right"></span>{{ __('ui.nav.startpage') }}</a></li>
                        <li><a href="{{ route('kas-clients.index') }}" class="{{ $activeClass(['kas-clients.*']) }}"><span uk-icon="thumbnails" class="uk-margin-small-right"></span>{{ __('ui.nav.accounts') }}</a></li>
                        <li><a href="{{ route('admin.mailboxes.index') }}" class="{{ $activeClass(['admin.mailboxes.*']) }}"><span uk-icon="mail" class="uk-margin-small-right"></span>{{ __('ui.nav.mailboxes') }}</a></li>
                        <li><a href="{{ route('admin.mailforwards.index') }}" class="{{ $activeClass(['admin.mailforwards.*']) }}"><span uk-icon="reply" class="uk-margin-small-right"></span>{{ __('ui.nav.mailforwards') }}</a></li>
                        <li><a href="{{ route('docs') }}" class="{{ $activeClass(['docs']) }}"><span uk-icon="file-text" class="uk-margin-small-right"></span>{{ __('ui.nav.docs') }}</a></li>
                        <li><a href="{{ route('stats') }}" class="{{ $activeClass(['stats']) }}"><span uk-icon="gitter" class="uk-margin-small-right"></span>{{ __('ui.nav.stats') }}</a></li>
                        <li><a href="{{ route('users.index') }}" class="{{ $activeClass(['users.*']) }}"><span uk-icon="users" class="uk-margin-small-right"></span>{{ __('ui.nav.user_management') }}</a></li>
                    @endif
                    <li class="uk-nav-divider"></li>
                    <li class="uk-nav-header">{{ __('ui.common.language') }}</li>
                    <li><a href="{{ route('locale.switch', 'de') }}" class="{{ $locale === 'de' ? 'nav-link-active' : '' }}"><span uk-icon="world" class="uk-margin-small-right"></span>Deutsch</a></li>
                    <li><a href="{{ route('locale.switch', 'en') }}" class="{{ $locale === 'en' ? 'nav-link-active' : '' }}"><span uk-icon="world" class="uk-margin-small-right"></span>English</a></li>
                    @if(Auth::guard('web')->check())
                        <li><a href="{{ route('config.index') }}" class="{{ $activeClass(['config.*']) }}"><span uk-icon="settings" class="uk-margin-small-right"></span>{{ __('ui.nav.settings') }}</a></li>
                    @endif
                </ul>
            </aside>

            <section id="content" class="uk-width-expand uk-padding-remove-left">
                @yield('content')
            </section>

            <aside class="uk-width-1-6@m uk-visible@m uk-border-left uk-padding-small">
                <h4 class="uk-heading-line"><span>{{ __('ui.hints.title') }}</span></h4>
                @hasSection('hints')
                    @yield('hints')
                @else
                    @include('partials.hints')
                @endif
            </aside>
        </div>
        @endif
    </div>
</main>

<footer class="uk-background-muted uk-padding-small uk-text-center" style="padding-left:45px; padding-right:45px;">
    <p>© 2025 R3D Internet Dienstleistungen · <a href="#">{{ __('ui.footer.imprint') }}</a> · <a href="#">{{ __('ui.footer.privacy') }}</a></p>
</footer>

<script>
    window.confirmDeleteTwice = function (message, keyword) {
        if (!window.confirm(message)) {
            return false;
        }

        var required = keyword || 'LOESCHEN';
        var entered = window.prompt('Bitte zur Bestaetigung "' + required + '" eingeben:');
        return entered === required;
    };
</script>

</body>
</html>
