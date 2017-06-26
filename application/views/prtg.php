<#system type="denyfornonadmins">

<#lang key="html.config_report.header1a" default="Configuration Report: User Accounts and User Groups" var="title">
<#setpagetitle pagetitle="@title" varexpand="pagetitle">

<#include file="includes\reportheader_configreports.htm">

  <img src="/images/reportheader.png">
    <br/>

  <h1><?=$this->lang->line('configReport_headLine')?></h1>
  <#lang key="html.config_report.header2" default="User Accounts" var="objecttype">

  <div class="onereport report1">

<h1><?=$this->lang->line('tableHeadline_sensors');?></h1>
<?=$sensors; ?>
<h1><h1><?=$this->lang->line('tableHeadline_devices');?></h1></h1>
<?=$devices; ?>
<h1><?=$this->lang->line('tableHeadline_groups');?></h1>
<?=$groups; ?>
<header>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <style type='text/css'>
  .label-google {
    background-color:#f9f9f9;
    color:#000000 !important;
  }
  /*!
   * Bootstrap v3.3.7 (http://getbootstrap.com)
   * Copyright 2011-2017 Twitter, Inc.
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
   */

  /*!
   * Generated using the Bootstrap Customizer (http://getbootstrap.com/customize/?id=d3e9ff31b7c798301c9c2bba02772e8b)
   * Config saved to config.json and https://gist.github.com/d3e9ff31b7c798301c9c2bba02772e8b
   */
  /*!
   * Bootstrap v3.3.7 (http://getbootstrap.com)
   * Copyright 2011-2016 Twitter, Inc.
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
   */
  /*! normalize.css v3.0.3 | MIT License | github.com/necolas/normalize.css */
  hr,img,legend{border:0}
legend,td,th{padding:0}
body,figure{margin:0}
html{font-family:sans-serif;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}
article,aside,details,figcaption,figure,footer,header,hgroup,main,menu,nav,section,summary{display:block}
audio,canvas,progress,video{display:inline-block;vertical-align:baseline}
audio:not([controls]){display:none;height:0}
[hidden],template{display:none}
a{background-color:transparent;color:#337ab7;text-decoration:none}
a:active,a:hover{outline:0}
abbr[title]{border-bottom:1px dotted}
b,optgroup,strong{font-weight:700}
dfn{font-style:italic}
h1{font-size:2em;margin:.67em 0}
mark{background:#ff0;color:#000}
.img-thumbnail,body{background-color:#fff}
small{font-size:80%}
sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}
sup{top:-.5em}
sub{bottom:-.25em}
img{vertical-align:middle}
svg:not(:root){overflow:hidden}
hr{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;height:0}
pre,textarea{overflow:auto}
code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}
button,input,optgroup,select,textarea{color:inherit;font:inherit;margin:0}
button{overflow:visible}
button,select{text-transform:none}
button,html input[type=button],input[type=reset],input[type=submit]{-webkit-appearance:button;cursor:pointer}
button[disabled],html input[disabled]{cursor:default}
button::-moz-focus-inner,input::-moz-focus-inner{border:0;padding:0}
input[type=checkbox],input[type=radio]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;padding:0}
input[type=number]::-webkit-inner-spin-button,input[type=number]::-webkit-outer-spin-button{height:auto}
input[type=search]{-webkit-appearance:textfield;-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box}
input[type=search]::-webkit-search-cancel-button,input[type=search]::-webkit-search-decoration{-webkit-appearance:none}
fieldset{border:1px solid silver;margin:0 2px;padding:.35em .625em .75em}
table{border-collapse:collapse;border-spacing:0}
*,:after,:before{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}
html{font-size:10px;-webkit-tap-highlight-color:transparent}
body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:1.42857143;color:#333}
button,input,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}
a:focus,a:hover{color:#23527c;text-decoration:underline}
a:focus{outline:-webkit-focus-ring-color auto 5px;outline-offset:-2px}
.img-responsive{display:block;max-width:100%;height:auto}
.img-rounded{border-radius:6px}
.img-thumbnail{padding:4px;line-height:1.42857143;border:1px solid #ddd;border-radius:4px;-webkit-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;transition:all .2s ease-in-out;display:inline-block;max-width:100%;height:auto}
.img-circle{border-radius:50%}
hr{margin-top:20px;margin-bottom:20px;border-top:1px solid #eee}
.sr-only{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);border:0}
.sr-only-focusable:active,.sr-only-focusable:focus{position:static;width:auto;height:auto;margin:0;overflow:visible;clip:auto}
[role=button]{cursor:pointer}
.label{display:inline;padding:.2em .6em .3em;font-size:75%;font-weight:700;line-height:1;color:#fff;text-align:center;white-space:nowrap;vertical-align:baseline;border-radius:.25em}
a.label:focus,a.label:hover{color:#fff;text-decoration:none;cursor:pointer}
.label:empty{display:none}
.btn .label{position:relative;top:-1px}
.label-default{background-color:#777}
.label-default[href]:focus,.label-default[href]:hover{background-color:#5e5e5e}
.label-primary{background-color:#337ab7}
.label-primary[href]:focus,.label-primary[href]:hover{background-color:#286090}
.label-success{background-color:#5cb85c}
.label-success[href]:focus,.label-success[href]:hover{background-color:#449d44}
.label-info{background-color:#5bc0de}
.label-info[href]:focus,.label-info[href]:hover{background-color:#31b0d5}
.label-warning{background-color:#f0ad4e}
.label-warning[href]:focus,.label-warning[href]:hover{background-color:#ec971f}
.label-danger{background-color:#d9534f}
.label-danger[href]:focus,.label-danger[href]:hover{background-color:#c9302c}
.clearfix:after,.clearfix:before{content:" ";display:table}
.clearfix:after{clear:both}
.center-block{display:block;margin-left:auto;margin-right:auto}
.pull-right{float:right!important}
.pull-left{float:left!important}
.hide{display:none!important}
.show{display:block!important}
.invisible{visibility:hidden}
.text-hide{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}
.hidden{display:none!important}
.affix{position:fixed}

  </style>
</header>

<script type='text/javascript'>

// convert times
$(".timestamp").each(function() {
    var date  = new Date($(this).attr('data-timestamp') * 1000);
    $(this).html(date.toLocaleString().slice(0,-3));
});
</script>
