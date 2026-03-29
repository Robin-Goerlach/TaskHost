<div class="auth-shell card">
    <div class="hero">
        <h1>Registrieren</h1>
        <p class="muted">
            Erstelle dein Konto und starte direkt mit deinen ersten Listen und Aufgaben.
        </p>
    </div>

    <form method="post" action="/register">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            Name
            <input type="text" name="name" value="<?= e(old('name')) ?>" required>
        </label>

        <label>
            E-Mail
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
        </label>

        <label>
            Passwort
            <input type="password" name="password" minlength="8" required>
        </label>

        <button type="submit">Konto erstellen</button>
    </form>

    <hr class="separator">

    <p class="muted small">
        Bereits registriert? <a href="/login">Zum Login</a>
    </p>
</div>
