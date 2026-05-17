"use strict";

/**
 * Устанавливает содержимое innerHTML для div-a с id == DivId
 * 
 * @param {String} Text - текст для установки в div.innerHTML
 * @param {String} DivId - id объекта DIV в текущем DOM
 * 
 * @returns {undefined}
 */
function setTextTo(Text, DivId) {
  if (Text === undefined || DivId === undefined || !DivId) {
    return false;
  }

  var myDiv = document.getElementById(DivId);
  if (!myDiv) { return false; }
  myDiv.innerHTML = Text;

  return true;
}

/**
 * Проверяет соответствие кода ответа сервера 200.
 * При отличии логирует ошибку (без alert).
 *
 * @param {Number} StatusCode
 * @param {String} StatusText
 * @returns {Boolean}
 */
function checkAndAlertStatus(StatusCode, StatusText) {
  if (typeof StatusCode !== 'undefined' && StatusCode !== 200) {
    var msg = (typeof StatusText !== 'undefined' && StatusText) ? StatusText : 'Ошибка при запросе к серверу';
    if (typeof NewCMSErrorHandler !== 'undefined') {
      NewCMSErrorHandler.error('HTTP ' + StatusCode + ': ' + msg, { statusCode: StatusCode, statusText: StatusText });
    } else {
      console.error('[NewCMS] HTTP ' + StatusCode + ':', msg);
    }
    return false;
  }
  return true;
}

/**
 * Создает и возвращает объект XHR для запросов к серверу.
 * Если такие объекты не поддерживаются браузером, вернется null
 * 
 * @returns {ActiveXObject|XMLHttpRequest}
 */
function createRequest() {
  var request;
  // для современных браузеров
  if (window.XMLHttpRequest) { request = new XMLHttpRequest(); }
  // для старых Internet Explorer
  else if (window.ActiveXObject) {
    // пробуем для IE разных версий
    try { request = new ActiveXObject('Msxml2.XMLHTTP'); }
    catch (e1) {
      try { request = new ActiveXObject('Microsoft.XMLHTTP'); }
      catch (e2) { return null; }
    }
  }
  return request;
}

/**
 * 
 * @param {String} locationRequest - URL запроса
 * @param {String} mtd - метод запроса, только POST или GET
 * @param {String} parameters - параметры запроса, 
 * если метод GET, то задается как в запросе через &, можно передать пустую строку или null.
 * Если метод POST, то будет передан в теле запроса, 
 * можно передать JSON или данные формы и загрузить файл
 * @param {String} func - функция-callback, 
 * которая будет вызвана в результате удачного асинхронного запроса,
 * в функцию будет передан аргумент Text - строка с ответом
 * @param {Object} context - если передан, 
 * то callback (func) будет вызван через call именнос этим контекстом
 * 
 * @returns {String|Boolean} - в случае асинхронного запроса всегда вернется true,
 * состояние запроса будет отслеживаться через обработчик onreadystatechange,
 * в случае синхронного запроса вернется строка с ответом, 
 * или false при возникновении ошибки 
 */
/**
 * @param {String}   locationRequest
 * @param {String}   mtd
 * @param {String}   parameters
 * @param {Function} func        - callback успешного ответа(str, StatusCode, StatusText)
 * @param {Object}   context
 * @param {Function} [errorFunc] - callback ошибки(str, StatusCode, StatusText); если не задан, вызывается func
 * @param {Number}   [timeoutMs] - таймаут мс (по умолчанию 30000)
 */
function sendRequest(locationRequest, mtd, parameters, func, context, errorFunc, timeoutMs) {
  if (typeof context === 'undefined' || context === null) {
    context = this;
  }
  if (!locationRequest) {
    if (typeof NewCMSErrorHandler !== 'undefined') {
      NewCMSErrorHandler.error('sendRequest: пустой URL');
    }
    return false;
  }
  // Создаем объект запроса
  var request = createRequest();
  if (!request) {
    if (typeof NewCMSErrorHandler !== 'undefined') {
      NewCMSErrorHandler.error('sendRequest: браузер не поддерживает AJAX');
    }
    if (typeof errorFunc === 'function') { errorFunc.call(context, '', 0, 'No AJAX'); }
    return false;
  }

  // указывает как будет производиться запрос , асинхронно или обычным (синхронным) способом
  var async = true;
  if (func === undefined || !func) { async = false; }

  var method = "GET";
  if (typeof mtd !== 'undefined' && mtd && mtd.toUpperCase() === "POST") { method = "POST"; }

  var requestTimeout = (typeof timeoutMs === 'number' && timeoutMs > 0) ? timeoutMs : 30000;

  // Посылаем запрос методом method 
  // Указываем адрес,
  // если GET то с параметрами в адресной строке
  if (method === "GET" && parameters !== undefined && parameters.length) {
    locationRequest += '?' + parameters;
    parameters = null;
  }
  request.open(method, locationRequest, async);
  if (method === "POST") {
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  }

  if (async) {
    request.timeout = requestTimeout;

    request.onreadystatechange = function () {
      if (this.readyState !== 4) { return; }

      var responseText = this.responseText || '';
      var isSuccess    = (this.status >= 200 && this.status < 300);

      if (isSuccess) {
        func.call(context, responseText, this.status, this.statusText || '');
      } else {
        if (typeof NewCMSErrorHandler !== 'undefined') {
          NewCMSErrorHandler.error('sendRequest: HTTP ' + this.status, { url: locationRequest });
        }
        var cb = (typeof errorFunc === 'function') ? errorFunc : func;
        cb.call(context, responseText, this.status, this.statusText || '');
      }
    };

    request.onerror = function () {
      if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.error('sendRequest: сетевая ошибка', { url: locationRequest });
      }
      var cb = (typeof errorFunc === 'function') ? errorFunc : func;
      if (typeof cb === 'function') { cb.call(context, '', 0, 'Network error'); }
    };

    request.ontimeout = function () {
      if (typeof NewCMSErrorHandler !== 'undefined') {
        NewCMSErrorHandler.warn('sendRequest: timeout (' + requestTimeout + 'ms)', { url: locationRequest });
      }
      var cb = (typeof errorFunc === 'function') ? errorFunc : func;
      if (typeof cb === 'function') { cb.call(context, '', 408, 'Timeout'); }
    };

    request.send(parameters);
    return true;
  }

  // Синхронный запрос
  try {
    request.send(parameters);
  } catch (e) {
    if (typeof NewCMSErrorHandler !== 'undefined') {
      NewCMSErrorHandler.error('sendRequest (sync): ошибка отправки', { error: e.message });
    }
    return false;
  }
  if (request.status < 200 || request.status >= 300) {
    if (typeof NewCMSErrorHandler !== 'undefined') {
      NewCMSErrorHandler.error('sendRequest (sync): HTTP ' + request.status, { url: locationRequest });
    }
    return false;
  }
  return request.responseText || '';
}
