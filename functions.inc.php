<?php


/**
 * @file common.func.php
 *
 * All the commonly used functions are defined here.
 */

namespace yookoala\discuzcode;

use Phata\Widgetfy as Widgetfy;

/**
 * publicly used API for replacing Discuz BBcode
 * with widget / video embed
 *
 * @param string $message forum post message
 * @param mixed[] $options array of options for rendering
 * @return string altered message
 */
function &replace(&$message, $options=array()) {

  // remember options to use
  _replace_options($options);

  // basic url to video support
  $message=preg_replace_callback('/\[url\](.+?)\[\/url\]/i',
    __NAMESPACE__ . '\widgetfy_callback', $message);
  $message=preg_replace_callback('/\[url=(https?|ftp){1}:\/\/([^\["\']+?)\](.+?)\[\/url\]/is',
    __NAMESPACE__ . '\widgetfy_callback', $message);

  return $message;
}

/**
 * internally used. store options
 * of the replace method
 *
 * @param mixed[] $options array of options for rendering
 */
function _replace_options($options=FALSE) {
  static $_options;
  if ($options !== FALSE) {
    $_options = $options;
  }
  return $_options;
}

/**
 * callback function to replace links
 * with themed widgetfy output.
 *
 * @param string[] $matches array of matches string provided
 *                 by preg_replace_callback
 */
function widgetfy_callback($matches) {
  
  if (sizeof($matches)==4) {
    $link = "{$matches[1]}://{$matches[2]}";
    $string = $matches[3];
  } else {
    $link   = $matches[1];  
    $string = $matches[1];
  }
  
  $link_raw = str_replace('&amp;', '&', $link);
  $url=parse_url($link_raw);

  // use Widgetfy to determine embed code
  $options = _replace_options(); // retrieve options
  if (($embed = Widgetfy::translate($link_raw, $options)) != NULL) {
    return theme(
      $embed['html'], $link, $string,
      $embed['dimension']->width,
      $embed['dimension']->height);
  }

  // return the original matching string
  // if no embed replacement could be found
  return $matches[0];
}

/**
 * Helper function to make a non-unicode string shortter
 *
 * @param string $string the string to be trimmed
 * @param int the targeted trimed length
 * @return string trimmed string
 */
function string_trim($string, $length) {
  $length = (int) $length;
  if ($length<16) return $string;
  if (strlen($string)>$length) {
    $str_head = substr($string, 0, $length-10);
    $str_tail = substr($string, -7, 7);
    return $str_head.'...'.$str_tail;
  }
  return $string;
}

/**
 * Helper function to do i18n interface.
 * only support Traditional Chinese new
 *
 * @param string $string the string to be translated
 * @param string $locale locale language code
 * @return string translated string
 */
function t($string, $locale='zh-tw') {
  static $lang;

  if (!isset($lang)) {
    $lang['zh-tw']['Source'] = '來源';
  }

  return isset($lang[$locale][$string]) ? $lang[$locale][$string] : $string;
}

/**
 * Helper function to apply theme to all video embeds
 *
 * @param string $embed HTML embed
 * @param string $link
 * @param string $text
 * @param mixed $width integer width of the wrapper div;
 *              or FALSE if obmitted.
 * @param mixed $height integer height of the wrapper div;
 *              of FALSE if obmitted.
 */
function theme($embed, $link=False, $text=False, $width=False, $height=False) {

  static $css_done;
  $css = '';

  // if the video string = video link
  if (($text==$link) || ($text == False)) {
    $text = string_trim($text, 45); // make the link shorter here
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
  transition: 0.5s;
}
.videoblock .video-desc a:hover {
  color: inherit;
  background-color: #FEFEFE;
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

