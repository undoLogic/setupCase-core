# SetupCase

SetupCase is opinionated. Not all feature requests will be accepted. The project roadmap is maintained by undoLogic.

# Get Started
Login to GitHub - SetupCase-Core
- Click use a template

# Open-Source CORE
Framework Enhancements
CakePHP structure patterns
Controller conventions
View conventions
Template layout standards
CodeBlocks (if generic)
Reusable UI helpers
Generic CRUD scaffolding
Generic Auth integration (not multi-tenant advanced logic)
Generic utilities

## This includes
Directory conventions
Template extraction patterns
Helper naming patterns
Class structure philosophy
Small reusable utilities
Non-business-specific components


## Codex Support
If you install Codex in the WSL container then when you start up each docker container
you will be able to share the existing login
- This allows to have multiple projects running on the same server without having to configure Codex on each project / docker container

install codex
```
npm install -g @openai/codex   # install Codex globally
codex login                    # login with ChatGPT (device or browser)
```
Now ~/.codex contains the keys, if you want you can remove Codex for security and use only Codex within each container
```
npm uninstall -g @openai/codex
```






###  setupCase-Core Version 4
SetupCase is an open-source development foundation used to rapidly create structured, maintainable business software.
It provides a standardized base system, tooling, and workflows that act as building blocks for initializing and scaling CakePHP-based projects in a consistent, repeatable way.

## Overview Steps

