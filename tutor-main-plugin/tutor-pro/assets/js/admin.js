(()=>{var t={34:function(){window.addEventListener("DOMContentLoaded",()=>{var r=document.getElementById("tutor-download-invoice");if(r){r.addEventListener("click",o=>{var n=r.dataset.orderId;r.classList.add("tutor-btn-loading");var a=document.getElementById("tutor-invoice-content");// Fix strikethrough elements before capture
var i=t(a);setTimeout(()=>{html2canvas(a,{scale:2,backgroundColor:"#ffffff",logging:false,windowWidth:a.scrollWidth,windowHeight:a.scrollHeight}).then(t=>{// Restore original strikethrough styles
e(i);var o=t.toDataURL("image/jpeg",1);var a=new jspdf.jsPDF({orientation:"p",unit:"mm",format:"a4"});var s=a.internal.pageSize.getWidth();var l=a.internal.pageSize.getHeight();var u=t.width;var d=t.height;var c=u/d;var v=10;var _=s-2*v;var f=l-2*v;var p=_;var m=p/c;if(m>f){m=f;p=m*c}a.addImage(o,"PNG",v,v,p,m);a.save("invoice-".concat(n,".pdf"));r.classList.remove("tutor-btn-loading")}).catch(t=>{e(i);r.classList.remove("tutor-btn-loading")})},0)})}});/**
 * Finds all elements with line-through and replaces the CSS decoration
 * with a visible absolutely-positioned <span> line that html2canvas can render.
 * Returns a list of elements to restore afterward.
 */function t(t){var e=[];t.querySelectorAll("*").forEach(t=>{var r=window.getComputedStyle(t);var o=r.textDecorationLine||r.textDecoration;if(o.includes("line-through")){// Wrap in a relative container if not already positioned
var n=window.getComputedStyle(t).position;if(n==="static"){t.style.position="relative";e.push({el:t,resetPosition:true})}else{e.push({el:t,resetPosition:false})}// Remove the CSS strikethrough
t.style.textDecoration="none";// Add a manual line overlay
var a=document.createElement("span");a.className="__strikethrough-overlay";a.style.cssText="\n                position: absolute;\n                left: 0;\n                right: 0;\n                top: 50%;\n                height: 1.5px;\n                background-color: ".concat(r.color,";\n                pointer-events: none;\n                transform: translateY(-50%);\n            ");t.appendChild(a)}});return e}/**
 * Restores original styles and removes injected overlay spans.
 */function e(t){t.forEach(t=>{var{el:e,resetPosition:r}=t;e.style.textDecoration="";if(r)e.style.position="";e.querySelectorAll(".__strikethrough-overlay").forEach(t=>t.remove())})}}};// The module cache
var e={};// The require function
function r(o){// Check if module is in cache
var n=e[o];if(n!==undefined){return n.exports}// Create a new module (and put it into the cache)
var a=e[o]={exports:{}};// Execute the module function
t[o](a,a.exports,r);// Return the exports of the module
return a.exports}// webpack/runtime/rspack_version
(()=>{r.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{r.ruid="bundler=rspack@1.6.5"})();// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(()=>{"use strict";// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function t(t,e,r,o,n,a,i){try{var s=t[a](i);var l=s.value}catch(t){r(t);return}if(s.done)e(l);else Promise.resolve(l).then(o,n)}function e(e){return function(){var r=this,o=arguments;return new Promise(function(n,a){var i=e.apply(r,o);function s(e){t(i,n,a,s,l,"next",e)}function l(e){t(i,n,a,s,l,"throw",e)}s(undefined)})}};// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var o=function(t){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var r=t.getTimezoneOffset();var o=addMinutes(t,r);return format(o,e)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var n=t=>{var e=new Date(t);var r=e.getTimezoneOffset();return addMinutes(e,-r)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var a=t=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);/**
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
 */function i(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var e=new FormData;Object.keys(t).forEach(r=>e.set(r,t[r]));e.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return e}/**
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
 */function s(t){return e(function*(){try{var e=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return e}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var l=(t,e)=>{var{__:r}=wp.i18n;var{data:o={}}=t||{};var{message:n=e||r("Something Went Wrong!","tutor-pro")}=o;return n};// EXTERNAL MODULE: ./assets/src/js/download-invoice.js
var u=r(34);// CONCATENATED MODULE: ./assets/src/js/license.js
window.addEventListener("DOMContentLoaded",function(){if(document.querySelector(".tutor-license-page-wrapper")){var t,r,o;var{__}=wp.i18n;// Hide and show license form
var n=document.getElementById("tutor-license-delete-button");var a=document.getElementById("tutor-license-edit-button");var l=document.getElementById("tutor-license-update-button");var u=document.getElementById("tutor-license-edit-cancel-button");var d=document.querySelector(".tutor-license-key-wrapper");var c=(t=document)===null||t===void 0?void 0:t.querySelector(".tutor-license-key-form-wrapper");var v=c===null||c===void 0?void 0:c.querySelector('input[type="text"]');var _=(r=document)===null||r===void 0?void 0:r.getElementById("tutor-common-confirmation-modal");var f=document.querySelectorAll("[data-tutor-modal-close]");var p=(o=document)===null||o===void 0?void 0:o.getElementById("tutor-license-remove-action");var m=document.getElementById("update-input-tutor-pro-license-key");a===null||a===void 0?void 0:a.addEventListener("click",function(t){t.preventDefault();d.classList.add("tutor-d-none");c.classList.remove("tutor-d-none");var e=m.value.length;m.setSelectionRange(e,e);m.focus()});p===null||p===void 0?void 0:p.addEventListener("click",t=>e(function*(){var e=t.currentTarget;var r=i({action:"delete_tutor_license"});try{e.classList.add("is-loading");e.setAttribute("disabled",true);var o=yield s(r);var n=yield o.json();var a=(n===null||n===void 0?void 0:n.response)?n.response:__("Something went wrong!!","tutor-pro");if(200===n.status_code){tutor_toast(a,"","Success");location.href=n===null||n===void 0?void 0:n.url}else{tutor_toast(a,__("Please try again!","tutor-pro"),"error")}}catch(t){tutor_toast(__("Something went wrong","tutor-pro"),__("Please try again!!!","tutor-pro"),"error")}finally{e.removeAttribute("disabled");e.classList.remove("is-loading")}})());l===null||l===void 0?void 0:l.addEventListener("click",t=>e(function*(){t.preventDefault();var e=t.currentTarget;var r=m.value;if(!r||!r.trim().length||r.indexOf("*")>-1){tutor_toast(__("Please enter valid license key","tutor-pro"),"","error");return}var o=i({action:"update_tutor_license",updated_license_key:r});try{e.classList.add("is-loading");e.setAttribute("disabled",true);var n=yield s(o);var a=yield n.json();var l=(a===null||a===void 0?void 0:a.response)?a.response:__("Something went wrong!!","tutor-pro");if(n.ok){if((a===null||a===void 0?void 0:a.status)==200){tutor_toast(l,"","success");location.href="".concat(_tutorobject.home_url,"/wp-admin/admin.php?page=tutor_settings&tab_page=license")}else{tutor_toast(l,"","error")}}else{tutor_toast(l,"","error")}}catch(t){tutor_toast(t,"","error")}finally{e.removeAttribute("disabled");e.classList.remove("is-loading")}})());u===null||u===void 0?void 0:u.addEventListener("click",function(){d.classList.remove("tutor-d-none");c.classList.add("tutor-d-none")});// Validate license form input and handle submit
var g=document.querySelector(".tutor-verify-license-wrapper");var h=document.getElementById("tutor-license-submit-btn");if(g&&h){h.addEventListener("click",function(t){return e(function*(){t.preventDefault();var e=g.querySelector('input[type="text"]');var r=e?e.value:"";if(!r||!r.trim().length||r.indexOf("*")>-1){tutor_toast(__("Failed","tutor-pro"),__("Please enter valid license key","tutor-pro"),"error");return}else{var o=i({action:"tutor_oauth_check",license_key:r});var n=Object.fromEntries(o.entries());try{h.innerText="Verifying...";h.classList.add("is-loading");h.setAttribute("disabled",true);var a=yield s(o);var l=yield a.json();var u=(l===null||l===void 0?void 0:l.response)?l.response:__("Something went wrong!!","tutor-pro");if(200===l.status){tutor_toast(u,"","success");window.location.assign(l.body_response)}else{tutor_toast(u,__("Please try again!!","tutor-pro"),"error")}}catch(t){tutor_toast(__("Something went wrong","tutor-pro"),__("Please try again!!","tutor-pro"),"error")}finally{h.removeAttribute("disabled");h.classList.remove("is-loading");h.innerText="Verify License"}}})()})}}// Handle license form input enter key press
var y=document.querySelector(".tutor-option-tab-pages #license");if(y){y.addEventListener("keydown",function(t){// Prevent default enter key behavior for all inputs and buttons
if(t.key==="Enter"&&(t.target.tagName==="INPUT"||t.target.tagName==="BUTTON")){t.preventDefault()}})}});// CONCATENATED MODULE: ./assets/src/js/admin.js
jQuery(document).ready(function(t){"use strict";var{__}=wp.i18n;t(document).on("click",".install-tutor-button",function(e){e.preventDefault();var r=t(this);t.ajax({type:"POST",url:ajaxurl,data:{install_plugin:"tutor",action:"install_tutor_plugin"},beforeSend:function t(){r.addClass("is-loading")},success:function e(e){t(".install-tutor-button").remove();t("#tutor_install_msg").html(e)},complete:function t(){r.removeClass("is-loading")}})});/**
     * Import Sample Grade Data
     *
     * @since v.1.4.2
     */t(document).on("click","#import-gradebook-sample-data",function(e){e.preventDefault();var r=t(this);t.ajax({type:"POST",url:ajaxurl,data:{action:"import_gradebook_sample_data"},beforeSend:function t(){r.addClass("is-loading")},success:function t(t){if(t.success){location.reload()}},complete:function t(){r.removeClass("is-loading")}})});/**
     * Hide cron frequency on wp cron disabling
     * @since v.1.8.7
     */t('[name="tutor_option[tutor_email_disable_wpcron]"]').change(function(){t('[name="tutor_option[tutor_email_cron_frequency]"]').closest(".tutor-option-field-row")[!t(this).prop("checked")?"show":"hide"]()}).trigger("change");/**
     * From Admin clear active session of a student
     *
     * @since 2.1.10
     */if(_tutorobject.current_page==="tutor-students"){var r=document.querySelectorAll(".tutor-clear-sessions");r.forEach(t=>{t.onclick=t=>e(function*(){var e=t.target;var r=t.target.dataset.studentId;var o=i({user_id:r,action:"tutor_clear_active_sessions"});try{e.classList.add("is-loading");e.setAttribute("disabled",true);var n=yield s(o);var a=yield n.json();var{success:l,data:u}=a;if(l){tutor_toast(__("Success","tutor-pro"),u,"success")}else{tutor_toast(__("Failed","tutor-pro"),u,"error");e.removeAttribute("disabled")}}catch(t){tutor_toast(__("Something went wrong","tutor-pro"),__("Please try again after reloading page!","tutor-pro"),"error");e.removeAttribute("disabled")}finally{e.classList.remove("is-loading")}})()})}/**
     * On course completion mode change toggle video lesson completion options.
     * 
     * @since 2.2.4
     */if(_tutorobject.current_page==="tutor_settings"){var o=t("#field_control_video_lesson_completion,#field_required_percentage_to_complete_video_lesson");var n=t("#field_course_completion_process input:checked").val();if(n==="flexible"){o.hide()}t("#field_course_completion_process input").change(function(){if(t(this).val()==="flexible"){o.hide()}else{o.show()}});t(".tutor-option-form .tutor-thumbnail-uploader").on("tutor_settings_media_selected",function(t){if(t.detail.settingsName==="email_template_bg"){var{width:e,height:r}=t.detail.attachment;var o=t.detail.wrapper;if(e!==602||r!==124){o.find("span.delete-btn").click();tutor_toast(__("Failed","tutor-pro"),__("The image dimensions need to be 602x124px","tutor-pro"),"error")}}});var a=t("#field_email_disable_banner input.tutor-form-toggle-input"),l=t("#field_email_template_bg");if(a){if(a.is(":checked")){l.removeClass("tutor-d-block").hide()}a.change(function(){if(a.is(":checked")){l.removeClass("tutor-d-block").hide()}else{l.addClass("tutor-d-block")}})}}if(_tutorobject.current_page==="tutor-instructors"){/**
         * Commission amount input hide/show handler
         *
         * @since 2.4.0
         */t("select.edit_commission_type").change(function(){var e=t(this).val();var r=e==="default"?"hide":"show";var o=t(this).parent().parent().next(".edit_commission_amount_field");o[r]();if(r==="show"){o.find('input[name="instructor_amount"]').eq(0).focus()}});/**
         * Instructor profile edit form submit handler
         * 
         * @since 2.3.1
         */t(document).on("submit","form.tutor-instructor-edit-modal",function(e){e.preventDefault();var r=t(this);var o=r.serializeObject();var n=r.find("[data-tutor-modal-submit]");var a=r.find("div.tutor-form-response").eq(0);o.action="tutor_update_instructor_data";t.ajax({url:window._tutorobject.ajaxurl,type:"POST",data:o,beforeSend:function t(){n.attr("disabled","disable").addClass("is-loading");a.html("")},success:function t(t){if(!t.success){var e;if(t===null||t===void 0?void 0:(e=t.data)===null||e===void 0?void 0:e.errors){for(var o of Object.values(t.data.errors)){a.append('\n                                    <div class=\'tutor-col\'>\n                                        <div class="tutor-alert tutor-warning">\n                                        <div class="tutor-alert-text">\n                                            <span class="tutor-alert-icon tutor-icon-circle-info tutor-mr-8"></span>\n                                            <span>'.concat(o,"</span>\n                                        </div>\n                                        </div>\n                                    </div>\n                                    "))}}}else{r.trigger("reset");tutor_toast(__("Success","tutor-pro"),__("Instructor data updated!","tutor-pro"),"success");location.reload()}},complete:function t(){n.removeAttr("disabled").removeClass("is-loading")}})})}// end tutor-instructors page
})})()})();