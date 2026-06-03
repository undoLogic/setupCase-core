# LaunchPad
Launch allows to efficiently deploy your GITHUB projects to testing, pending (staging) and LIVE servers. 
Our technology is very simple and dependable. We uses basic SSH commands to login to the server, prepare the sourcefiles without extra libraries or complex installations.

## Index
1. [ How it works](#how-it-works)
2. [ Configuring settings ](#configuring)
3. [ Using PAT (Github personal access token) ](#pat---personal-access-tokens)
3. [ Use SSH Keys (instead of PAT token) ](#setup-ssh-keys-optional)
3. [ Upgrade from previous versions ](#upgrade-from-previous-version)

## How it works:
- Run the local script for your environment DEV, PENDING and LIVE
- Launch will logon to your target server (your SSH passphrase adds extra security)
- All the source files from your GitHub account will be exported to the testing and/or staging locations
- You must create a Personal Access Token (in github). This file is NOT stored in your sourcFiles instead it is saved to a PHP.ini file on your server
- Alternatively you can setup SSH keys allowing your server to access the files from github ongoing without an expiring PAT token
- This allows you to test and verify all the changes before going LIVE
- After you are satisfied all changes and database changes have been completed you can 'PushLIVE' - which copies all the files to the LIVE location on your target server
- You first need to adjust your 'launch/settings.json' file to match your target servers

### Testing Server
This is an optional server designed to be a URL which you can test that does NOT share the database with the live production server
- Allows to preview new features to clients or colleagues without affecting LIVE production database
- Allow to use for DEV Environment and NOT use Docker (can be faster since Windows is slow for docker). Using SFTP all changed files are uploaded to the testing server automatically after initial files are launched
- Simply access the testing URL and preview your changes
- See below how to [Configure your testing server as your DEV environment](#configure-testing-server-as-dev-environment)

### Pending Server (Staging)
This is designed to be a URL that shares the database with the LIVE production. 
- This allows to verify all your changes before deploying to LIVE.
- The database can be modified and verified before going LIVE
- When you are satisfied with all new changes the goLIVE script will rsync these files from pending to the LIVE absolute folders

### Live Server
Live server must be on the same server as PENDING (different absolute paths)
- When you are happy with your PENDING server all files are rsync (copied) to the live absolute path

## Configuring
Launch needs to be configured for your target server as well as your github account.

### PAT - Personal Access Tokens
We use PAT to authenticate with GIT hub to export your files to your server
This is ideal as you can export all the projects you have access to and you do NOT need to setup ssh keys for each project anymore
- Github - Settings - Developer Settings - Personal Access Tokens - Tokens (Classic)
- Generate new token - Generate new token (classic)
- At least choose 'Repo' (checkbox) - Generate token
- Copy token and save to your server
- Simple add to your php.ini file on the server
- Use github format in settings: github.com/company/repo.git
- Do Not use subdomain.github.com

```php
PAT = 123456skdjflkdsj43094
```

### Configure Settings.json
- First time rename your new.settings.json to settings.json
- Future upgrades will not overwirte your existing settings.json file
- This file keeps all server information WITHOUT any private credentials

TESTING_URL
- This requires you have CREATED a 'Subdomain' on your control panel
- If your subdomain is 'test' then you would access your site with http://test.YourDomain.com / http://pending.servername.com
- ONLY add the url WITHOUT 'http://'
```angular2html
"TESTING_URL": "test.domain.com",
```

TESTING_USER
- This is the username in your control panel of your testing server
- Top LEFT in the 'hosting account' box you will see 'Username:'
```
"TESTING_USER": "username",
```

TESTING_GIT_ADDRESS:
- This is the path starting with github.com to your git repo
- https and the PAT will be added to this link when you run
- Ensure you add your PAT to the server php.ini as 
- PAT = 123456789...
- This will allow to not include any secrets in these source files
```angular2html
  "TESTING_GIT_ADDRESS": "github.com/undoLogic/setupCase-core.git",
```
OR with SSH-KEYS on the server
```angular2html
  "TESTING_GIT_ADDRESS": "git@github.com:undoLogic/setupCase-core.git",
```

TESTING_ABSOLUTE_PATH
- This is the path on your testing server to the location where the source files will be uploaded
- Navigate (on the control panel) to "File Manager" -> "WWW"
- the Path is located next to 'Location: ' for example "/home/username/www/test"
```angular2html
"TESTING_ABSOLUTE_PATH": "/home/undoweb/www/projectname",
```

TESTING_COPY_SRC_TO_ROOT
- copy all the files in the sourceFiles directory to the root of the folder
- false will leave all the files in the sub-folder sourceFiles and the current branch (eg test.domain.com/main/sourceFiles)
- true will copy them all to the root
   - This is important when you deal with authentication and your auth requires the login page be on the root of your sub-domain
```angular2html
  "TESTING_COPY_SRC_TO_ROOT": false
```

TESTING_POST_COMMANDS
- Allows to add a string command to run after the files have been succeffully deployed
- Eg composer required rubix/ml
```angular2html
  "TESTING_POST_COMMANDS": "composer require rubix/ml"
```


PENDING_URL
- This requires you have CREATED a 'Subdomain' on your control panel
- If your subdomain is 'PENDING' then you would access your site with http://pending.YourDomain.com / http://pending.servername.com
- ONLY add the url WITHOUT 'http://'
```angular2html
"PENDING_URL": "pending.undologic.com",
```

PENDING_USER
- This is the username in your control panel
- Top LEFT in the 'hosting account' box you will see 'Username:'
```
"PENDING_USER": "username",
```

PENDING_GIT_ADDRESS:
- This is the path starting with github.com to your git repo
- The PAT will be added to your link when you run this script
- ensure you have added PAT = 123 on your php.ini file on your server
```angular2html
  "PENDING_GIT_ADDRESS": "https://github.com/undoLogic/setupCase-core.git",
```
OR with SSH-KEYS on the server
```angular2html
  "PENDING_GIT_ADDRESS": "git@github.com:undoLogic/setupCase-core.git",
```

PENDING_ABSOLUTE_PATH
- This is the path on your server to the location where the source files will be uploaded
- Navigate (on the control panel) to "File Manager" -> "WWW"
- This is the path where you will see the PENDING_URL you created above, click that link
- the Path is located next to 'Location: ' for example "/home/username/www/pending"
```
"PENDING_ABSOLUTE_PATH": "/home/username/www/pending",
```
PENDING_COPY_SRC_TO_ROOT
- copy all the files in the sourceFiles directory to the root of the folder
- false will leave all the files in the sub-folder sourceFiles
- true will copy them all to the root
   - This is important when you deal with authentication and your auth requires the login page be on the root of your sub-domain
```angular2html
  "PENDING_COPY_SRC_TO_ROOT": false
```

PENDING_POST_COMMANDS
- Allows to add a string command to run after the files have been succeffully deployed
- Eg composer required rubix/ml
```angular2html
  "PENDING_POST_COMMANDS": "composer require rubix/ml"
```

LIVE_URL
- Similar to 'PENDING_URL' but for your LIVE production files 
- EXAMPLE: "LIVE_URL": "/home/undologic/www/www"

LIVE_USER
- Same as 'PENDING_USER' but for live server
```
"LIVE_USER": "undologic",
```

LIVE_ABSOLUTE_PATH
- Same as "PENDING_ABSOLUTE_PATH" above but for LIVE
```angular2html
"LIVE_ABSOLUTE_PATH": "/home/undologic/www/www",
```

SRC_FILES_RELATIVE_PATH:
- In your source files this represents where your project files are
- You will have a docker folder which has all the files to manage your docker, you will then have 'launch' which has all the files to configure launch, then you should have 'sourceFiles' which is where your project files are
- Normally this is 'sourceFiles' but if you change you can adjust here where to pull from
- When a project is posted LIVE only the 'sourceFiles' files are posted to the live location
```angular2html
 "SRC_FILES_RELATIVE_PATH": "sourceFiles",
```

BROWSER_LOCAL_PATH_WITH_PROGRAM
- After launch uploads the files it will auto open Firefox to that correct location so you can test
- This currently only works on Windows and does not work on MacOS yet.
```angular2html
    "BROWSER_LOCAL_PATH_WITH_PROGRAM": "C:\\Program Files\\Firefox Developer Edition\\firefox.exe",
```

# Upgrade from previous version
Open up a terminal and navigation to the root of your project files (you should see sourceFiles and launchPad when you ls within the directory)
- Run the following command which will download the newer files nad overwrite your launchPad directory
- The settings is named new.settings.json so that file will NOT be overwritten

**IMPORTANT: This will overwrite your settings.json file use github history to compare and merge your previous settings into the new file**

```shell
# Windows
mkdir temp
cd temp
git clone https://github.com/undoLogic/setupCase-core.git
robocopy setupCase-core/launchPad ../launchPad /MIR
```

=================== OPTIONAL =======================

### Setup SSH keys (OPTIONAL)
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
```

- Now create or edit your ssh config file
```
  vi ~/.ssh/config
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

```angular2html
git clone git@project1.github.com:OWNER/repo-project1.git
# OR
git clone git@project2.github.com:OWNER/repo-project2.git
```
