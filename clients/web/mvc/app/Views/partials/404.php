<div class="card hero">
    <h1>404 – Seite nicht gefunden</h1>
    <p class="muted">Die angeforderte Seite existiert nicht oder du hast keinen Zugriff darauf.</p>
    <div>
        <a class="button" href="<?= !empty($_SESSION['user_id']) ? '/dashboard' : '/login' ?>">Zurück</a>
    </div>
</div>
