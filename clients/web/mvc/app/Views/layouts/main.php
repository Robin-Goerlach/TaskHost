<?php
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'TaskHost') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<header class="app-header">
    <div class="container header-inner">
        <div>
            <a class="brand" href="<?= isset($_SESSION['user_id']) ? '/dashboard' : '/login' ?>">TaskHost</a>
            <div class="small muted">PHP MVC Aufgabenverwaltung</div>
        </div>
        <div class="nav-actions">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a class="button button-secondary" href="/dashboard">Dashboard</a>
                <form method="post" action="/logout" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="button-danger">Abmelden</button>
                </form>
            <?php else: ?>
                <a class="button button-secondary" href="/login">Anmelden</a>
                <a class="button" href="/register">Registrieren</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <div class="container">
        <?php require VIEW_PATH . '/partials/flash.php'; ?>
        <?= $content ?>
    </div>
</main>
</body>
</html>
