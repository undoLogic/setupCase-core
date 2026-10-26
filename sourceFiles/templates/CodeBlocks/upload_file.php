<?= $this->Form->Create(null, array(
    'novalidate' => true,
    'type' => 'file'
)); ?>

<?= $this->Form->file('file', array('class' => 'btn btn-primary')); ?>

<br>

<?= $this->Form->button('Upload', array('class' => 'btn btn-primary')); ?>
<?= $this->Form->end(); ?>
