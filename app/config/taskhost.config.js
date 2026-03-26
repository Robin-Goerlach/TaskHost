// Central TaskHost client configuration.
//
// Change only apiBaseUrl when the deployed API folder changes, for example:
//   https://api.sasd.de/taskhost
//   https://api.sasd.de/taskhost_old
//
// The client appends /v1 internally, so do not include /v1 here.
window.TASKHOST_CONFIG = {
  apiBaseUrl: 'http://127.0.0.1:8080',
  appName: 'TaskHost',
};
