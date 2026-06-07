<?= $this->Form->create($row); ?>
<?= $this->Form->hidden('id'); ?>
<?= $this->Form->hidden('cameFrom'); ?>
<div class="card">
    <div class="card-header">
        <h5>
            <?= $this->Html->link('Return to Translations', ['action' => 'index']); ?> ->

            Add/Edit</h5>


        <div class="card-header-right">

                <?= $this->Form->submit('Submit',['class'=>'btn btn-primary']); ?>

        </div>
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-lg-12">
                <?php if($row['id']): ?>
                <?= $this->Form->hidden('keyword'); ?>
                <h3>Keyword: <?= $row['keyword']; ?></h3>
                <?php else: ?>
                    <?= $this->Form->control('keyword',[
                        'class'=>'form-control',
                        'label'=>'Keyword',

                    ]); ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-12 mt-2">

                <h5 class="mt-3">English</h5>


                <div class="input-group">
                    <?= $this->Form->text('en', [
                        'class' => 'form-control',
                        'id' => 'translateInput', // ✅ add an ID for JS
                        'placeholder' => "Enter English text",
                        'aria-label' => "Recipient's username",
                        'aria-describedby' => 'basic-addon2',
                        'label' => false,
                        'div' => false,
                    ]); ?>
                    <div class="input-group-append">
                        <a href="#" id="translateBtn" class="btn btn-danger" target="_blank">
                            Lookup French word with Google Translate
                        </a>
                    </div>
                </div>

                <script>
                    document.getElementById('translateBtn').addEventListener('click', function(e) {
                        const input = document.getElementById('translateInput').value.trim();
                        if (!input) {
                            e.preventDefault();
                            alert('Please enter a word or phrase to translate.');
                            return;
                        }
                        // Encode the input to be URL safe
                        const url = 'https://translate.google.ca/?sl=en&tl=fr&text=' + encodeURIComponent(input) + '&op=translate';
                        this.href = url; // dynamically set the href before opening
                    });
                </script>








            </div>

            <div class="col-lg-12 mt-2">


                <h5 class="mt-3">French</h5>

                <div class="input-group">
                    <?= $this->Form->text('fr', [
                        'class' => 'form-control',
                        'id' => 'translateInputFr', // ✅ unique ID for JS
                        'placeholder' => "French translation",
                        'aria-label' => "French translation",
                        'aria-describedby' => 'basic-addon2',
                        'label' => false,
                        'div' => false,
                    ]); ?>
                    <div class="input-group-append">
                        <a href="#" id="translateBtnFr" class="btn btn-primary" target="_blank">
                            Lookup English word with Google Translate
                        </a>
                    </div>
                </div>

                <script>
                    document.getElementById('translateBtnFr').addEventListener('click', function(e) {
                        const input = document.getElementById('translateInputFr').value.trim();
                        if (!input) {
                            e.preventDefault();
                            alert('Please enter a French word or phrase to translate.');
                            return;
                        }
                        const url = 'https://translate.google.ca/?sl=fr&tl=en&text=' + encodeURIComponent(input) + '&op=translate';
                        this.href = url;
                    });
                </script>




            </div>


        </div>
    </div>
</div>
<?= $this->Form->end(); ?>
