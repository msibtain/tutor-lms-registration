(()=>{var t={};// The module cache
var e={};// The require function
function o(r){// Check if module is in cache
var i=e[r];if(i!==undefined){return i.exports}// Create a new module (and put it into the cache)
var n=e[r]={exports:{}};// Execute the module function
t[r](n,n.exports,o);// Return the exports of the module
return n.exports}// webpack/runtime/rspack_version
(()=>{o.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{o.ruid="bundler=rspack@1.6.5"})();window.tutor_ajax_counter=0;window.jQuery(document).ajaxStart(function(){window.tutor_ajax_counter++});window.jQuery(document).ajaxStop(function(){window.tutor_ajax_counter--});window.jQuery(document).ready(function(t){var e=function o(){setTimeout(function(){if(window.tutor_ajax_counter>0){e();return}var o=t("#tutor-course-topics");if(!o.find('[name="tutor_wpml_copy_source"]').length){o.append('<input type="hidden" name="tutor_wpml_copy_source" value="1"/>')}if(t("#save-post").length){t("#save-post").trigger("click")}else if(t("#publish").length){t("#publish").trigger("click")}else if(t(".editor-post-save-draft").length){t(".editor-post-save-draft").trigger("click");wp.data.subscribe(function(){var t=function e(){setTimeout(function(){var e=wp.data.select("core/editor").isSavingPost();if(e){t();return}window.location.reload()},1e3)};var e=wp.data.select("core/editor").isSavingPost();var o=wp.data.select("core/editor").isAutosavingPost();if(e&&!o){t()}})}},1e3)};var o=t("#icl_div #icl_cfo").get(0);if(o){o.addEventListener("click",function(){e()})}})})();