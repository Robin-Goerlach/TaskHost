import { ApiClient } from './client.js';

function unwrapData(response) {
  if (response && typeof response === 'object' && 'data' in response) {
    return response.data;
  }
  return response;
}

export function createTaskHostApi(baseUrl) {
  const client = new ApiClient(baseUrl);

  return {
    client,
    setToken(token) {
      client.setToken(token);
    },
    async register(payload) {
      return client.post('/auth/register', payload);
    },
    async login(payload) {
      return client.post('/auth/login', payload);
    },
    async logout() {
      return client.post('/auth/logout', {});
    },
    async me() {
      return client.get('/me');
    },
    async folders() {
      return unwrapData(await client.get('/folders'));
    },
    async createFolder(payload) {
      return unwrapData(await client.post('/folders', payload));
    },
    async updateFolder(id, payload) {
      return unwrapData(await client.patch(`/folders/${id}`, payload));
    },
    async deleteFolder(id) {
      return client.delete(`/folders/${id}`);
    },
    async lists() {
      return unwrapData(await client.get('/lists'));
    },
    async createList(payload) {
      return unwrapData(await client.post('/lists', payload));
    },
    async getList(id) {
      return unwrapData(await client.get(`/lists/${id}`));
    },
    async updateList(id, payload) {
      return unwrapData(await client.patch(`/lists/${id}`, payload));
    },
    async deleteList(id) {
      return client.delete(`/lists/${id}`);
    },
    async listMembers(id) {
      return unwrapData(await client.get(`/lists/${id}/members`));
    },
    async listInvitations(id) {
      return unwrapData(await client.get(`/lists/${id}/invitations`));
    },
    async shareList(id, payload) {
      return unwrapData(await client.post(`/lists/${id}/share`, payload));
    },
    async resendInvitation(listId, invitationId) {
      return unwrapData(await client.post(`/lists/${listId}/invitations/${invitationId}/resend`, {}));
    },
    async removeMember(listId, userId) {
      return client.delete(`/lists/${listId}/members/${userId}`);
    },
    async acceptInvitation(token) {
      return unwrapData(await client.post(`/invitations/${token}/accept`, {}));
    },
    async tasksForList(listId, includeCompleted = false) {
      return unwrapData(await client.get(`/lists/${listId}/tasks?include_completed=${includeCompleted ? '1' : '0'}`));
    },
    async createTask(listId, payload) {
      return unwrapData(await client.post(`/lists/${listId}/tasks`, payload));
    },
    async getTask(id) {
      return unwrapData(await client.get(`/tasks/${id}`));
    },
    async updateTask(id, payload) {
      return unwrapData(await client.patch(`/tasks/${id}`, payload));
    },
    async deleteTask(id) {
      return client.delete(`/tasks/${id}`);
    },
    async completeTask(id) {
      return unwrapData(await client.post(`/tasks/${id}/complete`, {}));
    },
    async restoreTask(id) {
      return unwrapData(await client.post(`/tasks/${id}/restore`, {}));
    },
    async smartView(view) {
      return unwrapData(await client.get(`/views/${view}`));
    },
    async search(query) {
      return unwrapData(await client.get(`/search?q=${encodeURIComponent(query)}`));
    },
    async subtasks(taskId) {
      return unwrapData(await client.get(`/tasks/${taskId}/subtasks`));
    },
    async createSubtask(taskId, payload) {
      return unwrapData(await client.post(`/tasks/${taskId}/subtasks`, payload));
    },
    async updateSubtask(id, payload) {
      return unwrapData(await client.patch(`/subtasks/${id}`, payload));
    },
    async deleteSubtask(id) {
      return client.delete(`/subtasks/${id}`);
    },
    async note(taskId) {
      return unwrapData(await client.get(`/tasks/${taskId}/note`));
    },
    async saveNote(taskId, payload) {
      return unwrapData(await client.put(`/tasks/${taskId}/note`, payload));
    },
    async comments(taskId) {
      return unwrapData(await client.get(`/tasks/${taskId}/comments`));
    },
    async createComment(taskId, payload) {
      return unwrapData(await client.post(`/tasks/${taskId}/comments`, payload));
    },
    async deleteComment(id) {
      return client.delete(`/comments/${id}`);
    },
    async reminders(taskId) {
      return unwrapData(await client.get(`/tasks/${taskId}/reminders`));
    },
    async createReminder(taskId, payload) {
      return unwrapData(await client.post(`/tasks/${taskId}/reminders`, payload));
    },
    async updateReminder(id, payload) {
      return unwrapData(await client.patch(`/reminders/${id}`, payload));
    },
    async deleteReminder(id) {
      return client.delete(`/reminders/${id}`);
    },
    async attachments(taskId) {
      return unwrapData(await client.get(`/tasks/${taskId}/attachments`));
    },
    async uploadAttachment(taskId, file) {
      const body = new FormData();
      body.append('file', file);
      return unwrapData(await client.request(`/tasks/${taskId}/attachments`, {
        method: 'POST',
        body,
      }));
    },
    async deleteAttachment(id) {
      return client.delete(`/attachments/${id}`);
    },
    async downloadAttachment(id) {
      return client.download(`/attachments/${id}/download`);
    },
  };
}
