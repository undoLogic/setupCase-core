<?php if (isset($isLoggedIn)): ?>
    LOGGED IN (<?php echo $this->Html->link('Logout', '/logout'); ?>)
<?php else: ?>
    NOT logged in (<?php echo $this->Html->link('Login', '/login'); ?>)
<?php endif; ?>

<hr/>


<h2>
    ADMIN ACCESS - current lang: <?php echo $lang; ?>
</h2>


<br/>

<?php echo $this->Html->link('Admin EN', array(
    'prefix' => 'Admin',
    'language' => 'en'

)); ?>

<br/>

<?php echo $this->Html->link('Admin FR', array(
    'prefix' => 'Admin',
    'language' => 'fr'

)); ?>

<br/>

<?php echo $this->Html->link('Admin lang (NOT SET)', array('prefix' => 'Admin')); ?>

<hr/>

<h2>
    Prefix
</h2>

<?php echo $this->Html->link('Return back to NON-admin prefix page', array(
    'prefix' => false,
)); ?>
