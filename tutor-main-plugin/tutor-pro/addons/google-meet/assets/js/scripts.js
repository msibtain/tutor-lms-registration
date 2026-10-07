(()=>{"use strict";var e={};// The module cache
var t={};// The require function
function r(o){// Check if module is in cache
var a=t[o];if(a!==undefined){return a.exports}// Create a new module (and put it into the cache)
var n=t[o]={exports:{}};// Execute the module function
e[o](n,n.exports,r);// Return the exports of the module
return n.exports}// webpack/runtime/rspack_version
(()=>{r.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{r.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function o(e,t,r,o,a,n,s){try{var i=e[n](s);var u=i.value}catch(e){r(e);return}if(i.done)t(u);else Promise.resolve(u).then(o,a)}function a(e){return function(){var t=this,r=arguments;return new Promise(function(a,n){var s=e.apply(t,r);function i(e){o(s,a,n,i,u,"next",e)}function u(e){o(s,a,n,i,u,"throw",e)}i(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var n=function(e){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var r=e.getTimezoneOffset();var o=addMinutes(e,r);return format(o,t)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var s=e=>{var t=new Date(e);var r=t.getTimezoneOffset();return addMinutes(t,-r)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var i=e=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);/**
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
 */function u(){var e=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var t=new FormData;Object.keys(e).forEach(r=>t.set(r,e[r]));t.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return t}/**
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
 */function l(e){return a(function*(){try{var t=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:e});return t}catch(e){tutor_toast(__("Operation failed","tutor-pro"),e,"error")}})()}var c=(e,t)=>{var{__:r}=wp.i18n;var{data:o={}}=e||{};var{message:a=t||r("Something Went Wrong!","tutor-pro")}=o;return a};// CONCATENATED MODULE: ./addons/google-meet/assets/src/js/scripts.js
/**
 * Google meet scripts for create, update & delete meetings
 * 
 * @since v2.1.0
 */window.jQuery(document).ready(function(e){var{__,sprintf:t}=wp.i18n;// Listen click event on meta-box
var r=document.getElementById("tutor-google-meet-meta-box-wrapper");var o=document.getElementById("tutor-common-confirmation-modal");var n=__("Something went wrong, please refresh the page & try again!","tutor-pro");var s=document.getElementById("tutor-course-content-builder-root");var i="tutor-gm-create-new-meeting";var u="tutor-gm-update-meeting";/**
     * Listen edit/delete event & act accordingly
     */if(r){r.onclick=e=>{var t=e.target;// Handle delete event.
if(t.classList.contains("tutor-google-meet-list-delete")){e.preventDefault();c(t)}/**
             * On the frontend meeting inside tutor-google-meet-list-delete class
             * there is icon and span tag. So if clicked on icon or span 
             * set target to delete.
             */if(t.classList.contains("tutor-gm-delete")){e.preventDefault();t=t.closest("a.tutor-google-meet-list-delete");c(t)}// Handle create
if(t.classList.contains(i)||t.classList.contains(u)){e.preventDefault();try{v(t)}catch(e){tutor_toast(__("Failed","tutor-pro"),n,"warning")}}if(t.hasAttribute("type")&&t.getAttribute("type")==="checkbox"){if(t.hasAttribute("checked")){t.removeAttribute("checked")}else{t.setAttribute("checked","checked")}}return};// Handle attendees checkbox
e(document.body).on("change",'#tutor-google-meet-meta-box-wrapper input[type="checkbox"]',function(){if(e(this).is(":checked")){e(this).closest(".tutor-modal").find("input[name=attendees]").val("Yes")}else{e(this).closest(".tutor-modal").find("input[name=attendees]").val("No")}})}// Prepare topics event to delete.
if(s){s.onclick=e=>{var t=e.target;// Handle delete event.
if(t.classList.contains("tutor-google-meet-list-delete")){e.preventDefault();c(t)}// Handle create/update.
if(t.classList.contains(i)||t.classList.contains(u)){e.preventDefault();try{v(t)}catch(e){tutor_toast(__("Failed","tutor-pro"),n,"warning")}}return};// Handle attendees checkbox
e(document.body).on("change",'#tutor-course-content-builder-root input[type="checkbox"]',function(){if(e(this).is(":checked")){e(this).closest(".tutor-modal").find("input[name=attendees]").val("Yes")}else{e(this).closest(".tutor-modal").find("input[name=attendees]").val("No")}})}function c(e){var t=e.dataset.eventId;var r=e.dataset.meetingPostId;var a=e.dataset.itemReference;o.querySelector("[name=id]").value=r;o.querySelector("[name=event-id]").value=t;o.querySelector("[name=item-reference]").value=a}/**
     * Handle meeting delete event
     */if(o){var d=o.querySelector("button[data-tutor-modal-submit]");d.onclick=t=>a(function*(){t.preventDefault();var r=t.target;var a=new FormData;var n=o.querySelector("[name=item-reference]").value;a.set("event-id",o.querySelector("[name=event-id]").value);a.set("post-id",o.querySelector("[name=id]").value);a.set("action","tutor_google_meet_delete");a.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);r.setAttribute("disabled",true);r.classList.add("is-loading");var s=yield l(a);if(s.ok){var i=yield s.json();if(i.status_code===200||i.status_code===201){tutor_toast(__("Success","tutor-pro"),i.message,"success");if(_tutorobject.current_page==="google-meet"){window.location.reload();return}}else{tutor_toast(__("Failed","tutor-pro"),i.message,"warning")}r.classList.remove("is-loading");r.removeAttribute("disabled");o.classList.remove("tutor-is-active");e("body").removeClass("tutor-modal-open");window.dispatchEvent(new Event(_tutorobject.content_change_event))}else{tutor_toast(__("Error","tutor-pro"),__("Something went wrong, please try after refreshing page","tutor-pro"),"error");r.classList.remove("is-loading");r.removeAttribute("disabled")}})()}/**
     * Manage AJAX request for meeting create or update
     * 
     * @param {*} wrapper selector, where form fields exists, all
     * form fields having name will be  selected.
     * 
     * @param {*} additionalFields  additional fields, array of objects. If need to set
     * additional fields in form data. 
     * @returns void
     */function v(r){return a(function*(r){var o=arguments.length>1&&arguments[1]!==void 0?arguments[1]:[];var a=r.closest(".tutor-modal");var n=a.getAttribute("id");var s=a.querySelectorAll("[name]");var i=new FormData;var u=[];s.forEach(e=>{// Exclude attendees from validation.
if(e.value===""&&e.name!=="attendees"){/* Translators: %s template name. */u.push(t(__("%s is required","tutor-pro"),e.name))}// Fallback if attendees value not set.
if(e.name==="attendees"){i.set(e.name,e.value===""?"Yes":e.value)}else{i.set(e.name,e.value)}});i.set("action","tutor_google_meet_new_meeting");// Set additional fields.
o.forEach(e=>{i.set(e.name,e.value)});r.classList.add("is-loading");r.setAttribute("disabled",true);// Validate request before post.
if(u.length){u.forEach(e=>{tutor_toast(__("Validation Error","tutor-pro"),m(e.replace(/_/g," ")),"warning")});r.removeAttribute("disabled");r.classList.remove("is-loading");return}var c=yield l(i);if(c.ok){var d=yield c.json();if(d.status_code===200||d.status_code===201){// If it is create form then reset after successful submit.
if(n==="tutor-google-meet-create-modal"||a.classList.contains("tutor-gm-topic-create-modal")){// Show success message.
tutor_toast(__("Success","tutor-pro"),d.message,"success")}else{// Show success message.
tutor_toast(__("Success","tutor-pro"),d.message,"success");// Reload if it is listing page.
if(_tutorobject.current_page==="google-meet"){window.location.reload();return}}a.classList.remove("tutor-is-active");// Close the meeting editor modal
e("body").removeClass("tutor-modal-open");window.dispatchEvent(new Event(_tutorobject.content_change_event))}else{tutor_toast(__("Failed","tutor-pro"),d.message,"warning")}}else{tutor_toast(__("Failed","tutor-pro"),__("Something went wrong, please try again!","tutor-pro"),"warning")}r.classList.remove("is-loading");r.removeAttribute("disabled")}).apply(this,arguments)}/**
     * Upper case the fist letter from string
     * 
     * @param string text 
     * @returns string
     */function m(e){var t=e.substr(0,1);return t.toUpperCase()+e.substr(1)}function g(t,r){var o=t.getBoundingClientRect();// use 'setTimeout' to prevent effect overridden by other scripts
setTimeout(function(){var a=e("body").scrollTop();r.dpDiv.css({top:o.top+t.offsetHeight+a})},0)}// Init timepicker
function f(){e(".tutor-google-meet-timepicker").timepicker({timeFormat:"hh:mm TT",beforeShow:function e(e,t){g(e,t)}})}function _(){// Reassign date and timepicker.
e(".tutor-google-meet-timepicker").timepicker({timeFormat:"hh:mm TT",beforeShow:function e(e,t){g(e,t)}})}f();window.addEventListener(_tutorobject.content_change_event,f);/**
     * Drag and Drop files -> Upload JSON (SET API)
     */var p=document.querySelectorAll(".tutor-google-meet-credential-form .drag-drop-zone input[type=file]");if(p.length>0){p.forEach(e=>{var t=e.closest(".drag-drop-zone");["dragover","dragleave","dragend"].forEach(e=>{if(e==="dragover"){t.addEventListener(e,e=>{e.preventDefault();t.classList.add("dragover")})}else{t.addEventListener(e,e=>{t.classList.remove("dragover")})}});t.addEventListener("drop",r=>{r.preventDefault();var o=r.dataTransfer.files;h(o,e,t);t.classList.remove("dragover")});e.addEventListener("change",r=>{var o=r.target.files;h(o,e,t)})})}// Show file info
var h=(e,t,r)=>{if(e.length){t.files=e;r.classList.add("file-attached");r.querySelector(".file-info").innerHTML="<strong>File attached</strong> - ".concat(e[0].name)}else{r.classList.remove("file-attached");r.querySelector(".file-info").innerHTML=""}};// Handle credential upload.
var y=document.getElementById("tutor-google-meet-credential-upload");var w=document.getElementById("tutor-google-meet-choose-label");var b=document.querySelector(".tutor-google-meet-credential-form .drag-drop-zone");if(w){w.onclick=e=>{y.click()}}if(y){y.onchange=e=>{var t=e.target.files[0];L(t)}}if(b){b.addEventListener("drop",e=>{e.preventDefault();var t=e.dataTransfer.files[0];L(t)})}function L(e){return a(function*(){var t=new FormData;var r=document.querySelector(".file-info");t.set("file",e);t.set("action","tutor_pro_google_meet_credential_upload");t.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);try{if(e.type==="application/json"){w.classList.add("is-loading");var o=yield l(t);if(o.ok){var a=yield o.json();if(a.success){tutor_toast(__("Success","tutor-pro"),a.message,"success");window.location.reload()}else{tutor_toast(__("Error","tutor-pro"),a.message,"warning")}w.classList.remove("is-loading")}}else{w.classList.remove("is-loading");tutor_toast(__("Error","tutor-pro"),__("Invalid file type!","tutor-pro"),"warning");y.value="";if(r){r.innerHTML=""}}}catch(e){tutor_toast(__("Error","tutor-pro"),__("Something went wrong, please try again!","tutor-pro"),"warning")}})()}// Handle settings event.
var E=document.getElementById("tutor-google-meet-settings");if(E){E.onchange=e=>a(function*(){var t=false;var r=e.target;// If search then return.
    if(r.hasAttribute("type")&&r.getAttribute("type")==="search"){return}// Check if user selecting same timezone.
    if(r.hasAttribute("data-value")){t=true;var o=e.target.dataset.value;var a=r.value;if(o===a){return}}var s=new FormData(E);try{var i=yield l(s);if(i.ok){var u=yield i.json();if(u.success){tutor_toast(__("Success","tutor-pro"),u.message,"success");if(t){r.setAttribute("data-value",r.value)}}else{tutor_toast(__("Failed","tutor-pro"),u.message,"error")}}else{tutor_toast(__("Error","tutor-pro"),__(i.statusText),"error")}}catch(e){tutor_toast(__("Error","tutor-pro"),n,"error")}})()}var S=document.getElementById("tutor-meet-confirmation-form");if(S){S.onsubmit=e=>a(function*(){e.preventDefault();var t=new FormData(S);var r=S.querySelector("[data-tutor-modal-submit]");r.classList.add("is-loading");r.setAttribute("disabled",true);try{var o=yield l(t);var a=yield o.json();var{success:n,message:s}=a;if(n){tutor_toast(__("Success","tutor-pro"),s,"success");location.reload()}else{tutor_toast(__("Failed","tutor-pro"),s,"error")}}catch(e){tutor_toast(__("Failed","tutor-pro"),__("Something went wrong, please try again","tutor-pro"),"error")}finally{r.classList.remove("is-loading");r.removeAttribute("disabled")}})()}})})();