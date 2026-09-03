"use strict";
function BasketPage(ContactForm, BasketDivName, CostDivName, ConfirmButtonId, CancelButtonId) {
  this.BasketDivName = BasketDivName;
  this.CostDivName = CostDivName;
  this.ConfirmButton = document.getElementById(ConfirmButtonId);
  this.CancelButton = document.getElementById(CancelButtonId);
  this.ContactForm = ContactForm;

  // ********** анти-спам: ограничение частоты отправки заявок **********
  // имя cookie, хранящей абсолютное Unix-время окончания блокировки отправки
  this.LockCookieName = "podvesnoi_basket_next_submit";
  // длительность блокировки отправки в секундах (3 минуты), синхронизируется с сервером
  this.LockIntervalSeconds = 180;
  // id элемента, в который выводится обратный отсчет до разрешенной отправки
  this.LockElementId = "basket_submit_timer";
  // абсолютное Unix-время следующей разрешенной отправки из PHP-сессии на сервере
  this.ServerNextSubmitTime = 0;
  // флаг выполняющейся отправки - защита от двойного клика
  this.isSubmitting = false;
  // идентификатор интервала таймера обратного отсчета
  this.LockTimerId = null;
  // абсолютное Unix-время окончания текущей блокировки
  this.LockEndTime = 0;

  // возвращает текущее Unix-время в секундах
  this.nowSeconds = function () {
    return Math.floor(Date.now() / 1000);
  };

  // вычисляет актуальное время окончания блокировки из cookie и серверной сессии
  this.getLockEndTime = function () {
    var Now = this.nowSeconds();
    var LockEnd = 0;

    if (typeof this.ServerNextSubmitTime === "number" &&
      this.ServerNextSubmitTime > Now) {
      LockEnd = this.ServerNextSubmitTime;
    }

    var CookieValue = parseInt(
      (typeof getCookie === "function") ? getCookie(this.LockCookieName) : "",
      10);
    if (!isNaN(CookieValue) && CookieValue > Now && CookieValue > LockEnd) {
      LockEnd = CookieValue;
    }

    // защита от устаревшей/испорченной cookie: блокировка не может превышать интервал
    if (LockEnd - Now > this.LockIntervalSeconds) {
      LockEnd = Now + this.LockIntervalSeconds;
    }
    return LockEnd;
  };

  // записывает cookie с абсолютным временем окончания блокировки
  this.setLockCookie = function (LockEndTime) {
    var MaxAge = LockEndTime - this.nowSeconds();
    if (MaxAge <= 0) {
      this.deleteLockCookie();
      return;
    }
    var CookieString = this.LockCookieName + "=" + LockEndTime +
      "; path=/; max-age=" + MaxAge + "; SameSite=Lax";
    if (window.location.protocol === "https:") {
      CookieString += "; Secure";
    }
    document.cookie = CookieString;
  };

  // удаляет cookie блокировки
  this.deleteLockCookie = function () {
    document.cookie = this.LockCookieName +
      "=; path=/; max-age=0; SameSite=Lax";
  };

  // форматирует оставшееся время в виде MM:SS
  this.formatLockTime = function (RemainingSeconds) {
    var Minutes = Math.floor(RemainingSeconds / 60);
    var Seconds = RemainingSeconds % 60;
    var MinutesString = (Minutes < 10 ? "0" : "") + Minutes;
    var SecondsString = (Seconds < 10 ? "0" : "") + Seconds;
    return MinutesString + ":" + SecondsString;
  };

  this.confirmOrder = function () {
    if (this.ContactForm === undefined) { return false; }
    if (this.isSubmitting) { return false; }
    if (this.ConfirmButton.disabled) { return false; }
    // повторная проверка блокировки на случай рассинхронизации интерфейса
    if (this.getLockEndTime() > this.nowSeconds()) {
      this.applyLockState();
      return false;
    }
    //        GlobalBasket.putGoodsToCookies();

    this.isSubmitting = true;
    document.getElementById(this.BasketDivName).innerHTML = loaderdiv;
    this.showButtons(0);
    window.location.hash = this.BasketDivName;
    //    OffsetY = document.getElementById(this.BasketDivName).offsetTop;
    //    window.scrollTo({top: OffsetY});
    var parameters;
    var message = encodeURIComponent(this.ContactForm.message.value); //encodeURIComponent(this.ContactForm.message.value);
    var fio = encodeURIComponent(this.ContactForm.fio.value);
    var email = this.ContactForm.email.value;
    var phone = this.ContactForm.phone.value;
    parameters = "message=" + message + "&fio=" + fio + "&email=" + email + "&phone=" + phone;
    parameters += "&coding=UTF-8";

    sendRequest(
      GlobalBasket.BasketControllerName + "/confirm",
      "POST",
      parameters,
      this.afterConfirm,
      this);
  };

  // выводит текст обратного отсчета в элемент таймера
  this.setLockMessage = function (Text) {
    var LockElement = document.getElementById(this.LockElementId);
    if (LockElement) {
      LockElement.textContent = Text;
    }
  };

  // обновляет текст обратного отсчета по текущему времени
  this.renderLockCountdown = function () {
    var Remaining = this.LockEndTime - this.nowSeconds();
    if (Remaining < 0) { Remaining = 0; }
    this.setLockMessage("Повторная отправка будет доступна через " +
      this.formatLockTime(Remaining));
  };

  // запускает таймер обратного отсчета (раз в секунду)
  this.startLockTimer = function () {
    if (this.LockTimerId !== null) { return; }
    var Self = this;
    this.LockTimerId = setInterval(function () { Self.onLockTick(); }, 1000);
  };

  // останавливает таймер обратного отсчета
  this.stopLockTimer = function () {
    if (this.LockTimerId !== null) {
      clearInterval(this.LockTimerId);
      this.LockTimerId = null;
    }
  };

  // тик таймера: обновляет отсчет или снимает блокировку по её истечении
  this.onLockTick = function () {
    if (this.LockEndTime <= this.nowSeconds()) {
      this.LockEndTime = 0;
      this.stopLockTimer();
      this.deleteLockCookie();
      this.setLockMessage("");
      this.ConfirmButton.disabled = false;
      this.safeShowButtons();
      return;
    }
    this.renderLockCountdown();
  };

  // безопасный вызов showButtons: GlobalBasket создается в main.js,
  // который подключается в футере страницы ПОСЛЕ инлайн-скрипта корзины,
  // поэтому на момент синхронной инициализации он может быть еще не определен
  this.safeShowButtons = function () {
    if (typeof GlobalBasket !== "undefined") {
      this.showButtons();
    }
  };

  // применяет актуальное состояние блокировки к кнопке и таймеру
  this.applyLockState = function () {
    var LockEndTime = this.getLockEndTime();
    if (LockEndTime > this.nowSeconds()) {
      this.LockEndTime = LockEndTime;
      this.ConfirmButton.disabled = true;
      this.renderLockCountdown();
      this.startLockTimer();
    }
    else {
      this.LockEndTime = 0;
      this.stopLockTimer();
      this.deleteLockCookie();
      this.setLockMessage("");
      this.ConfirmButton.disabled = false;
    }
    this.safeShowButtons();
  };

  // инициализация состояния ограничения (вызывается из шаблона корзины)
  this.initRateLimit = function (ServerNextSubmitTime, IntervalSeconds) {
    // значение сервера авторитетно: 0 означает отсутствие блокировки в сессии
    if (typeof ServerNextSubmitTime === "number" && ServerNextSubmitTime >= 0) {
      this.ServerNextSubmitTime = ServerNextSubmitTime;
    }
    if (typeof IntervalSeconds === "number" && IntervalSeconds > 0) {
      this.LockIntervalSeconds = IntervalSeconds;
    }
    this.applyLockState();
  };

  // извлекает оставшееся время блокировки из ответа сервера на 429
  this.parseRetrySeconds = function (ResponseText) {
    var DefaultSeconds = this.LockIntervalSeconds;
    if (!ResponseText) { return DefaultSeconds; }
    try {
      var Data = JSON.parse(ResponseText);
      if (Data && typeof Data.retryAfter !== "undefined") {
        var RetrySeconds = parseInt(Data.retryAfter, 10);
        if (!isNaN(RetrySeconds) && RetrySeconds > 0 &&
          RetrySeconds <= this.LockIntervalSeconds) {
          return RetrySeconds;
        }
      }
    }
    catch (e) {
      // ответ не JSON - пробуем найти время в формате MM:SS
      var Match = ResponseText.match(/(\d{1,2}):(\d{2})/);
      if (Match) {
        var Parsed = parseInt(Match[1], 10) * 60 + parseInt(Match[2], 10);
        if (Parsed > 0 && Parsed <= this.LockIntervalSeconds) {
          return Parsed;
        }
      }
    }
    return DefaultSeconds;
  };

  // обработка ответа 429: синхронизация блокировки с сервером
  this.handleTooManyRequests = function (ResponseText) {
    var RetrySeconds = this.parseRetrySeconds(ResponseText);
    this.setLockCookie(this.nowSeconds() + RetrySeconds);
    this.applyLockState();
    // восстанавливаем отображение товаров корзины вместо индикатора загрузки
    this.loadBasket();
  };

  // восстанавливает интерфейс после неуспешной отправки (ошибка почты, сервера, сети)
  this.restoreAfterError = function () {
    this.ConfirmButton.disabled = false;
    this.loadBasket();
  };

  this.afterConfirm = function (str, StatusCode, StatusText) {
    this.isSubmitting = false;

    // анти-спам: сервер отклонил отправку из-за ограничения частоты (HTTP 429)
    if (StatusCode === 429) {
      this.handleTooManyRequests(str);
      return;
    }

    if (!checkAndAlertStatus(StatusCode, StatusText)) { 
      console.error("[BASKET_CONFIRM_ERROR]", {
        statusCode: StatusCode,
        statusText: StatusText,
        responseText: str,
        timestamp: new Date().toISOString()
      });
      this.restoreAfterError();
      return; 
    }
    // успешная отправка: фиксируем блокировку в cookie и запускаем обратный отсчет
    this.setLockCookie(this.nowSeconds() + this.LockIntervalSeconds);
    this.applyLockState();
    setTextTo(str, this.BasketDivName);
    GlobalBasket.emptyBasket();
    this.ContactForm.message.value = "";
    this.showButtons();
  };

  this.loadBasket = function () {
    document.getElementById(this.BasketDivName).innerHTML = loaderdiv;

    sendRequest(
      GlobalBasket.BasketControllerName + "/form",
      "POST",
      "",
      this.afterLoad,
      this);
  };

  this.afterLoad = function (str, StatusCode, StatusText) {
    if (!checkAndAlertStatus(StatusCode, StatusText)) { return; }
    setTextTo(str, this.BasketDivName);
    this.showButtons();
    this.getCost();
  };

  this.emptyBasket = function () {
    GlobalBasket.emptyBasket();
    //        GlobalBasket.putGoodsToCookies();
    this.loadBasket();
  };

  this.changeCounts = function (id_product, inputelem) {
    GlobalBasket.setGoods(id_product, inputelem.value); // parentNode.countgoods.value);
    //        GlobalBasket.putGoodsToCookies();
    this.getCost();
  };

  this.removeProduct = function (id_product, elem) {
    GlobalBasket.removeGoods(id_product);
    //        GlobalBasket.putGoodsToCookies();
    elem.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode);
    this.getCost();
  };

  this.getCost = function () {
    sendRequest(
      GlobalBasket.BasketControllerName + "/get-cost",
      "POST",
      "",
      this.setBasketCost,
      this);
  };

  this.setBasketCost = function (str, StatusCode, StatusText) {
    if (!checkAndAlertStatus(StatusCode, StatusText)) { return; }
    GlobalBasket.TotalCost = Number(str);
    setTextTo(str, this.CostDivName);
  };

  this.showButtons = function (visibility) {
    if (visibility !== undefined) {
      if (!visibility) {
        this.ConfirmButton.style.visibility = "hidden";
        this.CancelButton.style.visibility = "hidden";
      }
      else {
        this.ConfirmButton.style.visibility = "visible";
        this.CancelButton.style.visibility = "visible";
      }
      return;
    }

    if (GlobalBasket.Goods.length) {
      this.CancelButton.style.visibility = "visible";
    }
    else {
      this.CancelButton.style.visibility = "hidden";
    }
    if (!this.ContactForm.email.value.length) {
      this.ConfirmButton.style.visibility = "hidden";
      return;
    }
    if (this.ContactForm.message.value.length ||
      GlobalBasket.Goods.length) {
      this.ConfirmButton.style.visibility = "visible";
      return;
    }

    this.ConfirmButton.style.visibility = "hidden";
  };

}
