<?php

$sourceFilesDir = dirname(__DIR__) . '/sourceFiles';
$githubDir = $sourceFilesDir . '/.github';
$workflowDir = $githubDir . '/workflows';
$workflowFile = $workflowDir . '/ci.yml';
$baselineDir = $sourceFilesDir . '/tests/TestCase/SetupCase';
$baselineFile = $baselineDir . '/SetupCaseBaselineTest.php';

if (!is_dir($sourceFilesDir)) {
    echo "ERROR - sourceFiles not found";
    exit;
}

function ensureDirExists(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function writeFileIfChanged(string $path, string $contents, string $label): void
{
    $exists = file_exists($path);
    $current = $exists ? file_get_contents($path) : false;

    if ($exists && $current === false) {
        echo "ERROR - Could not read {$label}<br/>";
        exit;
    }

    if ($current === $contents) {
        echo "{$label} already up to date — skipping<br/>";
        return;
    }

    file_put_contents($path, $contents);

    if ($exists) {
        echo "{$label} updated successfully<br/>";
        return;
    }

    echo "{$label} created successfully<br/>";
}

ensureDirExists($workflowDir);
ensureDirExists($baselineDir);

$workflowContents = <<<'YAML'
name: SetupCase CI

on:
  push:
    branches:
      - '**'
    paths-ignore:
      - '.ci_status.json'
  pull_request:
  workflow_dispatch:

permissions:
  contents: write

jobs:
  tests:
    runs-on: ubuntu-22.04

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Resolve CI branch
        id: ci_branch
        run: |
          if [ "${{ github.event_name }}" = "pull_request" ]; then
            echo "name=${{ github.head_ref }}" >> "$GITHUB_OUTPUT"
          else
            echo "name=${{ github.ref_name }}" >> "$GITHUB_OUTPUT"
          fi

      - name: Configure Git author
        if: github.event_name == 'push'
        run: |
          git config user.name "ci-bot"
          git config user.email "ci@undologic.com"

      - name: Write pending CI status
        run: |
          TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
          cat > .ci_status.json <<EOF_JSON
          {
            "status": "pending",
            "timestamp": "$TIMESTAMP",
            "commit": "${{ github.sha }}",
            "branch": "${{ steps.ci_branch.outputs.name }}",
            "environment": "github_actions_docker_wsl",
            "workflow": "${{ github.workflow }}"
          }
          EOF_JSON

      - name: Push pending CI status
        if: github.event_name == 'push'
        run: |
          git add .ci_status.json
          if git diff --cached --quiet; then
            exit 0
          fi
          git commit -m "CI status: pending for ${{ github.sha }}"
          git push origin HEAD:${{ github.ref_name }}

      - name: Start DockerWSL services
        run: docker compose -f dockerWSL/docker-compose.yml up -d --build

      - name: Wait for database
        run: |
          for attempt in $(seq 1 30); do
            if docker compose -f dockerWSL/docker-compose.yml exec -T db mysqladmin ping -h 127.0.0.1 -uroot -pundologic --silent; then
              exit 0
            fi
            sleep 2
          done
          echo "Database did not become ready in time"
          exit 1

      - name: Prepare test database
        run: docker compose -f dockerWSL/docker-compose.yml exec -T db mysql -uroot -pundologic -e "CREATE DATABASE IF NOT EXISTS test_automation"

      - name: Prepare app_local.php
        run: |
          if [ ! -f sourceFiles/config/app_local.php ] && [ -f sourceFiles/config/app_local.example.php ]; then
            cp sourceFiles/config/app_local.example.php sourceFiles/config/app_local.php
          fi

      - name: Install dependencies in container
        run: docker compose -f dockerWSL/docker-compose.yml exec -T web bash -lc "cd sourceFiles && composer install --no-interaction --prefer-dist"

      - name: Run PHPUnit in container
        id: phpunit
        run: |
          set +e
          docker compose -f dockerWSL/docker-compose.yml exec -T web bash -lc "cd sourceFiles && vendor/bin/phpunit --configuration phpunit.xml.dist"
          TEST_EXIT=$?
          echo "exit_code=$TEST_EXIT" >> "$GITHUB_OUTPUT"
          exit 0

      - name: Write final CI status
        if: always()
        run: |
          TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
          RESULT="fail"
          if [ "${{ steps.phpunit.outputs.exit_code }}" = "0" ]; then
            RESULT="success"
          fi
          cat > .ci_status.json <<EOF_JSON
          {
            "status": "$RESULT",
            "timestamp": "$TIMESTAMP",
            "commit": "${{ github.sha }}",
            "branch": "${{ steps.ci_branch.outputs.name }}",
            "environment": "github_actions_docker_wsl",
            "workflow": "${{ github.workflow }}"
          }
          EOF_JSON

      - name: Push final CI status
        if: always() && github.event_name == 'push'
        run: |
          git add .ci_status.json
          if git diff --cached --quiet; then
            exit 0
          fi
          git commit -m "CI status: final for ${{ github.sha }}"
          git push origin HEAD:${{ github.ref_name }}

      - name: Stop DockerWSL services
        if: always()
        run: docker compose -f dockerWSL/docker-compose.yml down -v

      - name: Fail workflow if tests failed
        if: always()
        run: |
          if [ "${{ steps.phpunit.outputs.exit_code }}" != "0" ]; then
            exit 1
          fi
YAML;

$baselineContents = <<<'PHP'
<?php
declare(strict_types=1);

namespace App\Test\TestCase\SetupCase;

use Cake\TestSuite\TestCase;

class SetupCaseBaselineTest extends TestCase
{
    public function testSetupCaseBaseline(): void
    {
        $this->fail('CI not configured yet');
    }
}
PHP;

writeFileIfChanged($workflowFile, $workflowContents . "\n", 'CI workflow');

if (!file_exists($baselineFile)) {
    writeFileIfChanged($baselineFile, $baselineContents . "\n", 'SetupCase baseline test');
} else {
    $baselineCurrent = file_get_contents($baselineFile);

    if ($baselineCurrent === false) {
        echo "ERROR - Could not read SetupCase baseline test<br/>";
        exit;
    }

    if (strpos($baselineCurrent, "CI not configured yet") !== false) {
        writeFileIfChanged($baselineFile, $baselineContents . "\n", 'SetupCase baseline test');
    } else {
        echo "SetupCase baseline test already customized — skipping<br/>";
    }
}
