(()=>{var e={};// The module cache
var _={};// The require function
function t(c){// Check if module is in cache
var r=_[c];if(r!==undefined){return r.exports}// Create a new module (and put it into the cache)
var a=_[c]={exports:{}};// Execute the module function
e[c](a,a.exports,t);// Return the exports of the module
return a.exports}// webpack/runtime/rspack_version
(()=>{t.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{t.ruid="bundler=rspack@1.6.5"})();jQuery(document).ready(function(e){var _=e('select[name="tutor_option[spam_protection_method]"]');var t=e('#field_enable_spam_protection input[type="checkbox"]');setTimeout(()=>_.trigger("change"));t.change(function(){_.trigger("change")});function c(_){if(_==="recaptcha_v3"){e("#field_recaptcha_v3_secret_key").show(0);e("#field_recaptcha_v3_site_key").show(0);e("#field_recaptcha_v2_secret_key").hide(0);e("#field_recaptcha_v2_site_key").hide(0)}else if(_==="recaptcha_v2"){e("#field_recaptcha_v3_secret_key").hide(0);e("#field_recaptcha_v3_site_key").hide(0);e("#field_recaptcha_v2_secret_key").show(0);e("#field_recaptcha_v2_site_key").show(0)}else{e("#field_recaptcha_v3_secret_key").hide(0);e("#field_recaptcha_v3_site_key").hide(0);e("#field_recaptcha_v2_secret_key").hide(0);e("#field_recaptcha_v2_site_key").hide(0)}}c(_.val());_.change(function(){var _=e(this).val();c(_)})})})();