(()=>{var t={};// The module cache
var e={};// The require function
function n(a){// Check if module is in cache
var i=e[a];if(i!==undefined){return i.exports}// Create a new module (and put it into the cache)
var o=e[a]={exports:{}};// Execute the module function
t[a](o,o.exports,n);// Return the exports of the module
return o.exports}// webpack/runtime/rspack_version
(()=>{n.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{n.ruid="bundler=rspack@1.6.5"})();var a=XMLHttpRequest.prototype.open;var i=XMLHttpRequest.prototype.send;var o,s="";// intercepting open method which is called when preparing an ajax request to send
// this method give the url and method of the request
XMLHttpRequest.prototype.open=function(t,e,n,i,r){s=e;o=t;return a.apply(this,arguments)};// intercepting and modifying send method which is called when sending the actual request
// this method provides the body of the request
XMLHttpRequest.prototype.send=function(t){var e=new URLSearchParams(s);var n=e.get("action");if(t){var a=new URLSearchParams(t);window.parent.postMessage({action:n,contentId:a.get("contentId")},"*")}return i.apply(this,arguments)};// Capture messages sent by an iframe
window.addEventListener("message",function(t){if(t.data&&t.data.action==="set_iframe"){var e=this.document.querySelector(t.data.selector);if(e){if(H5P){H5P.XAPIEvent.prototype.setObject=function(e){if(e.contentId){// Set question id on object property of statement before the h5p iframe content loads
this.data.statement.object={"id":this.getContentXAPIId(e),"objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":e.contentId,"http://h5p.org/x-api/h5p-local-question-id":t.data.question_id}}};if(e.subContentId){this.data.statement.object.definition.extensions["http://h5p.org/x-api/h5p-subContentId"]=e.subContentId;// Don't set titles on main content, title should come from publishing platform
if(typeof e.getTitle==="function"){this.data.statement.object.definition.name={"en-US":e.getTitle()}}}else{var n=H5P.getContentForInstance(e.contentId);if(n&&n.metadata&&n.metadata.title){this.data.statement.object.definition.name={"en-US":H5P.createTitle(n.metadata.title)}}}}else{// Content types view always expect to have a contentId when they are displayed.
// This is not the case if they are displayed in the editor as part of a preview.
// The fix is to set an empty object with definition for the xAPI event, so all
// the content types that rely on this does not have to handle it. This means
// that content types that are being previewed will send xAPI completed events,
// but since there are no scripts that catch these events in the editor,
// this is not a problem.
this.data.statement.object={definition:{}}}}}}}},false)})();