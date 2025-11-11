# DKIP-DEV-2025-11-11-01 — Navigation/Wrap/Arrow State Machine — Repro & Evidenz (Phase: PRP v0.1 PRE-FIX)

Status:
- Branch-Empfehlung: feature/nav-wrap-stabilisierung
- Phase: Nur Repro & Evidenz, keine neuen Fixes über bestehenden Stand hinaus.
- Basis-Code: aktueller Stand von build/view.js und dgp-hube.php in diesem Repo.

## 1. Test-Setup (gemeinsam für Tests 1–5)

- Block-Konfiguration:
  - 6 Bilder, gemappt auf:
    - front, right, back, left, top, bottom
  - Arrow Mode:
    - orthogonal (Default)
    - linear6 (für Mode-Vergleich)
  - Wrap Mode:
    - off (Baseline)
    - ring (für Gürtel-Wrap-Verhalten)
  - Debug:
    - data-debug="1" setzen
    - Dadurch verfügbar:
      - root.__cube / root._dgpCubeInstance
      - __cube.getDebugState():
        - { activeFace, lastBeltFace, axisLocked, lockedAxis }
- Logging:
  - Browser-Console:
    - dgp:hube:change Listener:
      - Logge:
        - interactionId
        - from, to
        - fromFace, toFace
        - axis, dir
        - step (falls vorhanden)
  - Optional:
    - Snapshot von __cube.getDebugState() vor/nach kritischen Aktionen.
- Artefakte:
  - Screen-Recording (GIF/Video) pro Testsatz.
  - Konsolen-Logs exportieren (JSON/Plaintext) als Anhang zum PR.

---

## 2. Test 1 — Top↔Bottom↔Restore (Keyboard & Doppelpfeil)

Ziel:
- Sicherstellen, dass vertikale Navigation keine Dead-States erzeugt und Restore korrekt funktioniert.

A) Keyboard-Sequenz

1. Start:
   - Fokus auf Würfel setzen.
   - Erwartung Initial:
     - activeFace = front
     - lastBeltFace = front
2. Schritt:
   - ArrowUp
   - Erwartung:
     - dgp:hube:change (axis=y, dir=up)
     - activeFace = top
     - lastBeltFace = front
3. Schritt:
   - ArrowDown
   - Erwartung:
     - dgp:hube:change (axis=y, dir=down)
     - activeFace = front (Restore)
     - lastBeltFace = front
4. Variation:
   - Von right/back/left jeweils:
     - ArrowUp → top
     - ArrowDown → Restore zur jeweiligen Gürtel-Fläche

Evidenz:
- Logs:
  - interactionId monotonic, je Sequenz genau 1 Event pro finalem Statewechsel.
- GIF:
  - Zeigt Top-Aufruf und sicheren Restore.

B) Doppelpfeil-Sequenz (UI-Button)

1. Start auf Gürtel-Fläche (z. B. right).
2. Click Doppelpfeil:
   - Erwartung:
     - toFace = top
     - step = "up"
     - lastBeltFaceForDoubleArrow = right
3. Click Doppelpfeil:
   - Erwartung:
     - toFace = bottom
     - step = "down"
4. Click Doppelpfeil:
   - Erwartung:
     - toFace = right (Restore)
     - step = "restore"
     - Kein Dead-State

Evidenz:
- Log-Ausschnitt für dgp:hube:change bei Doppelpfeil:
  - step: up/down/restore, axis=y, dir passend.
- GIF:
  - Sichtbare Sequenz Gürtel → Top → Bottom → Gürtel.

---

## 3. Test 2 — Gürtel-Loop ←×N/→×N (N > 6)

Ziel:
- Prüfen, dass 360°-Loops stabil bleiben; virtualIndex/activeFace (linear6) bleiben gekoppelt.

A) orthogonal

1. Start:
   - arrowMode=orthogonal, wrapMode=ring (falls verfügbar).
   - Fokus auf front.
2. 10× ArrowRight:
   - Erwartung:
     - Sequenz auf Gürtel:
       - front → right → back → left → front → ...
     - activeFace immer in {front,right,back,left}.
     - Keine Sprünge zu top/bottom.
3. 10× ArrowLeft:
   - Erwartung:
     - Umgekehrte Sequenz konsistent.

B) linear6

1. arrowMode=linear6.
2. 10× ArrowRight:
   - Erwartung:
     - Sequenz:
       - front → right → back → left → top → bottom → front → ...
     - Vollständiger 6er-Loop.
3. 10× ArrowLeft:
   - Erwartung:
     - Sequenz in Gegenrichtung.

Evidenz:
- Logs:
  - fromFace/toFace bilden lückenlose modulo-Sequenz.
  - Keine Abweichung zwischen sichtbarer Seite und geloggtem toFace.
- GIF:
  - Loop-Run (z. B. 2–3 volle Runden).

---

## 4. Test 3 — Drag (langsam/schnell) + sofortiger Pfeil-Input

Ziel:
- Race-Conditions vermeiden; keine Doppel-Events oder inkonsistente States.

Szenario 3.1 — Langsamer Drag

1. Start:
   - arrowMode=orthogonal.
2. Aktion:
   - Langsam von front nach rechts ziehen, bis Face klar wechselt; loslassen.
3. Erwartung:
   - Genau ein Snap:
     - fromFace=front → toFace=right
   - dgp:hube:change genau 1×.
   - Kein weiterer Turn bei Ruhe.

Szenario 3.2 — Drag + sofortiger Pfeil

