import {
  badgeClassFromColor,
  escapeHtml,
  formatDate,
  formatDateTime,
  formatRelativeDueLabel,
  humanFileSize,
  toDateTimeLocalValue,
} from '../utils/date.js';

function smartViewLabel(view) {
  return {
    today: 'Heute',
    planned: 'Geplant',
    starred: 'Wichtig',
    assigned: 'Mir zugewiesen',
    completed: 'Erledigt',
  }[view] || view;
}

function emptyState(title, body) {
  return `
    <div class="empty-state">
      <h3>${escapeHtml(title)}</h3>
      <p>${escapeHtml(body)}</p>
    </div>
  `;
}

function renderFolderBlock(folder, lists, selectedListId) {
  const items = lists
    .map(
      (list) => `
        <li class="nav-item ${selectedListId === Number(list.id) ? 'is-active' : ''}">
          <button type="button" data-action="open-list" data-list-id="${list.id}" class="nav-link">
            <span class="nav-link-main">
              <span class="color-dot" style="background:${escapeHtml(badgeClassFromColor(list.color))}"></span>
              <span>${escapeHtml(list.title)}</span>
            </span>
            <span class="nav-link-meta">${escapeHtml(list.access_role || '')}</span>
          </button>
        </li>
      `,
    )
    .join('');

  return `
    <section class="folder-card">
      <div class="folder-card-header">
        <div>
          <div class="eyebrow">Ordner</div>
          <strong>${escapeHtml(folder.title)}</strong>
        </div>
        <div class="inline-actions">
          <button type="button" class="ghost-button" data-action="move-folder-up" data-folder-id="${folder.id}" title="Ordner nach oben">↑</button>
          <button type="button" class="ghost-button" data-action="move-folder-down" data-folder-id="${folder.id}" title="Ordner nach unten">↓</button>
          <button type="button" class="ghost-button" data-action="open-edit-folder-modal" data-folder-id="${folder.id}" title="Ordner bearbeiten">✎</button>
        </div>
      </div>
      <ul class="nav-list">${items || '<li class="nav-item nav-empty">Noch keine Listen in diesem Ordner.</li>'}</ul>
    </section>
  `;
}

function renderUngroupedLists(lists, selectedListId) {
  if (!lists.length) {
    return '';
  }

  return `
    <section class="folder-card">
      <div class="folder-card-header">
        <div>
          <div class="eyebrow">Ohne Ordner</div>
          <strong>Eigenständige Listen</strong>
        </div>
      </div>
      <ul class="nav-list">
        ${lists
          .map(
            (list) => `
              <li class="nav-item ${selectedListId === Number(list.id) ? 'is-active' : ''}">
                <button type="button" data-action="open-list" data-list-id="${list.id}" class="nav-link">
                  <span class="nav-link-main">
                    <span class="color-dot" style="background:${escapeHtml(badgeClassFromColor(list.color))}"></span>
                    <span>${escapeHtml(list.title)}</span>
                  </span>
                  <span class="nav-link-meta">${escapeHtml(list.access_role || '')}</span>
                </button>
              </li>
            `,
          )
          .join('')}
      </ul>
    </section>
  `;
}

