<div class="col-xl-12">
    <div class="card">
        <div class="card-header">
            <h5>

                Activity Logs (Count: <?= number_format($logs->count(), 0, '.', ','); ?>)

            </h5>
            <div class="card-header-right">

            </div>
        </div>
        <div class="card-block table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <?php foreach ($cols as $col): ?>
                            <th>
                                <?= $col; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <?php foreach ($cols as $col): ?>
                                <th>
                                    <?= $row[$col]; ?>
                                </th>
                            <?php endforeach; ?>

                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
