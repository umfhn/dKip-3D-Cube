# DKIP-ACT-2025-11-11-BC-01 — Bestätigungsbericht & Zielmatrix v0  
DGP 3D Cube 360° (dgp-hube)

Bezug:
- Statusticket: DKIP-STAT-2025-11-11-01
- Templates: [.github/ISSUE_TEMPLATE/dkip-prp-v0-1.md](.github/ISSUE_TEMPLATE/dkip-prp-v0-1.md), [.github/ISSUE_TEMPLATE/dkip-qa-gate-v0-1.md](.github/ISSUE_TEMPLATE/dkip-qa-gate-v0-1.md)
- Scope: Wartung / Robustheit / Stabilität / Kompatibilität / Security / Performance für bestehendes Plugin, kein Funktionsausbau.

---

## A) Zielmatrix v0

Verbindliche Zielmatrix v0 für PRP v0.1 und QA-Gate v0.1. Ausrichtung: konservativ-stabil, kompatibel mit typischen LTS-Setups.

1. WordPress Versionen

- Minimum: 6.1 (LTS-nah, realistische Enterprise-/Hosting-Basis)
- Empfohlen: 6.3+
- Getestete/zu testende Spanne (Zielmatrix):
  - 6.1.x, 6.2.x, 6.3.x, 6.4.x (jeweils letzte Minor zum Testzeitpunkt)
- Obergrenze:
  - „jeweils aktuelle Stable-Mainline zum Testzeitpunkt“; bei Breaking-Änderungen gesonderte Freigabe nötig.

2. PHP Versionen

- Minimum: 7.4
  - Hintergrund: Viele produktive Umgebungen, aber bereits EOL; Support dient der Kompatibilitätsbrücke.
- Empfohlen: 8.0, 8.1, 8.2
- Zielmatrix:
  - 7.4, 8.0, 8.1, 8.2
- Obergrenze:
  - 8.3+ nach separater Prüfung; nicht automatisch im v0 Scope abgedeckt.

3. Browser / Devices

Ziel: Breite Kompatibilität auf moderner Basis, Fokus auf CSS3/JS ES5+ Unterstützung und stabiler 3D/Transform-Engine.

- Desktop (aktuelle Stable-Versionen, jeweils letzte 2 Major):
  - Chrome (Win/Mac)
  - Firefox (Win/Mac)
  - Edge (Chromium-basiert, Win)
  - Safari (macOS, letzte 2 Hauptversionen)
- Mobile:
  - iOS:
    - Safari iOS (letzte 2 Major-Versionen)
    - In-App WebViews auf Basis WKWebView (Erwartung: analog Safari iOS, bekannte Limitierungen dokumentieren)
  - Android:
    - Chrome (aktuelle Stable, gängige Hersteller)
    - Firefox Mobile (aktuell)
- Responsives Verhalten:
  - Mindestens: 320px (Mobile Portrait) bis 1920px (Desktop) ohne Funktionsverlust der Navigation/Modals.

---

## B) Umgebungen / Constraints

1. Produktiv-Themes

Die folgenden Kategorien werden als Referenz definiert:

- Primäres Referenz-Theme:
  - Twenty Twenty-Four (oder aktuelles offizielles Default-Theme des Testzeitpunkts)
- Zweites Referenz-Theme:
  - Ein weit verbreitetes, produktionsnahes Theme (z. B. GeneratePress, Astra oder Kadence).
- Anforderungen:
  - Das Plugin muss in diesen Themes ohne Layout-Brüche, ungewollte Überlagerungen oder JS-Konflikte laufen.
  - Kein hartes Styling, das globale Theme-Elemente überschreibt (Scope via CSS-Klassen/Container).

2. Hosting / Performance / Caching / CDN

- Erlaubte/typische Setups (müssen unterstützt werden):
  - Server-Caching (Page Cache)
  - Object Cache
  - CDN-Auslieferung von Assets
  - Kombinierte/minifizierte CSS-/JS-Bundles (Autoptimize, LiteSpeed Cache, etc.)
- Anforderungen an das Plugin:
  - Keine Abhängigkeit von inline-blockierenden Scripts, die durch Defer/Async zwingend brechen.
  - Initialisierungslogik robust gegenüber:
    - `defer`/`async` typischer Optimizer-Plugins
    - Aggregation von CSS/JS (kein Verlassen auf globale Seitenscope-Leaks)
- Security-Header / CSP:
  - Zielbild:
    - Kompatibel mit restriktiver CSP ohne `unsafe-inline` soweit möglich.
  - Erforderlich:
    - Modal-/Inline-Inhalte werden serverseitig sanitisiert.
    - Inline-Scripts im Plugin-Output vermeiden bzw. CSP-konform kapseln.
  - Falls für einzelne Features `unsafe-inline` oder lockere CSP nötig sind:
    - Müssen explizit dokumentiert und als Risiko im PRP/QA-Gate markiert werden.

