export class ApiError extends Error {
  constructor(message, status = 500, payload = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.payload = payload;
  }
}

export class ApiClient {
  constructor(baseUrl) {
    this.baseUrl = String(baseUrl || '').replace(/\/$/, '');
    this.token = null;
  }

  setToken(token) {
    this.token = token || null;
  }

  async request(path, options = {}) {
    const url = `${this.baseUrl}${path}`;
    const headers = new Headers(options.headers || {});
    const isFormData = options.body instanceof FormData;

    if (!isFormData && !headers.has('Content-Type') && options.body !== undefined) {
      headers.set('Content-Type', 'application/json');
    }

    if (this.token && !headers.has('Authorization')) {
      headers.set('Authorization', `Bearer ${this.token}`);
    }

    const response = await fetch(url, {
      method: options.method || 'GET',
      headers,
      body: isFormData
        ? options.body
        : options.body !== undefined
          ? JSON.stringify(options.body)
          : undefined,
    });

    const contentType = response.headers.get('content-type') || '';

    if (!response.ok) {
      let payload = null;
      try {
        payload = contentType.includes('application/json')
          ? await response.json()
          : await response.text();
      } catch {
        payload = null;
      }

      const message = payload?.error?.message || payload?.message || (typeof payload === 'string' && payload) || `HTTP-Fehler ${response.status}`;
      throw new ApiError(message, response.status, payload);
    }

    if (response.status === 204) {
      return null;
    }

    if (contentType.includes('application/json')) {
      return response.json();
    }

    if (options.expect === 'blob') {
      return response.blob();
    }

    return response.text();
  }

  get(path) {
    return this.request(path);
  }

  post(path, body) {
    return this.request(path, { method: 'POST', body });
  }

  put(path, body) {
    return this.request(path, { method: 'PUT', body });
  }

  patch(path, body) {
    return this.request(path, { method: 'PATCH', body });
  }

  delete(path) {
    return this.request(path, { method: 'DELETE' });
  }

  async download(path) {
    return this.request(path, { expect: 'blob' });
  }
}
