!function(e,t,o){"use strict";"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?module.exports=t():e.CookiesEuBanner=t()}(window,function(){"use strict";var e,t=window.document;return(e=function(t,o,n,i){if(!(this instanceof e))return new e(t);this.cookieTimeout=33696e6,this.bots=/bot|crawler|spider|crawling/i,this.cookieName="hasConsent",this.trackingCookiesNames=["__utma","__utmb","__utmc","__utmt","__utmv","__utmz","_ga","_gat","_gid"],this.launchFunction=t,this.waitAccept=o||!1,this.useLocalStorage=n||!1,this.init()}).prototype={init:function(){var e=this.bots.test(navigator.userAgent),t=navigator.doNotTrack||navigator.msDoNotTrack||window.doNotTrack;return e||!(null===t||void 0===t||t&&"yes"!==t&&1!==t&&"1"!==t)||!1===this.hasConsent()?(this.removeBanner(0),!1):!0===this.hasConsent()?(this.launchFunction(),!0):(this.showBanner(),void(this.waitAccept||this.setConsent(!0)))},showBanner:function(){var e=this,o=t.getElementById.bind(t),n=o("cookies-eu-banner"),i=o("cookies-eu-reject"),s=o("cookies-eu-accept"),a=o("cookies-eu-more"),c=void 0===n.dataset.waitRemove?0:parseInt(n.dataset.waitRemove),r=this.addClickListener,u=e.removeBanner.bind(e,c);n.style.display="block",a&&r(a,function(){e.deleteCookie(e.cookieName)}),s&&r(s,function(){u(),e.setConsent(!0),e.launchFunction()}),i&&r(i,function(){u(),e.setConsent(!1),e.trackingCookiesNames.map(e.deleteCookie)})},setConsent:function(e){if(this.useLocalStorage)return localStorage.setItem(this.cookieName,e);this.setCookie(this.cookieName,e)},hasConsent:function(){var e=this.cookieName,o=function(o){return t.cookie.indexOf(e+"="+o)>-1||localStorage.getItem(e)===o};return!!o("true")||!o("false")&&null},setCookie:function(e,o){var n=new Date;n.setTime(n.getTime()+this.cookieTimeout),t.cookie=e+"="+o+";expires="+n.toGMTString()+";path=/"},deleteCookie:function(e){var o=t.location.hostname.replace(/^www\./,""),n="; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/";t.cookie=e+"=; domain=."+o+n,t.cookie=e+"="+n},addClickListener:function(e,t){if(e.attachEvent)return e.attachEvent("onclick",t);e.addEventListener("click",t)},removeBanner:function(e){setTimeout(function(){var e=t.getElementById("cookies-eu-banner");e&&e.parentNode&&e.parentNode.removeChild(e)},e)}},e});