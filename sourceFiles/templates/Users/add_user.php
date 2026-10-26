<!-- in /templates/Users/login.php -->
<div class="users form">
    <?= $this->Flash->render() ?>
    <h3>Add New User Using Cake4 Form submit</h3>


    <?= $this->Form->create(null, ['url' => ['controller' => 'Users', 'action' => 'addUser']]); ?>
    <fieldset>

        <?= $this->Form->control('name', ['required' => true]) ?>
        <?= $this->Form->control('email', ['required' => true]) ?>

        <?= $this->Form->control('user_type', ['options' => ['ADMIN' => 'Admin', 'MANAGER' => 'Manager', 'STAFF' => 'Staff'], 'required' => true]) ?>

    </fieldset>
    <?= $this->Form->submit('Submit'); ?>
    <?= $this->Form->end() ?>
</div>


<div>
    <?php if(isset($user)): ?>
  User:<?php echo json_encode($user); ?>
    <?php endif; ?>
</div>

<div>
    <?php if(isset($users)): ?>
    Users: <?php echo json_encode($users); ?>
    <?php endif; ?>
</div>