1. [Initial Setup / Preparation](#step-1-initial-setup--preparation)
2. [Build a new CakePHP 4 project with our Platform-as-a-service](#step-2-build-a-new-cakephp-4-project-with-our-platform-as-a-service)
3. [Install software on your local computer with Chocolatey.org](#step-3-install-software-with-chocolateyorg)
4. [Checkout your new sourceFiles to your local computer](#step-4-checkout-your-new-sourcefiles-to-your-local-computer)
5. [Launch Changes](#step-5-launch-changes)
6. [Configure your IDE to automatically push changes to your server](#step-6-configure-your-ide-to-automatically-push-changes-to-your-server)
7. [Testing and watch updates on the test subdomain](#step-7-testing-and-watch-updates-on-the-test-subdomain)
8. [Initialization & Configuration of Source Files](#step-8-initialization--configuration-of-source-files)
9. [Launch changes](#step-9-launch-changes)
10. [Functional Testing](#step-10-functional-testing)
11. [Enhance Security](#step-11-enhance-security)
12. [Manually Modify Files On Server](#step-10-manually-modify-files-on-server)
13. [Prepare testing server](#step-11-prepare-testing-server)
14. [Optional: Convert to a dockerized container and launch your project on a popular VPS server](#step-12-convert-to-a-dockerized-container-for-vps-deployment)
15. [Refactor](#step-15-refactor)

### Step 1 Initial Setup / Preparation
This is setup on your DEVELOPMENT server (not live)
- Enable SSH (Control panel -> SSH Access -> SSH access is disabled -> Click Enable)
- Activate Wget/Curl (Control panel -> SSH Access -> Network tools -> Enable)
- Create sub-domains (Control panel -> Sub Domains -> Create 'test' & 'repos')
  - You can create other sub-domains in the future for other projects 
  - Each subdomain you create is publically available eg if you create 'test' then you can go to http://test.domain.com
- Activate Git Repo (Control panel -> Git -> fill in form)
  - Domain: choose your main domain
  - Sub-domain choose 'repos'
  - Leave 'web access path' blank'
  - Name: use your project name
  - Desc: A short desc about your project
- IMPORTANT: Copy info
  - Keep the Web Address, username and password after the Git repo is created as we will use them again in the future steps
  - NOTE: There is a link allowing you to change the password if you like. We will not have any private data until later, so an easier password is good for setup and later you will push into github which is more secure

[back to top](#overview-steps)

### Step 2 Build a new CakePHP 4 project with our Platform-as-a-service
- Use powershell / terminal to access the ssh server
```angular2html
ssh user@domain.com
```
- Fill-in the username / password that was sent with your welcome package
- Navigate to the 'test' sub-domain (eg test.domain.com) OR you can use any subdomain you like (ensure you create first in the control panel first)
```angular2html
cd ~/www/test
```
Copy/paste these commands to build the source files
```angular2html
wget https://raw.githubusercontent.com/undoLogic/setupCase-core/main/build/install_setupCase.sh
chmod +x install_setupCase.sh
./install_setupCase.sh
```
NOTE: Accept the permissions with Y


When you get asked for "Web address" this is from the previous step: i.e. http://repos.undoweb.com/projectname.git

This script will install CakePHP 4 with authentication and integrate the SetupCase library.
You can now test and view the working site: http://test.domain.com/sourceFiles

IMPORTANT: If you get permission issues follow the instructions on the screen to allow the mixed permission directories and then manually commit / push the git files

AFTER you followed the instructions on the screen, run these 2 commands to push all the files to the new REPO
```angular2html
git add . && git commit -m "Creating new project"
git push -u origin master
```

At this point the new CakePHP project has been build and all the source files were pushed into our temp GIT repo
- Now you can git pull these files to your local computer 

[back to top](#overview-steps)

### Step 3 Install Software with Chocolatey.org
Follow instructions to install software on your computer to manage your project

[Install Software on Windows](https://github.com/sacha-lewis/windows)
[Install Software on Lubuntu Linux](https://github.com/sacha-lewis/lubuntu)

[back to top](#overview-steps)

### Step 4 Checkout your new sourceFiles to your local computer
We will now prepare our IDE so we can program locally on our computer but all our files will be auto-uploaded to our server to view the changes

Use EITHER: PHPStorm (easier) or Powershell / terminal

PHP-Storm
- Click GIT
- Click Clone
- Paste in the web address from Step 1 

```angular2html
cd ~/PhpstormProjects
# replace with the webAddress from step 2
git clone http://repos.domain.com/project.git projectName
```

NOTE: If you are upgrading a previous version project and you already have a git Repo run this command on your LOCAL computer
```shell
# On your LOCAL computer run this command in the directory of your project 
# Replace URL with your repository URL that you setup above

git clone --depth=1 http://repos.domain.com/project.git tmpRepo

# we do not want any git association at all
rm -rf tmpRepo/.git

# now manually move the files to the desired directory where you are creating the new project
```

The files which you prepared on the server are now on your local computer

At this point you can keep working within the GIT - REPO on undoweb 
- This is easier and only requires a basic user/pass to checkout the files on your computer and you can start developing your project this way easier
- HOWEVER, if you want more security for your sourceFiles at this point you can migrate to GITHUB.com

GITHUB
- Create new Repository in your GitHub account
- As empty as possible (no readme, no gitignore files etc)
- Switch to https and copy that link
- Go back to PhpStorm (Menu -> GIT -> Manage Remotes)
- EDIT the existing 'origin' entry and you are REPLACING with the new github address
- Click ok and then Git - PUSH
- All your source files are now in GitHub


[back to top](#overview-steps)


### Step 5 Launch Changes
Launch allows to efficiently uploads your GITHUB projects to testing, staging and LIVE servers.
Our technology uses basic SSH commands to prepare the source files and does not require extra libraries.

5.1 - Configure TEST
Configure the test profile to push all the files from GITHUB to your test / dev server

modify launchPad/settings.json
- "TESTING_URL": "test.undoweb.com", - Change to your dev URL
- "TESTING_USER": "undoweb", - Server username
- "TESTING_ABSOLUTE_PATH": "/home/undoweb/www/test",
- "TESTING_COPY_SRC_TO_ROOT": false, - Only if you want to copy all the files to root

EITHER Using PAT token
- "TESTING_GIT_ADDRESS": "github.com/undoLogic/projectname.git",
- "TESTING_USE_PAT": true,

OR Using SSH KEYS
- "TESTING_GIT_ADDRESS": "git@github.com:undoLogic/projectname.git",
- "TESTING_USE_PAT": false,

5.2 - Push files
- Run the local script ./1_run.sh
- Launch will logon to your target server (your SSH passphrase adds extra security)
- All the source files from your GitHub account will be exported to the testing and/or staging locations
- You must create a Personal Access Token (in github). This file is NOT stored in your sourcFiles instead it is saved to a PHP.ini file on your server
- This allows you to test and verify all the changes before going LIVE
- After you are satisfied all changes and database changes have been completed you can 'PushLIVE' - which copies all the files to the LIVE location on your target server
- You first need to adjust your 'launch/settings.json' file to match your target servers

```php
# navigate to the launch dir
cd launchPad
./1_run.sh
```

Read the full documentation in the README.md file in the launchPad directory:
https://github.com/undoLogic/setupCase-core/blob/main/launchPad_win/README.md

[back to top](#overview-steps)


### Step 6 Configure your IDE to automatically push changes to your server

#### 6.1 Setup sFTP on your IDE
- This will allow to upload changes from your computer to your server 

Using PHPstorm:
- Tools -> Deployment -> Browse Remote Host
  - (a side panel will appear) NEXT click '...'
- Name: Test Server (can be anything) 
  - Choose type: sFTP - ssh over FTP
- SSH configuration - click '...'
- Create new config with "+"
  -  HOST: test.domain.com (replace domain with your server domainname)
  - USER: server username (included in your welcome package)
  - AUTHENTICATION TYPE: Key Pair
  - PRIVATE KEY FILE: click folder to navigate to private key
  - PASSPHRASE: Optional
- Click 'Test Connection' to ensure you can connect to the test server

Click OK to return to the previous screen

ROOT PATH: click 'Autodetect'

MAPPINGS (TAB)
- LOCAL PATH: Navigate to your 'sourceFiles' directory
- DEPLOYMENT PATH (During installation): 
  - click to navigate to 'www' -> 'test' (OR the subdomain you created in the control panel) -> 'sourceFiles'
- --- OR ---
- DEPLOYMENT PATH (After project is launched live): 
- click to navigate to 'www' - 'test' (OR the subdomain you created in the control panel)
    - During programming we upload to the root of the subdomain NOT the sourceFiles directory (better for authentication)

#### 6.2 Auto-upload changes
When activated anytime you change a file on your computer it will automatically sFTP that file to the server, allowing you to develop on the server

PHPstorm - Tools - Developement - Options
- UPLOAD CHANGED FILES AUTOMATICALLY TO THE DEFAULT SERVER: "Always"


#### 6.3 TROUBLESHOOTING
If you have issues where your IDE is not uploads the changes to the server follow these steps

Make sure your default upload is selected to the correct profile
- Tools -> Development -> Browse Remote Host (a side panel will appear) NEXT click '...'
- Right click on the correct profile and choose 'Set as Default'

[back to top](#overview-steps)
### Step 7 Testing and watch updates on the test subdomain

Test modifying a file on your computer and see the changes right away on your test server

http://test.domain.com/sourceFiles

[back to top](#overview-steps)




### Step 8 Initialization & Configuration of Source Files

#### 8.1 Integration with our SetupCase Features
Our solution will give a clear development path: 
- Url based language switching
- Authentication
- Optional MySQL database environments
- Server based credentials 
- No private credentials in source code (stored on server)
- We are only going to make minor changes to the CakePHP framework core so we have a simple upgrade path in the future


8.1.1. Add bootstrap override below the app_local 'bootstrap.php' (/sourceFiles/config/bootstrap.php)
```php
//if (file_exists(CONFIG . 'app_local.php')) {
//    Configure::load('app_local', 'default');
//}
//Add this BELOW the code above
if (file_exists($this->configDir . 'bootstrap-setupCase.php')) {
  require_once $this->configDir . 'bootstrap-setupCase.php';
}
```

Open 'bootstrap-setupCase.php' (/sourceFiles/config/bootstrap.php)
- Choose the domain names for the correct environment
```shell
$liveDomains = [
    'test.undoweb.com' => 'UNDOWEB',
    'pending.undoweb.com' => 'PENDING',
    'www.domain.com' => 'LIVE',
];
```

You can duplicate app_setupCase.php to a different environment
- Then add different credentials within your php.ini file


8.1.2. In the application.php page (sourceFiles/src/Application.php) add this function AFTER the "public function middleware(Middl....":
NOTE: Make sure you import the required classes after you paste
```php
protected function getAuthenticationService() : AuthenticationService {
  $authenticationService = new AuthenticationService([
      'unauthenticatedRedirect' => Router::url('/login'),
      'queryParam' => 'redirect',
  ]);
  $fields = [
      'username' => 'email',
      'password' => 'password',
  ];
  
  // Load identifiers, ensure we check email and password fields
  $authenticationService->loadIdentifier('Authentication.Password', [
      'fields' => $fields
  ]);
  
  // Load the authenticators, you want session first
  $authenticationService->loadAuthenticator('Authentication.Session');
  
  // Protect against Submit flooding
  //  $authenticationService->loadAuthenticator(FormLoginAttemptsAuthenticator::class, [
  //      'fields' => $fields,
  //      'loginUrl' => Router::url('/login'),
  //  ]);

  //Normal without flooding prevention
  $authenticationService->loadAuthenticator('Authentication.Form', [
      'fields' => $fields,
      'loginUrl' => Router::url('/login'),
  ]);
  
  // If the user is on the login page, check for a cookie as well.
  $authenticationService->loadAuthenticator('Authentication.Cookie', [
      'fields' => $fields,
      'loginUrl' => '/login',
      'cookie' => [
        'name' => 'remember_me_cookie',
        'expire' => strtotime('+30 days'), // Set the desired expiration time
        'httpOnly' => true,
      ],
  ]);
  
  return $authenticationService;
}
```

8.1.3. Now add to the same file Application.php add ABOVE the CSRF (sourceFiles\src\Application.php):
NOTE: You will need to right click and import these classes after you paste
```php
//Added by SetupCase-Core
->add(new EncryptedCookieMiddleware(
    ['CookieAuth'],
    'CHANGEMEWITHSECURE'
))
->add(new AuthenticationMiddleware($this->getAuthenticationService()))
->add(new LangMiddleware())
->add(new RbacMiddleware())
->add(new AccessMiddleware())
```


Ensure you import the required classes
```php
    use App\Authenticator\FormLoginAttemptsAuthenticator;
    use Cake\Http\Middleware\EncryptedCookieMiddleware;
```


8.1.4. AppController->initialize: ADD
```php
$this->loadComponent('Authentication.Authentication');
```

8.1.5. Don't forget to import the classes with right click (in PHPstorm)
8.1.6. In App_controller / beforeFilter
```php
public function beforeFilter(EventInterface $event) {
    parent::beforeFilter($event); // TODO: Change the autogenerated stub
    $this->setupCase();
}
function setupCase() {

    $setupCase = new SetupCase;
    $setupCase->requirePasswordExcept(['www.LIVESITE.com', 'LIVESITE.com'], $_SERVER, $this->request->getSession());
    $setupCase->requireSSLExcept([
        'localhost', //add other hosts which should NOT redict to SSL
    ], $this);

    //redirect older langs
//    $oldLangCheck = $this->request->getParam('language');
//    if ($oldLangCheck == 'eng') {
//        $this->redirect(['language' => 'en']);
//    } elseif ($oldLangCheck == 'fre') {
//        $this->redirect(['language' => 'fr']);
//    }

    //RBAC/Access middleware decides if they are allowed in - here we redirect if needed
    $access_granted = $this->request->getAttribute('access_granted');
    if (!$access_granted) {
        $this->Flash->error($this->request->getAttribute('access_msg'));
        $this->redirect($this->referer());
    } else {
        //We handle all RBAC from our RBAC middleware - disable the CakePHP authentication for all pages
        $this->Authentication->addUnauthenticatedActions([$this->request->getAttribute('params')['action']]);
    }
    $this->set('webroot', Router::url('/'));
}
```

8.1.7. Src / View Helpers
- AppView.php - add into initalize()
```php
$this->loadHelper('Auth');
$this->loadHelper('Lang');
```

8.1.9. Add routes
Add these to the function "$routes->scope('/', function (RouteBuilder $builder): void {"

```php
# in the function 
# $routes->scope('/', function (RouteBuilder $builder) {
# ADD
$builder->connect('/login', ['controller' => 'Users', 'action' => 'login']);
$builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout']);
$builder->connect('/beginReset', ['controller' => 'Users', 'action' => 'beginReset']);
$builder->connect('/reset', ['controller' => 'Users', 'action' => 'reset']);
            
// language
$builder->connect('/{language}', ['controller' => 'Pages', 'action' => 'home'], ['language' => 'en|fr|es']) ;
$builder->connect('/{language}/{controller}/{action}/*', [], ['language' => 'en|fr|es']);
$builder->connect('/{language}/{controller}', ['action' => 'index'], ['language' => 'en|fr|es']);
        
//redirect for older langs
//$builder->connect('/eng', ['controller' => 'Pages', 'action' => 'home'], ['language' => 'en|fr|es']) ;
//$builder->connect('/fre', ['controller' => 'Pages', 'action' => 'home'], ['language' => 'en|fr|es']) ;
//$builder->connect('/eng/{controller}/{action}/*', ['language' => 'eng', 'controller' => 'Pages', 'action' => 'redirect'], ['language' => 'en|fr|es']);
//$builder->connect('/fre/{controller}/{action}/*', [], ['language' => 'en|fr|es']);
//$builder->connect('/eng/{controller}', ['action' => 'index'], ['language' => 'en|fr|es']);
//$builder->connect('/fre/{controller}', ['action' => 'index'], ['language' => 'en|fr|es']);
```

Add these BELOW the function 
```php

### Then add below that function new functions for the different Usertypes / Prefixes
$routes->prefix('staff', function (RouteBuilder $routes) {

    //with the lang
    $routes->connect('/:language/:controller/:action/*', [])->setPatterns(['language' => 'en|fr|es']) ;

    $routes->fallbacks(DashedRoute::class);
});

$routes->prefix('admin', function (RouteBuilder $routes) {

    //with the lang
    $routes->connect('/:language/:controller/:action/*', [])->setPatterns(['language' => 'en|fr|es']) ;

    $routes->fallbacks(DashedRoute::class);
});
```


#### 8.2 Databases and Credentials (optional)
- We harness server based passwords / credentials / api keys, etc
- The source files do NOT contain any secret information ensuring that even if your sourceFiles get leaked, no private connection info will be exposed
- We will store all the private data in the server PHP.ini (GLOBAL) file.

- Ensure you have 2 databases created for main and testing
```php
# app_setupcase.php
'Datasources' => [
    'default' => [
        'url' => filter_var(env('DATABASE_DEFAULT_URL', get_cfg_var('DATABASE.DEFAULT.URL')), FILTER_VALIDATE_URL),
    ],
    'test' => [
        'url' => filter_var(env('DATABASE_TEST_URL', get_cfg_var('DATABASE.TEST.URL')), FILTER_VALIDATE_URL),
    ],
],
This will first try to load the docker environment vars otherwise will load from the PHP.ini file on the server
# docker-compose.yml
environment:
  DATABASE_URL: mysql://root:undologic@db/LIVE_database
  DATABASE_TEST_URL: mysql://root:undologic@db/automation
# PHP.ini
BOILER.Datasources.default.url = mysql://boilerplate:123@localhost/undoweb_boilerplate_testing
BOILER.Datasources.test.url = mysql://boilerplate:123@localhost/undoweb_boilerplate_test
```

TROUBLESHOOTING
ERROR: Class "App\Controller\SetupCase" not found (could also be Router, etc)
FIX: You did not import the class SetupCase



#### 8.3 Fix windows line endings
- Ensure our windows line endings are corrected
```php
cd PROJECTFILE/docker
./2loginDockerContainer.bat
// you are now inside the docker container
./fix-windows-line-endings.sh
//Now you can run command line tests without issues
```

[back to top](#overview-steps)



#### 8.4 Program with CodeBlocks

We develop with a modular programming process by harnessing standarized blocks of code.

https://codeblocks.setupcase.com

Search for the specific code fragments in order to convert the finalized visuals into working systems






#### 8.5 Integrate a professional visual layout to your project

8.5.1 Download layout source files
Before we start any programming, we first must create our Visual Layout Clickthrough.

Download a layout from your favourite Bootstrap layout supplier. GrayGrids is a great company which you can download beautiful professional layouts to create your visual clickthroughs.

This is a crucial step as it allows you to link together all the visual pages with finalized visuals and add mock-features which outline what programming needs to be completed. You and your team can revise the visuals as much as needed until everything is thought through and approved.

Now you can move on to the programming stage confidently knowing all the programming that will be developed has been throughly brainstormed and this ensures the programming scope won't change after it is started.

8.5.2 Add Layout Source Files

After you download all the layout source files, save them into "/src/webroot/modules/layoutSourceFiles"

8.5.3 View in Browser

Because they have been added to webroot, this means you can view the entire layout in your browser
```angular2html
http://test.domain.com/sourceFiles/modules/layoutSourceFiles
```

8.5.4 Create a new layout file

/src/templates/layout/newLayout.php
Copy all the files from one of the pages from your new layout for example

/src/webroot/modules/layoutSourceFiles/index.html
COPY contents into

/src/templates/layout/newLayout.php

8.5.5 Create baseLayout variable

All the source files we added to our layout reference files which are located in our modules directory
-> So we are going to simply create a variable the allows us to reference the corrrect location.

In your PagesController.php (or AppController.php) file

```php
function beforeFilter() {
$this->set('baseLayout', Router::url('/').'modules'.DS.'layout'.DS);
}
```

Now in the layout file (/src/templates/newLayout.php)

Find and replace the following:

```angular2html
FIND src=" REPLACEWITH src="<?= $baseLayout; ?>
FIND href=" REPLACEWITH href="<?= $baseLayout; ?>
```

8.5.6 Separate Layout Content From Each Page Content
We currently have a SINGLE layout file, but we need to create separate pages, so you need to separate the content (on the layout) which is common to all pages apart from the content that is different on each page.

Using inspector find the correct div and cut this content and add to a page

8.5.7 Create Each Page

Create all the visual pages by doing the following

Create a new function in the controller AND create a new view page

[back to top](#overview-steps)




























### Step 10 Functional Testing

- Testing currently works on our PaaS using a SSH terminal
1. SSH to your test server
2. Bake all the fixtures
```php
//This will create all the fixutres without any default data
bin/cake bake fixture all --count = 0

//If you want to create specific fixtures for a model
bin/cake bake fixture users
```
- This will give a basic fixture without any global data which is preferred (you can add to the records array if you want gloabl data)
3. Bake one table at a time as you add working testing into it
```php
bin/cake bake test Table Users
```
4. Add your database structure
- Using PHPmyAdmin export all your table
  - uncheck DATA only export the structure
  - uncheck as a transaction (faster)
  - view as text
  - copy all sql 
  - paste into sourceFiles/tests/schema.sql
- Change the bootstrap file for the tests to use the schema.sql instead of migrations (for larger projects)
```php
//ensure this reference is at the top of the file
use Cake\TestSuite\Fixture\SchemaLoader;
//Look at the bottom of sourceFiles/tests/bootstrap.php
(new \Cake\TestSuite\Fixture\SchemaLoader())->loadSqlFiles('./tests/schema.sql', 'test');
//(new Migrator())->run(); 
```
- Connect your test database and assign in your app_...php file

5. Replace the cakePHP tests with a basic boilerplate test
- Edit the file tests/TestCase/Model/Table/UsersTableTest.php
- Remove all functions EXCEPT the 'setUp' and 'tearDown' functions
- Add this function
```php
public function testBoilerPlateTest(): void
{
    $newUser = ['name' => 'new user here'];
    $user = $this->Users->newEntity($newUser);
    $res = $this->Users->save($user);
    
    //ensure it was written 
    $found = $this->Users->find('all')->first();
    $this->assertEquals(1, $found->id);
}
```
The easiest way to create tests is by exporting a PHP array from PHPmyAdmin and then adding
```aiignore

       $users = array(
            array('id' => '1',.........
        );
        $obj = FactoryLocator::get('Table')->get('Users'); $entities = $obj->newEntities($users); $response = $obj->saveMany($entities);
        
```
Now those rows will be on your testing database ready to use

6. Run the test
```php
# test all model tests
vendor/bin/phpunit tests/TestCase/Model/

# run a test and filter by a specific function
vendor/bin/phpunit tests/TestCase/Model/ --filter testFunctionName

# you can also test one model at a time
# vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php

# Controller only (working)
# vendor/bin/phpunit tests/TestCase/Controller/PagesControllerTest.php

```

Troubleshooting
If you get errors running your test ensure you have given execute permission to the phpunit
```php
chmod +x vendor/bin/phpunit
```


### Step 11 Enhance Security

- Here are some methods to increase the security of your account

1. Change documentRoot
- On the control panel click "Subdomains"
- Click the pencil next to the subdomain in question
  - If you want to change the LIVE subdomain (it would be 'www', but you can also apply this change to another subdomain)
  - Click the folder and a popup will appear. navigate to 'webroot'
  - After you click 'select' it will return and the 'Document Root' will now be /www/www/webroot
  - Click the checkbox to finalize
- Now your cakePHP files are only exposing the webroot to the public and all the other files are not accessible publically which will increase the security

2. Change file permissions
- Coming soon...

3. Database credentials stored on Server NOT Source-files
- On your server navigate to PHP Settings
- Create a global php.ini file (if it does not yet exist)
- add key/value pairs in this file eg:
```aiignore
paypal_user = 1234556
paypal_secret = 9999999
```
- Now in your PHP code to get this value do:
```php
echo get_cfg_var("paypal_user");
//or
echo get_cfg_var("paypal_secret");
```
- Now your DEV environment / server will automatically get test credentials and your live production will get the live 
- You do not have to worry about leaking any secrets in your source files 



### Step 12 Manually Modify Files On Server
- You are able to SSH into a server and perform emergency fixes.
- You can even do this with an iPad
  - Simply download iSH on your IOS device: https://ish.app/
1. SSH to your server - Add your PRIVATE KEY to your ISH root matching your PUBLIC key to your server
2. Add your github PAT (personal access token) to the php.ini file on your server. 
- This will ensure your sourceFiles do not have any private credentials.
- Github - Settings - Developer Settings - Personal Access Tokens - Tokens (Classic)
```php
#PHP.ini (global only - not individual subdomains) - copy and paste from Tokens (Classic) 
PAT = 123456789
```
3. Git clone your files to your server
```php
git clone "https://$(php -r 'echo get_cfg_var("PAT");')@github.com/USERNAME/project.git" --branch master --single-branch /path/to/server/dir/main/.
```
2. You now have your repo on the server
3. Use nano to modify a file
```php
nano /path/to/file/to/edit.php
```
4. CTRL+S / CTRL+X 
5. You can test the changes on the test url
6. LaunchPad/4_uploadChangesToLive.sh will automatically show you the files that were modified, simply select and this will ssh to your LIVE server (under construction)
7. Commit / Push changes from server
```php 
git add . && git commit -m "server changes"
git push -u origin master
```


### Step 11 Prepare testing server
You are able to efficiently copy the database from a LIVE server and push this data to your test server.
- This ensures that you can develop with real data on a test server securely. 
- Always PUSH data from LIVE to testing to maintain security by never giving access from a testing server to connect to LIVE.
- On your LIVE server simple copy this script and after you export the current database it will rsync the data to your test website. You can then manually import on the test website
- On the testing server place the public key into the authorized_hosts allowing SSH key connection

```shell
#!/bin/sh
d=$(date +%Y-%m-%d)
rsync -av --progress -e "ssh -i $HOME/.ssh/YOURKEY" *$d* user@testwebsite.com:~/private/.
```


### Step 12 Convert to a dockerized container for VPS deployment
Coming soon...


### Step 15 Refactor
After the initial development has started the following coding rules should be adhered to to keep the code manageable between developers and yourself 9 months later when you have to modify the source files. 
1. Assigned PUBLIC and PRIVATE to all the the functions
- PUBLIC functions MUST fit fully expanded within your screen
- Use PRIVATE functions with shared variables to move complex code out of your public function to keep it manageable
- PREPEND all private functions with the name of the PUBLIC function name to keep it all visible when you view your functions list alphabetically
- Validations should be all within a single PRIVATE function making it easy to add future validations in the future
2. All assignments in the controller need to be moved into the model to prepare for functional tests which are added at the model level
3. Anytime you send an email ensure it done through a EMAIL-QUEUE (code-blocks code coming soon) 
- This is important as when tests are added we are able to easily create an email and test that it was created successfully from the queue
- It becomes problematic when you are testings and manually checking the emails 
4. All PUBLIC functions should return a RESPONSE ARRAY 
- It must contain STATUS & MSG
- STATUS is similar to 200 meaning it was successful, 404 not found etc
- The reponse should contain ID's created when adding / updating the database to help later with tests / automation





### Step 16 LAUNCH
When your project is ready to launch ensure the following
1. Create LIVE profile
- duplicate 'app_setupCase.php' to 'app_live.php'
- force 'debug' => false,
- This will disable the DebugKit
-> If you want to manually disable debugKit for all profiles comment out "this->addPlugin('DebugKit');" in 'src/Application.php'

2. Verify no errors
- Open terminal
- navigate to the sourceFiles directory
- tail -f logs/error.log
- Browse all the pages and ensure no errors

3. Add custom ERROR page
- First open the environment file which will have an ERROR page Instead of the debugging info
- EG: app_SetupCase or app_pending or app_LIVE
```aiignore
    'debug' => true, //Show the debug info when errors are discovered
    'debug' => false, //do NOT show debug info instead show an ERROR page
```
- Then modify the error page 
```aiignore
sourceFiles/templates/layouts/error.php
```
- Add your own custom logo to these pages to improve the branding
OPTIONAL: to further adjust the specific types of error pages inline for each error
```aiignore
sourceFiles/templates/Error/error400.php
sourceFiles/templates/Error/error500.php
sourceFiles/templates/Error/missing_action.php
sourceFiles/templates/Error/missing_controller.php
sourceFiles/templates/Error/missing_template.php
```
- Now you will have a customized error page when visitors encounter errors

### Step 17 Backup
It is important to keep ongoing backups of your databases to mitigate data loss
- We recommend to create a local script to download from your website daily to your local computer. 

```powershell
$url = "https://www.domain.com/path/to/download/script";

# Define the current date in the format you want (e.g., YYYY-MM-DD)
$currentDate = Get-Date -Format "yyyy-MM-dd"

# Define the path where the file will be saved
$outputFile = "D:\Backups\$currentDate.sql"

try {
    # Download the file
    $response = Invoke-WebRequest -Uri $url -ErrorAction Stop

    # Check if the status code is 200 (OK)
    if ($response.StatusCode -eq 200) {
        # Save the file
        $response.Content | Out-File -FilePath $outputFile -Encoding ascii
        Write-Output "File downloaded and saved successfully."
    } else {
        Write-Output "Download failed with status code: $($response.StatusCode)"
    }
} catch {
    Write-Output "An error occurred: $_"
}
```

Now create a Task Scheduler on Windows
- Basic Task
- Trigger Daily 7:30
- Action: Start a program
- Program/Script: C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe
- Add Arguments: -NoProfile -ExecutionPolicy Bypass -File "D:\PhpstormProjects\projectName\download.ps1"
- Start in: D:\PhpstormProjects\projectName\

Now daily you will get all your data saved to your local computer 
- Setup the export directory to a location that is safe and is replicated offline

```php
public function dump($code = false): ?Response
    {
        ////////////////////////////////////// security /////////////////////////////
        $settings = [
            'allowed_ips' => [
                '123.456.789.000'
            ],
            'codes' => [
                '123'
            ]
        ];

        $remoteAddr = $_SERVER['REMOTE_ADDR'];

        $errors = false;

        if (!in_array($remoteAddr, $settings['allowed_ips'])) {
            throw new BadRequestException('Your location is NOT allowed to see this: '.$remoteAddr);
            $errors = true;
        }

        if (!in_array($code, $settings['codes'])) {
            throw new BadRequestException('Your access code is not correct: '.$code);
            $errors = true;
        }
        
        //IMPORTANT: Add more security here
        
        //////////////////////////////////////////////////////////////////////////

        if (!$errors) { //incase our exectpions do not work we ensure errors are false

            $connection = ConnectionManager::get('default');

            $config = $connection->config();

            $host = $config['host'];
            $port = $config['port'];
            $username = $config['username'];
            $password = $config['password'];
            $database = $config['database'];

            //dd($port);

            // Path to save the dump file
            $file = TMP . 'dump_' . date('Y-m-d_H-i-s') . '.sql';

            // mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($file)
            );

            //dd($command);
            // Execute the command
            exec($command, $output, $returnVar);

            //dd($returnVar);
            if ($returnVar === 0) {
                $this->response = $this->response->withFile($file, [
                    'download' => true,
                    'name' => basename($file),
                ]);
                return $this->response;
            } else {
                throw new BadRequestException('Could not create dump file');
            }

        } else {
            throw new BadRequestException('ERRORS detected');
        }

    }
```
## Pre-Commit Hook (Portable)
Use this control plane repo to install pre-commit size checks.

Files:
- `git-hooks/pre-commit`
- `git-hooks/9-Install-Soft-PreCommit-Hook.sh`
- `git-hooks/README.md`

Quick install in this project:
```bash
./git-hooks/9-Install-Soft-PreCommit-Hook.sh
```

Install into another project directory:
```bash
./git-hooks/9-Install-Soft-PreCommit-Hook.sh /path/to/other/project
```

This check blocks commit when `public` functions exceed the limit.
This check also blocks commit when base templates in `sourceFiles/templates/` exceed the limit.
Template files inside `sourceFiles/templates/element/` are exempt.
Override when needed with `git commit --no-verify` or `SOFT_PRECOMMIT_DISABLE=1`.

## Web Init Scripts
One-time web bootstrap scripts are grouped under `init-web/` so they can be removed after setup.

- `init-web/1-Install-Cake.php`
- `init-web/2-Install-CodeBlocks.php`
- `init-web/2-install-CodeBlocks-MANUAL-STEPS.md`
- `init-web/3-View-CodeBlocks.php`
- `init-web/5-Create-New-Project.php`
- `init-web/8-Save-CodeBlocks.php`

`init-web/1-Install-Cake.php` now includes a guard:
- If `sourceFiles/` already exists, install is skipped and no composer commands run.
