# DKIP-DEV-2025-11-11-02 — Modal/Vollbild/A11y — Repro & Evidenz (Phase: PRP v0.1 PRE-FIX)

Status:
- Branch-Empfehlung: feature/modal-a11y-stabilisierung
- Phase: Nur Repro & Evidenz auf aktuellem Stand; keine neuen Fixes über abgestimmten Code hinaus.
- Basis-Code:
  - Modal/Vollbild/A11y-Implementierung in [build/view.js](build/view.js:890)
  - Sanitizing/Markup in [dgp-hube.php](dgp-hube.php:43)

Ziel:
- Nachweis der aktuellen Modal- & Vollbild-Verhalten gegen die A11y-/QA-Anforderungen.
- Grundlage für spätere Fixes nach PRP v0.1-Freigabe.

---

## 1. Test-Setup

- Block-Konfiguration:
  - DGP 3D Cube Block mit:
    - Mind. 2–3 Bildern mit Inline-/Modal-Inhalt (action=inline/modal).
    - Fullscreen-Button aktiv (data-show-fullscreen="1").
    - Mehr-Info/Action-Button aktiv (data-show-action="1").
  - Debug:
    - data-debug="1", um interne Zustände prüfen zu können (optional).

- Prüfumgebung:
  - Moderne Browser (gemäß Zielmatrix; mind. 1 Desktop + 1 Mobile).
  - Screenreader:
    - z. B. NVDA/JAWS (Windows), VoiceOver (macOS/iOS).

- Logging:
  - Aktivieren eines Listeners:
    - window.addEventListener('dgp:hube:change', (e) => console.log('[dgp]', e.detail));
  - Ziel:
    - Interaktion-IDs, from/to, axis, dir sichtbar machen.
  - Für Modal-spezifische Tests reicht Fokus-/A11y-Beobachtung; Events dienen Kontext.

- Artefakte:
  - GIF/Video pro Kern-Szenario.
  - Notizen zu Fokus, Tastaturverhalten, Screenreader-Ausgabe.

---

## 2. Tastatur-Walkthrough (Tab/Shift+Tab/Enter/Escape)

Ziel:
- Sicherstellen, dass:
  - Modal deterministisch öffnet/schließt.
  - Focus-Trap aktiv ist.
  - Rückfokus zum Auslöser erfolgt.
  - ESC zuverlässig schließt.

Szenario 2.1 — Modal öffnen und Fokus-Trap prüfen

1. Fokus auf Würfel/Navigation legen (Tab bis Action-Button).
2. Enter/Space auf „Mehr Info“-Button (dgp-ctrl-action).
3. Erwartung:
   - Modal (dgp-modal) öffnet mit:
     - role="dialog"
     - aria-modal="true"
     - Titel aus aktuellem Bild (dgp-modal-title).
   - Body-Scroll gesperrt (overflow:hidden).
   - Erster Fokus im Dialog:
     - Auf Close-Button oder erstes fokussierbares Element (Focus-Trap initialisiert).

4. Tab/Shift+Tab im geöffneten Modal:
   - Fokus rotiert ausschließlich innerhalb:
     - Schließen-Button oben
     - Content-Links/Buttons
     - Footer-Schließen-Button
   - Niemals außerhalb des Modals.

5. Escape:
   - ESC schließt das Modal.
   - Erwartung:
     - Modal entfernt is-open, hidden=true.
     - Body-Scroll wiederhergestellt.
     - Fokus springt zurück auf vorherigen Auslöser (Action-Button) oder gespeichertes lastFocus.

Evidenz:
- GIF/Video:
  - Zeigt: Trigger → Modal → Tab/Shift+Tab im Kreis → ESC → Rückfokus.
- Kurzlog:
  - Notiere: Fokusstart im Modal, Reihenfolge der Fokusziele, Erfolgreicher Rückfokus-Ziel.

---

## 3. Screenreader-Kurzprotokoll (Titel/Status-Ansage)

Ziel:
- Prüfen, dass Screenreader ausreichend Kontext bekommen.

Szenario 3.1 — Dialog-Titel & Inhalt

1. Mit Screenreader aktiv:
   - Modal wie oben über Tastatur öffnen.
2. Erwartung:
   - Screenreader meldet:
     - Eintritt in Dialog (role="dialog"/aria-modal).
     - Titel (dgp-modal-title) als Dialogtitel.
   - Inhalte (Text/Links im Modal) werden normal gelesen.

Szenario 3.2 — Status-/Seitenansagen (Announce)

