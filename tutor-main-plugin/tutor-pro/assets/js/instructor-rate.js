(()=>{var r={};// The module cache
var t={};// The require function
function e(n){// Check if module is in cache
var o=t[n];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var u=t[n]={exports:{}};// Execute the module function
r[n](u,u.exports,e);// Return the exports of the module
return u.exports}// webpack/runtime/rspack_version
(()=>{e.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{e.ruid="bundler=rspack@1.6.5"})();jQuery(document).ready(function(r){r("#tutor_pro_instructor_amount_type_field").change(function(){var t=r(this).val();var e=t=="default"?"hide":"show";r("#tutor_pro_instructor_amount_field")[e]()}).trigger("change")})})();