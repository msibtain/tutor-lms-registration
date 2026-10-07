(()=>{"use strict";var t={};// The module cache
var e={};// The require function
function n(r){// Check if module is in cache
var a=e[r];if(a!==undefined){return a.exports}// Create a new module (and put it into the cache)
var i=e[r]={exports:{}};// Execute the module function
t[r](i,i.exports,n);// Return the exports of the module
return i.exports}// webpack/runtime/rspack_version
(()=>{n.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{n.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function r(t,e,n,r,a,i,o){try{var s=t[i](o);var u=s.value}catch(t){n(t);return}if(s.done)e(u);else Promise.resolve(u).then(r,a)}function a(t){return function(){var e=this,n=arguments;return new Promise(function(a,i){var o=t.apply(e,n);function s(t){r(o,a,i,s,u,"next",t)}function u(t){r(o,a,i,s,u,"throw",t)}s(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var i=function(t){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var n=t.getTimezoneOffset();var r=addMinutes(t,n);return format(r,e)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var o=t=>{var e=new Date(t);var n=e.getTimezoneOffset();return addMinutes(e,-n)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var s=t=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);/**
 * Creates a FormData object from a data object and automatically adds security nonce.
 * 
 * @param {Object} [data={}] - The data object to convert to FormData
 * @returns {FormData} A FormData object with the provided data and security nonce
 * 
 * @example
 * const formData = tutorFormData({
 *   action: 'save_lesson_note',
 *   lesson_id: 123,
 *   note_text: 'My note content'
 * });
 * 
 * @since v3.9.0
 */function u(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var e=new FormData;Object.keys(t).forEach(n=>e.set(n,t[n]));e.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return e}/**
 * Handles AJAX requests to the WordPress admin-ajax.php endpoint.
 * 
 * @param {FormData} formData - The FormData object containing the request data
 * @returns {Promise<Response|undefined>} The fetch Response object, or undefined if an error occurs
 * 
 * @example
 * const formData = tutorFormData({ action: 'get_lesson_data', lesson_id: 123 });
 * const response = await ajaxHandler(formData);
 * const data = await response.json();
 * 
 * @since v3.9.0
 */function d(t){return a(function*(){try{var e=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return e}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var c=(t,e)=>{var{__:n}=wp.i18n;var{data:r={}}=t||{};var{message:a=e||n("Something Went Wrong!","tutor-pro")}=r;return a};// CONCATENATED MODULE: ./addons/h5p/assets/src/js/quiz.js
window.jQuery(document).ready(function(t){var{__}=window.wp.i18n;var e=document.querySelector("button[form^='quiz-attempt-form-']");function n(n,r,i){return a(function*(){var a=false;var o=document.querySelectorAll("[data-content-id]");if(o&&o.length>0){var s=0;var c=0;o.forEach(e=>{var r=t(e).closest(".tutor-quiz-question");if(r){s=parseInt(t(r).attr("id").match(/\d+/)[0],10);c=parseInt(t(e).data("content-id"),10);n.push({"question_id":s,"content_id":c})}})}var l=u({action:"check_h5p_question_answered",question_ids:JSON.stringify(n),attempt_id:i,quiz_id:r});try{t(e).addClass("tutor-btn-loading");var v=yield d(l);var{data:p}=yield v.json();if(p){var f=JSON.parse(p.required_answers);n.forEach(t=>{var e=document.getElementById(t.question_id);var n=e.querySelector(".tutor-quiz-questions-error");if(n){e.removeChild(n)}});if(f.length>0){f.forEach(t=>{var e=document.createElement("div");e.className="tutor-quiz-questions-error";e.innerHTML=__("Answer for this question is required.","tutor-pro");var n=document.getElementById(t.question_id);var r=n.querySelector(".tutor-quiz-questions-error");if(n&&!r){n.appendChild(e)}})}else{a=true}}}catch(t){console.error(t)}finally{t(e).removeClass("tutor-btn-loading")}return a})()}if(e){var r=0;var i=0;var o=new AbortController;var s=t(e.form).attr("id");if(s&&typeof s==="string"){var c=s.match(/\d+/g);var l;r=(l=parseInt(c[1],10))!==null&&l!==void 0?l:0;var v;i=(v=parseInt(c[0],10))!==null&&v!==void 0?v:0}var p=[];e.addEventListener("click",t=>{t.preventDefault();n(p,r,i).then(t=>{if(t){o.abort();e.click()}})},{signal:o.signal})}// Check if all the h5p question are answered
t("button[name='quiz_answer_submit_btn']").on("click",()=>{var e=[];var n=false;var r=t("button[name='quiz_answer_submit_btn']");var a=document.querySelectorAll("[data-h5p-quiz-content-id]");if(a&&a.length>0){a.forEach(n=>{var r=parseInt(t(n).attr("id").match(/\d+/)[0],10);var a=parseInt(t(n).data("h5p-quiz-content-id"),10);e.push({"question_id":r,"content_id":a})})}t.ajax({url:_tutorobject.ajaxurl,type:"POST",async:false,data:{action:"check_h5p_question_answered",question_ids:JSON.stringify(e),attempt_id:parseInt(t("input[name='attempt_id']").val(),10),quiz_id:parseInt(t("#tutor_quiz_id").val(),10)},beforeSend:function t(){r.addClass("is-loading").attr("disabled",true)},success:function e(e){if(e.success){var r=e.data.required_answers;var i=JSON.parse(r);a.forEach(e=>{var n=t(e).children(".answer-help-block")[0];if(t(n).children("p").length>0){t(n).children("p").remove()}});if(i.length>0){t("#quiz-attempt-single-question-".concat(i[0].question_id)).get(0).scrollIntoView();i.forEach(e=>{var n=t("#quiz-attempt-single-question-"+e.question_id).children(".answer-help-block");t(n).html("<p class='answer-required' style=\"color: #dc3545\">".concat(__("The answer for this question is required","tutor_pro"),"</p>"))})}else{n=true}}},complete:function t(){r.removeClass("is-loading").attr("disabled",false)}});return n});var f=null;if(H5P||H5P.externalDispatcher){//Handle storing xapi data upon interaction
var h=function e(e){var n=0;var r=parseInt(t("#tutor_quiz_id").val(),10)||document.querySelector("[id^='quiz-attempt-form-']").id;if(r&&typeof r==="string"){var a=r.match(/\d+/g);var i;r=(i=parseInt(a[1],10))!==null&&i!==void 0?i:0;var o;n=(o=parseInt(a[0],10))!==null&&o!==void 0?o:0}var s="h5p-local-content-id";var u="h5p-local-question-id";f=e.data.statement;var d=f.object.definition.extensions["http://h5p.org/x-api/"+s];if(f.object.definition.extensions["http://h5p.org/x-api/"+u]!==undefined){var c=f.object.definition.extensions["http://h5p.org/x-api/"+u];t.ajax({url:_tutorobject.ajaxurl,type:"POST",data:{action:"save_h5p_question_xAPI_statement",quiz_id:r,_tutor_nonce:_tutorobject._tutor_nonce,question_id:c,statement:JSON.stringify(f),content_id:d,attempt_id:t("input[name='attempt_id']").val()||n}})}};H5P.externalDispatcher.on("xAPI",h)}var m=document.querySelectorAll(".h5p-content");// Here content id and question id are set on the object property of xAPI statement
// So that it can be obtained during saving the xAPI statement
m.forEach(e=>{var n=t(e).closest(".quiz-attempt-single-question");if(!n.length){n=t(e).closest(".tutor-quiz-question")}var r=parseInt(n.attr("id").match(/\d+/)[0],10);if(H5P){// XAPIEvent property of H5P is used which has method to update the object property of xAPI statement
H5P.XAPIEvent.prototype.setObject=function(t){if(t.contentId){// Here the extension property of the statements object is used
// To add the question id of the h5p question
this.data.statement.object={"id":this.getContentXAPIId(t),"objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":t.contentId,"http://h5p.org/x-api/h5p-local-question-id":r}}};if(t.subContentId){this.data.statement.object.definition.extensions["http://h5p.org/x-api/h5p-subContentId"]=t.subContentId;// Don't set titles on main content, title should come from publishing platform
if(typeof t.getTitle==="function"){this.data.statement.object.definition.name={"en-US":t.getTitle()}}}else{var e=H5P.getContentForInstance(t.contentId);if(e&&e.metadata&&e.metadata.title){this.data.statement.object.definition.name={"en-US":H5P.createTitle(e.metadata.title)}}}}else{// Content types view always expect to have a contentId when they are displayed.
// This is not the case if they are displayed in the editor as part of a preview.
// The fix is to set an empty object with definition for the xAPI event, so all
// the content types that rely on this does not have to handle it. This means
// that content types that are being previewed will send xAPI completed events,
// but since there are no scripts that catch these events in the editor,
// this is not a problem.
this.data.statement.object={definition:{}}}}}});var _=document.querySelectorAll(".h5p-iframe");// Setup the h5p content iframe upon loading
_.forEach(e=>{var n=t(e).closest(".quiz-attempt-single-question");if(!n.length){n=t(e).closest(".tutor-quiz-question")}var r=parseInt(n.attr("id").match(/\d+/)[0],10);var a=setInterval(()=>{var n=e.contentDocument||e.contentWindow.document;// Check if the iframe is visible or interactive
if(n.readyState==="complete"||n.readyState==="interactive"){// Send a signal from iframe to parent to perform some event after the content has loaded
e.contentWindow.postMessage({action:"set_iframe",selector:".h5p-content",question_id:r,content_id:t(e).closest(".quiz-attempt-single-question").data("h5p-quiz-content-id")},"*");//If not ready then clear timer and stop to go to next iteration
clearInterval(a)}},500)})})})();