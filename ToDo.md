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

## Nächste ToDos (Rezepte / Automationen - neu geplant, Admin First)

### Zielbild & Leitlinien
- [ ] Recipes als Operations-Container definieren (nicht als statische Datenansicht).
- [ ] Klares Scope-Modell je Recipe festlegen: `client`, `domain`, `subdomain`, `mail`, `dns`, `composite`.
- [ ] Prinzip festschreiben: Änderungen laufen über KAS-API-Calls + Run-Logs, keine stillen Direktmanipulationen.
- [ ] Dry-Run vor Apply als Standardfluss definieren.

### Rollenmodell (Admin vs Client)
- [ ] Rechte-Matrix final festlegen.
- [ ] Admin: Recipe-CRUD, Import/Export, globale Templates, Massen-Ausführung.
- [ ] Client: nur freigegebene Templates, nur auf eigenen Ressourcen, nur freigegebene Parameter.
- [ ] Kritische Aktionen explizit Admin-only: Account-Neuanlage, Limits/Quotas, SSL-Policy, DB/FTP-Massenanlage.

### Architektur (modular & auditierbar)
- [ ] Bestehende Struktur bestätigen und dokumentieren: `recipes`, `recipe_actions`, `recipe_runs`, `recipe_action_history`.
- [ ] Dispatcher-Contract finalisieren: Action-Handler pro Typ (`SetPhpVersion`, `EnableSsl`, `ForceHttps`, `DnsUpsert`, ...).
- [ ] Idempotenz-Strategie je Step einführen (`dedupe_key` / `external_ref`).
- [ ] Rollback als Compensating Actions modellieren (nicht DB-Transaktionsrollback über externe APIs).
- [ ] Drift-Check vor Apply ergänzen (aktueller KAS-Status vs geplanter Zustand).

### Phase A - Admin Recipe Core (MVP)
- [ ] Admin-UI für Recipes: Liste, Detail, Version, Status (`draft`, `active`, `archived`).
- [ ] Action-Builder mit Reihenfolge, Parametern und Validierung.
- [ ] Manual Run mit Dry-Run, Preview-Diff, Apply.
- [ ] Run-Statusmodell final: `pending`, `running`, `success`, `partial`, `failed`, `cancelled`.
- [ ] Run-Detailansicht: Step-Ergebnisse inkl. Fehler, Dauer, API-Responses (maskiert).

### Phase B - Admin Onboarding Wizard
- [ ] Wizard "Neues Konto / neue Umgebung bereitstellen" (mehrstufig).
- [ ] Eingaben: Hauptdomain, zusätzliche Domains, SSL ja/nein, PHP-Default, Anzahl Mailboxen, Anzahl Weiterleitungen, Anzahl Datenbanken.
- [ ] Eingabe für Mailbox-/Weiterleitungs-Präfixe (`vor dem @`) integrieren.
- [ ] Am Ende generierte Artefakte/Zugangsdaten als Ergebnisbundle anzeigen.
- [ ] Ergebnis nur einmal im Klartext sichtbar + optional sicherer Export.
- [ ] Wizard-Lauf optional als Recipe speichern (wiederverwendbar).

### Phase C - Defaults, Policies, Presets
- [ ] Admin-Einstellungen für globale Defaults bauen (PHP, SSL, DNS-Basis, Quotas, DB-Defaults).
- [ ] Passwort-Policy konfigurierbar machen (Länge, Komplexität, Sonderzeichen, Rotation).
- [ ] Pro Client Overrides zulassen (Fallback auf globale Defaults).
- [ ] Presets "all-inkl kompatibel" definieren und auswählbar machen.
- [ ] Policy-Validierung vor jedem Run erzwingen.

### Phase D - Import / Export
- [ ] `recipe-package.json` Schema definieren (`schema_version`, `meta`, `scope`, `actions`, `defaults`, `permissions`).
- [ ] JSON-Export/Import für Rezepte inkl. Versionsmetadaten, Prüfsumme, Kompatibilitätsprüfung.
- [ ] Konfliktstrategie beim Import umsetzen (`create_new`, `replace`, `skip_existing`).
- [ ] CSV-Import für Massenoperationen umsetzen:
- [ ] DNS (`dns.upsert`)
- [ ] Mailboxen (`mailbox.create` / `mailbox.update`)
- [ ] Weiterleitungen (`mailforward.create`)
- [ ] Domains/Subdomains (`domain.create`, `subdomain.create`)
- [ ] Import-Preview mit Zeilenvalidierung und Fehlerbericht bereitstellen.

### Phase E - Sicherheit, Secrets, Governance
- [ ] Geheimnisse sicher behandeln: keine Klartext-Passwörter in Logs/Audits.
- [ ] Einmalige Secret-Ausgabe mit expliziter Warnung und Ablauf.
- [ ] Optional verschlüsselter Secret-Export (`.json.enc`).
- [ ] Rate-Limits, Queue-Limits und Schutz gegen gefährliche Massenaktionen einführen.
- [ ] Optionales 4-Augen-Prinzip für kritische Recipes vorbereiten.

### Phase F - Betrieb & Qualität
- [ ] Asynchrone Runs über Queue mit Fortschritt pro Run/Step/Item.
- [ ] `retry failed only` und Wiederanlauf-Strategie implementieren.
- [ ] Downloadbare Run-Reports (`csv/json`) und Filter nach Fehlerklassen.
- [ ] Testpaket für Recipes aufbauen:
- [ ] Unit-Tests für Handler/Dispatcher
- [ ] Feature-Tests für Admin/Client-Berechtigungen
- [ ] E2E für Wizard + Import/Export + Dry-Run/Apply

### Beispiel-Recipes (Referenz für MVP)
- [ ] `Standard Joomla Setup`: `set_php_version`, `enable_ssl`, `force_https`, `set_document_root`, `disable_directory_listing`.
- [ ] `Migration auf PHP 8.3`: `check_php_version`, `set_php_version`, optional `clear_cache`.
- [ ] `Emergency SSL Fix`: `enable_sni`, `activate_certificate`, `force_https`.
