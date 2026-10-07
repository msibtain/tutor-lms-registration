(()=>{"use strict";var t={};// The module cache
var e={};// The require function
function r(o){// Check if module is in cache
var a=e[o];if(a!==undefined){return a.exports}// Create a new module (and put it into the cache)
var n=e[o]={exports:{}};// Execute the module function
t[o](n,n.exports,r);// Return the exports of the module
return n.exports}// webpack/runtime/rspack_version
(()=>{r.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{r.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function o(t,e,r,o,a,n,i){try{var s=t[n](i);var l=s.value}catch(t){r(t);return}if(s.done)e(l);else Promise.resolve(l).then(o,a)}function a(t){return function(){var e=this,r=arguments;return new Promise(function(a,n){var i=t.apply(e,r);function s(t){o(i,a,n,s,l,"next",t)}function l(t){o(i,a,n,s,l,"throw",t)}s(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var n=function(t){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var r=t.getTimezoneOffset();var o=addMinutes(t,r);return format(o,e)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var i=t=>{var e=new Date(t);var r=e.getTimezoneOffset();return addMinutes(e,-r)};/**
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
 */function l(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var e=new FormData;Object.keys(t).forEach(r=>e.set(r,t[r]));e.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return e}/**
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
 */function c(t){return a(function*(){try{var e=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return e}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var u=(t,e)=>{var{__:r}=wp.i18n;var{data:o={}}=t||{};var{message:a=e||r("Something Went Wrong!","tutor-pro")}=o;return a};// CONCATENATED MODULE: ./addons/tutor-report/assets/src/js/report.js
jQuery(document).ready(function(t){"use strict";var{__,_x:e,_n:r,_nx:o}=wp.i18n;var n=document.getElementById("tutor-common-confirmation-modal");var i=document.getElementById("tutor-common-confirmation-form");t(document).on("click",".tutor-quiz-attempt-delete-btn",function(e){e.preventDefault();var r=t(this);t.ajax({url:ajaxurl,type:"POST",data:{attempt_id:r.attr("data-attempt-id"),action:"treport_quiz_atttempt_delete"},beforeSend:function t(){r.addClass("updating-message")},success:function t(t){if(t.success){r.closest("tr").remove()}},complete:function t(){r.removeClass("updating-message")}})});/**
   * Datepicker initiate
   */function s(t,e){var r=new URL(window.location.href);var o=r.searchParams;o.set(t,e);r.search=o.toString();o.set("paged",1);r.search=o.toString();return r.toString()}t(".tutor-report-category").on("change",function(e){window.location=s("cat",t(this).val())});t(".tutor-report-sort").on("change",function(e){window.location=s("order",t(this).val())});t(".tutor-report-date").on("change",function(e){window.location=s("date",t(this).val())});t(document).on("click",".tutor-report-search-btn",function(e){window.location=s("search",t(".tutor-report-search").val())});t(document).on("click",".tutor-report-search-action",function(e){e.preventDefault();window.location=s("search",t(".tutor-report-search").val())});t(document).on("click",".details-link",function(e){e.preventDefault();if(t(this).hasClass("active")){t(this).removeClass("active")}else{t(this).addClass("active")}var r=t("#table-toggle-"+t(this).data("count"));if(r.hasClass("open")){r.removeClass("open")}else{r.addClass("open")}});/**
   * Delete recent reviews
   * 
   * @since v.2.0.0
   */var l=document.querySelectorAll(".tutor-delete-recent-reviews");var u=document.getElementById("tutor-admin-reviews-table");for(var d of l){if(d){d.onclick=t=>a(function*(){var e=t.currentTarget.dataset.id;if(i){i.elements.action.value="tutor_delete_review";i.elements.id.value=e}})()}}/**
   * Handle common confirmation form
   * review delete
   * 
   * @since v.2.0.0
   */if(i){i.onsubmit=t=>a(function*(){t.preventDefault();var e=new FormData(i);//show loading
    var r=i.querySelector("[data-tutor-modal-submit]");r.classList.add("is-loading");var o=yield c(e);//hide modal
    if(n.classList.contains("tutor-is-active")){n.classList.remove("tutor-is-active");document.body.classList.remove("tutor-modal-open")}if(o.ok){var a=yield o.json();if(a){if(u&&e.get("action")=="tutor_delete_review"){tutor_toast(__("Delete","tutor-pro"),__("Review has been deleted ","tutor-pro"),"success");// if there is less row then reload to avoid empty state related issue
    if(u.rows.length<3){location.reload()}else{// find row that need to remove
    var s=u.querySelector('a[data-id="'.concat(e.get("id"),'"]'));if(s){s.closest("tr").remove()}}}else{location.reload()}}else{tutor_toast(__("Failed","tutor-pro"),__("Review delete failed ","tutor-pro"),"error")}}else{tutor_toast(__("Failed","tutor-pro"),__("Review delete failed ","tutor-pro"),"error")}})()}// --------- Report Analytics ------------
/**
   * @since 4.0.0 Added to this file from analytics.js(tutor-report)
   */var v=document.querySelectorAll(".tutor-admin-report-frequency");for(var f of v){f.onclick=t=>{var e=t.target.dataset.key;if(e==="custom"){return}var r=new URL(window.location.href);var o=r.searchParams;// if(params.has('period') && params.get('period') === period) {
//     return;
// }
if(o.has("start_date")){o.delete("start_date")}if(o.has("end_date")){o.delete("end_date")}o.set("period",e);window.location=r}}/**
 * Prepare Line Charts for creating dynamically
 *
 * It will create four graph as mentioned on charts array of obj
 *
 * @since 1.9.9
 * @since 4.0.0 Added to this file from analytics.js(tutor-report)
 */for(var m of _tutor_analytics){var p;var h=(p=document.getElementById("".concat(m.id,"_canvas")))===null||p===void 0?void 0:p.getContext("2d");var _=[];var g=[];var w=[];for(var[y,b]of Object.entries(m.data)){var k={month:"short",day:"numeric"};var C=(b===null||b===void 0?void 0:b.date_format)?new Date(b.date_format).toLocaleDateString("en-US",k):b.label_name;_.push(C);g.push(b.total);if(b.fees){w.push(b.fees)}}var S=[];S.push({label:m.label,backgroundColor:"#3057D5",borderColor:"#3057D5",data:g,borderWidth:2,fill:false,lineTension:0});if(w.length){S.push({label:m.label2,backgroundColor:"rgba(200, 0, 0, 1)",borderColor:"rgba(200, 0, 0, 1)",data:w,borderWidth:2,fill:false,lineTension:0})}if(h){new Chart(h,{type:"line",data:{labels:_,datasets:S},options:{scales:{yAxes:[{ticks:{min:0,beginAtZero:true,callback:function t(t,e,r){if(Math.floor(t)===t){return t}}}}]},legend:{display:false}}})}}})})();