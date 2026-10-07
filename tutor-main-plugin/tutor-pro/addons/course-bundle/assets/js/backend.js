(()=>{var t={};// The module cache
var e={};// The require function
function r(a){// Check if module is in cache
var o=e[a];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var d=e[a]={exports:{}};// Execute the module function
t[a](d,d.exports,r);// Return the exports of the module
return d.exports}// webpack/runtime/rspack_version
(()=>{r.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{r.ruid="bundler=rspack@1.6.5"})();jQuery("document").ready(function(t){/**
     * Handle course-bundle move to trash event
     * @since 2.2.4
     */wp.data.subscribe(function(){if(wp.data&&wp.data.select("core/editor")){var t=wp.data.select("core/editor").getEditedPostAttribute("type");var{course_bundle_list_page_url:e,course_bundle_post_type:r}=tutorProCourseBundle;if(t===r){var a=wp.data.select("core/editor").getEditedPostAttribute("status");if(a==="trash"){// Redirect to course bundle list page
window.location.href=e}}}})})})();