1. Start:
   - Wiederholung Drag von front nach right.
2. Aktion:
   - Unmittelbar nach Loslassen:
     - Sofort Klick auf "Next"-Pfeil oder ArrowRight-Key.
3. Erwartung:
   - Während _isAnimating/_isSnapping:
     - turn()-Aufrufe werden verworfen (Eingaben entprellt).
   - Ergebnis:
     - Kein doppelter Sprung.
     - State nach Transition konsistent:
       - DebugState.activeFace und visuelle Face stimmen überein.

Szenario 3.3 — Fling

1. Schneller Swipe + hoher Velocity.
2. Sofort Pfeil/Key.
3. Erwartung:
   - Wie oben: stabiler Endstate; keine Ghost-Turns.

Evidenz:
- Logs:
  - Bei jedem Szenario: ein dgp:hube:change pro finalem Wechsel.
- GIF:
  - Zeigt Drag + sofortige Eingabe ohne Chaos.

---

## 5. Test 4 — Modes: linear6 vs orthogonal (Abgleich Verhalten)

Ziel:
- Sicherstellen, dass beide Modi konsistent ihr definiertes Modell einhalten.

Skript:

1. orthogonal:
   - Nur Gürtel-Seiten per ←/→.
   - ↑/↓ ausschließlich Top/Bottom+Restore.
2. linear6:
   - Gleiches Testset:
     - Pfeile (←/→/↑/↓), Doppelpfeil.
   - Erwartung:
     - lineare Sequenz über alle 6 Faces (1–6–1).
3. Vergleich:
   - Prüfen, dass:
     - orthogonal nie „linear6-Sprung“-Verhalten zeigt.
     - linear6 keine orthogonalen Lücken bildet.

Evidenz:
- Log-Vergleich aus beiden Modi.
- Kurz-Tabellen im PR:
  - Eingabe vs. Erwartetes Ziel vs. tatsächliches toFace.

---

## 6. Test 5 — WrapMode ring — Gürtel-Wrap ohne Top/Bottom-Sprung

Ziel:
- Sicherstellen, dass ring/hybrid nur horizontale Wraps beeinflusst.

Skript:

1. wrapMode=ring, arrowMode=orthogonal.
2. Aktionen:
   - Von front mehrfach ArrowRight:
     - Erwartung:
       - front → right → back → left → front → ...
       - Kein ästhetisch unbegründeter Top/Bottom-Sprung.
   - Von jeder Gürtel-Seite ArrowUp:
     - Erwartung:
       - Wechsel zu top; lastBeltFace gesetzt.
   - Von top ArrowDown:
     - Restore zu lastBeltFace.
3. Validierung:
   - dgp:hube:change:
     - axis/dir konsistent.
   - Debug:
     - lastBeltFace korrekt.

Evidenz:
- GIF:
  - Kombination Wrap-Gürtel + Top/Bottom ohne Ausreißer.
- Logs:
  - Keine Sprünge, die nicht durch H_NEIGHBORS/V_NEIGHBORS/State-Machine erklärbar sind.

---

## 7. Hotspotliste (mit 1-Satz-Hypothesen)

Die folgenden Stellen sind bei Auffälligkeiten im Repro bevorzugt zu prüfen:

1. turn()
   - Hypothese: Falsche Kopplung von stepsX/stepsY an arrowMode/wrapMode kann zu inkonsistenten Ziel-Faces führen.
2. determineVerticalTarget()
   - Hypothese: Fehlerhafte lastBeltFace-Verwendung erzeugt falsche Restore-Ziele.
3. handleVerticalDoubleArrowClick()
   - Hypothese: Unvollständige Pflege von lastBeltFaceForDoubleArrow kann Restore brechen.
4. emitChangeEvent()
   - Hypothese: Falsche from/to/axis/dir-Zuordnung erzeugt irreführende Telemetrie.
5. snapToFace()/onCubeTransitionEnd()
   - Hypothese: Unsaubere _isAnimating/_isSnapping-Resets verursachen Dead- oder Ghost-States.
6. onPointerUp()/calculateVelocity()
   - Hypothese: Falsche Schwellen/Velocity-Auswertung führen zu unerwarteten Extra-Turns.

---

## 8. CI-Anforderung

- Alle oben beschriebenen Repro-Läufe sind nur gültig, wenn:
  - CI-Workflow [ci-dgp-hube.yml](.github/workflows/ci-dgp-hube.yml:1) „grün“ läuft (Syntax/Lint/Static/Secrets).
- Fixes zu DKIP-DEV-2025-11-11-01:
  - Erst nach PRP v0.1-Review/Freigabe einchecken.
  - Jede Änderung muss:
    - dieses Repro-Protokoll erneut durchlaufen.
    - CI-grün belegen.
    - Logs + GIF/Video im PR verlinken.

---

Annahme-Kommentar (Vorlage PL)

> Annahme DKIP-DEV-2025-11-11-01 (Repro-Phase): 
> Repro-Protokoll Tests 1–5 und Hotspotliste hinterlegt (DKIP-DEV-2025-11-11-01_NAV-REPRO.md). 
> CI-Pipeline bereit. Fix-Implementierungen erfolgen erst nach PRP v0.1-Freigabe und werden je PR mit GIF/Logs/Matrix belegt.

Dieses Dokument schließt die geforderte Repro-/Evidenzvorbereitung für Ticket DKIP-DEV-2025-11-11-01 in der Startphase ab, ohne neue Fixes über den abgestimmten Code-Stand hinaus einzuführen.