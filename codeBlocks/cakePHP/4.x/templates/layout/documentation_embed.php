<?php
/**
 * Bare layout for the Documentation viewer inside the modal iframe
 * (DocumentationController::view with ?embed=1).
 *
 * @var \App\View\AppView $this
 * @var string $webroot
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->fetch('title')); ?></title>
    <link href="<?= $webroot; ?>css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-2">
<?= $this->Flash->render(); ?>
<?= $this->fetch('content'); ?>
</body>
</html>
