# Software Timeline

A per-project record of how the software has grown, one section per calendar quarter. The client sees only the polished summary of completed work; the raw git history stays in the master file.

## Files

| File | What it holds | Deployed |
|---|---|---|
| `docs/timeline/<project>-software-timeline.md` | Master: every section below. Written and edited by hand. | No |
| `sourceFiles/resources/timeline/software-timeline.md` | Public copy: the Platform Overview and the quarters' Public Summary lines only. | Yes (not web-reachable) |
| `docs/timeline/build-public.sh` | Builds the public copy from the master. | No |

Run `docs/timeline/build-public.sh` after every change to the master, then commit both files. Never edit the public copy by hand.

## Format (per quarter)

````
## 2024 Q3

### Raw Git Context

```text
areas: src/Model/Table (24), templates/element (24), ...
2024-07-03 | Author Name | commit subject
...
```

### Public Summary

- Polished, client-readable line about completed work
- Another line
````

- Quarters are newest first, with headings exactly `## YYYY Q1` to `## YYYY Q4`.
- Raw Git Context is a fenced `text` block: a line of the most-touched areas, then one line per commit (`date | author | subject`), oldest first.
- Public Summary is a plain bullet list, one line per item. No HTML, no links, no Markdown inside the line.
- A project may add other `###` sections (for example private notes). The page and the copy step read only `## YYYY Qn` headings and the `### Public Summary` bullets, so any other section is never shown.

## Platform Overview

One `## Platform Overview` section sits above the quarters. It is the first thing the client reads: what the platform does, its major systems, its capabilities, how it is developed, and how quality is looked after. The quarters below it are the evidence.

````
## Platform Overview

### At a Glance
- Plain sentence

### Major Systems
- System Name: what it does, in business terms

### Platform Capabilities
- Capability Name: what it means for the business

### Platform Lifecycle
- Plain sentence

### Testing & Quality
- Practice Name: what is done
- Closing plain sentence
````

- Only these five `###` sections are shown, always in this order. Any other `###` section inside the overview is ignored by the page and the copy step.
- In Major Systems, Platform Capabilities and Testing & Quality, a line is `Name: description` (name up to 60 characters). A line without `: ` shows as plain text.
- Every system, capability and quality practice must be supported by at least one quarter's Public Summary. No buzzwords that the history does not show.
- Write for a business reader: say what it does for the business first, technical terms only where they add meaning.
- Lifecycle: a mature production platform under continuous development. Never "alpha" or "beta", and never used to excuse defects.
- Testing & Quality: only practices the timeline documents, ending with a line that automated test coverage can keep growing with the platform.
- AI writes or revises the overview only when asked. Review it at each quarterly update: add a system or capability when a new quarter supports it.

## Rules

- Granularity is calendar quarter only. Never per-commit or per-month detail in the Public Summary.
- Raw Git Context is always kept, never deleted, never shown to customers.
- AI writes a Public Summary from the quarter's Raw Git Context ONLY when the Public Summary is empty. Once it has content, AI never regenerates or edits it.
- A person may edit any Public Summary by hand and reviews the wording before committing (the Git commit is the review step).
- Updates run only when asked (quarterly routine), never as a side task during unrelated work.
- A quarter is added only once it has ended.

## Writing the Public Summary

- Each line is something completed, in client terms: a feature, report, screen, integration, upgrade or fix. Nothing planned or half done.
- Include infrastructure and operations work too: hosting and servers, domains, storage, email delivery, backups, security and platform upgrades.
- Every line must be true and checkable against the work.
- No dollar amounts, hours, invoice numbers or billing wording.

## Quarterly update

1. Add `## YYYY Qn` for the quarter that just ended, at the top of the quarters.
2. Fill Raw Git Context from `git log --reverse --date=short --format='%ad | %an | %s'` for that quarter.
3. Write the Public Summary from the Raw Git Context.
4. Check whether the Platform Overview needs a new system or capability; update it only if the new quarter supports it.
5. Run `docs/timeline/build-public.sh`, review, commit.