function renderTaskRow(task, state) {
  const selected = state.selectedTaskId === Number(task.id);
  const inCompletedView = state.currentScope.type === 'smart' && state.currentScope.id === 'completed';
  const dueLabel = formatRelativeDueLabel(task.due_at);
  const canReorder = state.currentScope.type === 'list';

  return `
    <article class="task-row ${selected ? 'is-selected' : ''} ${task.completed_at ? 'is-completed' : ''}">
      <button
        type="button"
        class="task-toggle"
        data-action="${task.completed_at ? 'restore-task' : 'complete-task'}"
        data-task-id="${task.id}"
        title="${task.completed_at ? 'Wiederherstellen' : 'Erledigen'}"
      >${task.completed_at ? '↺' : '✓'}</button>
      <button type="button" class="task-main" data-action="open-task" data-task-id="${task.id}">
        <span class="task-title">${escapeHtml(task.title)}</span>
        <span class="task-meta-row">
          ${task.due_at ? `<span class="pill ${dueLabel.startsWith('Überfällig') ? 'pill-danger' : ''}">${escapeHtml(dueLabel || formatDate(task.due_at))}</span>` : ''}
          ${task.assignee_user_id ? `<span class="pill">Zugewiesen</span>` : ''}
          ${task.recurrence_type ? `<span class="pill">Wiederholt sich</span>` : ''}
          ${task.list_id ? `<span class="pill pill-soft">Liste ${escapeHtml(task.list_id)}</span>` : ''}
        </span>
      </button>
      <div class="inline-actions">
        <button type="button" class="ghost-button" data-action="toggle-star" data-task-id="${task.id}" title="Wichtig markieren">${Number(task.is_starred) ? '★' : '☆'}</button>
        ${canReorder ? `<button type="button" class="ghost-button" data-action="move-task-up" data-task-id="${task.id}" title="Aufgabe nach oben">↑</button>
        <button type="button" class="ghost-button" data-action="move-task-down" data-task-id="${task.id}" title="Aufgabe nach unten">↓</button>` : ''}
        ${!inCompletedView ? `<button type="button" class="ghost-button danger-text" data-action="delete-task" data-task-id="${task.id}" title="Aufgabe löschen">✕</button>` : ''}
      </div>
    </article>
  `;
}

function renderSubtaskRow(subtask) {
  return `
    <form class="subtask-row" data-form="update-subtask" data-subtask-id="${subtask.id}">
      <label class="subtask-check">
        <input type="checkbox" name="is_completed" value="1" ${Number(subtask.is_completed) ? 'checked' : ''} />
        <span></span>
      </label>
      <input type="text" name="title" value="${escapeHtml(subtask.title)}" placeholder="Unteraufgabe" required />
      <input type="number" name="position" value="${escapeHtml(subtask.position)}" min="0" />
      <button type="submit" class="ghost-button">Speichern</button>
      <button type="button" class="ghost-button danger-text" data-action="delete-subtask" data-subtask-id="${subtask.id}">Löschen</button>
    </form>
  `;
}

function renderReminderRow(reminder) {
  return `
    <form class="list-row-form" data-form="update-reminder" data-reminder-id="${reminder.id}">
      <input type="datetime-local" name="remind_at" value="${toDateTimeLocalValue(reminder.remind_at)}" required />
      <select name="channel">
        <option value="in_app" ${reminder.channel === 'in_app' ? 'selected' : ''}>In App</option>
        <option value="email" ${reminder.channel === 'email' ? 'selected' : ''}>E-Mail</option>
      </select>
      <button type="submit" class="ghost-button">Aktualisieren</button>
      <button type="button" class="ghost-button danger-text" data-action="delete-reminder" data-reminder-id="${reminder.id}">Löschen</button>
    </form>
  `;
}

function renderCommentRow(comment, currentUserId) {
  return `
    <article class="comment-card">
      <header>
        <div>
          <strong>${escapeHtml(comment.display_name || comment.email || 'Unbekannt')}</strong>
          <div class="muted-text">${escapeHtml(comment.email || '')}</div>
        </div>
        <div class="inline-actions">
          <span class="muted-text">${escapeHtml(formatDateTime(comment.created_at))}</span>
          ${Number(comment.user_id) === Number(currentUserId) ? `<button type="button" class="ghost-button danger-text" data-action="delete-comment" data-comment-id="${comment.id}">Löschen</button>` : ''}
        </div>
      </header>
      <p>${escapeHtml(comment.content)}</p>
    </article>
  `;
}

function renderAttachmentRow(attachment) {
  return `
    <article class="attachment-row">
      <div>
        <strong>${escapeHtml(attachment.original_name)}</strong>
        <div class="muted-text">${escapeHtml(humanFileSize(attachment.file_size))} · ${escapeHtml(attachment.mime_type)}</div>
      </div>
      <div class="inline-actions">
        <button type="button" class="ghost-button" data-action="download-attachment" data-attachment-id="${attachment.id}">Download</button>
        <button type="button" class="ghost-button danger-text" data-action="delete-attachment" data-attachment-id="${attachment.id}">Löschen</button>
      </div>
    </article>
  `;
}

