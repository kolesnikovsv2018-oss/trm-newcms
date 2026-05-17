"use strict";

var TopicPath = "/topics/main";

var waitingimage = TopicPath + "/images/icons/waiting-big.gif";

var loaderdiv = '<div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;"><img src="' + waitingimage + '"></div>';

var lastgoodscookies = window.location.host + ".last";
//lastgoodscookies = lastgoodscookies.replace("www.", "");
lastgoodscookies = lastgoodscookies.replace(/\./g, '_');
