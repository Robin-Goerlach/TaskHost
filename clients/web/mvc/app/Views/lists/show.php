<div class="hero">
    <div class="actions">
        <a class="button button-secondary" href="/dashboard">← Zurück zum Dashboard</a>
    </div>
    <h1><?= e($list['title']) ?></h1>
    <p class="muted">
        Verwalte hier die Aufgaben dieser Liste. Du kannst Aufgaben anlegen, abhaken, ändern und löschen.
    </p>
</div>

<div class="split">
    <section class="card">
        <h2>Liste bearbeiten</h2>
        <form method="post" action="/lists/<?= (int) $list['id'] ?>/rename">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label>
                Listenname
                <input type="text" name="title" value="<?= e($list['title']) ?>" required>
            </label>
            <button type="submit">Umbenennen</button>
        </form>

        <hr class="separator">

        <form method="post" action="/lists/<?= (int) $list['id'] ?>/delete" onsubmit="return confirm('Liste wirklich löschen? Alle Aufgaben dieser Liste werden entfernt.');">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="button-danger">Liste löschen</button>
        </form>
    </section>

    <section class="card">
        <h2>Neue Aufgabe</h2>
        <form method="post" action="/lists/<?= (int) $list['id'] ?>/tasks">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <label>
                Titel
                <input type="text" name="title" placeholder="z. B. Angebot schreiben" required>
            </label>

            <label>
                Priorität
                <select name="priority">
                    <option value="low">Niedrig</option>
                    <option value="medium" selected>Mittel</option>
                    <option value="high">Hoch</option>
                </select>
            </label>

            <label>
                Fällig am
                <input type="date" name="due_date">
            </label>

            <label>
                Notizen
                <textarea name="notes" placeholder="Zusätzliche Hinweise zur Aufgabe"></textarea>
            </label>

            <button type="submit">Aufgabe speichern</button>
        </form>
    </section>
</div>

<section style="margin-top: 1.5rem;" class="grid">
    <div class="hero">
        <h2>Aufgaben</h2>
        <p class="muted">Offene Aufgaben stehen oben, erledigte Aufgaben darunter.</p>
    </div>

    <?php if ($tasks === []): ?>
        <div class="card">
            <p>Noch keine Aufgaben in dieser Liste vorhanden.</p>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <?php $completed = (int) $task['is_completed'] === 1; ?>
            <article class="task-item <?= $completed ? 'task-completed' : '' ?>">
                <div class="task-header">
                    <div>
                        <div class="task-title"><?= e($task['title']) ?></div>
                        <div class="muted small">
                            <?php if (!empty($task['due_date'])): ?>
                                Fällig: <?= e($task['due_date']) ?> ·
                            <?php endif; ?>
                            Erstellt: <?= e(substr((string) $task['created_at'], 0, 10)) ?>
                        </div>
                    </div>
                    <div class="priority priority-<?= e($task['priority']) ?>">
                        <?= match ($task['priority']) {
                            'low' => 'Niedrig',
                            'high' => 'Hoch',
                            default => 'Mittel',
                        } ?>
                    </div>
                </div>

                <?php if (!empty($task['notes'])): ?>
                    <div class="muted"><?= nl2br(e($task['notes'])) ?></div>
                <?php endif; ?>

                <div class="actions">
                    <form method="post" action="/tasks/<?= (int) $task['id'] ?>/toggle" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="<?= $completed ? 'button-warning' : 'button-secondary' ?>">
                            <?= $completed ? 'Als offen markieren' : 'Erledigt' ?>
                        </button>
                    </form>
                </div>

                <details>
                    <summary>Bearbeiten</summary>
                    <form method="post" action="/tasks/<?= (int) $task['id'] ?>/update" style="margin-top: 0.8rem;">
                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                        <label>
                            Titel
                            <input type="text" name="title" value="<?= e($task['title']) ?>" required>
                        </label>

                        <label>
                            Priorität
                            <select name="priority">
                                <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Niedrig</option>
                                <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Mittel</option>
                                <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>Hoch</option>
                            </select>
                        </label>

                        <label>
                            Fällig am
                            <input type="date" name="due_date" value="<?= e((string) ($task['due_date'] ?? '')) ?>">
                        </label>

                        <label>
                            Notizen
                            <textarea name="notes"><?= e((string) ($task['notes'] ?? '')) ?></textarea>
                        </label>

                        <div class="actions">
                            <button type="submit">Änderungen speichern</button>
                        </div>
                    </form>
                </details>

                <form method="post" action="/tasks/<?= (int) $task['id'] ?>/delete" onsubmit="return confirm('Aufgabe wirklich löschen?');">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="button-danger">Aufgabe löschen</button>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
