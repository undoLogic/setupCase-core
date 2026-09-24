# SetupCase Core Platform Build and CodeBlocks Update Workflow

## Purpose

SetupCase Core is an **opinionated, deterministic application foundation** for quickly starting new software projects from a known working baseline.

AI may help design, improve, document, test, and maintain SetupCase Core, but **AI must not dynamically generate the initial project foundation**.

> **AI helps build and maintain the factory. The factory deterministically builds the project.**

This feature defines how SetupCase Core organizes platform initialization, reusable CodeBlocks, TestFlight development, and the AI-assisted process for propagating proven improvements back into the reusable baseline.

---

# Core Principles

## Deterministic Project Creation

New projects must be created using version-controlled scripts and known source files.

The initial project build must:

- be repeatable
- produce a known baseline
- use tested SetupCase Core assets
- not depend on AI deciding how the project should be assembled
- remain understandable and executable without AI

AI may create or improve deterministic scripts. Once reviewed and committed to SetupCase Core, those scripts become part of the authoritative build procedure.

## Opinionated Platforms

SetupCase Core intentionally chooses a preferred technology for each supported platform.

| Platform | Current Technology | Status |
|---|---|---|
| Web | CakePHP / PHP | Primary focus |
| Mobile | To be finalized | Experimental / future |
| Desktop | Python | Experimental / future |

These choices may evolve. SetupCase should not attempt to support every framework simultaneously.

---

# Repository Organization

The repository should progressively move toward clear platform separation.

Conceptually:

```text
/
├── Web/
│   └── CakePHP/
├── Mobile/
├── Desktop/
├── CodeBlocks/
├── Docker/
├── Launchpad-Linux/
├── Launchpad-Windows/
├── sourceFiles/
└── docs/
    └── features/
```

Exact names and migration details should be reviewed against the existing repository before files are moved.

Do not reorganize working infrastructure merely for cosmetic consistency.

## Docker

Docker remains a shared root-level infrastructure concern because it may support web, mobile, desktop, testing, or supporting services.

Existing Docker structures such as Linux, WSL, or legacy Windows tooling should not be reorganized unless there is a practical reason.

## Launchpad

Platform-specific developer launch tooling may remain separate.

Current examples:

```text
Launchpad-Linux
Launchpad-Windows
```

A macOS implementation may be added later if needed. Avoid unnecessary abstraction while these implementations remain small and stable.

---

# CodeBlocks

`CodeBlocks` contains reusable, proven SetupCase implementation assets.

Keep CodeBlocks cross-platform at the root, with technology/version-specific implementations underneath it.

Example:

```text
CodeBlocks/
└── CakePHP/
    └── 4.x/
        ├── README.md
        ├── changelog-2026.md
        ├── scripts/
        └── ...
```

Future CodeBlocks may support other web frameworks, mobile platforms, or desktop technologies.

## Version Meaning

A version-specific CodeBlocks directory means its contents and setup procedures have been tested against that platform/version.

For example:

```text
CodeBlocks/CakePHP/4.x/
```

means the contained CodeBlocks and associated procedures are intended and tested for CakePHP 4.x.

Platform-specific initialization and transformation scripts should live with the CodeBlocks version they support whenever practical.

This keeps reusable files, required transformations, setup procedures, documentation, and history together.

---

# CodeBlock Installation Strategy

Prefer deterministic file synchronization whenever possible.

Reusable assets may be copied or `rsync`-ed directly into the generated project when SetupCase fully controls those files.

Examples include:

- utility classes
- SetupCase-specific components
- templates
- reusable application files

Not every integration can safely be performed through file replacement. Some features require modifying framework-generated or project-specific files.

For those cases:

- use deterministic scripts
- make the transformation explicit
- keep the script with the appropriate platform/version
- avoid asking AI to modify the generated project during initial installation

The installation model is therefore:

```text
Known files + deterministic synchronization + deterministic transformation scripts
```

---

# Generated Source

After initialization, the generated application source remains in its established project source directory.

For the current web implementation:

```text
sourceFiles/
```

Future mobile or desktop implementations should use separate source directories rather than mixing unrelated application types into `sourceFiles`.

---

# TestFlight

SetupCase uses TestFlight as a **working proof of the reusable baseline**.

> Do not provide only examples or theoretical snippets. Maintain a real, running implementation that proves the SetupCase baseline works.

Typical workflow:

