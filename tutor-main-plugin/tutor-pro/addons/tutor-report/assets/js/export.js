(()=>{var n={};// The module cache
var e={};// The require function
function t(a){// Check if module is in cache
var o=e[a];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var r=e[a]={exports:{}};// Execute the module function
n[a](r,r.exports,t);// Return the exports of the module
return r.exports}// webpack/runtime/rspack_version
(()=>{t.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{t.ruid="bundler=rspack@1.6.5"})();jQuery(document).ready(function(n){n(document).on("click","#download_analytics",function(t){t.preventDefault();var a=n(this);if(a.hasClass("tutor-btn-loading")){return}n.ajax({url:window._tutorobject.ajaxurl,type:"POST",data:{action:"export_analytics",_tutor_nonce:window._tutorobject._tutor_nonce},beforeSend:function n(){a.addClass("tutor-btn-loading")},success:function n(n){if(n.success){e(n.data)}},complete:function n(){a.removeClass("tutor-btn-loading")}})});function e(n){var e=n.students;var t=n.earnings;var a=n.discounts.length;var o=n.refunds;var r=new JSZip;// get keys as array
if(e.length){var i=Object.keys(e[0]);var c=[i.join(","),e.map(n=>i.map(e=>n[e]).join(",")).join("\n")].join("\n");//generate csv
var s=new Blob([c]);r.file("students.csv",s)}if(t.length){var l=Object.keys(t[0]);var u=[l.join(","),t.map(n=>l.map(e=>n[e]).join(",")).join("\n")].join("\n");//generate csv
var d=new Blob([u]);//add file
r.file("earnings.csv",d)}if(a.length){var v=Object.keys(a[0]);var j=[v.join(","),a.map(n=>v.map(e=>n[e]).join(",")).join("\n")].join("\n");//generate csv
var f=new Blob([j]);//add file 
r.file("discounts.csv",f)}if(o.length){var p=Object.keys(o[0]);var b=[p.join(","),o.map(n=>p.map(e=>n[e]).join(",")).join("\n")].join("\n");//generate csv
var m=new Blob([b]);//add file
r.file("refunds.csv",m)}//generate zip archive
try{r.generateAsync({type:"blob"}).then(function(n){var e=new Blob([n],{type:"application/zip"});var t=document.createElement("a");document.body.appendChild(t);t.download="analytics-data.zip";t.href=URL.createObjectURL(e);t.click();URL.revokeObjectURL(t.href)})}catch(n){alert(n)}}})})();