---

## C) Repro-Fälle / Logs (Kurzliste)

Ziel: Klar identifizierbare Prüf- und Repro-Szenarien für bekannte Problemklassen, basierend auf der 5.1.x Historie.

1. Navigation / 6-Seiten-Loop / Doppelpfeil

- Szenario:
  - Würfel mit 6 aktiven Seiten im Editor konfigurieren.
  - Frontansicht laden, nacheinander:
    - Horizontale Pfeile (←/→) mehrfach auslösen.
    - Vertikale Pfeile (↑/↓) + Doppelpfeil testen.
- Erwartung:
  - Vollständige 360°-Loop-Navigation ohne Dead-Ends.
  - Doppelpfeil bleibt aktiv, keine falsch gesetzten disabled-Zustände.
- Logs:
  - Browser-Konsole auf JS-Errors prüfen.
  - Optionale Debug-Ausgabe via `data-debug="1"`:
    - `__cube.getDebugState()` konsistent bzgl. `activeFace`, `lastBeltFace`, `axisLocked`.

2. Wrap- / Hybrid-Mode

- Szenario:
  - `data-wrap-mode="hybrid"` aktivieren.
  - Horizontal Swipen/Drag auf Touch/Mouse.
- Erwartung:
  - Endlosschleife über Gürtel-Flächen ohne Sprünge zu Top/Btm.
- Logs:
  - Keine Exceptions bei häufiger Interaktion, stabiler Event-Stream (`dgp:hube:change`).

3. Modal / Vollbild / Fokus

- Szenario:
  - Info-/Fullscreen-/Zoom-Aktionen auf mehreren Seiten aktivieren.
  - Schnell hintereinander öffnen/schliessen, mit Tastatur navigieren.
- Erwartung:
  - Modal erscheint im korrekten Layer, Position top-center wie spezifiziert.
  - Fokus wechselt bei Öffnen ins Modal und bei Schliessen zurück zum auslösenden Element.
- Logs:
  - Keine Endless-Event-Loops, keine doppelten Listener, keine JS-Fehler.

4. Keyboard / A11y-Pfade

- Szenario:
  - Nur mit Tab/Shift+Tab und Pfeiltasten navigieren.
- Erwartung:
  - Erreichbarkeit aller relevanten Controls.
  - Keine Fokus-Fallen.
- Logs:
  - Optional: A11y-Inspektion (z. B. Lighthouse/axe) ohne kritische Blocker für den Block-spezifischen Bereich.

---

## D) A11y- und Datenschutz-Baseline

A11y Baseline:

- Vollständige Tastaturbedienbarkeit:
  - Navigationspfeile, Doppelpfeile, Dots, Steuer-Buttons, Modals.
- Screenreader:
  - Nutzung von `aria-live` im Statusbereich zur Ansage der aktiven Fläche.
  - Eindeutige Labels für Buttons (z. B. „Nächste Seite“, „Vorherige Seite“, „Vollbild öffnen“).
- Fokus:
  - Konsistentes Fokus-Management bei:
    - Öffnen/Schließen von Modals.
    - Wechsel zwischen Vollbild/Normalansicht.

Datenschutz Baseline:

- Kein Tracking, keine Analytics, keine Telemetrie durch das Plugin.
- Keine externen Requests ohne explizite Konfiguration durch den Anwender (z. B. eigene Medien).
- Modal-Inhalte:
  - Werden vom Site-Owner bereitgestellt; Plugin stellt sicher:
    - Sicheres Sanitizing (keine unkontrollierten Skript-Einbettungen).
- Ergebnis:
  - Plugin ist „datenschutzneutral“, d. h. erzeugt selbst keine zusätzlichen personenbezogenen Datenverarbeitungen.

---

## E) Performance-Referenz

Ziel: Plugin darf Kernmetriken nicht signifikant verschlechtern.

Referenz-Setup:

- Vergleich:
  - Seite ohne DGP-Würfel vs. identische Seite mit einem eingebundenen DGP-Würfel.
- Umgebung:
  - Standard-Theme + typisches Caching aktiv.
  - Messwerkzeuge:
    - WebPageTest, PageSpeed Insights / Lighthouse, Browser-DevTools.
- Bewertungsrahmen:
  - LCP:
    - Kein stark verzögertes Laden durch Plugin-Ressourcen.
  - CLS:
    - Keine Layoutsprünge beim Laden des Würfels (fixe Containerhöhen / stabile Platzhalter).
  - Interaktivität:
    - Würfel-Initialisierung blockiert nicht übermässig den Main-Thread.
