(()=>{"use strict";var t={};// The module cache
var r={};// The require function
function e(o){// Check if module is in cache
var n=r[o];if(n!==undefined){return n.exports}// Create a new module (and put it into the cache)
var a=r[o]={exports:{}};// Execute the module function
t[o](a,a.exports,e);// Return the exports of the module
return a.exports}// webpack/runtime/rspack_version
(()=>{e.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{e.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function o(t,r,e,o,n,a,i){try{var u=t[a](i);var c=u.value}catch(t){e(t);return}if(u.done)r(c);else Promise.resolve(c).then(o,n)}function n(t){return function(){var r=this,e=arguments;return new Promise(function(n,a){var i=t.apply(r,e);function u(t){o(i,n,a,u,c,"next",t)}function c(t){o(i,n,a,u,c,"throw",t)}u(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var a=function(t){var r=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var e=t.getTimezoneOffset();var o=addMinutes(t,e);return format(o,r)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var i=t=>{var r=new Date(t);var e=r.getTimezoneOffset();return addMinutes(r,-e)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var u=t=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);/**
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
 */function c(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var r=new FormData;Object.keys(t).forEach(e=>r.set(e,t[e]));r.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return r}/**
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
 */function l(t){return n(function*(){try{var r=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return r}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var s=(t,r)=>{var{__:e}=wp.i18n;var{data:o={}}=t||{};var{message:n=r||e("Something Went Wrong!","tutor-pro")}=o;return n};// CONCATENATED MODULE: ./addons/social-login/assets/src/js/scripts.js
/**
 * Social Login Scripts
 * 
 * @since 2.1.9
 */window.addEventListener("DOMContentLoaded",function(){var{__}=wp.i18n;var{is_enabled_google_login:t,google_client_id:r}=tutorProSocialLogin;var e=__("Something went wrong, please try again","tutor-pro");// Google authentication
var o=document.getElementById("tutor-pro-social-authentication");var a=document.getElementById("tutor-pro-google-authentication");var i=document.querySelector("form input[name=tutor_action]");var u=document.querySelector("form input[name=redirect_to]");var s="tutor_user_login";if(i){s=i.value}if(o){if(t){// Initialize google auth
google.accounts.id.initialize({client_id:r,callback:d});// Render button
google.accounts.id.renderButton(a,{theme:"outline",size:"large",width:"100%"});/**
             * Handle response after user consent
             */function d(t){return n(function*(){try{if(!t||!t.credential){tutor_toast(__("Authentication failed","tutor-pro"),e,"error");return}var r=_(t.credential);if(!r||!r.email){tutor_toast(__("Authentication failed","tutor-pro"),e,"error");return}var o=r.name||(r.email?r.email.split("@")[0]:"");// Prepare form data
var n={action:"tutor_pro_social_authentication",auth:"google",token:t.credential,first_name:r.given_name||"",last_name:r.family_name||"",user_login:o.replace(/\s+/g,"_"),email:r.email,auth_user_id:r.sub,auth_id_token:r.aud,profile_url:r.picture||"",attempt:s};var a=c(n);tutor_toast(__("Authentication Processed","tutor-pro"),__("Please wait...","tutor-pro"),"success");var i=yield l(a);var d=yield i.json();var{success:v,data:f}=d;if(v){tutor_toast(__("Authentication success","tutor-pro"),f,"success");if(u){window.location.href=u.value}else{window.location.href=_tutorobject.tutor_frontend_dashboard_url}}else{if(Array.isArray(f)){var m=f[0];if(m&&m.code==="tutor_login_limit"){var p=document.querySelector(".tutor-login-form-wrapper");p.insertAdjacentHTML("afterbegin",'<div class="tutor-alert tutor-warning tutor-mb-12" style="display:block;">'.concat(m.message,"</div>"));return}}tutor_toast(__("Authentication failed","tutor-pro"),f,"error")}}catch(t){tutor_toast(__("Authentication failed","tutor-pro"),e,"error")}})()}}/**
         * Decode JWT response
         *
         * @param {*} token encoded token
         * @returns  object|null
         */function _(t){try{var r=t.split(".")[1];var e=r.replace(/-/g,"+").replace(/_/g,"/");var o=decodeURIComponent(atob(e).split("").map(function(t){return"%"+("00"+t.charCodeAt(0).toString(16)).slice(-2)}).join(""));return JSON.parse(o)}catch(t){return null}};}})})();