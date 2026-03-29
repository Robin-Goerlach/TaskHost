<?php
$messages = ($_SESSION['_flash'] ?? []);
unset($_SESSION['_flash']);
?>
<?php foreach ($messages as $message): ?>
    <div class="flash flash-<?= e($message['type']) ?>">
        <?= e($message['message']) ?>
    </div>
<?php endforeach; ?>
