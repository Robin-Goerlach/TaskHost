import { createTaskHostApi } from './api/taskhost-api.js';
import { renderApp } from './ui/templates.js';
import { fromDateTimeLocalValue, normalizeNullableNumber } from './utils/date.js';

const STORAGE_KEY = 'taskhost.frontend.session';
const SMART_VIEWS = ['today', 'planned', 'starred', 'assigned', 'completed'];

function formToObject(form) {
  const data = new FormData(form);
  return Object.fromEntries(data.entries());
}

function nextToastId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export class TaskHostApp {
  constructor(root, config = {}) {
    this.root = root;
    this.config = {
      apiBaseUrl: config.apiBaseUrl || '/api/v1',
      appName: config.appName || 'TaskHost',
    };

    this.api = createTaskHostApi(this.config.apiBaseUrl);
    this.searchTimer = null;

    this.state = {
      config: this.config,
      route: { screen: 'login' },
      auth: { token: null, user: null },
      folders: [],
      lists: [],
      currentScope: { type: 'list', id: null },
      currentTasks: [],
      selectedTaskId: null,
      selectedTask: null,
      selectedTaskExtras: {
        subtasks: [],
        note: null,
        comments: [],
        reminders: [],
        attachments: [],
      },
      currentTaskMembers: [],
      includeCompleted: false,
      modal: null,
      share: { members: [], invitations: [] },
      search: { term: '', results: [] },
      pendingInvitationToken: null,
      error: null,
      toasts: [],
    };
  }

  async init() {
    this.bindEvents();
    this.handleHash();
    window.addEventListener('hashchange', () => this.handleHash());

    this.restoreSession();
    this.render();

    if (this.state.auth.token) {
      await this.bootstrap();
    }
  }

  bindEvents() {
    this.root.addEventListener('click', (event) => this.handleClick(event));
    this.root.addEventListener('submit', (event) => this.handleSubmit(event));
    this.root.addEventListener('input', (event) => this.handleInput(event));
    this.root.addEventListener('change', (event) => this.handleChange(event));
  }

  handleHash() {
    const hash = window.location.hash.replace(/^#/, '');
    if (hash.startsWith('invite/')) {
      const token = hash.split('/')[1];
      this.state.pendingInvitationToken = token || null;
      if (!this.state.auth.token) {
        this.state.route.screen = 'login';
      } else if (this.state.pendingInvitationToken) {
        this.acceptPendingInvitation();
      }
    } else if (!this.state.auth.token) {
      this.state.route.screen = hash === 'register' ? 'register' : 'login';
    }

    this.render();
  }

  restoreSession() {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      return;
    }

    try {
      const session = JSON.parse(raw);
      if (session?.token) {
        this.state.auth.token = session.token;
        this.state.auth.user = session.user || null;
        this.api.setToken(session.token);
      }
    } catch {
      window.localStorage.removeItem(STORAGE_KEY);
    }
  }

  persistSession() {
    if (!this.state.auth.token) {
      window.localStorage.removeItem(STORAGE_KEY);
      return;
    }

    window.localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        token: this.state.auth.token,
        user: this.state.auth.user,
      }),
    );
  }

  setError(message) {
    this.state.error = message || null;
    this.render();
  }

  addToast(message, type = 'info') {
    const toast = { id: nextToastId(), message, type };
    this.state.toasts = [...this.state.toasts, toast];
    this.render();

    window.setTimeout(() => {
      this.state.toasts = this.state.toasts.filter((entry) => entry.id !== toast.id);
      this.render();
    }, 3500);
  }

  clearError() {
    this.state.error = null;
  }

  listTitleById(listId) {
    const list = this.state.lists.find((entry) => Number(entry.id) === Number(listId));
    return list?.title || `Liste ${listId}`;
  }

  async bootstrap() {
    try {
      this.clearError();
      this.api.setToken(this.state.auth.token);
      const meResponse = await this.api.me();
      this.state.auth.user = meResponse.user;
      this.persistSession();
      await this.reloadStructure();

      if (this.state.pendingInvitationToken) {
        await this.acceptPendingInvitation();
      }
    } catch (error) {
      this.hardLogout();
      this.setError(error.message || 'Sitzung konnte nicht wiederhergestellt werden.');
    }
  }

  async reloadStructure() {
    const [folders, lists] = await Promise.all([this.api.folders(), this.api.lists()]);
    this.state.folders = folders;
    this.state.lists = lists;

    const hasCurrentList = this.state.currentScope.type === 'list'
      && lists.some((list) => Number(list.id) === Number(this.state.currentScope.id));

    if (!hasCurrentList && this.state.currentScope.type === 'list') {
      const fallback = lists.find((list) => Number(list.is_default)) || lists[0];
      this.state.currentScope = fallback
        ? { type: 'list', id: Number(fallback.id) }
        : { type: 'smart', id: 'today' };
    }

    if (this.state.currentScope.type === 'smart' && !SMART_VIEWS.includes(this.state.currentScope.id)) {
      this.state.currentScope = { type: 'smart', id: 'today' };
    }

    await this.reloadCurrentScope();
  }

  async reloadCurrentScope() {
    if (this.state.currentScope.type === 'list') {
      this.state.currentTasks = await this.api.tasksForList(this.state.currentScope.id, this.state.includeCompleted);
    } else {
      this.state.currentTasks = await this.api.smartView(this.state.currentScope.id);
    }

    if (this.state.selectedTaskId) {
      const taskStillVisible = this.state.currentTasks.some((task) => Number(task.id) === Number(this.state.selectedTaskId));
      if (taskStillVisible) {
        await this.loadTaskDetails(this.state.selectedTaskId);
      } else {
        this.clearTaskSelection();
      }
    }

    this.render();
  }

  clearTaskSelection() {
    this.state.selectedTaskId = null;
    this.state.selectedTask = null;
    this.state.currentTaskMembers = [];
    this.state.selectedTaskExtras = {
      subtasks: [],
      note: null,
      comments: [],
      reminders: [],
      attachments: [],
    };
  }

  async selectScope(scope) {
    this.clearError();
    this.state.currentScope = scope;
    this.clearTaskSelection();
    this.render();
    await this.reloadCurrentScope();
  }

  async loadTaskDetails(taskId, preferredListId = null) {
    this.clearError();
    this.state.selectedTaskId = Number(taskId);
    this.render();

    const task = await this.api.getTask(taskId);
    const effectiveListId = preferredListId || task.list_id;
    const [subtasks, note, comments, reminders, attachments, members] = await Promise.all([
      this.api.subtasks(taskId),
      this.api.note(taskId).catch(() => null),
      this.api.comments(taskId),
      this.api.reminders(taskId),
      this.api.attachments(taskId),
      this.api.listMembers(effectiveListId),
    ]);

    this.state.selectedTask = task;
    this.state.selectedTaskExtras = { subtasks, note, comments, reminders, attachments };
    this.state.currentTaskMembers = members;
    this.render();
  }

  serializeTaskPayload(source) {
    return {
      title: source.title?.trim(),
      list_id: normalizeNullableNumber(source.list_id),
      assignee_user_id: normalizeNullableNumber(source.assignee_user_id),
      due_at: fromDateTimeLocalValue(source.due_at),
      is_starred: Boolean(source.is_starred),
      recurrence_type: source.recurrence_type || null,
      recurrence_interval: source.recurrence_type ? Math.max(1, Number(source.recurrence_interval || 1)) : 1,
    };
  }

  async acceptPendingInvitation() {
    if (!this.state.pendingInvitationToken) {
      return;
    }

    try {
      const list = await this.api.acceptInvitation(this.state.pendingInvitationToken);
      this.state.pendingInvitationToken = null;
      window.location.hash = '';
      this.addToast(`Einladung für „${list.title}“ angenommen.`);
      await this.reloadStructure();
      await this.selectScope({ type: 'list', id: Number(list.id) });
    } catch (error) {
      this.state.pendingInvitationToken = null;
      this.addToast(error.message || 'Einladung konnte nicht angenommen werden.', 'error');
      this.render();
    }
  }

  async hardLogout() {
    this.state.auth = { token: null, user: null };
    this.state.route = { screen: 'login' };
    this.state.folders = [];
    this.state.lists = [];
    this.state.currentTasks = [];
    this.state.currentScope = { type: 'smart', id: 'today' };
    this.state.modal = null;
    this.clearTaskSelection();
    this.api.setToken(null);
    this.persistSession();
    this.render();
  }

  async withErrorHandling(action) {
    try {
      await action();
      this.clearError();
    } catch (error) {
      if (error?.status === 401 && this.state.auth.token) {
        await this.hardLogout();
        this.setError('Die Sitzung ist abgelaufen. Bitte melde dich erneut an.');
        this.addToast('Die Sitzung ist abgelaufen. Bitte melde dich erneut an.', 'error');
        return;
      }

      this.setError(error.message || 'Unbekannter Fehler');
      this.addToast(error.message || 'Unbekannter Fehler', 'error');
    }
  }

  handleInput(event) {
    const searchField = event.target.closest('[data-input="search"]');
    if (!searchField) {
      return;
    }

    this.state.search.term = searchField.value;
    if (this.searchTimer) {
      window.clearTimeout(this.searchTimer);
    }

    this.searchTimer = window.setTimeout(async () => {
      if (this.state.search.term.trim().length < 2 || !this.state.auth.token) {
        this.state.search.results = [];
        this.render();
        return;
      }

      await this.withErrorHandling(async () => {
        this.state.search.results = await this.api.search(this.state.search.term.trim());
        this.render();
      });
    }, 250);
  }

  async handleChange(event) {
    const includeToggle = event.target.closest('[data-action="toggle-include-completed"]');
    if (includeToggle) {
      this.state.includeCompleted = includeToggle.checked;
      await this.withErrorHandling(async () => {
        await this.reloadCurrentScope();
      });
    }
  }

  async handleClick(event) {
    const button = event.target.closest('[data-action]');
    if (!button) {
      return;
    }

    const action = button.dataset.action;
    const taskId = Number(button.dataset.taskId || 0);
    const listId = Number(button.dataset.listId || 0);
    const folderId = Number(button.dataset.folderId || 0);
    const subtaskId = Number(button.dataset.subtaskId || 0);
    const reminderId = Number(button.dataset.reminderId || 0);
    const attachmentId = Number(button.dataset.attachmentId || 0);
    const commentId = Number(button.dataset.commentId || 0);
    const userId = Number(button.dataset.userId || 0);
    const invitationId = Number(button.dataset.invitationId || 0);

    await this.withErrorHandling(async () => {
      switch (action) {
        case 'switch-auth-screen': {
          this.state.route.screen = button.dataset.screen === 'register' ? 'register' : 'login';
          window.location.hash = this.state.route.screen === 'register' ? 'register' : '';
          this.render();
          break;
        }
        case 'logout': {
          try {
            await this.api.logout();
          } catch {
            // Logout soll lokal trotzdem funktionieren.
          }
          await this.hardLogout();
          break;
        }
        case 'open-smart-view': {
          await this.selectScope({ type: 'smart', id: button.dataset.view });
          break;
        }
        case 'open-list': {
          await this.selectScope({ type: 'list', id: listId });
          break;
        }
        case 'open-task': {
          const preferredListId = Number(button.dataset.listId || 0) || null;
          await this.loadTaskDetails(taskId, preferredListId);
          break;
        }
        case 'complete-task': {
          await this.api.completeTask(taskId);
          this.addToast('Aufgabe als erledigt markiert.');
          await this.reloadCurrentScope();
          break;
        }
        case 'restore-task': {
          await this.api.restoreTask(taskId);
          this.addToast('Aufgabe wiederhergestellt.');
          await this.reloadCurrentScope();
          break;
        }
        case 'toggle-star': {
          const task = this.findTaskById(taskId);
          if (!task) return;
          await this.api.updateTask(taskId, { is_starred: !Number(task.is_starred) });
          await this.reloadCurrentScope();
          break;
        }
        case 'delete-task': {
          if (!window.confirm('Soll diese Aufgabe wirklich gelöscht werden?')) return;
          await this.api.deleteTask(taskId);
          this.clearTaskSelection();
          this.addToast('Aufgabe gelöscht.');
          await this.reloadCurrentScope();
          break;
        }
        case 'open-create-folder-modal': {
          this.state.modal = { type: 'folder-create' };
          this.render();
          break;
        }
        case 'open-edit-folder-modal': {
          const folder = this.state.folders.find((entry) => Number(entry.id) === folderId);
          this.state.modal = { type: 'folder-edit', folder };
          this.render();
          break;
        }
        case 'open-create-list-modal': {
          this.state.modal = { type: 'list-create' };
          this.render();
          break;
        }
        case 'open-edit-list-modal': {
          const list = this.state.lists.find((entry) => Number(entry.id) === listId);
          this.state.modal = { type: 'list-edit', list };
          this.render();
          break;
        }
        case 'open-share-modal': {
          const list = this.state.lists.find((entry) => Number(entry.id) === listId) || null;
          this.state.share = { members: [], invitations: [] };
          this.state.modal = { type: 'share', list };
          this.render();
          const [members, invitations] = await Promise.all([
            this.api.listMembers(listId),
            this.api.listInvitations(listId),
          ]);
          this.state.share = { members, invitations };
          this.render();
          break;
        }
        case 'close-modal': {
          this.state.modal = null;
          this.state.share = { members: [], invitations: [] };
          this.render();
          break;
        }
        case 'delete-folder': {
          if (!window.confirm('Soll dieser Ordner wirklich gelöscht werden? Listen bleiben erhalten und werden aus dem Ordner gelöst.')) return;
          await this.api.deleteFolder(folderId);
          this.state.modal = null;
          this.addToast('Ordner gelöscht.');
          await this.reloadStructure();
          break;
        }
        case 'delete-list': {
          if (!window.confirm('Soll diese Liste wirklich gelöscht werden? Alle enthaltenen Aufgaben gehen verloren.')) return;
          await this.api.deleteList(listId);
          this.state.modal = null;
          this.clearTaskSelection();
          this.addToast('Liste gelöscht.');
          await this.reloadStructure();
          break;
        }
        case 'remove-member': {
          if (!window.confirm('Mitglied wirklich aus der Liste entfernen?')) return;
          await this.api.removeMember(listId, userId);
          this.state.share.members = await this.api.listMembers(listId);
          this.addToast('Mitglied entfernt.');
          this.render();
          break;
        }
        case 'resend-invitation': {
          await this.api.resendInvitation(listId, invitationId);
          this.state.share.invitations = await this.api.listInvitations(listId);
          this.addToast('Einladung wurde erneut zum Versand eingeplant.');
          this.render();
          break;
        }
        case 'copy-invitation-link': {
          const invitation = this.state.share.invitations.find((entry) => Number(entry.id) === invitationId);
          if (!invitation?.token) {
            this.addToast('Einladungslink konnte nicht ermittelt werden.', 'error');
            return;
          }

          const inviteUrl = `${window.location.origin}${window.location.pathname}#invite/${invitation.token}`;
          try {
            if (navigator.clipboard?.writeText) {
              await navigator.clipboard.writeText(inviteUrl);
              this.addToast('Einladungslink in die Zwischenablage kopiert.');
            } else {
              window.prompt('Einladungslink kopieren:', inviteUrl);
            }
          } catch {
            window.prompt('Einladungslink kopieren:', inviteUrl);
          }
          break;
        }
        case 'delete-subtask': {
          if (!window.confirm('Unteraufgabe löschen?')) return;
          await this.api.deleteSubtask(subtaskId);
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'delete-comment': {
          if (!window.confirm('Kommentar löschen?')) return;
          await this.api.deleteComment(commentId);
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'delete-reminder': {
          if (!window.confirm('Erinnerung löschen?')) return;
          await this.api.deleteReminder(reminderId);
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'download-attachment': {
          const attachment = this.state.selectedTaskExtras.attachments.find((entry) => Number(entry.id) === attachmentId);
          const blob = await this.api.downloadAttachment(attachmentId);
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = attachment?.original_name || 'download';
          link.click();
          URL.revokeObjectURL(url);
          break;
        }
        case 'delete-attachment': {
          if (!window.confirm('Anhang löschen?')) return;
          await this.api.deleteAttachment(attachmentId);
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'move-task-up': {
          await this.repositionTask(taskId, -1);
          break;
        }
        case 'move-task-down': {
          await this.repositionTask(taskId, 1);
          break;
        }
        case 'move-list-up': {
          await this.repositionList(listId, -1);
          break;
        }
        case 'move-list-down': {
          await this.repositionList(listId, 1);
          break;
        }
        case 'move-folder-up': {
          await this.repositionFolder(folderId, -1);
          break;
        }
        case 'move-folder-down': {
          await this.repositionFolder(folderId, 1);
          break;
        }
        case 'dismiss-toast': {
          this.state.toasts = this.state.toasts.filter((entry) => entry.id !== button.dataset.toastId);
          this.render();
          break;
        }
        default:
          break;
      }
    });
  }

  findTaskById(taskId) {
    return this.state.currentTasks.find((task) => Number(task.id) === Number(taskId))
      || (this.state.selectedTask && Number(this.state.selectedTask.id) === Number(taskId) ? this.state.selectedTask : null);
  }

  async repositionTask(taskId, direction) {
    if (this.state.currentScope.type !== 'list') {
      return;
    }

    const tasks = [...this.state.currentTasks];
    const index = tasks.findIndex((task) => Number(task.id) === Number(taskId));
    const targetIndex = index + direction;
    if (index === -1 || targetIndex < 0 || targetIndex >= tasks.length) {
      return;
    }

    [tasks[index], tasks[targetIndex]] = [tasks[targetIndex], tasks[index]];
    await Promise.all(
      tasks.map((task, position) => this.api.updateTask(task.id, { position })),
    );
    await this.reloadCurrentScope();
  }

  async repositionList(listId, direction) {
    const lists = [...this.state.lists].sort((a, b) => Number(a.position) - Number(b.position) || Number(a.id) - Number(b.id));
    const index = lists.findIndex((list) => Number(list.id) === Number(listId));
    const targetIndex = index + direction;
    if (index === -1 || targetIndex < 0 || targetIndex >= lists.length) {
      return;
    }

    [lists[index], lists[targetIndex]] = [lists[targetIndex], lists[index]];
    await Promise.all(lists.map((list, position) => this.api.updateList(list.id, { position })));
    await this.reloadStructure();
  }

  async repositionFolder(folderId, direction) {
    const folders = [...this.state.folders].sort((a, b) => Number(a.position) - Number(b.position) || Number(a.id) - Number(b.id));
    const index = folders.findIndex((folder) => Number(folder.id) === Number(folderId));
    const targetIndex = index + direction;
    if (index === -1 || targetIndex < 0 || targetIndex >= folders.length) {
      return;
    }

    [folders[index], folders[targetIndex]] = [folders[targetIndex], folders[index]];
    await Promise.all(folders.map((folder, position) => this.api.updateFolder(folder.id, { position })));
    await this.reloadStructure();
  }

  async handleSubmit(event) {
    const form = event.target.closest('[data-form]');
    if (!form) {
      return;
    }

    event.preventDefault();
    const formName = form.dataset.form;
    const values = formToObject(form);

    await this.withErrorHandling(async () => {
      switch (formName) {
        case 'login': {
          const result = await this.api.login({ email: values.email, password: values.password });
          this.state.auth.token = result.token;
          this.state.auth.user = result.user;
          this.api.setToken(result.token);
          this.persistSession();
          await this.bootstrap();
          this.addToast('Anmeldung erfolgreich.');
          break;
        }
        case 'register': {
          const result = await this.api.register({
            email: values.email,
            password: values.password,
            display_name: values.display_name,
            timezone: values.timezone || 'Europe/Berlin',
          });
          this.state.auth.token = result.token;
          this.state.auth.user = result.user;
          this.api.setToken(result.token);
          this.persistSession();
          await this.bootstrap();
          this.addToast('Konto erfolgreich angelegt.');
          break;
        }
        case 'create-folder': {
          await this.api.createFolder({ title: values.title, position: Number(values.position || 0) });
          this.state.modal = null;
          this.addToast('Ordner angelegt.');
          await this.reloadStructure();
          break;
        }
        case 'update-folder': {
          await this.api.updateFolder(Number(form.dataset.folderId), {
            title: values.title,
            position: Number(values.position || 0),
          });
          this.state.modal = null;
          this.addToast('Ordner aktualisiert.');
          await this.reloadStructure();
          break;
        }
        case 'create-list': {
          const list = await this.api.createList({
            title: values.title,
            color: values.color || null,
            folder_id: normalizeNullableNumber(values.folder_id),
            position: Number(values.position || 0),
          });
          this.state.modal = null;
          this.addToast('Liste angelegt.');
          await this.reloadStructure();
          await this.selectScope({ type: 'list', id: Number(list.id) });
          break;
        }
        case 'update-list': {
          await this.api.updateList(Number(form.dataset.listId), {
            title: values.title,
            color: values.color || null,
            folder_id: normalizeNullableNumber(values.folder_id),
            position: Number(values.position || 0),
            is_archived: Boolean(values.is_archived),
          });
          this.state.modal = null;
          this.addToast('Liste aktualisiert.');
          await this.reloadStructure();
          break;
        }
        case 'share-list': {
          const listId = Number(form.dataset.listId);
          const result = await this.api.shareList(listId, {
            email: values.email,
            role: values.role,
            notify: values.notify === '1',
          });
          this.state.share.members = await this.api.listMembers(listId);
          this.state.share.invitations = await this.api.listInvitations(listId);

          if (result.mode === 'invitation') {
            this.addToast(result.notification?.queued
              ? 'Einladung angelegt und zum Versand eingeplant.'
              : 'Einladung angelegt. Kein Mailversand ausgelöst.');
          } else {
            this.addToast(result.notification?.queued
              ? 'Mitglied hinzugefügt und Benachrichtigung eingeplant.'
              : 'Mitglied direkt hinzugefügt.');
          }

          form.reset();
          const notifyField = form.querySelector('input[name="notify"]');
          if (notifyField) notifyField.checked = true;
          this.render();
          break;
        }
        case 'create-task': {
          if (this.state.currentScope.type !== 'list') {
            return;
          }
          const payload = this.serializeTaskPayload(values);
          payload.title = values.title;
          const task = await this.api.createTask(this.state.currentScope.id, payload);
          form.reset();
          await this.reloadCurrentScope();
          await this.loadTaskDetails(task.id, this.state.currentScope.id);
          this.addToast('Aufgabe angelegt.');
          break;
        }
        case 'update-task': {
          const payload = this.serializeTaskPayload({
            ...values,
            is_starred: values.is_starred === '1',
          });
          payload.title = values.title;
          await this.api.updateTask(Number(form.dataset.taskId), payload);
          await this.reloadStructure();
          await this.loadTaskDetails(Number(form.dataset.taskId), payload.list_id || this.state.selectedTask?.list_id);
          this.addToast('Aufgabe aktualisiert.');
          break;
        }
        case 'create-subtask': {
          await this.api.createSubtask(Number(form.dataset.taskId), { title: values.title, position: 0 });
          form.reset();
          await this.loadTaskDetails(this.state.selectedTaskId);
          this.addToast('Unteraufgabe angelegt.');
          break;
        }
        case 'update-subtask': {
          await this.api.updateSubtask(Number(form.dataset.subtaskId), {
            title: values.title,
            position: Number(values.position || 0),
            is_completed: values.is_completed === '1',
          });
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'save-note': {
          await this.api.saveNote(Number(form.dataset.taskId), {
            content: values.content || '',
          });
          await this.loadTaskDetails(this.state.selectedTaskId);
          this.addToast('Notiz gespeichert.');
          break;
        }
        case 'create-comment': {
          await this.api.createComment(Number(form.dataset.taskId), { content: values.content });
          form.reset();
          await this.loadTaskDetails(this.state.selectedTaskId);
          break;
        }
        case 'create-reminder': {
          await this.api.createReminder(Number(form.dataset.taskId), {
            remind_at: fromDateTimeLocalValue(values.remind_at),
            channel: values.channel,
          });
          form.reset();
          await this.loadTaskDetails(this.state.selectedTaskId);
          this.addToast(values.channel === 'email' || values.channel === 'both'
            ? 'Erinnerung angelegt. Der Mailversand erfolgt asynchron.'
            : 'Erinnerung angelegt.');
          break;
        }
        case 'update-reminder': {
          await this.api.updateReminder(Number(form.dataset.reminderId), {
            remind_at: fromDateTimeLocalValue(values.remind_at),
            channel: values.channel,
          });
          await this.loadTaskDetails(this.state.selectedTaskId);
          this.addToast(values.channel === 'email' || values.channel === 'both'
            ? 'Erinnerung aktualisiert. Mailversand wird bei Fälligkeit asynchron verarbeitet.'
            : 'Erinnerung aktualisiert.');
          break;
        }
        case 'upload-attachment': {
          const fileInput = form.querySelector('input[name="file"]');
          const file = fileInput?.files?.[0];
          if (!file) {
            this.addToast('Bitte zuerst eine Datei auswählen.', 'error');
            return;
          }
          await this.api.uploadAttachment(Number(form.dataset.taskId), file);
          form.reset();
          await this.loadTaskDetails(this.state.selectedTaskId);
          this.addToast('Datei hochgeladen.');
          break;
        }
        default:
          break;
      }
    });
  }

  render() {
    this.root.innerHTML = renderApp(this.state);
  }
}
