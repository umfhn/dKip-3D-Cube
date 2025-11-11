# DKIP-DEV-2025-11-11-04 — Regression 5.1.15 — Verifikation (Phase: PRP v0.1 PRE-FIX)

Status:
- Branch-Empfehlung: feature/regression-5.1.15-verifikation
- Phase: Reine Verifikation & Evidenz auf aktuellem Stand (5.1.15-Basis + Hardening), Fix-Code nur falls zwingend und erst NACH PRP v0.1-Freigabe.
- Basis-Code:
  - Navigation/State-Machine/Doppelpfeil: [build/view.js](build/view.js:1655)
  - Modal/A11y/Vollbild: [build/view.js](build/view.js:890)
  - Sanitizing / Renderpfad: [dgp-hube.php](dgp-hube.php:43)

Ziel:
- Sicherstellen, dass keine der in 5.1.15 adressierten Problemlagen (insb. Doppelpfeil/6-Seiten-Navigation/Focus) reaktiviert wurde.
- Alle Regressionstests laufen unter CI-Grün.

---

## 1. Test-Setup (gemeinsam für Sequenzen A/B/C)

- Block-Konfiguration:
  - Genau 6 Bilder, zugeordnet auf:
    - front, right, back, left, top, bottom
  - Navigation:
    - Pfeile sichtbar
    - Doppelpfeil-Button sichtbar
  - Modi:
    - orthogonal + wrapMode=off
    - orthogonal + wrapMode=ring
    - linear6 (optional für erweiterten Check)
  - Debug:
    - data-debug="1" am Block → Zugriff auf:
      - root.__cube / root._dgpCubeInstance
      - __cube.getDebugState():
        - { activeFace, lastBeltFace, axisLocked, lockedAxis }

- Logging (für alle Sequenzen):
  - JS:
    - window.addEventListener('dgp:hube:change', (e) => console.log('[dgp-change]', e.detail));
  - Zu protokollierende Felder:
    - interactionId
    - from, to
    - fromFace, toFace
    - axis, dir
    - step (falls vorhanden, z. B. Doppelpfeil)
  - Optional:
    - Vor/nach kritischen Aktionen: console.log(__cube.getDebugState()).

- Artefakte:
  - Kurzvideos/GIFs pro Sequenz.
  - Konsolen-Logexport (Text/JSON) je Lauf.
  - Alle Läufe unter aktiver CI (grün).

---

## 2. Sequenz A — Top↔Bottom↔Restore + Doppelpfeil (Regression Doppelpfeil / Restore)

Ziel:
- Keine Rückfälle des „Doppelpfeil tot“ oder falscher Restore-Ziele.

A.1 — Keyboard vertikal

1. Start:
   - Fokus auf Würfel.
   - __cube.getDebugState() prüfen:
     - activeFace = front, lastBeltFace = front.
2. Aktion:
   - ArrowUp:
     - Erwartung:
       - dgp:hube:change:
         - axis = 'y', dir = 'up'
         - fromFace = front, toFace = top
       - Debug:
         - activeFace = top
         - lastBeltFace = front
3. Aktion:
   - ArrowDown:
     - Erwartung:
       - axis = 'y', dir = 'down'
       - toFace = front (Restore)
       - lastBeltFace bleibt konsistent.

Variation:
- Start von right/back/left analog:
  - ArrowUp → top
  - ArrowDown → Restore auf jeweilige Gürtel-Fläche.

A.2 — Doppelpfeil-Button

1. Start:
   - activeFace = right, lastBeltFace = right.
2. Click Doppelpfeil:
   - Erwartung:
     - toFace = top
     - step = 'up'
     - lastBeltFaceForDoubleArrow = right
3. Click Doppelpfeil:
   - Erwartung:
     - toFace = bottom
     - step = 'down'
4. Click Doppelpfeil:
   - Erwartung:
     - toFace = right (Restore)
     - step = 'restore'
     - Kein Dead-State; weitere Klicks wiederholen Zyklus.

Evidenz:
- Logauszug:
  - Drei dgp:hube:change-Events mit sauberer step-/axis-/dir-Payload.
- GIF:
  - Sichtbare Sequenz Gürtel → Top → Bottom → Gürtel.

Kriterium:
- GRÜN, wenn Doppelpfeil/Key-Sequenzen deterministisch funktionieren und nie in einem Zustand enden, in dem keine Navigation mehr reagiert.

---

## 3. Sequenz B — Gürtel-Loop > 6 Steps (virtualIndex / activeFace Kopplung)

Ziel:
- Keine Regression der 6-Seiten-Navigation/virtualIndex-Kopplung.

B.1 — orthogonal + wrapMode=ring

1. Start:
   - arrowMode=orthogonal, wrapMode=ring.
   - activeFace = front.