1. Start from SetupCase Core.
2. Run the deterministic initialization process.
3. Generate `sourceFiles`.
4. Create/use the `TestFlight` branch.
5. Commit the generated working application.
6. Deploy it to the SetupCase TestFlight environment.
7. Develop and verify improvements against the running application.

TestFlight is allowed to evolve quickly.

The developer should be able to implement and test a feature there without first manually reconstructing its reusable CodeBlock representation.

---

# Development Flow

```text
SetupCase Core
      ↓
Deterministic Build
      ↓
TestFlight Working Application
      ↓
Develop / Test New Feature
      ↓
Verify Feature Works
      ↓
AI-Assisted "Update CodeBlocks"
      ↓
Reusable CodeBlocks + Scripts + Documentation
      ↓
Review
      ↓
Merge back into Main
      ↓
Future Deterministic Builds Include Improvement
```

This separates two concerns.

### Building a new project

**Deterministic.**

Known scripts and CodeBlocks build the project.

### Improving SetupCase Core

**AI-assisted.**

AI may analyze a proven TestFlight implementation and determine how that improvement should be represented in reusable CodeBlocks and deterministic installation scripts.

---

# Update CodeBlocks

`Update CodeBlocks` is a standard SetupCase Core maintenance operation.

When the user tells an AI coding tool:

```text
Update CodeBlocks
```

the tool should interpret this as a request to propagate relevant proven changes from the working project back into the reusable SetupCase Core baseline.

## Safety Check

Before performing an Update CodeBlocks operation, confirm that the repository contains:

```text
CodeBlocks/
```

If it does not exist, stop and warn that the current repository may not be SetupCase Core or may not support this workflow.

Do not invent a CodeBlocks structure inside an unrelated project.

## AI Responsibilities

During `Update CodeBlocks`, AI should:

1. Inspect the relevant recent Git history.
2. Inspect the actual code changes associated with that history.
3. Identify which changes represent reusable SetupCase improvements.
4. Group related commits and file changes into logical features or improvements.
5. Determine whether each reusable change belongs as:
   - a directly synchronized CodeBlock
   - a deterministic transformation/setup script
   - both
6. Update the appropriate platform/version CodeBlocks.
7. Update associated deterministic installation scripts when required.
8. Update the appropriate yearly changelog.
9. Update relevant documentation.
10. Report what changed and anything intentionally excluded.

AI must not blindly copy every changed project file into CodeBlocks.

The goal is to extract the **reusable implementation** from the proven working TestFlight project.

---

# Git History as Change Evidence

Git history should help determine what changed since the previous CodeBlocks update.

AI should inspect:

- commit dates
- commit messages
- diffs
- affected files
- existing CodeBlocks
- existing changelog entries

Git history provides evidence of the development sequence, but **commit boundaries do not define changelog boundaries**.

---

# Changelog

Each platform/version maintains its own yearly changelog.

Example:

```text
CodeBlocks/
└── CakePHP/
    └── 4.x/
        ├── changelog-2026.md
        ├── changelog-2027.md
        └── ...
```

Naming convention:

```text
changelog-YYYY.md
```

Start a new yearly file when the calendar year changes. Preserve historical changelogs.

## Changelog Grouping

Do **not** create one changelog entry per Git commit.

A feature or improvement may span several commits.

AI should use Git history and diffs to infer logical groups. For example, several commits involving middleware, controllers, route handling, tests, and documentation may collectively represent one feature such as:

```text
Stateless Asset Requests
```

That should normally become one meaningful changelog entry rather than several implementation-level entries.

The changelog describes **what changed in SetupCase**, not raw Git history.

## Changelog Dates

Use relevant Git commit dates as evidence when determining the date of an entry.

When a logical change spans multiple commits, use the most appropriate date based on the grouped work, normally the date the change reached its completed/reusable state.

Do not fabricate dates.

## Suggested Changelog Entry

```markdown
## 2026-09-24 - Stateless Asset Requests

Added reusable support for explicitly stateless application routes so high-concurrency public assets can bypass unnecessary PHP session handling.

### Included

- Added reusable stateless route detection.
- Added middleware bypass support.
- Updated authentication and language handling for stateless routes.
- Added deterministic SetupCase installation/update handling.
```

The exact format may evolve, but entries should remain concise and human-readable.

---

# CodeBlocks README

Each major platform/version should contain a README explaining how its reusable baseline works.

Example:

```text
CodeBlocks/CakePHP/4.x/README.md
```

The README should explain:

- what the CodeBlocks directory represents
- supported framework/version
- how reusable files are applied
- where deterministic transformation scripts live
- how TestFlight relates to CodeBlocks
- how `Update CodeBlocks` works
- how changelogs are maintained
- important platform-specific limitations

