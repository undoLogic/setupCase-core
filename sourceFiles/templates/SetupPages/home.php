<link href="https://fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.3.slim.min.js" integrity="sha256-ZwqZIVdD3iXNyGHbSYdsmWP//UBokj2FHAxKuSBKDSo=" crossorigin="anonymous"></script>
<script src="<?= $webroot; ?>modules/prism.js"></script>
<link rel="stylesheet" href="<?= $webroot; ?>modules/prism.css">


<?php $classEach = 'col-12 col-md-6 col-lg-4'; ?>


<?php if (0): ?>


    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Title</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">

                    </code></pre>
            </div>
        </div>
    </div>


<?php endif; ?>









<div class="row">

    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">



                <h5>POPUP home page disclaimer

                    <a name="popupDisclaimer"></a> <a href="#popupDisclaimer">#</a>

                </h5>
                
                After you click the button a cookie will prevent it from appearing on the next page load. 
                
                You can add to the address bar to reset and have the popup appear again: 
                www.domain.com?clearCookie
            </div>
            <div class="card-body">

                <h3>
                    CSS
                </h3>
                <pre><code class="language-css">


/* Popup container */
.popup-container {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #f1f1f1;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    padding: 15px;
    box-sizing: border-box;
    animation: slide-up 0.5s ease-out;
    text-align: center;
}

