(()=>{"use strict";var t={};// The module cache
var r={};// The require function
function e(n){// Check if module is in cache
var o=r[n];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var a=r[n]={exports:{}};// Execute the module function
t[n](a,a.exports,e);// Return the exports of the module
return a.exports}// webpack/runtime/rspack_version
(()=>{e.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{e.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function n(t,r,e,n,o,a,u){try{var i=t[a](u);var d=i.value}catch(t){e(t);return}if(i.done)r(d);else Promise.resolve(d).then(n,o)}function o(t){return function(){var r=this,e=arguments;return new Promise(function(o,a){var u=t.apply(r,e);function i(t){n(u,o,a,i,d,"next",t)}function d(t){n(u,o,a,i,d,"throw",t)}i(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var a=function(t){var r=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var e=t.getTimezoneOffset();var n=addMinutes(t,e);return format(n,r)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var u=t=>{var r=new Date(t);var e=r.getTimezoneOffset();return addMinutes(r,-e)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var i=t=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);/**
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
 */function d(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var r=new FormData;Object.keys(t).forEach(e=>r.set(e,t[e]));r.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return r}/**
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
 */function s(t){return o(function*(){try{var r=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return r}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var c=(t,r)=>{var{__:e}=wp.i18n;var{data:n={}}=t||{};var{message:o=r||e("Something Went Wrong!","tutor-pro")}=n;return o};// CONCATENATED MODULE: ./addons/course-bundle/assets/src/js/common.js
/**
 * Course bundle builder
 *
 * @since 2.2.0
 */document.addEventListener("DOMContentLoaded",function(){return o(function*(){var{__}=wp.i18n;var t=__("Something went wrong, please try again after refreshing page","tutor-pro");/** 
    * Create new draft course bundle
    * 
    * @since 3.2.0
    */var r=document.querySelectorAll("a.tutor-add-new-course-bundle,button.tutor-add-new-course-bundle,li.tutor-add-new-course-bundle a");if(r){r.forEach(r=>{r.addEventListener("click",function(e){return o(function*(){e.preventDefault();var n=document.querySelector("body.tutor-frontend");var o={action:"tutor_create_course_bundle",source:n?"frontend":"backend"};var a=d(o);try{if(e.target.classList.contains("ab-item")){e.target.innerHTML="Creating..."}r.classList.add("is-loading","tutor-btn-loading");r.setAttribute("disabled","disabled");var u=yield s(a);var i=yield u.json();if(i.status_code===200){window.location.href=i.data}else{tutor_toast(__("Failed","tutor-pro"),i.message||__("Bundle creation failed","tutor-pro"),"error")}}catch(r){tutor_toast(__("Failed","tutor-pro"),t,"error")}finally{r.classList.remove("is-loading","tutor-btn-loading");r.removeAttribute("disabled")}})()})})}})()})})();