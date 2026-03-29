<div class="auth-shell card">
    <div class="hero">
        <h1>Anmelden</h1>
        <p class="muted">
            Willkommen bei TaskHost. Melde dich an, um deine Listen und Aufgaben zu verwalten.
        </p>
    </div>

    <form method="post" action="/login">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <label>
            E-Mail
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
        </label>

        <label>
            Passwort
            <input type="password" name="password" required>
        </label>

        <button type="submit">Anmelden</button>
    </form>

    <hr class="separator">

    <p class="muted small">
        Noch kein Konto? <a href="/register">Jetzt registrieren</a>
    </p>
</div>