/* Button to close the popup */
.close-button {
    background-color: #4962AA;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Animation for sliding up */
@keyframes slide-up {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}


                    </code></pre>








                <h3>
                    HTML
                </h3>
                <pre><code class="language-php">

<!-- Popup content -->
&lt;div id="popup" class="popup-container">
Text goes here

    &lt;button class="close-button" onclick="closePopup()">
        OK
    &lt;/button>
<!-- Add your popup content here -->

&lt;/div>

                    </code>
                </pre>





                <h3>
                    Javascript
                </h3>
                <pre><code class="language-javascript">


// Function to open the popup
function openPopup() {
    document.getElementById('popup').style.display = 'block';
}

// Function to close the popup
function closePopup() {
    document.getElementById('popup').style.display = 'none';
    document.cookie = 'popupClosed=true; expires=' + new Date(new Date().getTime() + 24 * 60 * 60 * 1000).toUTCString() + '; path=/';
}

function clearCookie() {
    // Set the 'popupClosed' cookie to expire in the past, effectively deleting it
    document.cookie = 'popupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}

if (document.cookie.indexOf('popupClosed=true') === -1) {
    setTimeout(function () {
        openPopup();
    }, 1000);
} else {
    // Show an alert if the cookie is set
    //alert('Popup already closed!');
}

&lt;?php if (isset($_GET['clearCookie'])): ?>
    document.cookie = 'popupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

    if (document.cookie.indexOf('popupClosed=true') === -1) {
    alert('Cookie is cleared')
    } else {
    alert('NO');
    }
&lt;?php endif; ?>





                    </code>
                </pre>





            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>
                    GIT remove .idea project files already pushed
                </h5>
            </div>
            <div class="card-body">
                <pre><code class="language-php">

                git rm -r --cached .idea
                git commit -am "Removed .idea"
                git push


                    </code></pre>
            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>GIT RESET --hard</h5>
            </div>
            <div class="card-body">
                <pre><code class="language-php">

git log --oneline
git reset --hard commit_hash
git push --force

                    </code></pre>
            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Auth Change password field</h5>
            </div>
            <div class="card-body">
                <pre><code class="language-php">

//add new database field
ALTER TABLE `users` ADD `password_v4` VARCHAR(200) NOT NULL AFTER `password`;
ALTER TABLE `users` ADD `reset_token` VARCHAR(200) NOT NULL AFTER `password_v4`;
ALTER TABLE `users` ADD `user_type` VARCHAR(20) NOT NULL AFTER `reset_token`;

//change password to a new link - application.php
$authenticationService->loadIdentifier('Authentication.Password', [
    'fields' => [
        'username' => 'email',
        'password' => 'password_v4',
    ]
]);

//Users controller - RESET PASSWORD
$row->password_v4 = $passObj->hash($postData['new_password']);



//Users table - SAVE user password
$user->password_v4 = $password;
if ($this->save($user)) {

                    </code></pre>
            </div>
        </div>
    </div>


    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>HTML Link</h5>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$this->Html->link('Name', ['prefix' => 'Staff','controller' => '', 'action' => ''], ['class' => 'btn btn-primary', 'confirm' => '']);</code></pre>
            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>PHP USORT</h5>
            </div>
            <div class="card-body">
                <pre><code class="language-php">
$default['field'] = 'votes';
$default['dir'] = 'DESC';
usort($status, function ($item1, $item2) use ($default) {
    $field = $default['field'];
    $dir = $default['dir'];
    if ($item1[$field] == $item2[$field]) return 0;
    if ($dir == 'ASC') {
        return $item1[$field] < $item2[$field] ? -1 : 1;
    } else {
        return $item1[$field] > $item2[$field] ? -1 : 1;
    }
});

                    </code></pre>
            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">

                <a name="containWithConditions"></a>
                <h5>CakePHP 4 Contain with conditions <a href="#containWithConditions">#</a></h5>


            </div>
            <div class="card-body">
                <pre><code class="language-php">$row = $this
    ->find()
    ->contain('ASSOCIATED_MODEL',  function(\Cake\ORM\Query $q) {
        return $q->where(['ASSOCIATED_MODEL.removed' => 0]);
    })
    ->contain('ANOTHER_ASSOCIATED_MODEL',  function(\Cake\ORM\Query $q) {
        return $q->where(['ANOTHER_ASSOCIATED_MODEL.removed' => 0]);
    })
    ->contain('ANOTHER_ASSOCIATED_MODEL.DEEPER_ASSOCIATED_MODEL',  function(\Cake\ORM\Query $q) {
        $q->order('id DESC');
        $q->limit(1);
        return $q;
    })
    ->where([
        'MODEL.id' => $id
    ])
->first();
                    </code></pre>
            </div>
        </div>
    </div>

    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Offset Anchor for Fixed Header</h5>
            </div>
            <div class="card-body">

                <pre><code class="language-html">&lt;a class="anchor" id="top">&lt;/a>

                    </code></pre>

                <pre><code class="language-css">
a.anchor {
    display: block;
    position: relative;
    top: -100px;
    visibility: hidden;
}

                    </code></pre>
            </div>
        </div>
    </div>

    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Language Switching</h5>
                <div class="card-header-right">

                    <?= $this->Lang->get(); ?>

                    <?php echo $this->Html->link('English', ['language' => 'en'], [
                        'class' => $this->Lang->getActiveClass('en')
                    ]); ?>
                    /
                    <?php echo $this->Html->link('French', ['language' => 'fr'], [
                        'class' => $this->Lang->getActiveClass('fr')
                    ]); ?>
                    /
                    <?php echo $this->Html->link('Spanish', ['language' => 'es'], [
                        'class' => $this->Lang->getActiveClass('es')
                    ]); ?>
                    /
                    <?php echo $this->Html->link('Lang-not-set', array()); ?>

                </div>
            </div>
            <div class="card-body">
<pre><code class="language-php">$this->Html->link('English',
    ['language' => 'en'],
    ['class' => $this->Lang->getActiveClass('en')
]);</code></pre>
<pre><code class="language-php">$this->Html->link('French',
    ['language' => 'fr'],
    ['class' => $this->Lang->getActiveClass('fr')]
);</code></pre>
<pre><code class="language-php">$this->Html->link('Spanish',
    ['language' => 'es'],
    ['class' => $this->Lang->getActiveClass('es')]
);</code></pre>
<pre><code class="language-php">$this->Html->link('Lang-not-set', array());</code></pre>

            </div>
        </div>
    </div>

    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Auth</h5>

                <div class="card-header-right">

                    <?php if ($this->Auth->isLoggedIn()): ?>
                        LOGGED IN (<?php echo $this->Html->link('Logout', '/logout'); ?>)
                    <?php else: ?>
                        NOT logged in (<?php echo $this->Html->link('Login', '/login'); ?>)
                    <?php endif; ?>

                    /
                    <?php echo $this->Html->link('Reset', array('prefix' => false, 'controller' => 'Users', 'action' => 'beginReset')); ?>
                    /
                    <?php echo $this->Html->link('AddUser', array('prefix' => false, 'controller' => 'Users', 'action' => 'add')); ?>
                    /
                    <?php echo $this->Html->link('StaffPrefx', array(
                        'prefix' => 'Staff',
                    )); ?>
                    /
                    <?php echo $this->Html->link('AdminPrefix', array(
                        'prefix' => 'Admin',
                    )); ?>

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">&lt;?php if ($this->Auth->isLoggedIn()): ?>
LOGGED IN (&lt;?php echo $this->Html->link('Logout', '/logout'); ?>)
&lt;?php else: ?>
NOT logged in (&lt;?php echo $this->Html->link('Login', '/login'); ?>)
&lt;?php endif; ?>

&lt;?php echo $this->Html->link('Reset', array('prefix' => false, 'controller' => 'Users', 'action' => 'beginReset')); ?>

&lt;?php echo $this->Html->link('AddUser', array('prefix' => false, 'controller' => 'Users', 'action' => 'add')); ?>

&lt;?php echo $this->Html->link('StaffPrefx', array(
'prefix' => 'Staff',
)); ?>

&lt;?php echo $this->Html->link('AdminPrefix', array(
'prefix' => 'Admin',
)); ?>


                    </code></pre>
            </div>
        </div>
    </div>

    <div class="<?= $classEach; ?>">
    <div class="card">
        <div class="card-header">
            <h5>VUE - Axios POST</h5>

            <div class="card-header-right">

            </div>
        </div>
        <div class="card-body">
                <pre><code class="language-javascript">function postExample() {
    <?php if (!isset($$csrf)) $csrf = '$csrf_token'; ?>
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = "<?= $csrf; ?>";

    console.log('formdata - submit form');
    console.log(this.formData);

    var objData = JSON.stringify(this.formData);

    console.log('objData');
    console.log(objData);
    let URL = "<?= $webroot; ?>SetupPages/get";
    axios.post(URL, objData).then(function (response) {
    console.log("response");
    console.log(response);
    if (response.data.STATUS == 200) {
    console.log();
    } else {
    console.log('Fail - something went wrong');
    }
    });
}
                    </code></pre>
        </div>
    </div>
    </div>




    <div class="<?= $classEach; ?>">
    <div class="card">
        <div class="card-header">
            <h5>AngularJS (deprecated) - HTTP POST</h5>

            <div class="card-header-right">

            </div>
        </div>
        <div class="card-body">
                <pre><code class="language-javascript">

                        var xhttp = new XMLHttpRequest();

                        xhttp.open("POST", URL, true);
                        xhttp.setRequestHeader('x-csrf-token', '<?= $csrf; ?>');
                        xhttp.onload = function(event);
                        ....
                    </code></pre>
        </div>
    </div>
    </div>

    <div class="<?= $classEach; ?>">
    <div class="card">
        <div class="card-header">
            <h5>VUE - Axios GET</h5>

            <div class="card-header-right">

            </div>
        </div>
        <div class="card-body">
                <pre><code class="language-php">function getExample() {
    console.log(URL);
    let URL = "<?= $webroot; ?>SetupPages/get";
    axios.get(URL).then(function (response) {
    console.log("response");
    console.log(response);
    if (response.data.STATUS == 200) {
        console.log();
    } else {
        console.log('Fail - something went wrong');
    }
    });
}
                    </code></pre>
        </div>
    </div>
    </div>

    <div class="<?= $classEach; ?>">
    <div class="card">
        <div class="card-header">
            <h5>JSON</h5>

            <div class="card-header-right">

            </div>
        </div>
        <div class="card-body">
                <pre><code class="language-php">function jsonName(){
    $jsonData = file_get_contents('php://input');
    if (empty($jsonData)) {
    $jsonData ='{"id":-1}';
    }

    $user_id = $this->getUserId();
    $res = $this->Models->function($user_id, $jsonData);

    $this->jsonHeaders( json_encode($res) );
}
                    </code></pre>
        </div>
    </div>
    </div>

    <div class="<?= $classEach; ?>">
    <div class="card">
        <div class="card-header">
            <h5>Transaction</h5>

            <div class="card-header-right">

            </div>
        </div>
        <div class="card-body">
                <pre><code class="language-php">

    $errors = false;

    $connection = ConnectionManager::get('default');
    $connection->begin();

    if ($anyProblems) {
    $errors = true;
    }

    if ($errors) {
    $connection->rollback();
    } else {
    $connection->commit();
    }

                    </code></pre>
        </div>
    </div>
    </div>

    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5> CSS Hide / show on Desktop / phone</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">
@media screen and (max-width: 600px) {
    .hiddenDesktop {
        display: none;
    }
}

@media screen and (min-width: 600px) {
    .hiddenPhone {
        display: none;
    }
}
                    </code></pre>
            </div>
        </div>
    </div>








    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>VUEjs - Conditional classes</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">:class="{greyed: shipping.key === 'create'}"

                    </code></pre>
            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP 4 datepicker</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$this->Form->text('date',['type' => 'date']); ?></code></pre>
            </div>
        </div>
    </div>






    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - Table Locator</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$object = $this->getTableLocator()->get('MODEL')->method();</code></pre>
            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - Factor Locator</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$object = FactoryLocator::get('Table')->get('MODEL')->method();</code></pre>
            </div>
        </div>
    </div>






    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>VUEjs - Dropdown</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">

&#x3C;label&#x3E;Gender &#x3C;small class=&#x22;&#x22;red-font&#x22;&#x22;&#x3E;*&#x3C;/small&#x3E;&#x3C;/label&#x3E;
&#x3C;select id=&#x22;&#x22;id&#x22;&#x22; v-model=&#x22;&#x22;formData.inputname&#x22;&#x22; name=&#x22;&#x22;formData.inputName&#x22;&#x22; class=&#x22;&#x22;form-control&#x22;&#x22;&#x3E;
&#x3C;option v-for=&#x22;&#x22;(value, key) in dropdown&#x22;&#x22; :value=&#x22;&#x22;key&#x22;&#x22;&#x3E;{{value}}&#x3C;/option&#x3E;
&#x3C;/select&#x3E;

                    </code></pre>
            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - find LIST</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$list = $this->MODEL->find('list', ['keyField' => 'id', 'valueField' => ('user_type')])->toArray();</code></pre>
            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - JoinTable in Model</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$this->belongsToMany('Users',
    ['joinTable' => 'rebate_dealers_users',
    'foreignKey' => 'rebate_dealer_id',
    'targetForeignKey' => 'user_id'
]);
                    </code></pre>

            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - Add created / modified automatically</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">
                        //add to initialize in model
                        $this->addBehavior('Timestamp');
                    </code></pre>

            </div>
        </div>
    </div>






    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>VUEjs - joinTable</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">&#x3C;li ng-repeat=&#x22;season in dealer.rebate_seasons&#x22; ng-if=&#x22;season._joinData.visible_supplier&#x22;&#x3E;</code></pre>

            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP 4 - Find first</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$user = $this->ind()->select('id')->where(['email' => $email])->first();
                    </code></pre>

            </div>
        </div>
    </div>


    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP 4 - Delete link</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">echo $this->Html->link('Delete', ['prefix' =>'Manager', 'action' => 'delete', $each[ 'id' ]],['confirm' => 'Are You sure you want to delete']);?>
                    </code></pre>

            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP 4 - New layout</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-php">$this->viewBuilder()->setLayout("vue_layout"); // assign layout
                    </code></pre>

                <pre><code class="language-php">$this->viewBuilder()->disableAutoLayout(); // to disable layout
                    </code></pre>

            </div>
        </div>
    </div>



    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Javascript - hide / add class</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">document.getElementById("results").setAttribute("style", "display: none;");
                    </code></pre>


                <pre><code class="language-css">document.getElementById("results").classList = "msg-success";
                    </code></pre>



            </div>
        </div>
    </div>





    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - Save data with entity</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">$data = json_decode($jsonData, true);
$dataEntity = $this->newEntity($data);
                    </code></pre>






            </div>
        </div>
    </div>



    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>VUEjs - Foreach</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">Object.entries(newThis.jobTitles).forEach(entry => {    const [key, value] = entry;    console.log('key');    console.log(key);    console.log('value');    console.log(value);    });

                    </code></pre>

            </div>
        </div>
    </div>




    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>CakePHP4 - Form Create</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <pre><code class="language-css">$this->Form->create(null, ['url' => ['language' => $this->Lang->get(), 'controller' => 'Users', 'action' => 'submitToUpdatecase']]); ?>
                    </code></pre>



                <pre><code class="language-css">public function addUser(){
$user = $this->Users->newEmptyEntity();
if ($this->request->is('post')) {
    $userData = $this->Users->patchEntity($user, $this->request->getData());
    // Edit: some of the entity properties are manually set at this point, e.g.
    $userData->group_id = 1;
    if ($this->Users->save($userData)) {
        $userData = json_encode($userData->toArray());
        $this->Flash->success('User Saved');
    }else{
        $this->Flash->error('Error not Saved');
    }
}// end of  post

                    </code></pre>

            </div>
        </div>
    </div>






    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <a name="pdfRender"></a>
                <h5>PHP - Render PDF <a href="#pdfRender">#</a></h5>
                <p>
                    Allows to render a PDF from a HTML page which contains various data you want your client to print.
                    IMPORTANT: You MUST add IP verification or token authorization as the concept is insecure
                </p>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">


                <pre><code class="language-php">//located in a prefix to ensure only validated users can initiate
public function download($id) {
    $setupCase = new SetupCase; //our setup case library
    $setupCase->createPdf(Router::url('/', true).'Users/download/'.$id.'?token=123', 'infoPdf.pdf');
}
                    </code></pre>

                <pre><code class="language-php">//This is located in our SetupCase library in a util class
var $dpi = 30;
var $factor = 1;
function setSize($width, $height) {
    $this->width_pixels = $width * (($this->dpi * $this->factor) + 0); //1 in = 96 px / add fine adjustments to the end
    $this->height_pixels = $height * (($this->dpi * $this->factor) + 0); //1 in = 96 px
}
function createPdf($url, $filename) {

    $this->setSize(8.5, 11);

    //dd($url);
    $cmd = "wkhtmltopdf "
    . " --dpi ".$this->dpi." --page-width ".$this->width_pixels." --page-height ".$this->height_pixels
    ." --margin-top 0 --margin-right 0 --margin-bottom 0 --margin-left 0 " . $url . " " . TMP.$filename;
    if (isset($_GET['debug'])) {
    dd($cmd); //used to debug and get the link for testing
    }
    exec($cmd);
    header("Content-type:application/pdf");
    header("Content-Disposition:attachment;filename=\"$filename\"");
    //Add more headers here
    readfile(TMP.$filename);
    exit;
}
                    </code></pre>

                <pre><code class="language-php">//this is located in an un-authenticated function call (without a prefix)

public function download($id) {

    //@todo add security here to ensure this view never get's displayed to the public (ip address / token etc)
    $this->viewBuilder()->setLayout('print'); //make sure you create a print layout

    //get your data you want to display in the view which will be saved as a pdf
    $info = $this->Users->find('all',[
    'conditions' => [],
    'contain' => ['Groups']
    ])->first()->toArray();

    $this->set(compact('info'));

}
                    </code></pre>


                <pre><code class="language-html">//templates/Users/download.php
    < h1>< ?= $info['name']; ?></h1>
                        ......

                    </code></pre>




            </div>
        </div>
    </div>










    <div class="<?= $classEach; ?>">
        <div class="card">
            <div class="card-header">
                <h5>Cake Initial index/edit table</h5>

                <div class="card-header-right">

                </div>
            </div>
            <div class="card-body">
                <h3>
                    Controller
                </h3>
                <pre><code class="language-php">function index() {
    $cols = $this->MODEL->getSchema()->columns();
    $this->set('cols', $cols);

    $rows = $this->MODEL->find('all');
    $this->set(compact('rows'));
}//index

function edit($id=false) {

        $cols = $this->MODEL->getSchema()->columns();
        $this->set('cols', $cols);

        $row = $this->MODEL->newEmptyEntity();
        $postData = $this->request->getData();

        if (!empty($postData)) {
            $row = $this->MODEL->patchEntity($row, $postData);
            if ($this->MODEL->save($row)) {
                $this->Flash->success('Saved');
                if (in_array($row['cameFrom'], ['/', ''])) {
                    $this->redirect(['action' => 'index']);
                } else {
                    $this->redirect($row['cameFrom']);
                }
            } else {
                $this->Flash->error('Error saving');
            }
        } else {// end of  post
            if ($id === 'new') {
                //New - row entity will be used
            } else {
                $row = $this->MODEL->get($id);
            }
            $row->cameFrom = $this->referer();
        }
        $this->set('row', $row);
    }//edit

function delete($id = NULL) {
    if (!$id) {
        $this->Flash->error('Specify ID');
        $this->redirect(array('prefix'=>'Staff', 'action' => 'index'));
    }else {
        $entity = $this->MODEL->get($id);
        if ($this->MODEL->delete($entity)) {
            $this->Flash->success('Deleted '.$entity->name);
        } else {
            $this->Flash->error('Error cannot delete');
        }
        $this->redirect($this->referer());
    }
}//delete</code></pre>

                <h3>
                    View - index.php
                </h3>
                <pre><code class="language-php">&lt;table class="table">
    &lt;thead>
    &lt;tr>
        &lt;th>
            Actions
        &lt;/th>
        &lt;?php foreach ($cols as $col): ?>
            &lt;th>
                &lt;?= $col; ?>
            &lt;/th>
        &lt;?php endforeach; ?>
    &lt;/tr>
    &lt;/thead>
    &lt;tbody>
    &lt;?php foreach ($rows as $row): ?>
        &lt;tr>
            &lt;th>
                &lt;?= $this->Html->link('Edit', [
                    'action' => 'edit', $row['id']
                ], ['class' => 'btn btn-primary']); ?>
            &lt;/th>
            &lt;?php foreach ($cols as $col): ?>
                &lt;th>
                    &lt;?= $row[$col]; ?>
                &lt;/th>
            &lt;?php endforeach; ?>
        &lt;/tr>
    &lt;?php endforeach; ?>
    &lt;/tbody>
&lt;/table></code></pre>

                <h3>
                    View - edit.php
                </h3>
                <pre><code class="language-php">&lt;?= $this->Form->create($row, array(
    'novalidate' => true,
    //'type' => 'file' //uncomment if you want to upload files
)); ?>
&lt;?= $this->Form->hidden('id'); ?>
&lt;?= $this->Form->hidden('cameFrom'); ?>

&lt;?php foreach ($cols as $col): ?>
    &lt;?= $this->Form->control($col, ['class' => 'form-control']); ?>
&lt;?php endforeach; ?>

&lt;?= $this->Form->button('Save', ['class' => 'btn btn-primary']); ?>
&lt;?= $this->Form->end(); ?></code></pre>
            </div>
        </div>
    </div>

</div>




    <h3> OLD TO BE MOVED ABOVE</h3>
    <table class="table">
        <tr>
            <th>
                CodeBlock
            </th>
            <th>
                Description
            </th>
        </tr>

        <tr>
            <th>
                Language
            </th>
            <td>

            </td>
        </tr>

        <tr>
            <th>
                File Storage (Store files on your server)
            </th>
            <td>
                <?php echo $this->Form->create(null, ['type' => 'file', 'url' => ['controller' => 'SetupPages', 'action' => 'fileAdd']]); ?>

                <?php echo $this->Form->file('fileToUpload', ['type' => 'button']); ?>

                <?= $this->Form->control('key_name', ['class' => 'form_control']); ?>
                <?= $this->Form->button('Upload'); ?>

                <?php echo $this->Form->end(); ?>

                <?php if (!empty($allFiles)): ?>
                    <hr/><?php endif; ?>

                <?php foreach ($allFiles as $object) :

                    //pr ($object);
                    ?>
                    <div class="row">

                        <div class="col-lg-3">
                            <?= $this->Html->image('/SetupPages/fileView/' . $object['key_name'], ['style' => 'width: 250px;']); ?>
                        </div>

                        <div class="col-lg-9">

                            Filename: <?= $object['filename']; ?> (key_name: <?= $object['key_name']; ?>)
                            <?= $this->Html->link('Download', ['action' => 'fileDownload', $object['key_name']]); ?>

                            <br/>

                            <a style="color: red; font-weight: bold;"
                               href="<?= $webroot; ?>SetupPages/fileDelete/<?= $object['key_name']; ?>">X</a>

                        </div>

                    </div>
                <?php endforeach; ?>


            </td>
        </tr>
        <tr>
            <th>
                Object Storage (Store files in a database)
            </th>
            <td>
                <?php echo $this->Form->create(null, ['type' => 'file', 'url' => ['controller' => 'SetupPages', 'action' => 'objAdd']]); ?>

                <?php echo $this->Form->file('fileToUpload', ['type' => 'button']); ?>

                <?= $this->Form->control('key_name', ['class' => 'form_control']); ?>
                <?= $this->Form->button('Upload'); ?>

                <?php echo $this->Form->end(); ?>

                <?php if (!empty($allFiles)): ?>
                    <hr/><?php endif; ?>

                <?php foreach ($allFiles as $object) :

                    //pr ($object);
                    ?>
                    <div class="row">

                        <div class="col-lg-3">
                            <?= $this->Html->image('/SetupPages/fileView/' . $object['key_name'],['style' => 'width: 250px;']); ?>
                        </div>

                        <div class="col-lg-9">

                            Filename: <?= $object['filename']; ?> (key_name: <?= $object['key_name']; ?>)
                            <?= $this->Html->link('Download', ['action' => 'fileDownload', $object['key_name']]); ?>

                            <br/>

                            <a style="color: red; font-weight: bold;"
                               href="<?= $webroot; ?>SetupPages/fileDelete/<?= $object['key_name']; ?>">X</a>

                        </div>

                    </div>
                <?php endforeach; ?>


            </td>
        </tr>
        <tr>
            <th>
                <?php echo $this->Html->link('Responsive Table', ['controller' => 'SetupPages', 'action' => 'responsiveTable']); ?>
            </th>
            <td>
                HTML table adjusts to rows when on a mobile device. Desktop is a normal table view
            </td>
        </tr>
        <tr>
            <th>
                <?php echo $this->Html->link('Sticky div / Floating div', ['controller' => 'SetupPages', 'action' => 'sticky']); ?>
            </th>
            <td>

                A div will float (or stick to the top of the window) as the user scrolls.

            </td>
        </tr>
        <tr>
            <th>
                <?php echo $this->Html->link('VUE validation', ['language' => 'en', 'controller' => 'SetupPages', 'action' => 'formValidation']); ?>

            </th>
            <td>
                Validate a form with a simple validation object
            </td>
        </tr>
        <tr>
            <th>
                <?php echo $this->Html->link('VUE timed form submission', ['language' => 'en', 'controller' => 'SetupPages', 'action' => 'setTimer']); ?>

            </th>
            <td>
                Form input will only submit after the user stops typing for a few seconds
            </td>
        </tr>
        <tr>
            <th>
                <?php echo $this->Html->link('Auto pagination', ['controller' => 'SetupPages', 'action' => 'increaseLimit']); ?>

            </th>
            <td>
                As the user scrolls to the bottom of the page, the system will automatically load the next page / set of
                results. Instead of manually pushing next / previous pages.
            </td>
        </tr>
        <tr><!-- digital signage -->
            <th>
                <?php echo $this->Html->link('Digital Signage Template', ['controller' => 'SetupPages', 'action' => 'digitalSignage']); ?>

            </th>
            <td>
                Basic template for digital signage. Title, subtitle, text cycling between slides which can be scheduled
                with a php array
            </td>
        </tr>
        <tr>
            <th>
                Activity Monitor
            </th>
            <td>
                Tracks actions of users on predefined actions in a database for long term and ease of viewing

                <br/>

                <?= $this->Html->link('View-ActivityLog', ['prefix' => 'Admin', 'controller' => 'SetupPages', 'action' => 'activity-logs']); ?>
                <?= $this->Html->link('Test-addToLog', ['controller' => 'SetupPages', 'action' => 'activityLogAddToLog']); ?>
            </td>
        </tr>
        <tr>
            <th>
                Drag and Drop Upload

            </th>
            <td>
                Drag and drop files on the screen to upload efficiently
                <br/>

                <?= $this->Html->link('Add-Files', ['prefix' => 'Staff', 'controller' => 'SetupPages', 'action' => 'dragDrop']); ?>

            </td>
        </tr>
        <tr>
            <th>
                Google Analytics GA4
            </th>
            <td>
                Google Analytics
                <br/>

                <?= $this->Html->link('GoogleAnalytics', [
                    'prefix' => false,
                    'controller' => 'SetupPages',
                    'action' => 'googleAnalytics'
                ]); ?>

            </td>
        </tr>
        <tr>
            <th>
                Export to CSV

            </th>
            <td>
                Export an array into a CSV file that is downloaded to your computer. Simply add to your controller a
                basic array of your prepared data.
                It will auto create the CSV and add headers to download to your computer

                <br/>
                SetupCase::downloadCsv($rows, $filename, $columnsSort = false);
            </td>
        </tr>
        <tr>
            <th>
                Render PDF
                <br/>
                (COMING SOON...)
            </th>
            <td>
                Render a PDF from a custom HTML view and export and save to your computer
            </td>
        </tr>
        <tr>
            <th>
                JSON API Headers
                <br/>
                (COMING SOON...)
            </th>
            <td>
                Common function to always call the correct JSON headers when you are sending data between the front-end
                / back-end API
            </td>
        </tr>




        <tr>
            <th>
                Read more with fade
            </th>
            <td>

                Show partial text with a fade out and link to reveal all

                <?= $this->Html->link('Read more with fade', ['action' => 'readMore'], ['class' => 'btn btn-primary']); ?>


            </td>
        </tr>




    </table>


    Code Blocks


    <br/>
    <br/>
    <br/>
    <br/>
    <br/>
    <br/>
    <br/>
