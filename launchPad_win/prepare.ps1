param(
    [string]$envName = "dev"  # default if no argument provided
)

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

###

# Load configuration file
$configFile = ".\config.json"
if (-not (Test-Path $configFile)) {
    Write-Error "Config file not found: $configFile"
    exit 1
}

# Read JSON and select environment
$configRoot = Get-Content $configFile -Raw | ConvertFrom-Json
if (-not $configRoot.environments.PSObject.Properties.Name -contains $envName) {
    Write-Error "Environment '$envName' not found in settings.json"
    exit 1
}

$config = $configRoot.environments.$envName

# Extract values
$user = $config.USER
$url = $config.URL
$git = $config.GIT_ADDRESS
$path = $config.ABSOLUTE_PATH
$postCommands = $config.POST_COMMANDS
$usePAT = $config.USE_PAT
$postCommandSuffix = ""

if (-not [string]::IsNullOrWhiteSpace($postCommands)) {
    $postCommandSuffix = " &&`n$postCommands"
}

if ($usePAT -eq $true) {

    $pat = $env:GITHUB_PAT

    if ([string]::IsNullOrWhiteSpace($pat)) {
        Write-Host ""
        Write-Host "ERROR: GITHUB_PAT is not set on your machine." -ForegroundColor Red
        Write-Host ""
        Write-Host "Please set your personal PAT using the following PowerShell command:" -ForegroundColor Yellow
        Write-Host ""
        Write-Host '[System.Environment]::SetEnvironmentVariable("GITHUB_PAT", "ghp_xxxxxxxxxxxxxx", "User")' -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Then restart your PowerShell session." -ForegroundColor Yellow
        Write-Host ""
        exit 1
    }

    # Build the remote command
    $remoteCommand = @"
PAT='$pat' &&
cd $path &&
rm -rf * &&
rm -rf $branch &&
git clone `"https://\$PAT@$git`" --branch $branch --single-branch $path/$branch &&
rsync -a --info=progress2,stats --no-perms --omit-dir-times --fake-super $path/$branch/sourceFiles/ .$postCommandSuffix
"@

} else {
    # Build the remote command
    $remoteCommand = @"
cd $path &&
rm -rf * &&
rm -rf $branch &&
git clone $git --branch $branch --single-branch $path/$branch &&
rsync -a --info=progress2,stats --no-perms --omit-dir-times --fake-super $path/$branch/sourceFiles/ .$postCommandSuffix
"@
}






# Strip CRLF (Windows -> Linux)
$remoteCommandStripped = $remoteCommand -replace "`r`n", "`n"

Write-Host "`n--- Environment: $envName ---`n"
Write-Host $remoteCommandStripped

# Execute remotely
ssh "$user@$url" $remoteCommandStripped

# Open browser
Start-Process "https://$url"





if ($envName -eq "dev" -or $envName -eq "pending") {
    $ciStatusFile = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot "..\.ci_status.json"))
    $ciStatus = Get-CiStatusInfo -StatusFilePath $ciStatusFile

    if ($ciStatus.Exists) {
        Write-Host "`n--- CI Status ---"
        if (-not $ciStatus.IsValid) {
            Write-Host ".ci_status.json is invalid JSON"
            Write-Host "error: $($ciStatus.Error)"
        } else {
            Write-Host "status: $($ciStatus.Status)"
            Write-Host "commit: $($ciStatus.Commit)"
            Write-Host "timestamp: $($ciStatus.Timestamp)"
        }
    }
}
