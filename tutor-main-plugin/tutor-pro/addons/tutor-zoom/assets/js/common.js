(()=>{var t={};// The module cache
var e={};// The require function
function o(r){// Check if module is in cache
var a=e[r];if(a!==undefined){return a.exports}// Create a new module (and put it into the cache)
var n=e[r]={exports:{}};// Execute the module function
t[r](n,n.exports,o);// Return the exports of the module
return n.exports}// webpack/runtime/rspack_version
(()=>{o.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{o.ruid="bundler=rspack@1.6.5"})();window.jQuery(document).ready(function(t){var{__}=wp.i18n;function e(e,o){var r=e.getBoundingClientRect();// use 'setTimeout' to prevent effect overridden by other scripts
setTimeout(function(){var a=t("body").scrollTop();o.dpDiv.css({top:r.top+e.offsetHeight+a})},0)}// init datepicker for search meetings
function o(){t(".tutor_zoom_datepicker").datepicker({dateFormat:_tutorobject.wp_date_format});t(".tutor_zoom_timepicker").timepicker({timeFormat:"hh:mm TT",beforeShow:function t(t,o){e(t,o)}})}o();window.addEventListener(_tutorobject.content_change_event,o);t(document).on("click",".update_zoom_meeting_modal_btn",function(o){o.preventDefault();var r=t(this);var a=r.closest(".tutor-modal");// Add name property to the fields.
a.find("[data-name]").each(function(){t(this).attr("name",t(this).attr("data-name"))});// Get the zoom editor modal data
var n=a.find(":input").serializeObject();n.timezone=Intl.DateTimeFormat().resolvedOptions().timeZone;// Remove name attribute to avaoid normal submission from wordpress editor
a.find("[data-name]").removeAttr("name");for(var i in n){if(n[i]!=="timezone"&&!n[i]){tutor_toast(__("Warning!","tutor-pro"),__("Please fill all the fields","tutor-pro"),"warning");return}}t.ajax({url:window._tutorobject.ajaxurl,type:"POST",data:n,beforeSend:function t(){r.attr("disabled","disabled").addClass("is-loading")},success:function o(o){var{success:n,data:i={}}=o||{};var{selector:s,replace_selector:c,course_contents:d,editor_modal_html:u}=i;if(o.status_code!==200){tutor_toast(__("Error!","tutor-pro"),o.message,"error");return}tutor_toast(__("Success","tutor-pro"),o.message,"success");// Reload page if not course builder. Maybe backend or frontend dashboard.
if(!d){window.location.reload();return}// Apply sortabillity
if(s=="course-builder"){if(c){t(c).replaceWith(d)}else{r.closest(".tutor-topics-body").find(".tutor-lessons").append(d)}enable_sorting_topic_lesson()}else{t(o.data.selector).html(o.data.course_contents)}// Replace the modal that is used for new zoom lesson
if(u){a.replaceWith(u)}// Reassign date and timepicker
t(".tutor_zoom_timepicker").timepicker({timeFormat:"hh:mm TT",beforeShow:function t(t,o){e(t,o)}});t(".tutor_zoom_datepicker").datepicker({dateFormat:_tutorobject.wp_date_format,minDate:0});// Close the meeting editor modal
a.removeClass("tutor-is-active");t("body").removeClass("tutor-modal-open");window.dispatchEvent(new Event(_tutorobject.content_change_event))},complete:function t(){r.removeAttr("disabled").removeClass("is-loading")}})})})})();