function renderTaskDetail(state) {
  const task = state.selectedTask;
  if (!task) {
    return emptyState(
      'Noch keine Aufgabe geöffnet',
      'Wähle links eine Aufgabe aus oder lege direkt eine neue Aufgabe an.',
    );
  }

  const members = state.currentTaskMembers || [];
  const note = state.selectedTaskExtras.note;

  return `
    <div class="panel-stack">
      <section class="panel-card">
        <div class="detail-header">
          <div>
            <div class="eyebrow">Aufgabendetails</div>
            <h2>${escapeHtml(task.title)}</h2>
          </div>
          <div class="inline-actions">
            <button type="button" class="ghost-button" data-action="${task.completed_at ? 'restore-task' : 'complete-task'}" data-task-id="${task.id}">${task.completed_at ? 'Wieder aktivieren' : 'Erledigen'}</button>
            <button type="button" class="ghost-button" data-action="toggle-star" data-task-id="${task.id}">${Number(task.is_starred) ? '★ Wichtig' : '☆ Wichtig'}</button>
            <button type="button" class="ghost-button danger-text" data-action="delete-task" data-task-id="${task.id}">Löschen</button>
          </div>
        </div>

        <form class="detail-form" data-form="update-task" data-task-id="${task.id}">
          <label>
            <span>Titel</span>
            <input type="text" name="title" value="${escapeHtml(task.title)}" required />
          </label>

          <div class="grid-two">
            <label>
              <span>Liste</span>
              <select name="list_id">
                ${state.lists
                  .map(
                    (list) => `<option value="${list.id}" ${Number(list.id) === Number(task.list_id) ? 'selected' : ''}>${escapeHtml(list.title)}</option>`,
                  )
                  .join('')}
              </select>
            </label>
            <label>
              <span>Bearbeiter</span>
              <select name="assignee_user_id">
                <option value="">Niemand</option>
                ${members
                  .map(
                    (member) => `<option value="${member.user_id}" ${Number(member.user_id) === Number(task.assignee_user_id) ? 'selected' : ''}>${escapeHtml(member.display_name || member.email)}</option>`,
                  )
                  .join('')}
              </select>
            </label>
          </div>

          <div class="grid-three">
            <label>
              <span>Fällig am</span>
              <input type="datetime-local" name="due_at" value="${toDateTimeLocalValue(task.due_at)}" />
            </label>
            <label>
              <span>Wiederholung</span>
              <select name="recurrence_type">
                <option value="">Keine</option>
                <option value="day" ${task.recurrence_type === 'day' ? 'selected' : ''}>Täglich</option>
                <option value="week" ${task.recurrence_type === 'week' ? 'selected' : ''}>Wöchentlich</option>
                <option value="month" ${task.recurrence_type === 'month' ? 'selected' : ''}>Monatlich</option>
                <option value="year" ${task.recurrence_type === 'year' ? 'selected' : ''}>Jährlich</option>
              </select>
            </label>
            <label>
              <span>Intervall</span>
              <input type="number" name="recurrence_interval" value="${escapeHtml(task.recurrence_interval || 1)}" min="1" />
            </label>
          </div>

          <label class="checkbox-row">
            <input type="checkbox" name="is_starred" value="1" ${Number(task.is_starred) ? 'checked' : ''} />
            <span>Als wichtig markieren</span>
          </label>

          <div class="form-actions">
            <button type="submit" class="primary-button">Änderungen speichern</button>
          </div>
        </form>

        <div class="meta-columns">
          <div>
            <div class="eyebrow">Erstellt</div>
            <div>${escapeHtml(formatDateTime(task.created_at))}</div>
          </div>
          <div>
            <div class="eyebrow">Zuletzt geändert</div>
            <div>${escapeHtml(formatDateTime(task.updated_at))}</div>
          </div>
          <div>
            <div class="eyebrow">Status</div>
            <div>${task.completed_at ? `Erledigt am ${escapeHtml(formatDateTime(task.completed_at))}` : 'Offen'}</div>
          </div>
        </div>
      </section>

      <section class="panel-card">
        <div class="panel-header-inline">
          <h3>Unteraufgaben</h3>
          <span class="pill pill-soft">${state.selectedTaskExtras.subtasks.length}</span>
        </div>
        <div class="stack-list">
          ${state.selectedTaskExtras.subtasks.map(renderSubtaskRow).join('') || '<div class="muted-card">Noch keine Unteraufgaben.</div>'}
        </div>
        <form class="inline-form" data-form="create-subtask" data-task-id="${task.id}">
          <input type="text" name="title" placeholder="Neue Unteraufgabe" required />
          <button type="submit" class="primary-button">Hinzufügen</button>
        </form>
      </section>

      <section class="panel-card">
        <div class="panel-header-inline">
          <h3>Notiz</h3>
          <span class="muted-text">Für Beschreibungen, Absprachen oder Checklisten.</span>
        </div>
        <form data-form="save-note" data-task-id="${task.id}">
          <textarea name="content" rows="7" placeholder="Notiz zur Aufgabe">${escapeHtml(note?.content || '')}</textarea>
          <div class="form-actions">
            <button type="submit" class="primary-button">Notiz speichern</button>
          </div>
        </form>
      </section>

      <section class="panel-card">
        <div class="panel-header-inline">
          <h3>Kommentare</h3>
          <span class="pill pill-soft">${state.selectedTaskExtras.comments.length}</span>
        </div>
        <div class="stack-list">
          ${state.selectedTaskExtras.comments.map((comment) => renderCommentRow(comment, state.auth.user?.id)).join('') || '<div class="muted-card">Noch keine Kommentare.</div>'}
        </div>
        <form class="stack-form" data-form="create-comment" data-task-id="${task.id}">
          <textarea name="content" rows="3" placeholder="Kommentar hinzufügen" required></textarea>
          <div class="form-actions">
            <button type="submit" class="primary-button">Kommentar senden</button>
          </div>
        </form>
      </section>

      <section class="panel-card">
        <div class="panel-header-inline">
          <h3>Erinnerungen</h3>
          <span class="pill pill-soft">${state.selectedTaskExtras.reminders.length}</span>
        </div>
        <div class="stack-list">
          ${state.selectedTaskExtras.reminders.map(renderReminderRow).join('') || '<div class="muted-card">Noch keine Erinnerungen.</div>'}
        </div>
        <form class="inline-form inline-form-wide" data-form="create-reminder" data-task-id="${task.id}">
          <input type="datetime-local" name="remind_at" required />
          <select name="channel">
            <option value="in_app">In App</option>
            <option value="email">E-Mail</option>
          </select>
          <button type="submit" class="primary-button">Erinnerung anlegen</button>
        </form>
      </section>

      <section class="panel-card">
        <div class="panel-header-inline">
          <h3>Anhänge</h3>
          <span class="pill pill-soft">${state.selectedTaskExtras.attachments.length}</span>
        </div>
        <div class="stack-list">
          ${state.selectedTaskExtras.attachments.map(renderAttachmentRow).join('') || '<div class="muted-card">Noch keine Anhänge.</div>'}
        </div>
        <form class="inline-form inline-form-wide" data-form="upload-attachment" data-task-id="${task.id}">
          <input type="file" name="file" required />
          <button type="submit" class="primary-button">Datei hochladen</button>
        </form>
      </section>
    </div>
  `;
}

