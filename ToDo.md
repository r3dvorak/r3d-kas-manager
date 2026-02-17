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
- [x] E2E-Checkliste komplett gruen
- [x] Manuelle Browsertests (Firefox/Chrome, normal + privat)
- [x] Dokumentation aktualisieren (`README.md`)
- [x] Release Notes + Betriebsanleitung

## Test-Checkliste (Abnahme)
- [x] Tab A als Admin angemeldet, Tab B bleibt neutraler Login bis Login erfolgt.
- [x] Tab B als Client B, Tab C als Client C, beide zeigen unterschiedliche Daten stabil.
- [x] Wechsel in Tab B darf Tab C nicht beeinflussen.
- [x] Admin in Tab A bleibt Admin, wenn in anderem Tab Client-Login stattfindet.
- [x] Impersonation oeffnet neuen Tab mit isoliertem Kontext.
- [x] Logout in einem Tab beendet nicht alle anderen Tabs (nur aktueller Workspace).
- [x] Spracheinstellung bleibt pro Workspace konsistent.

## Offene Entscheidungen
- [x] Query-Parameter vs. Path-basiert fuer Workspace
- [x] Workspace-Lebensdauer (Session-only vs. persistiert)
- [x] Anzahl paralleler Workspaces pro User begrenzen?
- [x] UI-Hinweis anzeigen, welcher Workspace aktiv ist?

## Notizen
- Kein Framework-Port notwendig; Umsetzung ist in Laravel moeglich.
- Symfony haette dieselben Cookie-Grundlagen, daher kein Vorteil fuer diesen Punkt.

## Nächste ToDos (UI / Inhalt)

### Client Dashboard
- [x] Aktuelle Server-IP wird angezeigt (nicht leer).
- [x] Servername wird angezeigt (nicht leer).
- [x] Button `Abmelden` unter dem Inhalt entfernen (Logout bleibt in der Navbar).

### Ressourcen-Bereiche
- [x] Feld `Reserviert` korrekt berechnen (nicht immer `0`).
- [x] Buttons `Änderungen Prüfung (KAS vs DB)` und `Sync jetzt` in Ressourcen-Ansichten ergänzen.

### CRUD-Aktionen in Client-Listen
- [x] `NEU`-Button ergänzen bei Domains.
- [x] `NEU`-Button ergänzen bei Subdomains.
- [x] `NEU`-Button ergänzen bei Mailforwards.
- [x] `NEU`-Button ergänzen bei FTP.
- [x] `NEU`-Button ergänzen bei Datenbanken.

### Internationalisierung / Sprache
- [x] Alle UI-Texte in Sprach-Keys auslagern (keine hartkodierten Texte mehr).
- [x] Vollständige DE- und EN-Übersetzungen pflegen.
- [x] Gemischte Sprache in Views bereinigen (DE/EN konsistent pro Locale).
- [x] Deutsche Texte mit korrekten Umlauten/Sonderzeichen schreiben (z. B. `Änderungen Prüfung`).

## Nächste ToDos (Rezepte / Automationen - Admin First)

### Rollenmodell (klar getrennt)
- [ ] Rechte-Matrix final festlegen: `Admin` (voll), `Client` (eingeschränkt).
- [ ] Admin darf Rezepte erstellen, importieren, exportieren, ausführen und global speichern.
- [ ] Client darf nur freigegebene Rezepte sehen/ausführen (kein globales Erstellen/Löschen).
- [ ] Kritische Aktionen nur Admin: Account-Neuanlage, Ressourcen-Limits, SSL-Policy, DB-/FTP-Massenanlage.

### Admin Recipe Phase 1 (Start)
- [ ] Admin-Recipe-CRUD als zentrale Oberfläche aufbauen (Liste, Detail, Version, Status).
- [ ] Step-basierte Definition mit Reihenfolge und Validierung bereitstellen.
- [ ] Dry-Run/Preview (Diff) vor Ausführung verpflichtend machen.
- [ ] Run-Historie mit Ergebnis pro Schritt anzeigen.

### Admin-Onboarding-Wizard (neues Konto / neue Umgebung)
- [ ] Wizard für "neues Konto bereitstellen" erstellen (mehrstufig).
- [ ] Eingaben: Hauptdomain, weitere Domains, SSL ja/nein, PHP-Default, Anzahl Mailboxen, Anzahl Weiterleitungen, Anzahl Datenbanken.
- [ ] Für Mailboxen/Weiterleitungen Eingabe-Schema für Prefix (`vor dem @`) unterstützen.
- [ ] Ergebnisseite mit allen erzeugten Zugangsdaten (nur einmal sichtbar + Download) bereitstellen.
- [ ] Optional: sofortige Ausführung oder als Recipe-Entwurf speichern.

### Defaults & Policy-Engine
- [ ] Globale Default-Parameter in Admin-Einstellungen pflegbar machen.
- [ ] Passwort-Defaults (Länge, Zeichensatz, Sonderzeichenpflicht, Rotation) definieren.
- [ ] Default-Werte für PHP-Version, SSL-Standard, DNS-Basis-Records, Mailbox-Quota definieren.
- [ ] Pro Client überschreibbare Defaults unterstützen (Fallback auf global).
- [ ] "all-inkl kompatible" sinnvolle Standardprofile als Presets hinterlegen.

### Geheimnisse / Zugangsdaten
- [ ] Generierte Passwörter nur verschlüsselt speichern oder als einmaliges Secret ausgeben.
- [ ] Klartext-Ausgabe nur im Run-Result direkt nach Erstellung + expliziter Hinweis.
- [ ] Secret-Export optional als verschlüsselte Datei (`.json.enc`) bereitstellen.
- [ ] Audit-Log ohne Klartext-Passwörter sicherstellen.

### Import / Export (Admin)
- [ ] `recipe-package.json` Schema finalisieren (`schema_version`, `meta`, `steps`, `defaults`, `permissions`).
- [ ] JSON-Import mit Validierung und Konfliktstrategie (`create_new`, `replace`, `skip_existing`).
- [ ] CSV-Import für Massenaufgaben: DNS, Mailforwards, Mailboxen, Domains.
- [ ] CSV-Preview mit Zeilenfehlern und Korrekturhinweisen.
- [ ] Export von Rezepten inkl. Versionsmetadaten und Prüfsumme.

### Client Recipes (eingeschränkt)
- [ ] Client sieht nur vom Admin freigegebene Recipe-Templates.
- [ ] Client darf nur erlaubte Parameter setzen (Whitelisting).
- [ ] Client darf nur auf eigene Ressourcen ausführen (harte Tenant-Prüfung).
- [ ] Keine Ausgabe sensitiver Zugangsdaten aus Admin-only Aktionen im Client-Kontext.

### Sicherheit / Governance
- [ ] Run-Freigaben für kritische Rezepte (4-Augen optional) vorbereiten.
- [ ] Rate-Limits und Queue-Limits für große Massenjobs setzen.
- [ ] Idempotenz pro Step (`external_ref` / `dedupe_key`) erzwingen.
- [ ] Vollständiges Audit: wer, wann, welche Parameter, welche Änderungen.

### UX / Betrieb
- [ ] Fortschrittsanzeige pro Run (gesamt + pro Step + pro Item).
- [ ] Wiederanlauf fehlgeschlagener Items (`retry failed only`).
- [ ] Downloadbare Run-Reports (`csv/json`) mit Fehlergruppen.
- [ ] Wizard- und Import-Tests als feste Abnahme-Checkliste ergänzen.
