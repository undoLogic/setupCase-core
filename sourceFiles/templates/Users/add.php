<!-- in /templates/Users/login.php -->
<div class="users form">
    <?= $this->Flash->render() ?>
    <h3>Create New User</h3>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Please enter your username and password') ?></legend>
        <?= $this->Form->control('email', ['required' => true]) ?>
        <?= $this->Form->control('password', ['required' => true]) ?>
        <?= $this->Form->control('user_type', ['options' => ['ADMIN' => 'Admin', 'MANAGER' => 'Manager', 'STAFF' => 'Staff'], 'required' => true]) ?>

    </fieldset>
    <?= $this->Form->submit(__('Login')); ?>
    <?= $this->Form->end() ?>
</div>

<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