function renderModalContent(state) {
  const modal = state.modal;
  if (!modal) {
    return '';
  }

  if (modal.type === 'folder-create') {
    return `
      <div class="modal-card">
        <header><h3>Ordner anlegen</h3><button type="button" class="ghost-button" data-action="close-modal">✕</button></header>
        <form class="stack-form" data-form="create-folder">
          <label><span>Titel</span><input type="text" name="title" placeholder="z. B. Arbeit" required /></label>
          <label><span>Position</span><input type="number" name="position" value="0" min="0" /></label>
          <div class="form-actions"><button type="submit" class="primary-button">Ordner speichern</button></div>
        </form>
      </div>
    `;
  }

  if (modal.type === 'folder-edit' && modal.folder) {
    return `
      <div class="modal-card">
        <header><h3>Ordner bearbeiten</h3><button type="button" class="ghost-button" data-action="close-modal">✕</button></header>
        <form class="stack-form" data-form="update-folder" data-folder-id="${modal.folder.id}">
          <label><span>Titel</span><input type="text" name="title" value="${escapeHtml(modal.folder.title)}" required /></label>
          <label><span>Position</span><input type="number" name="position" value="${escapeHtml(modal.folder.position)}" min="0" /></label>
          <div class="form-actions split-actions">
            <button type="button" class="ghost-button danger-text" data-action="delete-folder" data-folder-id="${modal.folder.id}">Ordner löschen</button>
            <button type="submit" class="primary-button">Änderungen speichern</button>
          </div>
        </form>
      </div>
    `;
  }

  if (modal.type === 'list-create') {
    return `
      <div class="modal-card">
        <header><h3>Liste anlegen</h3><button type="button" class="ghost-button" data-action="close-modal">✕</button></header>
        <form class="stack-form" data-form="create-list">
          <label><span>Titel</span><input type="text" name="title" placeholder="z. B. Kundenprojekte" required /></label>
          <label><span>Farbe</span><input type="text" name="color" placeholder="#2d6cdf" /></label>
          <label><span>Ordner</span>
            <select name="folder_id">
              <option value="">Kein Ordner</option>
              ${state.folders.map((folder) => `<option value="${folder.id}">${escapeHtml(folder.title)}</option>`).join('')}
            </select>
          </label>
          <label><span>Position</span><input type="number" name="position" value="0" min="0" /></label>
          <div class="form-actions"><button type="submit" class="primary-button">Liste speichern</button></div>
        </form>
      </div>
    `;
  }

  if (modal.type === 'list-edit' && modal.list) {
    return `
      <div class="modal-card">
        <header><h3>Liste bearbeiten</h3><button type="button" class="ghost-button" data-action="close-modal">✕</button></header>
        <form class="stack-form" data-form="update-list" data-list-id="${modal.list.id}">
          <label><span>Titel</span><input type="text" name="title" value="${escapeHtml(modal.list.title)}" required /></label>
          <label><span>Farbe</span><input type="text" name="color" value="${escapeHtml(modal.list.color || '')}" /></label>
          <label><span>Ordner</span>
            <select name="folder_id">
              <option value="">Kein Ordner</option>
              ${state.folders.map((folder) => `<option value="${folder.id}" ${Number(folder.id) === Number(modal.list.folder_id) ? 'selected' : ''}>${escapeHtml(folder.title)}</option>`).join('')}
            </select>
          </label>
          <label><span>Position</span><input type="number" name="position" value="${escapeHtml(modal.list.position)}" min="0" /></label>
          <label class="checkbox-row">
            <input type="checkbox" name="is_archived" value="1" ${Number(modal.list.is_archived) ? 'checked' : ''} />
            <span>Liste archivieren</span>
          </label>
          <div class="form-actions split-actions">
            <button type="button" class="ghost-button danger-text" data-action="delete-list" data-list-id="${modal.list.id}">Liste löschen</button>
            <button type="submit" class="primary-button">Änderungen speichern</button></div>
        </form>
      </div>
    `;
  }

  if (modal.type === 'share' && modal.list) {
    return `
      <div class="modal-card modal-card-wide">
        <header><h3>Liste teilen · ${escapeHtml(modal.list.title)}</h3><button type="button" class="ghost-button" data-action="close-modal">✕</button></header>
        <section class="share-grid">
          <div>
            <h4>Mitglieder</h4>
            <div class="stack-list compact-list">
              ${state.share.members.map((member) => `
                <article class="list-row-form compact-card">
                  <div>
                    <strong>${escapeHtml(member.display_name || member.email)}</strong>
                    <div class="muted-text">${escapeHtml(member.email || '')} · ${escapeHtml(member.role || 'owner')}</div>
                  </div>
                  ${member.role !== 'owner' ? `<button type="button" class="ghost-button danger-text" data-action="remove-member" data-list-id="${modal.list.id}" data-user-id="${member.user_id}">Entfernen</button>` : ''}
                </article>
              `).join('') || '<div class="muted-card">Keine Mitglieder gefunden.</div>'}
            </div>
          </div>
          <div>
            <h4>Einladen</h4>
            <form class="stack-form" data-form="share-list" data-list-id="${modal.list.id}">
              <label><span>E-Mail</span><input type="email" name="email" placeholder="kollege@example.com" required /></label>
              <label><span>Rolle</span>
                <select name="role">
                  <option value="editor">Editor</option>
                  <option value="viewer">Viewer</option>
                </select>
              </label>
              <div class="form-actions"><button type="submit" class="primary-button">Einladen</button></div>
            </form>
            <h4>Offene Einladungen</h4>
            <div class="stack-list compact-list">
              ${state.share.invitations.map((invitation) => `
                <article class="compact-card">
                  <strong>${escapeHtml(invitation.invited_email)}</strong>
                  <div class="muted-text">${escapeHtml(invitation.role)} · gültig bis ${escapeHtml(formatDateTime(invitation.expires_at))}</div>
                </article>
              `).join('') || '<div class="muted-card">Keine offenen Einladungen.</div>'}
            </div>
          </div>
        </section>
      </div>
    `;
  }

  return '';
}

