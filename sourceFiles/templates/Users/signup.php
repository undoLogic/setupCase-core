<?= $this->Form->create(); ?>
    <div class="card">
        <div class="card-header">
            <h5>Signup</h5>
        </div>

        <div class="card-body">
            <div class="row">

                <div class="col-lg-12">
                    <?= $this->Form->control('email', ['class' => 'form-control', 'placeholder' => 'Email']); ?>
                </div>
                <div class="col-lg-12">
                    <?= $this->Form->control('password', ['class' => 'form-control', 'type' => 'password', 'placeholder' => 'Password']); ?>
                </div>
                <div class="col-lg-12 text-left m-t-2">
                    <?= $this->Form->submit('Signup', ['class' => 'btn btn-primary']); ?>
                </div>
            </div>
        </div>

    </div>
<?= $this->Form->end(); ?>
