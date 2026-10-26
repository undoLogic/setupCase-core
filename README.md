# What is SetupCase?

**SetupCase is the foundation behind how we build software at undoLogic.**

Developed and refined through **years of real-world projects**, SetupCase provides a proven starting point for creating web applications without rebuilding the same foundation every time.

### SetupCase provides:

* **A proven application foundation** — start new projects from an established architecture instead of starting from scratch.
* **Built on CakePHP** — leveraging a mature PHP framework while adding our own conventions, structure and reusable components.
* **Consistent development patterns** — projects follow the same architecture and conventions, making them easier to understand and maintain.
* **Reusable functionality** — improvements developed for individual projects can be refined and pushed back into SetupCase Core.
* **Faster project development** — spend more time building what makes a project unique instead of rebuilding common infrastructure.
* **Long-term maintainability** — projects benefit from improvements made to the core architecture over time.

### Build → Improve → Contribute Back

SetupCase follows a simple philosophy:

**Start with SetupCase Core → Build the project → Improve the foundation → Push reusable improvements back to SetupCase Core → Use those improvements on future projects.**

This allows SetupCase to continuously evolve based on **real production software and real-world requirements**.

> **SetupCase is opinionated.** Not all feature requests will be accepted. The project roadmap and architectural direction are maintained by **undoLogic**.

## Disclaimer

> **Important: SetupCase Core is rough scaffolding, not a secured product.**
>
> SetupCase Core is a starting point for getting a basic CakePHP application up and running quickly. It is provided as is, without warranty of any kind, and makes no promise of security or fitness for production.
>
> Built-in safeguards are basic only. They are not meant to replace the detailed security review, testing and hardening you should do before launching your own project publicly. That work is your responsibility.

