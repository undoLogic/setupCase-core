<nav class="sidebar pt-3">
    <ul class="nav flex-column">

        <?php foreach ($menu as $menuKey => $eachMenu): ?>

            <li class="nav-item <?= !empty($eachMenu['active']) ? 'active' : '' ?>">

                <?php if (empty($eachMenu['children'])): ?>

                    <?= $this->Html->link(
                        $eachMenu['name'],
                        $eachMenu['link'],
                        [
                            'class' => 'nav-link ' . (!empty($eachMenu['active']) ? 'active' : '')
                        ]
                    ); ?>

                <?php else: ?>

                    <a class="nav-link d-flex justify-content-between align-items-center <?= !empty($eachMenu['active']) ? 'active' : '' ?>"
                       data-bs-toggle="collapse"
                       href="#menu<?= h($menuKey); ?>"
                       role="button"
                       aria-expanded="<?= !empty($eachMenu['active']) ? 'true' : 'false' ?>">
                        <?= h($eachMenu['name']); ?>
                        <span class="bi bi-chevron-down small"></span>
                    </a>

                    <div class="collapse ps-3 <?= !empty($eachMenu['active']) ? 'show' : '' ?>"
                         id="menu<?= h($menuKey); ?>">
                        <ul class="nav flex-column">

                            <?php foreach ($eachMenu['children'] as $eachSubMenu): ?>

                                <li class="nav-item <?= !empty($eachSubMenu['active']) ? 'active' : '' ?>">
                                    <?= $this->Html->link(
                                        $eachSubMenu['name'],
                                        $eachSubMenu['link'],
                                        [
                                            'class' => 'nav-link ' . (!empty($eachSubMenu['active']) ? 'active' : '')
                                        ]
                                    ); ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>
                    </div>

                <?php endif; ?>

            </li>

        <?php endforeach; ?>

    </ul>
</nav>


    <hr class="my-3">

    <small class="text-muted px-3">
        Developed by <a href="https://www.undoLogic.com/" target="_blank">undoLogic</a>
    </small>
</nav>
