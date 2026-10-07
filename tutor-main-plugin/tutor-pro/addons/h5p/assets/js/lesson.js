(()=>{var t={};// The module cache
var e={};// The require function
function n(a){// Check if module is in cache
var o=e[a];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var r=e[a]={exports:{}};// Execute the module function
t[a](r,r.exports,n);// Return the exports of the module
return r.exports}// webpack/runtime/rspack_version
(()=>{n.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{n.ruid="bundler=rspack@1.6.5"})();var a=document.querySelector("#complete_lesson_enabled")?document.querySelector("#complete_lesson_enabled").value:0;var o=document.querySelector('button[name="complete_lesson_btn"]');// this will hold h5p content ids that will be checked if finished
// for enabling and disabling mark as complete button 
var r=[];document.querySelectorAll("[data-content-id]").forEach(t=>{r.push(t.dataset.contentId)});var i=document.querySelector(".tutor-spotlight-h5p-lesson-content");var s=parseInt(_tutorobject.is_legacy_learning_mode,10);window.jQuery(document).ready(t=>{var{__:e}=wp.i18n;if(a){t(o).attr("disabled",true)}if(H5P||H5P.externalDispatcher){var n=t(".tutor-spotlight-h5p-lesson-content");var i=parseInt(n.data("course-id"),10);var s=parseInt(n.data("topic-id"),10);var c=parseInt(n.data("lesson-id"),10);var d="h5p-local-content-id";// Save xAPI statements and quiz result to database
var u=function e(e){var n=e.data.statement.object.definition.extensions["http://h5p.org/x-api/"+d];t.ajax({url:_tutorobject.ajaxurl,type:"POST",data:{_tutor_nonce:_tutorobject._tutor_nonce,action:"save_h5p_lesson_xAPI_statement",course_id:i,content_id:n,topic_id:s,lesson_id:c,statement:JSON.stringify(e.data.statement)}})};H5P.externalDispatcher.on("xAPI",u);// this is for contents not in iframe
H5P.externalDispatcher.on("initialized",function(){if(H5P.instances.length>0){H5P.instances.forEach(t=>{// this is how xapi statement is captured on h5p.js of the plugin
H5P.on(t,"xAPI",function(t){// this is similar to .xAPICompleted event method of h5p plugin
if((t.getVerb()==="completed"||t.getVerb()==="answered")&&!t.getVerifiedStatementValue(["context","contextActivities","parent"])){r=r.filter(t=>parseInt(t)!==parseInt(this.contentId));if(r.length===0){o.attributes.removeNamedItem("disabled");jQuery.ajax({url:_tutorobject.ajaxurl,type:"POST",data:{_tutor_nonce:_tutorobject._tutor_nonce,action:"set_h5p_lesson_finished",_tutor_nonce:_tutorobject._tutor_nonce,lesson_id:c}})}}})})}})}});// for handling contents in iframe
// a message from the h5p content iframe is send with content id and the action
// the ajax request action that is intercepted and captured 
window.addEventListener("message",function(t){if(t.data&&t.data.action==="h5p_setFinished"){r=r.filter(e=>e!==t.data.contentId);var e=jQuery(i).data("lesson-id");if(r.length===0){o.attributes.removeNamedItem("disabled");jQuery.ajax({url:_tutorobject.ajaxurl,type:"POST",data:{action:"set_h5p_lesson_finished",_tutor_nonce:_tutorobject._tutor_nonce,lesson_id:e}})}}})})();