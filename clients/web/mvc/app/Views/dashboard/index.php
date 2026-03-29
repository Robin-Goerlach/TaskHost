<div class="hero">
    <h1>Hallo <?= e($user['name'] ?? 'Benutzer') ?> 👋</h1>
    <p class="muted">
        Hier siehst du deine Listen. Lege zuerst eine Liste an und verwalte darin deine Aufgaben.
    </p>
</div>

<div class="split">
    <section class="card">
        <h2>Neue Liste anlegen</h2>
        <p class="muted small">Beispiele: Arbeit, Privat, Einkaufen, Ideen, Weiterbildung</p>

        <form method="post" action="/lists">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <label>
                Listenname
                <input type="text" name="title" placeholder="z. B. Arbeit" required>
            </label>

            <button type="submit">Liste erstellen</button>
        </form>
    </section>

    <section class="card">
        <h2>Kurzübersicht</h2>
        <div class="grid">
            <div><span class="badge">Benutzer</span> <?= e($user['email'] ?? '') ?></div>
            <div><span class="badge">Listen</span> <?= count($lists) ?></div>
            <div><span class="badge">Seit</span> <?= e(substr((string) ($user['created_at'] ?? ''), 0, 10)) ?></div>
        </div>
    </section>
</div>

<section style="margin-top: 1.5rem;">
    <div class="hero">
        <h2>Deine Listen</h2>
        <p class="muted">Öffne eine Liste, um Aufgaben anzulegen, zu bearbeiten oder abzuhaken.</p>
    </div>

    <?php if ($lists === []): ?>
        <div class="card">
            <p>Du hast noch keine Listen angelegt.</p>
        </div>
    <?php else: ?>
        <div class="list-grid">
            <?php foreach ($lists as $list): ?>
                <article class="card list-card">
                    <div>
                        <h3><?= e($list['title']) ?></h3>
                        <p class="muted small">
                            <?= (int) $list['completed_count'] ?> von <?= (int) $list['task_count'] ?> Aufgaben erledigt
                        </p>
                    </div>

                    <div class="actions">
                        <a class="button" href="/lists/<?= (int) $list['id'] ?>">Öffnen</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
