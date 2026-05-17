"use strict";

// Consumer app must inject web paths before loading NewCMS JS, e.g.:
// window.NewCMSPaths = { topicWebPath: '/themes/main' };
var NewCMSPaths = (typeof window !== "undefined" && window.NewCMSPaths) ? window.NewCMSPaths : {};
var TopicPath = "";

if (typeof NewCMSPaths.topicWebPath === "string" && NewCMSPaths.topicWebPath.length > 0) {
	TopicPath = NewCMSPaths.topicWebPath.replace(/\/+$/, "");
} else if (typeof window !== "undefined" && typeof window.TOPIC_WEB_PATH === "string" && window.TOPIC_WEB_PATH.length > 0) {
	TopicPath = window.TOPIC_WEB_PATH.replace(/\/+$/, "");
}

if (!TopicPath && typeof console !== "undefined" && typeof console.warn === "function") {
	console.warn("NewCMS: topic web path is not configured. Set window.NewCMSPaths.topicWebPath before loading jsglobal.js.");
}

var waitingimage = TopicPath ? (TopicPath + "/images/icons/waiting-big.gif") : "";

var loaderimage = waitingimage ? ('<img src="' + waitingimage + '">') : "";

var loaderdiv = '<div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">' + loaderimage + '</div>';

var lastgoodscookies = window.location.host + ".last";
//lastgoodscookies = lastgoodscookies.replace("www.", "");
lastgoodscookies = lastgoodscookies.replace(/\./g, '_');
