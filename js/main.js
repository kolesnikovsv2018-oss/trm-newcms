"use strict";

var GlobalBasket = new Basket();

function checkAgreement() {
  var checkCookie = getCookie('agreement');

  if (
    !checkCookie ||
    checkCookie !== 'yes'
  ) {
    var agreementDiv = document.getElementById('agreementdivid');
    if (agreementDiv) {
      agreementDiv.style.display = 'flex';
    }
  }
}

function acceptAgreement() {
  document.cookie = 'agreement=yes; max-age=' + 60 * 60 * 24 * 365 + '; path=/';

  var agreementDiv = document.getElementById('agreementdivid');
  if (agreementDiv) {
    agreementDiv.style.display = 'none';
  }
}

function setBasketCountsInForms() {
  GlobalBasket.getGoodsFromCookies();

  var xxx = document.getElementsByClassName('order_goods_form');
  for (var i = 0; i < xxx.length; i++) {
    if (typeof xxx[i].countgoods !== "undefined") {
      if ((typeof GlobalBasket.getGoodsCount(xxx[i].idgoods.value) !== "undefined")
        && (GlobalBasket.getGoodsCount(xxx[i].idgoods.value) > 0)) {
        xxx[i].countgoods.value = GlobalBasket.getGoodsCount(xxx[i].idgoods.value);
      }
    }
  }
}

function setOnScrollArrow() {
  window.onscroll = function () {
    var scrolled = window.pageYOffset || document.documentElement.scrollTop;
    var arrowdiv = document.getElementById('uparrowid');
    if (!arrowdiv) { return; }
    if (scrolled >= 500) arrowdiv.style.display = 'flex';
    if (scrolled < 500) arrowdiv.style.display = 'none';
  };
}

document.addEventListener(
  'DOMContentLoaded',
  function () {
    checkAgreement();
    setBasketCountsInForms();
  },
  false
);

setOnScrollArrow();