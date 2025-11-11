# DGP 3D Cube 360° (Stabilitäts-Set PRP v0.1) – Version 5.1.15-prp.0

Dieses WordPress-Plugin stellt einen Gutenberg-Block bereit, um einen interaktiven 3D-Würfel zu erstellen. Jede der sechs Seiten kann ein Bild und eine damit verknüpfte Aktion (Link, Modal, Event) enthalten. Der Würfel ist vollständig über den Block-Editor anpassbar.

**NEU in Version 5.1.15**: DOPPELPFEIL-BUTTON REPARIERT + Vollständige 6-Seiten-Navigation. Der vertikale Doppelpfeil-Button war dauerhaft deaktiviert und ist jetzt vollständig funktionsfähig. Alle 6 Seiten (front, right, back, left, top, bottom) sind über Pfeiltasten und Doppelpfeil erreichbar mit nahtloser 360°-Loop-Navigation.

---

## Status der Version 5.1.15

**REPARATUR & OPTIMIERUNG**: Doppelpfeil-Button war deaktiviert und ist jetzt vollständig funktionsfähig. Die 6-Seiten-Navigation ist jetzt komplett repariert.

*   **REPARATUR**: Vertikaler Doppelpfeil-Button repariert - Button war dauerhaft deaktiviert (disabled="true") aufgrund falscher isDisabled-Logik
*   **REPAIR**: updateVerticalDoubleArrowAccessibility() Funktion korrigiert - isDisabled von this._isAnimating auf false geändert
*   **FEATURE**: Doppelpfeil-Button ist jetzt IMMER aktiviert und funktionsfähig auf allen 6 Seiten
*   **FEATURE**: Vollständige 6-Seiten-Navigation mit Pfeilbuttons: front, right, back, left, top, bottom sind alle erreichbar
*   **FEATURE**: Doppelpfeil-State-Machine funktioniert korrekt: Gürtel → Top → Bottom → Zurück zur Gürtelansicht
*   **REPAIR**: turn() und goto() Funktionen vollständig repariert für alle 6 Seiten
*   **REPARATUR**: Pfeil-Button-Navigation vollständig repariert - alle 6 Seiten (Seite 1-6) sind jetzt über Pfeiltasten erreichbar ohne Blockaden nach Seite 3
*   **FIX**: turn() Funktion korrigiert - fehlerhafte Index-Berechnung in linear6 Navigation behoben, virtuelle 360°-Loop-Rotation implementiert
*   **FIX**: Geometrisch korrekte 3D-Würfel-Navigation (statt linear6) für horizontale/vertikale Pfeil-Navigation
*   **FIX**: Vollbild-Modus Modal wird jetzt in der Mitte-oben verankert (top-center) statt unterschiedlicher Positionen
*   **FEATURE**: Kontinuierliche 360°-Rotation ohne Blockaden implementiert
*   **TECH**: Virtueller Index für nahtlose Navigation zwischen allen 6 Seiten hinzugefügt

*   **Wartungsmodus:** Das Plugin bleibt im Wartungsmodus. Updates adressieren gezielt Bugs, Performance und Sicherheit.
*   **Maintenance Clean:** Pointer-/Fullscreen-Listener werden einmalig gebunden (Cleanup via `destroy()` + MutationObserver); Clamp-Szenarien sparen Transition & Events; `announce()` arbeitet nur bei echten Indexwechseln; Debug-Handle steht ausschließlich bei `data-debug="1"` zur Verfügung (automatisch im Editor).
*   **Navigation & Wrap:** `wrapMode="hybrid"` / `data-wrap-mode="hybrid"` ermöglicht endloses horizontales Swipen (Drag/Swipe). Pfeile & Tastatur bedienen den Gürtel (Links/Rechts) sowie Top/Bottom (Oben/Unten) inklusive Restore zur zuletzt aktiven Gürtel-Fläche. `verticalSwipe` (`data-vertical-swipe="0|1"`, Default `1`) dient als Backout.
*   **Flags & Events:** Experimenteller Linear-Lauf über alle sechs Seiten via `data-arrow-mode="linear6"` (opt-in). `dgp:hube:change` liefert weiterhin vollständige Payload (`axis/dir/mode/from/to/total`).

### Wichtiger Hinweis zur Touch-Interaktion

Die Steuerung des Würfels per Touch-Geste (Wischen/Swipen) ist ab Version 5.1.3 **vollständig implementiert und aktiviert**. Es wurde großer Wert auf Stabilität, Performance und eine intuitive Benutzererfahrung gelegt, inklusive Scroll-Hijacking-Prävention und intelligentem Einrasten.

Die Interaktion erfolgt über:
*   Navigationspfeile (Klick)
*   Navigations-Dots (Klick)
*   Tastatur (Pfeiltasten)
*   Steuer-Buttons (Info, Zoom, Audio etc.)

### Pfeil-Mapping & Flags

*   **Orthogonal (Default):** Links/Rechts über `wrapMode="hybrid"` im Gürtel; Oben/Unten springen auf Top/Bottom und zurück zur zuletzt aktiven Gürtel-Fläche (`lastBeltFace`).
*   **Linear 1→6 (Opt-in):** `data-arrow-mode="linear6"` (oder Block-Attribut `arrowMode="linear6"`) lässt Links/Rechts sequenziell durch alle sechs Seiten laufen. Drag/Swipe bleibt unverändert.
*   **Debug/QA:** `data-debug="1"` aktiviert das Debug-Handle `__cube.getDebugState()` (im Editor automatisch aktiv); in Produktion standardmäßig deaktiviert.

### Sicherheit

Das Plugin wurde mit Fokus auf Sicherheit entwickelt. Nutzereingaben für das interne Modal (HTML, CSS) werden serverseitig validiert und bereinigt (`wp_kses`), um Cross-Site-Scripting (XSS) zu verhindern. CSS wird automatisch "gescoped", sodass es nur den Inhalt des Modals beeinflusst und nicht die gesamte Website. Die Ausführung von Shortcodes ist an die WordPress-Benutzerrechte (`unfiltered_html`) gekoppelt.
