# LaunchPAD (Windows)

Batch files launch POWERSHELL script for LaunchPad
- Works with SSH-AGENT

## 2 methods for "git_address"

### 1. Github Personal Access Token (PAT)
Using PAT = true
```angular2html
  "TESTING_GIT_ADDRESS": "github.com/undoLogic/setupCase-core.git",
```
If you use PAT you must add to your LOCAL Windows computer using Powershell:
```powershell
[System.Environment]::SetEnvironmentVariable("GITHUB_PAT", "ghp_xxxxxxxxxxxxxx", "User")
```
To get your PAT, go to https://github.com/settings/tokens/new
- Add a note or label for this token eg "Laptop"
- Choose a date when expiry (eg until the end of the year)
- Choose REPO checkbox (which checks 5 sub checkboxes)
- Click at the bottom "Generate token"

Reboot your PowerShell and you can confirm it is working
```powershell
echo $env:GITHUB_PAT
```

### 2. SSH-Keys
OR with SSH-KEYS on the server (PAT = false)
```angular2html
  "TESTING_GIT_ADDRESS": "git@github.com:undoLogic/setupCase-core.git",
```
Then you must setup SSH keys on your server and add the public key to your GitHub account.

####  Setup SSH keys 
If you do not want to use a PAT, you can also add SSH keys for EACH project. To allow to export the GitHub source files to the server we must setup a public / private key. the PRIVATE key is ONLY on the server. the PUBLIC key goes onto Github -> deploy keys
1. Logon to your server via SSH using the PENDING credentials
- Use format in Settings: git@github.com:company/repo.git

NOTE: New servers you need to put your PUBLIC SSH KEY into the Control panel -> ssh keys -> import ssh key
ssh PENDING_USER @ PENDING_URL
```angular2html
ssh undologic@pending.undologic.com
```

2. First time only - Setup keys - This will create the private / public key (*.pub) in your .ssh directory (do NOT add a passphase).
```
cd ~/.ssh
ssh-keygen -t ed25519 -C "you@email.com"
cat id_ed25519.pub
```
3. Copy and paste the public key (ends with .pub) into the 'Deploy keys' on github.com (in your project)

4. You are now ready to run the LAUNCH script

IMPORTANT: The first time you need to manually run the script as it will require you confirm YES to the authenticity. When you run the script copy and paste the script manully into your favourite ssh program
```
cd launch
./2_setupPendingServer.sh
```







### GITHUB_HOST - Configure multiple projects on the same server

Github does NOT allow (for security) to add mutliple SSH-KEYS to the same server. In order to setup multiple projects on the same server you need to create separate github hostnames to reference each project.
- First create the new private/public file which will be used for this github project and we are specifying the "-f ..." so we won't overright our original key pairs
```
cd ~/.ssh
ssh-keygen -t ed25519 -C "you@email.com" -f id_ed25519_projectName1
chmod 600 id_ed25519_projectName1*

cat id_ed25519_projectName1.pub
```

- Now create or edit your ssh config file
```
  nano ~/.ssh/config
```
- Add your new public (ends with .PUB) you created above into the ssh config file
- Ensure the GITHUB_HOST matches the Host line (Hostname is ALWAYS github.com)
- project1 can be any name to represent your project

```
Host project1.github.com
        Hostname github.com
        IdentityFile ~/.ssh/id_ed25519_projectName1
Host project2.github.com
        Hostname github.com
        IdentityFile ~/.ssh/id_ed25519_projectName2
```

NOTE: You do NOT need to manually clone, but just so you understand how this works, and if you wanted to manually clone
Launch will automatically do this for you after you change 'GITHUB_HOST' in the launch/settings.json

```shell
git clone git@project1.github.com:OWNER/repo-project1.git
# OR
git clone git@project2.github.com:OWNER/repo-project2.git
```
