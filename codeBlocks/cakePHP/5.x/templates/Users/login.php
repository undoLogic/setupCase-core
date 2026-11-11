<div class="container py-5" style="max-width: 420px;">
    <?= $this->Flash->render() ?>
    <h3>Login</h3>
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Please enter your email and password') ?></legend>
        <?= $this->Form->control('email', ['type' => 'email', 'required' => true]) ?>
        <?= $this->Form->control('password', ['required' => true]) ?>
        <?= $this->Form->control('remember_me', [
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </fieldset>
    <?= $this->Form->submit(__('Login')) ?>
    <?= $this->Form->end() ?>
</div>