1. Würfel ohne Modal mit Tastatur oder Buttons durch Seiten navigieren.
2. Erwartung:
   - .dgp-cube-a11y Live-Region gibt:
     - „Aktive Fläche: <Titel> – X von L – [Richtungshinweis sofern vorhanden].“
   - Keine übermäßige Wiederholung:
     - Ansage nur bei tatsächlichem Face-Wechsel.

Evidenz:
- Kurzprotokoll:
  - Auflisten:
    - Welche Texte beim Öffnen des Modals angesagt wurden.
    - Welche Texte bei Seitenwechseln angesagt wurden.
  - Highlighten, dass Titel und Position eindeutig sind.

---

## 4. GIF/Video — Modal → Vollbild → Close

Ziel:
- Demonstration stabiler Interaktion zwischen Modal und Vollbild.

Szenario 4.1 — Vollbild ohne Modal

1. Klick auf Fullscreen-Button.
2. Erwartung:
   - Würfel geht in Vollbild (root.is-fullscreen, FS-Close-Button sichtbar).
   - Fokus bleibt kontrolliert im Cube-Bereich.
3. ESC oder Vollbild-Schließen-Button:
   - Vollbild wird beendet.
   - UI-Icons und States (Fullscreen-Button) werden korrekt zurückgesetzt.

Szenario 4.2 — Modal im Vollbild

1. In Vollbild wechseln.
2. Aus Vollbild heraus „Mehr Info“-Button auslösen.
3. Erwartung:
   - Modal sichtbar im Vollbild (top-center).
   - Fokus-Trap funktioniert auch im Vollbild.
4. Modal schließen (ESC oder Close):
   - Fokus zurück auf Trigger im Vollbild.
5. Vollbild verlassen:
   - Fokus geht konsistent auf Cube/Steuer-Elemente zurück.
   - Keine „hängenden“ Klassen (is-fullscreen, is-open etc.).

Artefakt:
- GIF/Video:
  - Sequenz:
    - Normal → Modal → Close
    - Normal → Vollbild → Modal → Modal Close → Vollbild Close
  - Ziel: Kein Fokusverlust, kein Scroll-Sprung, konsistente Icons.

---

## 5. Schnellfolge Öffnen/Schließen & Cleanup

Ziel:
- Kein Event-/Focus-Leak bei schneller Interaktion.

Szenario 5.1 — Rapid Open/Close

1. Mehrfach schnell hintereinander:
   - „Mehr Info“ öffnen, ESC drücken, erneut öffnen, etc.
2. Erwartung:
   - Modal verhält sich jedes Mal gleich.
   - Keine doppelten keydown-Listener (Tab/Escape verhalten sich stabil).
   - Rückfokus immer auf Trigger.

Szenario 5.2 — Modal + Navigationswechsel

1. Modal öffnen.
2. Ohne Maus:
   - ESC drücken.
   - Direkt Pfeiltasten für Cube-Navigation verwenden.
3. Erwartung:
   - Kein hängender Focus-Trap.
   - Navigation reagiert normal.

Evidenz:
- Kurznotizen:
  - Kein unerwartetes Tab-Verhalten nach Mehrfach-Open/Close.
  - Keine JS-Errors in der Console.
- Optional:
  - DevTools Performance/Timeline, um multiple Listener auszuschließen (kein Muss, nur Zusatz).

---

## 6. CI-Anforderung

- Alle o. g. Szenarien gelten als gültig, wenn:
  - GitHub Actions Workflow [ci-dgp-hube.yml](.github/workflows/ci-dgp-hube.yml:1) durchläuft:
    - PHP Syntax, PHPCS, PHPStan
    - ESLint, Prettier
    - gitleaks
  - Fixes zu DKIP-DEV-2025-11-11-02:
    - Erst nach PRP v0.1-Freigabe einbringen.
    - Jeder Fix-PR:
      - Verweist auf dieses Repro-Dokument.
      - Liefert GIF/Video + Screenreader-/Fokus-Logs.
      - Muss CI-grün sein.

---

## 7. Annahme-Kommentar (Vorlage)

> Annahme DKIP-DEV-2025-11-11-02 (Repro-Phase): 
> Tastatur-Walkthrough, Screenreader-Kurzprotokoll und Modal/Vollbild-Sequenzen sind in DKIP-DEV-2025-11-11-02_MODAL-A11Y-REPRO.md definiert. 
> CI-Pipeline ist aktiv. Fix-Implementierungen erfolgen erst nach PRP v0.1-Freigabe und werden pro PR mit GIF/Video, Fokus-/SR-Notizen und grünem CI-Status nachgewiesen.

Dieses Dokument erfüllt die PL-Vorgaben für die Startphase von DKIP-DEV-2025-11-11-02 (Modal/Vollbild/A11y) und bildet die verbindliche Basis für spätere Fix-PRs.