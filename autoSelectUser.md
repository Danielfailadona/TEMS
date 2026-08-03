# Auto-Select Enforcer Fix

## Files Changed

1. `resources/views/tracking/index.blade.php` — Lines 239–243 (inline tracking JS)
2. `resources/js/tracking.js` — Lines 194–203 (modular tracking JS — same fix for consistency)

---

## Original Code

**`resources/views/tracking/index.blade.php` (lines 239–243):**
```javascript
if (state.selectedEnforcerId) {
    const still = state.enforcers.find(e => e.id === state.selectedEnforcerId);
    if (!still) selectEnforcer(state.enforcers[0]?.id || null);
    else selectEnforcer(state.selectedEnforcerId);
} else if (state.enforcers.length > 0) { selectEnforcer(state.enforcers[0].id); }
```

**`resources/js/tracking.js` (lines 194–203):**
```javascript
if (state.selectedEnforcerId) {
    const stillExists = state.enforcers.find(e => e.id === state.selectedEnforcerId);
    if (!stillExists) {
        selectEnforcer(state.enforcers[0]?.id || null);
    } else {
        selectEnforcer(state.selectedEnforcerId);
    }
} else if (state.enforcers.length > 0) {
    selectEnforcer(state.enforcers[0].id);
}
```

---

## New Code

**`resources/views/tracking/index.blade.php`:**
```javascript
if (state.selectedEnforcerId) {
    const still = state.enforcers.find(e => e.id === state.selectedEnforcerId);
    if (!still) state.selectedEnforcerId = null;
    else selectEnforcer(state.selectedEnforcerId);
}
```

**`resources/js/tracking.js`:**
```javascript
if (state.selectedEnforcerId) {
    const stillExists = state.enforcers.find(e => e.id === state.selectedEnforcerId);
    if (!stillExists) {
        state.selectedEnforcerId = null;
    } else {
        selectEnforcer(state.selectedEnforcerId);
    }
}
```

---

## What It Was Doing (The Problem)

1. **Auto-select on page load (line 243 / line 201):** When the tracking page loaded (or every 15s refresh), if no enforcer was selected (`state.selectedEnforcerId` was `null`), the code automatically selected the **first enforcer** from the list and called `selectEnforcer()` on them. This caused the map to immediately **fly from the default view** (zoom 12, center 121.0402, 14.5432) to that enforcer's location (zoom 15), and the detail panel auto-populated with their info — even though the user never clicked anything.

2. **Auto-replace missing selected (line 241 / line 196):** If a previously selected enforcer disappeared from the list (e.g., went offline), it auto-selected the **new first enforcer** instead of just clearing the selection. This caused the map to jump to a different enforcer without user action.

---

## What It Is Doing Now (The Fix)

1. **No auto-select on load:** When no enforcer is selected, nothing happens. The map stays at the default zoom level showing all enforcer markers. The user must explicitly click an enforcer (on the map or in the sidebar) to select them.

2. **Graceful disappearance:** If a selected enforcer disappears from the list, `state.selectedEnforcerId` is simply cleared (`null`). No replacement enforcer is auto-selected. The detail panel returns to the default "Select an enforcer" message, and the map stays at its current position.

---

## Reason for the Change

The auto-select behavior violated **user intent**: the tracking page should let the user freely observe all enforcer locations without forcing a selection. Auto-zooming to a specific enforcer without user action was disorienting, especially on page load where the user hasn't had a chance to interact yet.