2. Aktion:
   - 10× ArrowRight.
3. Erwartung:
   - Sequence:
     - Nur Gürtel-Seiten (front/right/back/left) im Loop.
     - Kein Sprung zu top/bottom.
   - dgp:hube:change:
     - toFace rotiert modulo 4.
     - interactionId monotonic, 1 Event je Key.

B.2 — linear6

1. arrowMode=linear6.
2. Aktion:
   - 10× ArrowRight.
3. Erwartung:
   - Voller 6er-Loop:
     - front → right → back → left → top → bottom → front → ...
   - Kein Dead-State; jede Eingabe führt zu konsistenter Next-Seite.
4. Aktion:
   - 10× ArrowLeft.
   - Erwartung:
     - Umgekehrte 6er-Sequenz, symmetrisch.

Evidenz:
- Tabelle (Beispiel):
  - Spalten: Step, Input, fromFace, toFace, axis, dir.
- GIF:
  - Mehrfach-Loop (rechts/links) ohne Fehlerbild.

Kriterium:
- GRÜN, wenn virtualIndex (implizit erkennbar durch Sequenz) und activeFace nie auseinanderlaufen und der Nutzer keinen „Hänger“ erlebt.

---

## 4. Sequenz C — Drag + Sofortige Eingabe (Race-Conditions / Doppel-Events)

Ziel:
- Regressionstest gegen Race-Bugs (z. B. Eingaben während Animation/Drag → Ghost-States).

C.1 — Langsamer Drag

1. Aktion:
   - Langsamer Horizontal-Drag von front nach right; klarer Snap beim Loslassen.
2. Erwartung:
   - Genau ein dgp:hube:change:
     - fromFace=front, toFace=right.
   - Debug:
     - activeFace=right, axisLocked-Flags sinnvoll.

C.2 — Drag + Pfeil/Key während Animation

1. Aktion:
   - Schnell draggen (Snap auslösen).
   - Direkt beim/kurz nach Loslassen:
     - Klick auf Next-Pfeil oder ArrowRight-Key.
2. Erwartung:
   - Durch Entprellung (isLocked/_isAnimating/_isSnapping):
     - Entweder:
       - Input ignoriert, oder
       - erst nach stabilisiertem State sauber verarbeitet.
   - Kein doppelter Sprung.
   - Keine JS-Errors.

C.3 — Fling + Sofortige Eingabe

1. Schneller Swipe mit hoher Geschwindigkeit.
2. Direkt anschließend Pfeil- oder Key-Eingabe.
3. Erwartung:
   - Wie C.2:
     - Stabiler finaler State, kein Ghost-Face, kein to==from Leak.

Evidenz:
- Logs:
  - Für jede Interaktion maximal ein Event pro finalem Statewechsel.
- GIF:
  - Drag + sofortiger Input ohne sichtbares „Zerreißen“ der Navigation.

Kriterium:
- GRÜN, wenn kein Szenario mehrfach-Events, Dead-States oder inkonsistente Directions erzeugt.

---

## 5. CI-Bedingung

- Alle Regressionstests gelten nur als bestanden, wenn:
  - GitHub Actions Workflow [ci-dgp-hube.yml](.github/workflows/ci-dgp-hube.yml:1) vollständig grün ist:
    - PHP Syntax / PHPCS / PHPStan
    - ESLint / Prettier
    - gitleaks

---

## 6. Fix-Code Policy

- Fixes im Rahmen DKIP-DEV-2025-11-11-04:
  - Nur nach PRP v0.1-Freigabe.
  - Jeder Fix-PR:
    - Referenziert dieses Dokument.
    - Liefert:
      - aktualisierte Regression-Protokolle,
      - GIF/Video kritischer Sequenzen A/B/C,
      - dgp:hube:change-Logauszüge (interactionId/from/to/axis/dir),
      - CI: GRÜN.

---

## 7. Annahme-Kommentar (Vorlage)

> Annahme DKIP-DEV-2025-11-11-04 (Regression-Phase PRE-FIX): 
> Regression-Szenarien A (Top/Bottom/Restore + Doppelpfeil), B (Gürtel-Loop >6 Steps) und C (Drag + sofortige Eingaben) sind in DKIP-DEV-2025-11-11-04_REGRESSION-5.1.15-REPRO.md definiert. 
> CI-Workflow ist aktiv. Fixes erfolgen nur nach PRP v0.1-Freigabe und werden mit Matrix, GIF/Video und dgp:hube:change-Logs nachgewiesen.

Dieses Dokument erfüllt die PL-Vorgaben für die Startphase von DKIP-DEV-2025-11-11-04 und stellt ein vollständiges, CI-integriertes Regression-Framework für die 5.1.15-Fixes bereit.