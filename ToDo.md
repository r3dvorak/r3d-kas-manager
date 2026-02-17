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

## Umsetzungsphasen

### Phase 0 - Konzept fixieren
- [ ] URL-Strategie final: `/?w=<key>` oder `/w/{key}/...`
- [ ] Workspace-Key-Format/Laenge festlegen (random, opaque)
- [ ] Security-Regeln finalisieren (TTL, invalidation, audit)
- [ ] Fehlerstrategie definieren (ungueltiger/abgelaufener Workspace)

### Phase 1 - Fundament Workspace
- [ ] WorkspaceResolver-Middleware anlegen
- [ ] Workspace-Kontext in Request/Container bereitstellen
- [ ] Fallback bei fehlendem `w` implementieren
- [ ] Unit-/Feature-Tests fuer Resolver

### Phase 2 - Session-Isolation pro Workspace
- [ ] Dynamische Session-Cookie-Namen pro Guard + Workspace
- [ ] `web` und `kas_client` sauber trennen
- [ ] Logout-Semantik festlegen:
- [ ] nur aktueller Workspace
- [ ] optional: alle Workspaces
- [ ] Feature-Tests fuer parallele Sessions

### Phase 3 - Routing und Link-Propagation
- [ ] Workspace in Hauptnavigation verankern
- [ ] Helper fuer workspace-sichere Links bauen (`route_w(...)`)
- [ ] Offcanvas/Sidebar/Actions auf Helper umstellen
- [ ] Redirects behalten/setzen den Workspace
- [ ] Regressionstest auf verlorenen Kontext

### Phase 4 - Login/Impersonation UX
- [ ] Login erzeugt bei Bedarf neuen Workspace
- [ ] Admin-Login und Client-Login parallel im selben Browserprofil pruefen
- [ ] Impersonate erzeugt neuen Workspace + neuer Tab
- [ ] Ruecksprung aus Impersonation korrekt pro Workspace

### Phase 5 - Externe Launches (Webmail/PMA etc.)
- [ ] LaunchToken-Service (one-time, short TTL)
- [ ] Token-Speicher + Ablauf/Invalidation
- [ ] Webmail-/PMA-Links ueber Token-Flow
- [ ] Audit-Log fuer externe Launches

### Phase 6 - Härtung
- [ ] Rate-Limits und Abuse-Schutz
- [ ] Security-Review (CSRF, fixation, replay)
- [ ] Bereinigung alter Workspaces per Job
- [ ] Observability (Logs/Debug-Infos)

### Phase 7 - Migration & Backward Compatibility
- [ ] Legacy-Links ohne `w` behandeln
- [ ] Soft-Rollout mit Feature-Flag
- [ ] Rueckfallstrategie dokumentieren
- [ ] Datenbankmigrationen finalisieren

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
- [ ] Query-Parameter vs. Path-basiert fuer Workspace
- [ ] Workspace-Lebensdauer (Session-only vs. persistiert)
- [ ] Anzahl paralleler Workspaces pro User begrenzen?
- [ ] UI-Hinweis anzeigen, welcher Workspace aktiv ist?

## Notizen
- Kein Framework-Port notwendig; Umsetzung ist in Laravel moeglich.
- Symfony haette dieselben Cookie-Grundlagen, daher kein Vorteil fuer diesen Punkt.
