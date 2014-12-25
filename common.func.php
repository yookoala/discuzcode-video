<?php

/**
 * @file common.func.php
 *
 * All the commonly used functions are defined here.
 */


/***********************************************************
* simplified interface
************************************************************/

function &_discuzcode_video_replace(&$message) {

  // basic url to video support
  $message=preg_replace_callback('/\[url\](.+?)\[\/url\]/i',
    '_discuzcode_video_callback', $message);
  $message=preg_replace_callback('/\[url=(https?|ftp){1}:\/\/([^\["\']+?)\](.+?)\[\/url\]/is',
    '_discuzcode_video_callback', $message);

  return $message;
}


/**********************************************************
* interfaces
***********************************************************/

/**
* the interface for discuz or other program to use
* will return embed video html as long as the link
* matches any case provided in this function
*/
function _discuzcode_video_callback($matches) {
  
  if (sizeof($matches)==4) {
    $link = "{$matches[1]}://{$matches[2]}";
    $string = $matches[3];
  } else {
    $link   = $matches[1];  
    $string = $matches[1];
  }
  
  $url=parse_url(str_replace('&amp;', '&', $link));

  // use Widgetfy to determine embed code
  if (($embed = Phata\Widgetfy\Site::translate($link)) != NULL) {
    return _discuzcode_video_template(
      $embed['html'], $link, $string, $embed['width'], $embed['height']);
  }

  switch (TRUE) {
    case (strtolower($url['scheme']) == 'mms'):
    case (preg_match('/\.(wmv|avi|asx|mpg|mpeg)$/i', basename(strtolower($url['path'])))):
    case (preg_match('/^uploaded_videos\.php$/i', basename(strtolower($url['path'])))):
      $embed=sprintf('<OBJECT ID="MediaPlayer" WIDTH="480" HEIGHT="290" '.
      'CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"'.
      'STANDBY="Loading Windows Media Player components..." TYPE="application/x-oleobject">'.
      '<PARAM NAME="FileName" VALUE="%s">'.
      '<PARAM NAME="autostart" VALUE="false">'.
      '<PARAM NAME="ShowControls" VALUE="true">'.
      '<PARAM NAME="ShowStatusBar" VALUE="false">'.
      '<PARAM NAME="ShowDisplay" VALUE="false">'.
      '<EMBED TYPE="application/x-mplayer2" SRC="%s" NAME="MediaPlayer"'.
      'WIDTH="480" HEIGHT="290" ShowControls="1" ShowStatusBar="1" ShowDisplay="0" autostart="0"></EMBED>'.
      '</OBJECT>', $link, $link);
      return _discuzcode_video_template($embed, $link, $string, 480);
    break;
    case (preg_match('/\.(ogg)$/i', basename(strtolower($url['path'])))):
      $embed = sprintf('<video width=600 src="%s" controls=true>Sorry, your browser has the following problem(s):
<ul><li>It does not support playing <a href="http://www.theora.org/" target="_blank">OGG Theora</a>; or</li>
<li>It does notthe HTML5 &lt;video&gt; element.</li></ul> Please upgrade to a browser such as <a
href="http://www.getfirefox.com">Firefox 3.6</a>.</video>', $link);
      return _discuzcode_video_template($embed, $link, $string, 600);
    break;
    case (preg_match('/\.(rm|rmvb)$/i', basename(strtolower($url['path'])))): 
      $embed=sprintf('<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="300" autostart="false" '.
      'controls="imagewindow" nojava="true" '.
      'console="c1183760810807" '.
      'pluginspage="//www.real.com/"></embed><br>'.
      '<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="26" autostart="false" '.
      'nojava="true" controls="ControlPanel" '.
      'console="c1183760810807"></embed>', $link, $link);
      return _discuzcode_video_template($embed, $link, $string, 400);
    break;
  }
  
  return $matches[0];
}

/********************************************************
* helper functions
*********************************************************/

/**
* helper function make a non-unicode string shortter
*/
function _discuzcode_string_trim($string, $length) {
  $length = (int) $length;
  if ($length<16) return $string;
  if (strlen($string)>$length) {
    $str_head = substr($string, 0, $length-10);
    $str_tail = substr($string, -7, 7);
    return $str_head.'...'.$str_tail;
  }
  return $string;
}

/********************************************************
* language
*********************************************************/

function t($string, $locale='zh-tw') {
  static $lang;

  if (!isset($lang)) {
    $lang['zh-tw']['Source'] = '來源';
  }

  return isset($lang[$locale][$string]) ? $lang[$locale][$string] : $string;
}

/********************************************************
* themeing
*********************************************************/

/**
* apply an overall template to all video
*/
function _discuzcode_video_template($embed, $link=False, $text=False, $width=False, $height=False) {

  static $css_done;
  $css = '';

  // if the video string = video link
  if (($text==$link) || ($text == False)) {
    $text = _discuzcode_string_trim($text, 45); // make the link shorter here
  }
  
  // experimental: check, in the embed code, the width of it
  preg_match('/width=\"([0-9]+)\"/', $embed, $result); $width_default = 480;
  $width=($width===False) ? (!empty($result) ? $result[1] : $width_default) : $width;
  $heightcode=($height===False) ? '':' height: '.$height.'px;';
  $source_text = t('Source');
 
  if (!isset($css_done)) {
    $css = <<<CODEBLOCK
<style type="text/css">
.videoblock {
  border: solid 1px #DDD;
  border-bottom-left-radius: 3px;
  border-bottom-right-radius: 3px;
  box-shadow: 4px 4px 4px #AAA;
}
.videoblock .video-wrapper {
  background: #EEE;
}
.videoblock .video-desc a {
  display: block;
  padding: 5px 6px 7px;
  margin: 2px;
  color: #555;
  text-decoration: none;
  overflow-x: hidden;
  border-radius: 3px;
}
.videoblock .video-desc a:hover {
  color: inherit;
  background: #FEFEFE;
}
</style>
CODEBLOCK;
  }

  if (($link===False) AND ($text===False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode}">
  <div class="video-wrapper">$embed</div>
</div>
CODEBLOCK;
  } elseif (($link===False) AND ($text!==False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #DDD; background: #CCC;">
  <div class="video-wrapper" style="{$heightcode}">$embed</div>
  <div class="video-desc">$text</div>
</div>
CODEBLOCK;
  } else {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #DDD; background: #CCC;">
  <div class="video-wrapper" style="{$heightcode}">$embed</div>
  <div class="video-desc"><a href="$link" target="_blank">$source_text: $text</a></div>
</div>
CODEBLOCK;
  }
  return str_replace(array("\r", "\n  ", "\n"), array('', '', ''), $css.$codeblock);
}


