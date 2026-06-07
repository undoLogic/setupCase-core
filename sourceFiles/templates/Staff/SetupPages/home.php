<?php if (isset($isLoggedIn)): ?>
    LOGGED IN (<?php echo $this->Html->link('Logout', '/logout'); ?>)
<?php else: ?>
    NOT logged in (<?php echo $this->Html->link('Login', '/login'); ?>)
<?php endif; ?>

<hr/>


<h2>
    STAFF ACCESS - current lang: ???
</h2>


<br/>

<?php echo $this->Html->link('Staff EN', array(
    'prefix' => 'Staff',
    'language' => 'en'

)); ?>

<br/>

<?php echo $this->Html->link('Staff FR', array(
    'prefix' => 'Staff',
    'language' => 'fr'

)); ?>

<br/>

<?php echo $this->Html->link('Staff lang (NOT SET)', array('prefix' => 'Staff')); ?>

<hr/>

<h2>
    Prefix
</h2>

<?php echo $this->Html->link('Return back to NON prefix page', array(
    'prefix' => false,
)); ?>
