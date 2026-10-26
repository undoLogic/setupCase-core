
<?= $this->Form->create(); ?>

<div class="card my-5">
    <div class="card-body">

        <?php if (isset($email_sent)): ?>
            <h4 class="text-center f-w-500 mt-4 mb-3">
                E-Mail Sent !
                <hr/>
                Check your email to continue
            </h4>
        <?php else: ?>

            <h4 class="text-center f-w-500 mt-4 mb-3">Reset Password</h4>
            <div class="form-group mb-3">
                <?= $this->Form->control('email', ['class' => 'form-control', 'placeholder' => 'Email Address']); ?>
            </div>
            <div class="text-center mt-4">
                <?= $this->Form->submit('Begin Reset', ['class' => 'btn btn-primary shadow px-sm-4']); ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?= $this->Form->end(); ?>