- Akzeptanzindikator:
  - Differenz zwischen Referenzseite und Plugin-Seite liegt in einem vertretbaren Rahmen (z. B. kein massiver Einbruch >20–25% ohne Begründung).
  - Etwaige Abweichungen werden im QA-Gate dokumentiert.

---

## F) Security-Hotspots

1. PHP / Backend

- Escaping & Sanitizing:
  - Sämtliche Ausgaben im Template werden über WP-Helfer (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`) abgesichert.
- Modal-Inhalte:
  - `dgp_hube_sanitize_modal_html()`:
    - Entfernt unsichere Tags/Attribute.
    - Kapselt CSS (`dgp_hube_scope_modal_css()`), verhindert globale Theme-Manipulation.
- Capabilities:
  - Potenziell gefährliche Inhalte (Shortcodes, HTML) sind an WordPress-Rechte (z. B. `unfiltered_html`) gebunden.

2. JavaScript / Frontend

- Keine Verwendung von `eval`.
- Vorsichtiger Umgang mit `innerHTML`:
  - Nur auf Basis bereits sanitizierter Inhalte aus PHP.
- Events/Listener:
  - Cleanup via `destroy()` / MutationObserver zur Vermeidung von Leaks.

3. CSP / Inline-Content

- CSS/HTML wird so aufbereitet, dass eine restriktive CSP-Konfiguration möglich bleibt.
- Falls spezielle CSP-Ausnahmen nötig werden:
  - Müssen diese im PRP/QA-Gate dokumentiert und freigegeben werden.

---

## CI-Status (Kurzfassung)

Aktueller Stand im Repo (dgp-hube):

- PHPCS / PHPStan:
  - Empfohlen: Ja.
  - Konkrete Aktivierung:
    - Über projektspezifische CI (z. B. GitHub Actions Workflow) mit:
      - `php -l` für Syntax.
      - PHPCS mit WordPress-Standard.
- ESLint:
  - Für JS/Build-Dateien empfohlen.
- Formatter:
  - PHP-CS-Fixer / Prettier nach Projektstandard empfohlen.
- Secret-Scan:
  - Einsatz von gitleaks / ähnlichem Tool empfohlen.

Konkrete Umsetzung:
- Diese Bestätigung definiert das CI-Minimum:
  - Syntax-Check PHP
  - Lint (PHPCS/ESLint)
  - Format-Konsistenz
  - Secret-Scan
- Die tatsächliche Pipeline-Konfiguration (GitHub Actions o. ä.) ist als nächster Schritt als eigenes PR/Ticket anzulegen und mit diesem Dokument zu verlinken. Damit ist das CI-Minimum „aktivierbar“ im Sinne der DoR für PRP v0.1.

---

## Vorschlag Test-Accounts / URLs / Debug

Empfohlene Struktur (von PL/Infra bereitzustellen):

- Staging-URL:
  - z. B. `https://staging.example.tld/dgp-hube-test`
- Admin-Testaccount:
  - Rolle: Administrator
  - Zweck: Block-Konfiguration, Debug/Logs, Repro technischer Issues.
- Redakteur-Testaccount:
  - Rolle: Editor/Author
  - Zweck: Realistisches Arbeiten mit dem Block ohne Vollzugriff auf Systemkonfiguration.
- Debug:
  - Aktivierung von `data-debug="1"` in definierten Staging-Instanzen, um:
    - `__cube.getDebugState()` nutzen zu können.
    - Konsistente Logs/Telemetrie für QA ohne Produktivrisiko zu erhalten.

Falls Bereitstellung dieser Staging-Umgebung/Testaccounts nicht möglich ist, ist dies im PRP v0.1 als Blocker zu dokumentieren.

---

## Zusammenfassung (für Schließung DKIP-ACT-2025-11-11-BC-01)

- Zielmatrix v0 ist vollständig definiert (WP/PHP/Browser).
- Umgebungs- und System-Constraints sind klar beschrieben.
- Repro-Fälle / Logs für Kernprobleme (Navigation, Doppelpfeil, Modal, Fokus) sind benannt.
- A11y- und Datenschutz-Baseline sind festgelegt.
- Performance-Referenz-Szenario ist beschrieben.
- Security-Hotspots und zentrale Schutzmechanismen sind identifiziert.
- CI-Minimum ist definiert und technisch ohne Architekturbruch aktivierbar.
- Vorschlag für Staging/Test-Setups ist enthalten.

Dieses Dokument dient als verbindliche Grundlage für den Start von PRP v0.1 gemäss Definition of Ready.