Maintaining SetupCase Core itself (moving improvements from `testFlight` to `main`)? See [Maintaining SetupCase Core](#maintaining-setupcase-core-testflight---main) at the end of this file.

## Creating a New Project from SetupCase Core

SetupCase Core is used as the starting point for new SetupCase projects.

The following steps create a new private GitHub repository from the SetupCase Core template, clone the project locally, start the Docker development environment, and initialize the application.

---

## 1. Create the GitHub Repository

Open the SetupCase Core repository:

[SetupCase-Core Repository](https://github.com/undoLogic/setupCase-core)

### Create a Repository from the Template

1. Click **Use this template**.
2. Select **Create a new repository**.

### Configure the New Repository

Use the following settings:

* **Include all branches:** **UNCHECK** this option.
* **Owner:** Choose your preferred owner eg `undoLogic`
* **Repository name:** Enter the name of the new project.
* **Visibility:** Select **Private**.

> **Important:** Do not select **Include all branches**. The new project should be created from the primary SetupCase Core template branch only.

Click **Create repository**.

The new project now has its own GitHub repository based on SetupCase Core.

---

## 2. Clone the Project in PhpStorm

The new repository can now be cloned to the local development environment using PhpStorm.

Clone the newly created repository and save it inside the existing local project directory used for SetupCase development.

The project should be stored inside the WSL/Ubuntu development environment or if you are working in Linux or Mac it's easier you can work in your normal files.

---

## 3. Open in Terminal

Open a terminal using the Ubuntu WSL environment
- Windows use Ubuntu WSL environment
- Mac/Linux use Terminal

Navigate to the newly cloned project directory.

For example:

```bash
cd /path/to/projects/new-project
```

Confirm that you are in the project's root directory before continuing.
---

## 4. Build the Docker Environment

Navigate into the SetupCase Docker directory 

Windows 
```bash
cd dockerWSL
```
OR Linux
```bash
cd dockerLinux
```

Run the SetupCase Docker build script:

```bash
./1buildDocker.sh
```

The script will build and start the Docker development environment required by the project.

Wait for the process to complete and confirm that there are no Docker errors.

At this point, the project's local Docker environment should be running.

---

## 5. Open the Local Website

Open a browser and navigate to:

http://localhost

Confirm that the local SetupCase project loads successfully.

---

## 6. Initialize the Project

Navigate to:

```text
http://localhost/init-web
```

Run:

```text
1-Install.php
```

Allow the installation process to complete.

### Verify Installation

Confirm that:

* The installation completes successfully.
* No PHP errors are displayed. 
* No CakePHP errors are displayed.
* No database or configuration errors are reported.

Do not continue until the initialization completes without errors.
- NOTE: Linux currently shows a wrong exit code when it does work successfully
---

## 7. Verify Source Files

Navigate to:

http://localhost/sourceFiles/en

You will be prompted for the SetupCase source-file password.

### Development Password

The password is the **numeric value of the current month**.

Examples:

| Month     | Password |
| --------- | -------: |
| January   |      `1` |
| February  |      `2` |
| March     |      `3` |
| April     |      `4` |
| May       |      `5` |
| June      |      `6` |
| July      |      `7` |
| August    |      `8` |
| September |      `9` |
| October   |     `10` |
| November  |     `11` |
| December  |     `12` |

Verify that the source-files interface loads correctly.

---

## Setup Complete

At this point:

* A dedicated private GitHub repository has been created from SetupCase Core.
* The repository has been cloned into the local WSL development environment.
* Docker has been built and started.
* SetupCase initialization has completed successfully.
* The source-files interface has been verified.

The project is now ready for project-specific configuration and development.

## NEXT

- Now that the project has been **initialized and all required files are set up**, you can switch back to Windows and continue development using our **Cloud development server**, which requires significantly less local overhead.
- Even when developing on Linux, the **Cloud server is the preferred environment for day-to-day development**. It avoids running unnecessary local Docker containers and makes switching between projects faster and easier.
- Using the Cloud environment means you **do not need to close, rebuild, or restart Docker containers** every time you move between projects.

> **Local Docker is primarily used for project setup and local testing when required. For normal development, use the Cloud development environment.**is primarily used for project setup and local testing when required. For normal development, use the Cloud development environment.

---

# Maintaining SetupCase Core (testFlight -> main)

For SetupCase Core maintainers only. This is the checklist. The full rules, reasoning and test scenarios are in `docs/features/SetupCase-Core-Platform-Build-and-CodeBlocks-Workflow.md`.

## Branches

| Branch | Contains `sourceFiles/`? | Role |
|---|---|---|
| `main` | No | The factory. GitHub template source for new projects. |
| `testFlight` | Yes | The running proof: factory files plus a built and extended app. Never used for customer work. |

- Never merge `testFlight` into `main` wholesale. `main` must never contain `sourceFiles/`.
- On `testFlight`, keep **feature commits** (touching `sourceFiles/`) separate from **factory commits** (touching `codeBlocks/`, `init-web/`, `docs/`, scripts).

## The Cycle

### 1. Build on testFlight

- Build and verify features directly in `sourceFiles/`.
- Write or update the feature file in `docs/features/` (see `AGENTS.md`).
- Commit with the `FEATURE: <feature-file>.md - <stage>` message convention. This is how the next step groups changes.

### 2. Update CodeBlocks (on testFlight)

Tell the AI coding tool: **`Update CodeBlocks`**.

- Starting point: the latest `Source commit` in `codeBlocks/cakePHP/4.x/changeLog.md`. Everything in `git log <source-commit>..HEAD -- sourceFiles/` is reviewed.
- The AI classifies every changed file as **sync** (copy into `codeBlocks/cakePHP/4.x/`), **transform** (an `init-web/9-*` script), **schema** (dated SQL), or **exclude** (project-specific, with the reason).
- It updates `init-web/8-Save-CodeBlocks.php`, adds a changelog entry with the new `Source commit`, and reports what was promoted and what was excluded.
- The AI never stages or commits. Review its changes yourself.

### 3. Rebuild Check

A promotion is only proven when a fresh build reproduces it. The AI copies `codeBlocks/`, `init-web/` and `AGENTS.md` into a temporary `.rebuild-check/` folder, runs `php init-web/1-Install.php` there inside the container as `www-data`, compares the result with the real `sourceFiles/`, then deletes the folder.

Every promoted change must reappear. If one does not, it only ever lived in `testFlight` and would be lost. Fix CodeBlocks before going on.

### 4. Commit on testFlight

Stage and commit the factory changes yourself (factory commit, separate from feature commits), then push.

### 5. Bring main-only edits back first

If anything was edited directly on `main` (in `codeBlocks/`, `init-web/`, `docs/`, `dockerLinux/`, `AGENTS.md` or `README.md`), copy it into `testFlight` and commit it there first. The import script stops and lists these files if you forget.

### 6. Import into main

1. Switch to `main`.
2. If a `sourceFiles/` folder is left behind (ignored files such as `tmp/` and `logs/`), confirm it matches `testFlight`, then delete it.
3. Run from the repo root:

   ```bash
   ./import_from_testflight.sh
   ```

   It overwrites `codeBlocks/`, `init-web/`, `docs/`, `dockerLinux/`, `AGENTS.md` and `README.md` with the `testFlight` versions, in the working tree only. It never imports `sourceFiles/` and it stages nothing. Other root files (for example `.gitignore`) are not imported; copy those by hand if needed.
4. Review the diff. Confirm no `sourceFiles/` is included.
5. Stage and commit yourself.

### 7. Check Before Every Push

- No client names, customer data, secrets, credentials or hosting details.
- No customer-specific journeys.
- Re-read `AGENTS.md` changes as well.

### 8. Back to testFlight

Switch back to `testFlight` and continue. The next `Update CodeBlocks` starts from the newest `Source commit` in the changelog.

## Adding New Concepts to SetupCase Core

SetupCase Core grows from what real projects need. When project work shows a concept that future projects would also benefit from, it is rewritten into SetupCase Core as a general foundation feature. Project code is never copied in.

1. Describe the concept in general terms: the problem it solves and the behaviour every project needs.
2. Build it fresh in `sourceFiles/` on `testFlight`, with its own feature file. Use only generic names and sample data. No project-specific names, data, credentials or business rules.
3. Let it travel to `main` through the normal cycle above (Update CodeBlocks, then import).
