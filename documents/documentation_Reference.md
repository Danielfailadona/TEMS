# Change Documentation Template

Use this template whenever you make a change to the system. Fill in the sections
below, replacing the `<placeholders>`. Keep it plain markdown. Required sections
are marked `(required)`; put "—" or "N/A" where an optional section does not
apply.

---

## <Short Change Title>

### Date Edited / Applied `(required)`

When the change was made and applied (e.g. `YYYY-MM-DD` or a full date).

```
Example:
- Edited and applied: 2026-08-11
```

### Type of Change `(required)`

One line: bug fix / feature / refactor / dependency / other.

```
Example:
- Bug fix
```

### Requested By / Source `(optional)`

Who reported or requested the change and where it was observed (URL, page,
element).

```
Example:
- Reported by <user> at /teams/2/edit
```

### Problem `(required)`

What was wrong or missing. Include where it was observed (URL, page, element) and
the user-visible symptom.

```
Example:
- The Team Members checklist showed all members at once, causing a long scroll.
- Location: /teams/2/edit → Team Members card → Members list
```

### Root Cause `(required)`

Why the problem existed (if known). For bugs: what code/behavior caused it.

### Files Changed `(required)`

List every file touched (full path from repo root), one per bullet.

- `path/to/file1.php`
- `resources/views/example/index.blade.php`

### What Parts Changed `(required)`

For each file, describe which part changed and the before/after. Use code blocks.

#### <file 1>

**Before:**

```php
<old code>
```

**After:**

```php
<new code>
```

### Behavior of the New Changes `(required)`

Describe how the system acts now vs before. Use a table with "Before" and "After".

| Scenario | Before | After |
|---|---|---|
| <scenario 1> | <old behavior> | <new behavior> |
| <scenario 2> | <old behavior> | <new behavior> |

### Impact & Risk `(required)`

Which pages, roles, or features are affected by this change and what could break
(regression risk).

```
Example:
- Affects: /teams/2/edit, teams/create, zones/edit, zones/create maps.
- Risk: other pages using zone-picker.js should be re-checked after the change.
```

### Database / Migration Impact `(optional)`

Whether the change touches the schema, seeders, or migrations. "None" is a valid
entry.

```
Example:
- None
```

### Untouched `(optional)`

List parts of the system intentionally left unchanged (e.g., controllers, models,
routes, validation) so reviewers know the change is isolated.

### Known Issues / Follow-ups `(optional)`

Anything still open, unexpected behavior to be aware of, or future work related
to this change.

```
Example:
- Browsers may cache old JS/CSS; a hard reload (Ctrl+F5) is needed to see the fix.
```

### Testing / Verification `(required)`

How the change was verified (commands run, pages visited, edge cases checked).

- <command or check 1>
- <command or check 2>
