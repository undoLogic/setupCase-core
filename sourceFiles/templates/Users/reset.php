<?= $this->Form->create(); ?>
    <div class="card">
        <div class="card-header">
            <h5>Reset password for <?= $email; ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12">
                    <?= $this->Form->control('new_password', ['class' => 'form-control', 'type' => 'password', 'placeholder' => 'Password']); ?>

                    <br/>
                    <?= $this->Form->submit('Reset Password', ['class' => 'btn btn-primary']); ?>
                </div>
            </div>
        </div>
    </div>
<?= $this->Form->end(); ?>
