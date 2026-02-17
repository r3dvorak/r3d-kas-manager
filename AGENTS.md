# AGENTS.md

Projektregeln für Codex im Repository `r3d-kas-manager`.

## Verbindliche Regeln

1. Uptick-Regel
- Version nur in der tatsächlich geänderten Datei erhöhen (wenn die Datei einen `@version`-Header hat).
- `@date` im Header ist Erstellungsdatum und wird bei späteren Änderungen nicht aktualisiert.
- Zusätzlich bei Versionsänderung immer ein neues Repo-Tag mit derselben Version erstellen (z. B. `v0.27.2-alpha`).

2. Immer Commit-Message liefern
- Nach jeder umgesetzten Änderung immer eine konkrete Commit-Message ausgeben.
- Pflichtformat:
  - Start mit der neuen Version, z. B. `v0.27.2-alpha`
  - Danach eine kurze One-Liner-Zusammenfassung aller erledigten Schritte.

## Arbeitsstil

- Änderungen direkt umsetzen, nicht nur beschreiben.
- Bestehende Architektur und UI-Linien beibehalten.
- Bei Fehlerbehebungen zuerst reproduzierbare Ursache nennen, dann Fix.
