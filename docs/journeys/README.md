# Journeys

A **journey** is a named, ordered flow that a real person walks through the product — "submit a quote", "design a garment", "reset a password".

Features describe what the software *can* do. Journeys describe the *path* a person takes through it.

Each journey file is both:

1. **The spec we discuss** — readable markdown you can paste into a conversation, rework, and paste back.
2. **The executable source** — the runner walks it to capture screenshots, and the PDF builder renders it into a client-facing walkthrough.

One file, two outputs. Change the file, regenerate, send the client the new PDF. They review the flow without having to test it themselves.

---

## Folder layout

```
docs/journeys/
├── README.md            ← this file (the spec)
├── submit-quote.md      ← one file per journey
├── design-garment.md
└── ...
```

- One journey per file.
- File names are kebab-case and describe the flow: `submit-quote.md`, not `journey1.md`.

---

## File format

A journey file has YAML front matter, followed by one `##` heading per step.

```markdown
---
name: Submit a quote
description: How a customer turns a finished design into a quote request.
start_url: /
---

## Open the designer

- action: goto
- target: /designer
- expect: The design canvas is visible
- caption: Start from the designer, where every order begins.

## Choose a garment

- action: click
- target: [data-testid="garment-hoodie"]
- expect: The hoodie template loads on the canvas
- caption: Pick the base garment. Everything else builds on this choice.
```

### Front matter

| Field         | Required | Notes                                              |
|---------------|----------|----------------------------------------------------|
| `name`        | yes      | Human-readable journey name. Used as the PDF title. |
| `description` | yes      | One sentence. Appears on the PDF cover.            |
| `start_url`   | yes      | Where the runner begins, relative to the base URL. |

### Step fields

Every step has the same fields, in the same order. Never skip one.

| Field     | Purpose                                                        |
|-----------|----------------------------------------------------------------|
| heading   | The step **title**, shown above the screenshot.                 |
| `action`  | What the runner does: `goto`, `click`, `fill`, `select`, `upload`, `wait`. |
| `target`  | A URL (for `goto`) or a selector. Prefer `data-testid`.         |
| `value`   | Only for `fill`, `select`, `upload`. The text, option, or file. |
| `expect`  | What must be true after the action. The runner checks this before capturing. |
| `caption` | The explanation shown below the screenshot.                     |

The runner performs the action, confirms the expectation, then captures **one screenshot per step**.

---

## Writing rules

These apply to humans and AI agents alike.

**Step numbers are never stored.** Order in the file *is* the order. The PDF builder counts steps at render time and prints "Step 3 of 12". Inserting or removing a step renumbers everything automatically — do not write numbers into headings or captions.

**Titles are short.** Four or five words, 40 characters max. Say *where you are*, not what happens: "Review the quote", not "The customer now reviews their quote before sending".

**Captions are hard-capped at 140 characters.** That is two lines on the page and no more. If you can't explain a step in 140 characters, the step is doing too much — split it into two steps.

**Write for the client, not the developer.** Plain language, present tense, no selectors, no component names, no jargon. The PDF goes to people who will never see the code.

**AI drafts, humans refine.** Agents may generate titles and captions. Once a human has edited one, treat the wording as intentional and don't rewrite it unless asked.

---

## Screenshots

- Browser viewport: **1920 × 1080**.
- Captured at **16:9**, uncropped and unannotated.
- Nothing is drawn over the screenshot. The image stays clean so nothing important is ever covered.

---

## PDF layout

- **Landscape A4**, one step per page.
- **Above** the image: `Step N of M` and the step title.
- **Middle**: the 16:9 screenshot.
- **Below** the image: the caption, as a band beneath the picture — never overlaid on it.

Titles and captions are real PDF text, not pixels. They stay sharp at any zoom, are searchable, and can be reworded and re-rendered **without re-running the browser**.

Page numbers match step numbers, so "page 4 looks wrong" means the same thing to you and the client.

---

## Out of scope (for now)

- Arrows, highlight boxes, or any drawing on the screenshot. The target is already known per step, so this is easy to add later — but captions come first.
- Multiple screenshots per step.
- Branching journeys. If a flow branches, write two journeys.

---

## Checklist for creating a journey

1. Create `docs/journeys/<flow-name>.md`.
2. Add front matter: `name`, `description`, `start_url`.
3. Add one `##` step per screen the person sees, with every step field filled in.
4. Keep titles ≤ 40 characters and captions ≤ 140 characters.
5. Don't write step numbers anywhere.
6. Regenerate screenshots and the PDF, then review the PDF page by page.