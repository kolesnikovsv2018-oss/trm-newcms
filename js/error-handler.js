"use strict";

/**
 * Централизованный обработчик ошибок NewCMS.
 * Должен подключаться ПЕРВЫМ среди всех скриптов NewCMS.
 */
var NewCMSErrorHandler = {
  LEVEL_DEBUG: 0,
  LEVEL_INFO:  1,
  LEVEL_WARN:  2,
  LEVEL_ERROR: 3,

  currentLevel: 2, // по умолчанию WARN и выше
  remoteLogging: false,
  remoteLoggingUrl: null,
  _history: [],
  _maxHistory: 100,

  init: function (options) {
    if (!options) { return; }
    if (options.logLevel    !== undefined)  { this.currentLevel     = options.logLevel; }
    if (options.remoteLogging !== undefined) { this.remoteLogging    = options.remoteLogging; }
    if (options.remoteLoggingUrl)            { this.remoteLoggingUrl = options.remoteLoggingUrl; }
  },

  log: function (level, message, details) {
    if (level < this.currentLevel) { return; }
    var levelNames = ['DEBUG', 'INFO', 'WARN', 'ERROR'];
    var levelName  = levelNames[level] || 'LOG';
    var entry = {
      timestamp:  new Date().toISOString(),
      level:      levelName,
      message:    message,
      details:    details || null,
      url:        (typeof window !== 'undefined') ? window.location.href : ''
    };

    this._history.push(entry);
    if (this._history.length > this._maxHistory) { this._history.shift(); }

    if (typeof console !== 'undefined') {
      var methods = ['log', 'log', 'warn', 'error'];
      var fn = console[methods[level]] || console.log;
      fn.call(console, '[NewCMS:' + levelName + ']', message, details || '');
    }

    if (this.remoteLogging && this.remoteLoggingUrl && level >= this.LEVEL_WARN) {
      this._sendToServer(entry);
    }
  },

  debug: function (msg, d) { this.log(this.LEVEL_DEBUG, msg, d); },
  info:  function (msg, d) { this.log(this.LEVEL_INFO,  msg, d); },
  warn:  function (msg, d) { this.log(this.LEVEL_WARN,  msg, d); },
  error: function (msg, d) { this.log(this.LEVEL_ERROR, msg, d); },

  getHistory: function () { return this._history; },

  _sendToServer: function (entry) {
    try {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', this.remoteLoggingUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify(entry));
    } catch (e) { /* молча игнорируем */ }
  }
};

// Глобальный перехват необработанных ошибок
if (typeof window !== 'undefined') {
  window.addEventListener('error', function (ev) {
    NewCMSErrorHandler.error('Необработанная JS ошибка', {
      message:  ev.message,
      filename: ev.filename,
      lineno:   ev.lineno,
      colno:    ev.colno
    });
  });

  window.addEventListener('unhandledrejection', function (ev) {
    NewCMSErrorHandler.error('Необработанный Promise rejection', {
      reason: String(ev.reason)
    });
  });
}

/**
 * Безопасный JSON-парсинг.
 * @param {string} str
 * @param {*} [fallback=null]
 * @returns {*}
 */
function parseJSONSafe(str, fallback) {
  if (typeof fallback === 'undefined') { fallback = null; }
  if (!str || typeof str !== 'string') { return fallback; }
  try {
    return JSON.parse(str);
  } catch (e) {
    NewCMSErrorHandler.warn('parseJSONSafe: ошибка JSON.parse', { preview: str.substring(0, 120), error: e.message });
    return fallback;
  }
}

/**
 * Безопасный document.getElementById — логирует предупреждение если элемент не найден.
 * @param {string} id
 * @returns {Element|null}
 */
function safeGetElement(id) {
  if (!id) { return null; }
  var el = document.getElementById(id);
  if (!el) {
    NewCMSErrorHandler.warn('safeGetElement: элемент не найден', { id: id });
  }
  return el;
}