export function renderApp(state) {
  if (!state.auth.token) {
    const isRegister = state.route.screen === 'register';
    return `
      <main class="auth-shell">
        <section class="auth-card hero-card">
          <div class="eyebrow">TaskHost</div>
          <h1>Frontend für eine Wunderlist-inspirierte Aufgabenverwaltung</h1>
          <p>
            Vollwertige Oberflächen für Listen, Aufgaben, Unteraufgaben, Kommentare, Erinnerungen,
            Anhänge, Freigaben und smarte Ansichten – direkt gegen die PHP REST API.
          </p>
          ${state.pendingInvitationToken ? `<div class="inline-notice">Es liegt eine Einladung vor. Nach dem Login oder der Registrierung wird sie automatisch angenommen.</div>` : ''}
        </section>
        <section class="auth-card">
          <div class="tab-row">
            <button type="button" class="tab-button ${!isRegister ? 'is-active' : ''}" data-action="switch-auth-screen" data-screen="login">Login</button>
            <button type="button" class="tab-button ${isRegister ? 'is-active' : ''}" data-action="switch-auth-screen" data-screen="register">Registrieren</button>
          </div>
          ${!isRegister ? `
            <form class="stack-form" data-form="login">
              <label><span>E-Mail</span><input type="email" name="email" placeholder="alice@example.com" required /></label>
              <label><span>Passwort</span><input type="password" name="password" required /></label>
              <div class="form-actions"><button type="submit" class="primary-button">Anmelden</button></div>
            </form>
          ` : `
            <form class="stack-form" data-form="register">
              <label><span>Anzeigename</span><input type="text" name="display_name" placeholder="Alice" required /></label>
              <label><span>E-Mail</span><input type="email" name="email" required /></label>
              <label><span>Passwort</span><input type="password" name="password" minlength="8" required /></label>
              <label><span>Zeitzone</span><input type="text" name="timezone" value="Europe/Berlin" required /></label>
              <div class="form-actions"><button type="submit" class="primary-button">Konto anlegen</button></div>
            </form>
          `}
        </section>
      </main>
      ${state.error ? `<div class="error-banner">${escapeHtml(state.error)}</div>` : ''}
      ${renderToasts(state)}
    `;
  }

  const selectedList = state.currentScope.type === 'list'
    ? state.lists.find((item) => Number(item.id) === Number(state.currentScope.id))
    : null;
  const viewTitle = selectedList
    ? selectedList.title
    : smartViewLabel(state.currentScope.id);

  const groupedFolders = state.folders.map((folder) => ({
    folder,
    lists: state.lists.filter((list) => Number(list.folder_id) === Number(folder.id) && !Number(list.is_archived)),
  }));
  const ungrouped = state.lists.filter((list) => !list.folder_id && !Number(list.is_archived));
  const archived = state.lists.filter((list) => Number(list.is_archived));

  const taskSection = state.currentTasks.length
    ? state.currentTasks.map((task) => renderTaskRow(task, state)).join('')
    : emptyState('Keine Aufgaben', 'Für diese Ansicht wurden aktuell keine Aufgaben gefunden.');

  return `
    <div class="app-shell">
      <aside class="sidebar">
        <div class="brand-block">
          <div>
            <div class="eyebrow">${escapeHtml(state.config.appName)}</div>
            <h1>TaskHost</h1>
          </div>
          <button type="button" class="ghost-button" data-action="logout">Logout</button>
        </div>

        <section class="profile-card">
          <strong>${escapeHtml(state.auth.user?.display_name || state.auth.user?.email || '')}</strong>
          <div class="muted-text">${escapeHtml(state.auth.user?.email || '')}</div>
        </section>

        <section class="search-card">
          <label>
            <span class="eyebrow">Suche</span>
            <input type="search" name="search" value="${escapeHtml(state.search.term)}" placeholder="Aufgaben oder Notizen durchsuchen" data-input="search" />
          </label>
          ${state.search.results.length ? `
            <div class="search-results">
              ${state.search.results
                .map(
                  (task) => `
                    <button type="button" class="search-result" data-action="open-task" data-task-id="${task.id}" data-list-id="${task.list_id}">
                      <strong>${escapeHtml(task.title)}</strong>
                      <span class="muted-text">Liste ${escapeHtml(task.list_id)}${task.due_at ? ` · ${escapeHtml(formatDate(task.due_at))}` : ''}</span>
                    </button>
                  `,
                )
                .join('')}
            </div>
          ` : state.search.term.length >= 2 ? '<div class="muted-text tiny-top-gap">Keine Treffer.</div>' : ''}
        </section>

        <section class="sidebar-section">
          <div class="section-header">
            <h3>Smarte Ansichten</h3>
          </div>
          <ul class="nav-list">
            ${['today', 'planned', 'starred', 'assigned', 'completed']
              .map(
                (view) => `
                  <li class="nav-item ${state.currentScope.type === 'smart' && state.currentScope.id === view ? 'is-active' : ''}">
                    <button type="button" class="nav-link" data-action="open-smart-view" data-view="${view}">${smartViewLabel(view)}</button>
                  </li>
                `,
              )
              .join('')}
          </ul>
        </section>

        <section class="sidebar-section">
          <div class="section-header">
            <h3>Arbeitsbereich</h3>
            <div class="inline-actions">
              <button type="button" class="ghost-button" data-action="open-create-folder-modal">+ Ordner</button>
              <button type="button" class="ghost-button" data-action="open-create-list-modal">+ Liste</button>
            </div>
          </div>
          <div class="folder-stack">
            ${groupedFolders.map(({ folder, lists }) => renderFolderBlock(folder, lists, state.currentScope.type === 'list' ? Number(state.currentScope.id) : null)).join('')}
            ${renderUngroupedLists(ungrouped, state.currentScope.type === 'list' ? Number(state.currentScope.id) : null)}
            ${archived.length ? `
              <section class="folder-card muted-surface">
                <div class="folder-card-header"><div><div class="eyebrow">Archiv</div><strong>Archivierte Listen</strong></div></div>
                <ul class="nav-list">
                  ${archived.map((list) => `
                    <li class="nav-item ${state.currentScope.type === 'list' && Number(state.currentScope.id) === Number(list.id) ? 'is-active' : ''}">
                      <button type="button" class="nav-link" data-action="open-list" data-list-id="${list.id}">${escapeHtml(list.title)}</button>
                    </li>
                  `).join('')}
                </ul>
              </section>
            ` : ''}
          </div>
        </section>
      </aside>

      <main class="main-panel">
        <header class="main-header">
          <div>
            <div class="eyebrow">${selectedList ? `Liste · ${escapeHtml(selectedList.access_role || 'owner')}` : 'Ansicht'}</div>
            <h2>${escapeHtml(viewTitle || 'Aufgaben')}</h2>
            <p>${selectedList ? 'Verwalte Aufgaben, Mitglieder und Metadaten dieser Liste.' : 'Sammelansicht über mehrere Listen hinweg.'}</p>
          </div>
          <div class="header-actions">
            ${selectedList ? `
              <button type="button" class="ghost-button" data-action="move-list-up" data-list-id="${selectedList.id}">Liste ↑</button>
              <button type="button" class="ghost-button" data-action="move-list-down" data-list-id="${selectedList.id}">Liste ↓</button>
              <button type="button" class="ghost-button" data-action="open-edit-list-modal" data-list-id="${selectedList.id}">Liste bearbeiten</button>
              <button type="button" class="ghost-button" data-action="open-share-modal" data-list-id="${selectedList.id}">Teilen</button>
            ` : ''}
          </div>
        </header>

        <section class="composer-card">
          <form class="task-composer" data-form="create-task">
            <label class="task-composer-title">
              <span class="eyebrow">Neue Aufgabe</span>
              <input type="text" name="title" placeholder="Was ist als Nächstes zu tun?" required ${state.currentScope.type !== 'list' ? 'disabled' : ''} />
            </label>
            <div class="task-composer-fields">
              <input type="datetime-local" name="due_at" ${state.currentScope.type !== 'list' ? 'disabled' : ''} />
              <select name="recurrence_type" ${state.currentScope.type !== 'list' ? 'disabled' : ''}>
                <option value="">Keine Wiederholung</option>
                <option value="day">Täglich</option>
                <option value="week">Wöchentlich</option>
                <option value="month">Monatlich</option>
                <option value="year">Jährlich</option>
              </select>
              <input type="number" name="recurrence_interval" value="1" min="1" ${state.currentScope.type !== 'list' ? 'disabled' : ''} />
              <label class="checkbox-row compact-check">
                <input type="checkbox" name="is_starred" value="1" ${state.currentScope.type !== 'list' ? 'disabled' : ''} />
                <span>Wichtig</span>
              </label>
              <button type="submit" class="primary-button" ${state.currentScope.type !== 'list' ? 'disabled' : ''}>Anlegen</button>
            </div>
          </form>
          ${state.currentScope.type !== 'list' ? '<div class="muted-text">Aufgaben können direkt in einer Liste angelegt werden. Wähle links eine Liste aus.</div>' : ''}
        </section>

        <section class="task-pane">
          <div class="task-pane-header">
            <div class="inline-actions">
              <label class="checkbox-row compact-check">
                <input type="checkbox" ${state.includeCompleted ? 'checked' : ''} data-action="toggle-include-completed" />
                <span>Erledigte Aufgaben einblenden</span>
              </label>
            </div>
            <div class="muted-text">${state.currentTasks.length} Aufgabe${state.currentTasks.length === 1 ? '' : 'n'}</div>
          </div>
          <div class="task-list">
            ${taskSection}
          </div>
        </section>
      </main>

      <aside class="detail-panel">
        ${renderTaskDetail(state)}
      </aside>
    </div>

    ${state.modal ? `<div class="modal-backdrop">${renderModalContent(state)}</div>` : ''}
    ${state.error ? `<div class="error-banner">${escapeHtml(state.error)}</div>` : ''}
    ${renderToasts(state)}
  `;
}

export function renderToasts(state) {
  if (!state.toasts.length) {
    return '';
  }

  return `
    <div class="toast-stack">
      ${state.toasts
        .map(
          (toast) => `
            <div class="toast ${toast.type === 'error' ? 'toast-error' : ''}">
              <div>${escapeHtml(toast.message)}</div>
              <button type="button" class="ghost-button" data-action="dismiss-toast" data-toast-id="${toast.id}">✕</button>
            </div>
          `,
        )
        .join('')}
    </div>
  `;
}
