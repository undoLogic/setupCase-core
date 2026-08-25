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

The project should be stored inside the WSL/Ubuntu development environment.

---

## 3. Open the WSL Terminal

Open a terminal using the Ubuntu WSL environment.

Navigate to the newly cloned project directory.

For example:

```bash
cd /path/to/projects/new-project
```

Confirm that you are in the project's root directory before continuing.

---

## 4. Build the Docker Environment

Navigate into the SetupCase Docker WSL directory:

```bash
cd dockerWSL
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