The README explains the system rather than duplicating every feature specification.

Detailed feature behaviour remains in the relevant feature files.

---

# AGENTS.md Integration

The root `AGENTS.md` should contain a concise `Update CodeBlocks` rule so AI coding tools recognize the workflow across SetupCase-based projects.

Suggested guidance:

```markdown
## Update SetupCase Core / CodeBlocks

When the user asks to `Update CodeBlocks`:

- Treat the working implementation and recent Git history as evidence of what changed.
- Locate the appropriate reusable implementation under `CodeBlocks/`.
- If `CodeBlocks/` does not exist, stop and confirm this is the correct SetupCase Core repository.
- Group related commits into logical features; do not create one changelog entry per commit.
- Propagate reusable files into the appropriate platform/version CodeBlocks.
- Update deterministic setup/transformation scripts when direct file synchronization is insufficient.
- Update `changelog-YYYY.md` using Git dates and human-readable feature groupings.
- Update relevant feature documentation when behaviour has changed.
- Do not make new-project creation dependent on AI. SetupCase initialization must remain deterministic.
```

The detailed workflow belongs in CodeBlocks documentation and this feature specification.

`AGENTS.md` should provide enough context for AI to discover and follow that documentation without becoming unnecessarily large.

---

# AI Boundary

AI is intentionally used in the maintenance workflow because identifying reusable changes from a working project may require contextual reasoning.

AI may:

- analyze Git changes
- group related commits
- identify reusable portions of project code
- update CodeBlocks
- create or improve deterministic scripts
- update feature documentation
- update changelogs
- identify missing files or dependencies
- propose improvements

AI must not become a runtime dependency of the initial project build.

The architecture is:

```text
AI-assisted maintenance
        ↓
Reviewed deterministic baseline
        ↓
Version-controlled scripts + CodeBlocks
        ↓
Deterministic project creation
```

---

# Testing

## Deterministic Build

**Intent:** Ensure a new SetupCase project can be created from the maintained baseline without requiring AI decisions during the build.

**Surfaces:**
- platform initialization scripts
- `CodeBlocks/`
- platform/version scripts
- generated source directories

### Scenarios

- [ ] A supported platform can be initialized using version-controlled scripts without AI.
- [ ] Re-running the same supported initialization procedure produces the expected baseline.
- [ ] Files suitable for direct synchronization are sourced from the appropriate CodeBlocks platform/version.
- [ ] Framework files requiring modification are handled by deterministic scripts rather than ad-hoc AI edits.

## Update CodeBlocks

**Intent:** Ensure proven TestFlight improvements can be efficiently propagated back into the reusable SetupCase baseline.

**Surfaces:**
- Git history
- TestFlight implementation
- `CodeBlocks/`
- platform/version scripts
- platform/version README
- yearly changelog
- feature documentation

### Scenarios

- [ ] `Update CodeBlocks` verifies that the expected CodeBlocks structure exists.
- [ ] Recent Git history is inspected to identify changes since the previous reusable update.
- [ ] Related commits are grouped into logical changes rather than treated as individual changelog entries.
- [ ] Reusable files are propagated to the correct platform/version.
- [ ] Changes that cannot be directly synchronized are represented by deterministic scripts.
- [ ] Project-specific changes that do not belong in SetupCase Core are excluded.
- [ ] The current `changelog-YYYY.md` is updated using dates supported by Git history.
- [ ] Existing historical changelog files are preserved.
- [ ] Relevant feature documentation is updated when behaviour changes.
- [ ] The resulting baseline remains deterministic after the AI-assisted update.

---

# Completion Criteria

This workflow is established when:

- platform initialization is clearly separated from reusable CodeBlocks
- CakePHP 4.x has a defined CodeBlocks platform/version location
- platform-specific setup scripts live with the baseline they support
- TestFlight remains the working proof of the baseline
- `Update CodeBlocks` is documented as a standard AI-assisted operation
- each platform/version has a README
- each platform/version maintains `changelog-YYYY.md`
- `AGENTS.md` contains a concise reference to the Update CodeBlocks workflow
- new-project initialization remains fully deterministic
- AI-assisted maintenance can improve the deterministic baseline without becoming part of the build dependency

---

# Architectural Principle

> **Use AI to improve the factory, not to replace the factory.**

SetupCase Core should harness AI where reasoning provides value while converting those improvements into transparent, version-controlled, deterministic procedures that can be executed repeatedly without AI.
