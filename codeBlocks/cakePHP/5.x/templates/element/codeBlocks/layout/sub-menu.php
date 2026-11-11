<ul class="navbar-nav ms-auto">

    <li>
        <span class="badge badge-info">
            <?= $this->Lang->get(); ?>
        </span>
    </li>

    <li>
        <?php echo $this->Html->link('English', ['language' => 'en'], [
            'class' => $this->Lang->getActiveClass('en')
        ]); ?>
    </li>

    <li>
        <?php echo $this->Html->link('French',
        ['language' => 'fr'], [
            'class' => $this->Lang->getActiveClass('fr')
        ]); ?>
    </li>

    <li>
        <?php echo $this->Html->link('Spanish', ['language' => 'es'], [
            'class' => $this->Lang->getActiveClass('es')
        ]); ?>
    </li>







    <li class="nav-item">
        <a class="nav-link" target="_blank" href="https://www.setupcase.com/">SetupCase.com</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" target="_blank" href="https://store.setupcase.com/">Store</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" target="_blank" href="https://github.com/undoLogic/setupCase-core">GitHub</a>
    </li>
</ul>
