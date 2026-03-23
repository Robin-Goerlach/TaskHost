import { TaskHostApp } from './app.js';

const root = document.getElementById('app');

if (!root) {
  throw new Error('Das App-Element #app wurde nicht gefunden.');
}

const app = new TaskHostApp(root, window.TASKHOST_CONFIG ?? {});
app.init();
