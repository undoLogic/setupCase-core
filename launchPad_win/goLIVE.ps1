# Load configuration file

function Get-CiStatusInfo {
    param(
        [string]$StatusFilePath
    )

    $result = [ordered]@{
        FilePath  = $StatusFilePath
        Exists    = $false
        IsValid   = $false
        Status    = ""
        Commit    = ""
        Timestamp = ""
        Error     = ""
    }

    if (-not (Test-Path $StatusFilePath)) {
        return [pscustomobject]$result
    }

    $result.Exists = $true

    try {
        $statusJson = Get-Content $StatusFilePath -Raw | ConvertFrom-Json -ErrorAction Stop
    } catch {
        $result.Error = $_.Exception.Message
        return [pscustomobject]$result
    }

    $result.IsValid = $true
    $result.Status = [string]$statusJson.status
    $result.Commit = [string]$statusJson.commit
    $result.Timestamp = [string]$statusJson.timestamp

    return [pscustomobject]$result
}

# Get the current Git branch name
$branch = git rev-parse --abbrev-ref HEAD
if ($branch -eq "master") {
    $branch = "master"
} else {
    $branch = $branch
}
Write-Host "Current GIT branch: $branch"

$deployCommit = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($deployCommit)) {
    Write-Error "Unable to determine deploy commit from git."
    exit 1
}

$ciStatusFile = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot "..\.ci_status.json"))
$ciStatus = Get-CiStatusInfo -StatusFilePath $ciStatusFile

if ($ciStatus.Exists) {
    if (-not $ciStatus.IsValid) {
        Write-Error ".ci_status.json is invalid JSON"
        Write-Error "error: $($ciStatus.Error)"
        exit 1
    }

    $ciState = [string]$ciStatus.Status
    $ciCommit = [string]$ciStatus.Commit
    $ciTimestamp = [string]$ciStatus.Timestamp

    Write-Host "`n--- CI Gate ---"
    Write-Host "status: $ciState"
    Write-Host "commit: $ciCommit"
    Write-Host "timestamp: $ciTimestamp"
    Write-Host "deploy commit: $deployCommit"

    if ($ciState -ne "success") {
        Write-Error "Deployment blocked: CI status must be 'success'."
        exit 1
    }

    if ([string]::IsNullOrWhiteSpace($ciCommit) -or $ciCommit -ne $deployCommit) {
        Write-Error "Deployment blocked: CI commit does not match deploy commit."
        exit 1
    }

    Write-Host "CI gate passed for this commit."
}


$configFile = ".\config.json"
if (-not (Test-Path $configFile)) {
    Write-Error "Config file not found: $configFile"
    exit 1
}

$configRoot = Get-Content $configFile -Raw | ConvertFrom-Json
$config = $configRoot.environments

# Testing display
$configJson = Get-Content $configFile -Raw
Write-Host $configJson | Format-List *

# Extract values
$user = $config.pending.USER
$url = $config.LIVE.URL
$postCommands = $config.LIVE.POST_COMMANDS

$pendingURL = $config.pending.ABSOLUTE_PATH
$liveURL = $config.LIVE.ABSOLUTE_PATH

# Build the remote command
$remoteCommand = @"
rsync -av --no-perms --omit-dir-times --fake-super $pendingURL/$branch/sourceFiles/. $liveURL/. && cd $liveURL && $postCommands
"@

# Strip CRLF (Windows -> Linux)
$remoteCommandStripped = $remoteCommand -replace "`r`n", "`n"

Write-Host $remoteCommandStripped

# Execute remotely
Write-Host "ssh $user@$url" $remoteCommandStripped

Ssh "$user@$url" $remoteCommandStripped

Start-Process "https://$url"
