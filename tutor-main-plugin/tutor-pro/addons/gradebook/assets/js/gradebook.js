(()=>{"use strict";var t={};// The module cache
var e={};// The require function
function r(o){// Check if module is in cache
var a=e[o];if(a!==undefined){return a.exports}// Create a new module (and put it into the cache)
var n=e[o]={exports:{}};// Execute the module function
t[o](n,n.exports,r);// Return the exports of the module
return n.exports}// webpack/runtime/rspack_version
(()=>{r.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{r.ruid="bundler=rspack@1.6.5"})();// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function o(t,e,r,o,a,n,u){try{var d=t[n](u);var i=d.value}catch(t){r(t);return}if(d.done)e(i);else Promise.resolve(i).then(o,a)}function a(t){return function(){var e=this,r=arguments;return new Promise(function(a,n){var u=t.apply(e,r);function d(t){o(u,a,n,d,i,"next",t)}function i(t){o(u,a,n,d,i,"throw",t)}d(undefined)})}};// CONCATENATED MODULE: ./addons/gradebook/assets/src/js/gradebook.js
jQuery(document).ready(function(t){"use strict";var{__,_x:e,_n:r,_nx:o}=wp.i18n;// Backend Filter Output
t(document).on("click",".tutor-gradebook-filter",function(e){var r="",o=window.location.href,a=t(".tutor-gradebook-filter-select option").filter(":selected").val();if(o.includes("courseid")){var n=new URL(o);n.searchParams.set("courseid",a);r=n.toString()}else{r=o+"&courseid="+a}window.location.href=r});/**
	 * Add new grade
	 *
	 * @since v2.0.0
	 */t(document).on("submit","#tutor-add-new-gradebook-form",function(e){return a(function*(){e.preventDefault();var r=t(this);var o=new FormData(document.getElementById("tutor-add-new-gradebook-form"));var a=t("#tutor-add-new-grad-form-response");var n=yield u(o,e.target);var d=yield n.json();if(!d.success){a.html("");for(var i of Object.values(d.data)){a.append("<div><li class='tutor-alert tutor-alert-warning'>".concat(i,"</li></div>"))}}else{r.trigger("reset");tutor_toast(__("Success","tutor-pro"),__("New Grade Added","tutor-pro"),"success");location.reload()}}).call(this)});//edit grade
var n="#tutor-update-gradebook-form";t(document).on("click",".tutor-open-grade-update-modal",function(e){var r=e.target;t("".concat(n," input[name=gradebook_id]")).val(r.dataset.id);t("".concat(n," input[name=grade_name]")).val(r.dataset.name);t("".concat(n," input[name=grade_point]")).val(r.dataset.point);t("".concat(n," input[name=percent_to]")).val(r.dataset.maximum);t("".concat(n," input[name=percent_from]")).val(r.dataset.minimum);document.getElementById("tutor-update-grade-color").value=r.dataset.color;t("".concat(n," .button.wp-color-result")).css("background-color",r.dataset.color)});//update grade
t(n).on("submit",function(e){return a(function*(){e.preventDefault();var r=new FormData(document.getElementById("tutor-update-gradebook-form"));var o=t("#tutor-update-gradebook-form .grade-percent-to").val();var a=t("#tutor-update-gradebook-form .grade-percent-from").val();o=Number(o);a=Number(a);if(a>o||o<a){tutor_toast(__("Operation failed","tutor-pro"),__("Minimum Percentile must be lower then the Maximum Percentile","tutor-pro"),"error");return}var n=document.getElementById("tutor-update-grade-form-response");var d=yield u(r,e.target);var i=yield d.json();if(!i.success){if(i.data){for(var c of Object.values(i.data)){n.innerHTML+="<div><li class='tutor-alert tutor-alert-warning'>".concat(c,"</li></div>")}}}else{tutor_toast(__("Success","tutor-pro"),__("Grade updated","tutor-pro"),"success");location.reload()}})()});/**
	 * Handle ajax request show toast message on success | failure
	 *
	 * @param {*} formData including action and all form fields
	 */function u(t,e){return a(function*(){try{var r=e.querySelector("[data-tutor-modal-submit]");r.classList.add("is-loading");var o=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});r.classList.remove("is-loading");return o}catch(t){submitButton.classList.remove("is-loading");tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}/**
	 * Delete grade
	 */t("[data-delete_url]").click(function(e){var r=t(this).data("delete_url");t(this).prop("disabled",true).addClass("is-loading");window.location.href=r})})})();