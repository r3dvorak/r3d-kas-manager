# ToDo - Workspace-basierte Session-Trennung

## Zielbild
- Mehrere parallele Sessions im gleichen Browserprofil ermöglichen:
- Tab A: Admin
- Tab B: Client B
- Tab C: Client C
- Kein gegenseitiges Ueberschreiben von Session/Ansicht/Rechten.

## Hintergrund (Kurzfassung)
- Standard-Cookies sind pro Domain geteilt und damit tab-uebergreifend.
- Einfache Guard-Trennung reicht fuer `Admin + 1 Client`, aber nicht fuer `mehrere Clients parallel`.
- Loesung: Workspace-Kontext pro Tab mit eigener Session-Isolation.

## Architektur-Ideen
- Workspace-Key als Kontext pro Tab (`w`).
- Alle relevanten Routen tragen den Workspace-Kontext.
- Dynamischer Session-Cookie-Name je Workspace und Guard:
- `kas_admin_session_<w>`
- `kas_client_session_<w>`
- Interne Links propagieren den aktuellen Workspace automatisch.
- Impersonation startet in neuem Workspace (neuer Tab).
- Externe Tools (Webmail/PMA) nur ueber kurzlebige Launch-Tokens (kein internes Session-Leak).

## Phase-0 Entscheidungen (fix)
- URL-Strategie: Query-Parameter `?w=<key>`
- Workspace-Key: random opaque, `40` hex Zeichen (`160` bit)
- Lebensdauer: Browser-Session + Server-Timeout via `SESSION_LIFETIME`
- Timeout-Vorgabe: `SESSION_LIFETIME=300`
- Session-Cookie: `expire_on_close=true` (kein persistentes Workspace-Cookie)
- Ohne `w`: neuen Workspace erzeugen und auf URL mit `w` redirecten
- Ungueltiger/abgelaufener `w`: neuen Workspace erzeugen + Hinweisbanner
- Logout: beide Varianten
- Standard: nur aktueller Workspace
- Zusatzaktion: alle Workspaces abmelden
- Max. parallele Workspaces: initial unbegrenzt
- Workspace-UI-Hinweis: dezent, nur fuer Admin
- Logging in Startphase: minimal (create/login/logout/impersonate)

## Umsetzungsphasen

### Phase 0 - Konzept fixieren
- [x] URL-Strategie final: Query-Parameter `?w=<key>`
- [x] Workspace-Key-Format/Laenge festgelegt: `40` hex Zeichen (`160` bit, random opaque)
- [x] Security-Regeln finalisiert (TTL, invalidation, audit als Startprofil)
- [x] Fehlerstrategie definiert (ungueltiger/abgelaufener Workspace => neu + Banner)

### Phase 1 - Fundament Workspace
- [x] WorkspaceResolver-Middleware anlegen
- [x] Workspace-Kontext in Request/Container bereitstellen
- [x] Fallback bei fehlendem `w` implementieren
- [x] Unit-/Feature-Tests fuer Resolver

### Phase 2 - Session-Isolation pro Workspace
- [x] Dynamische Session-Cookie-Namen pro Workspace
- [x] `web` und `kas_client` sauber trennen
- [x] Logout-Semantik festlegen:
- [x] nur aktueller Workspace
- [x] optional: alle Workspaces
- [x] Feature-Tests fuer parallele Sessions

### Phase 3 - Routing und Link-Propagation
- [x] Workspace in Hauptnavigation verankern
- [x] Helper fuer workspace-sichere Links bauen (`route_w(...)`)
- [x] Offcanvas/Sidebar/Actions auf Helper umstellen
- [x] Redirects behalten/setzen den Workspace
- [x] Regressionstest auf verlorenen Kontext

### Phase 4 - Login/Impersonation UX
- [x] Login erzeugt bei Bedarf neuen Workspace
- [x] Admin-Login und Client-Login parallel im selben Browserprofil pruefen
- [x] Impersonate erzeugt neuen Workspace + neuer Tab
- [x] Ruecksprung aus Impersonation korrekt pro Workspace

### Phase 5 - Externe Launches (Webmail/PMA etc.)
- [x] LaunchToken-Service (one-time, short TTL)
- [x] Token-Speicher + Ablauf/Invalidation
- [x] Webmail-/PMA-Links ueber Token-Flow
- [x] Audit-Log fuer externe Launches

### Phase 6 - Härtung
- [x] Rate-Limits und Abuse-Schutz
- [x] Security-Review (CSRF, fixation, replay)
- [x] Bereinigung alter Workspaces per Job
- [x] Observability (Logs/Debug-Infos)

### Phase 7 - Migration & Backward Compatibility
- [x] Legacy-Links ohne `w` behandeln
- [x] Soft-Rollout mit Feature-Flag
- [x] Rueckfallstrategie dokumentieren
- [x] Datenbankmigrationen finalisieren

### Phase 8 - Abnahme
- [ ] E2E-Checkliste komplett gruen
- [ ] Manuelle Browsertests (Firefox/Chrome, normal + privat)
- [ ] Dokumentation aktualisieren (`README.md`)
- [ ] Release Notes + Betriebsanleitung

## Test-Checkliste (Abnahme)
- [ ] Tab A als Admin angemeldet, Tab B bleibt neutraler Login bis Login erfolgt.
- [ ] Tab B als Client B, Tab C als Client C, beide zeigen unterschiedliche Daten stabil.
- [ ] Wechsel in Tab B darf Tab C nicht beeinflussen.
- [ ] Admin in Tab A bleibt Admin, wenn in anderem Tab Client-Login stattfindet.
- [ ] Impersonation oeffnet neuen Tab mit isoliertem Kontext.
- [ ] Logout in einem Tab beendet nicht alle anderen Tabs (nur aktueller Workspace).
- [ ] Spracheinstellung bleibt pro Workspace konsistent.

## Offene Entscheidungen
- [x] Query-Parameter vs. Path-basiert fuer Workspace
- [x] Workspace-Lebensdauer (Session-only vs. persistiert)
- [ ] Anzahl paralleler Workspaces pro User begrenzen?
- [x] UI-Hinweis anzeigen, welcher Workspace aktiv ist?

## Notizen
- Kein Framework-Port notwendig; Umsetzung ist in Laravel moeglich.
- Symfony haette dieselben Cookie-Grundlagen, daher kein Vorteil fuer diesen Punkt.
