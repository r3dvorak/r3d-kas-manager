# AGENTS.md

Projektregeln für Codex im Repository `r3d-kas-manager`.

## Verbindliche Regeln

1. Uptick-Regel
- Standard: Immer Uptick, außer der User schreibt explizit "kein uptick".
- Version nur in der tatsächlich geänderten Datei erhöhen (wenn die Datei einen `@version`-Header hat).
- `@date` im Header ist Erstellungsdatum und wird bei späteren Änderungen nicht aktualisiert.
- Zusätzlich bei Versionsänderung immer ein neues Repo-Tag mit derselben Version erstellen (z. B. `v0.27.2-alpha`).

2. Immer Commit-Message liefern
- Nach jeder umgesetzten Änderung immer eine konkrete Commit-Message ausgeben.
- Pflichtformat:
  - Start mit der neuen Version, z. B. `v0.27.2-alpha`
  - Danach eine kurze One-Liner-Zusammenfassung aller erledigten Schritte.

3. Git-Automation
- Standard: Codex führt Commit und Push selbst aus.
- Nach dem Push wird der zugehörige Versionstag ebenfalls gepusht.
- Auf GitHub wird unter "Releases" ein Release für den Tag erstellt/aktualisiert.
- Ausnahme nur, wenn der User explizit etwas anderes vorgibt (z. B. "kein uptick", "nicht pushen").

## Arbeitsstil

- Änderungen direkt umsetzen, nicht nur beschreiben.
- Bestehende Architektur und UI-Linien beibehalten.
- Bei Fehlerbehebungen zuerst reproduzierbare Ursache nennen, dann Fix.
