# WÜRFEL-GEOMETRIE UND KORREKTE NAVIGATION

## 🧩 3D-Würfel Seiten-Anordnung:

```
       [Seite 5: TOP]
            ↑
    [4]← [1] → [2] → [3] ←
            ↓
      [Seite 6: BOTTOM]
```

**Normale Pfeil-Navigation sollte so funktionieren:**

### Horizontale Pfeile (←/→):
- **→ (Rechts):** front → right → back → left → front (360° Loop)
- **← (Links):** front → left → back → right → front (360° Loop)

### Vertikale Pfeile (↑/↓):
- **↑ (Hoch):** Gürtel-Seiten (front,right,back,left) → TOP
- **↓ (Runter):** TOP/BOTTOM → Gürtel-Seiten

## ❌ Aktueller Fehler:
Die linear6 Navigation geht ALLE 6 Seiten linear durch: 1→2→3→4→5→6→1
Das ist **NICHT** die korrekte Würfel-Navigation!

## ✅ Korrekte Lösung:
1. **Horizontale Navigation:** Nur die 4 Gürtel-Seiten (front, right, back, left)
2. **Vertikale Navigation:** Wechsel zu TOP/BOTTOM und zurück
3. **360° Loop:** Nahtlose zyklische Navigation ohne "Seiten zu überspringen"

**Problem:** Die Pfeil-Buttons sollten Seite 4 (left) zeigen, nicht direkt zu Seite 5 (top) springen!