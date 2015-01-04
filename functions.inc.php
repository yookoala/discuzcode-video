<?php

/**
 * @file functions.inc.php
 *
 * All the commonly used functions are defined here.
 *
 * This is a php script for Discuz! forum.
 * After install this script, your Discuz! forum will automatically turn
 * several video links into embed flash player. You don't have to give
 * provide users HTML capability and they still get inline video. The
 * site is more hack prove and everybody is happy.
 * requires PHP 5 or above
 *
 * @author Koala Yeung
 * @version 8.x
 **/

namespace yookoala\discuzcode;

use Phata\Widgetfy as Widgetfy;
use Phata\Widgetfy\Utils\Dimension as Dimension;

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
      $embed, $link, $string,
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
 * @param mixed[] $embed result array of Widgetfy::translate()
 * @param string $link
 * @param string $text
 */
function theme($embed, $link=False, $text=False) {

  static $css_done;
  $css = '';

  // use object dimension as dimension reference
  $d = &$embed['dimension'];

  // test if the link is kickstarter
  if ($link !== FALSE && (preg_match('/^\w+\:\/\/(www\.|)kickstarter\.com/', $link) == 1)) {
    // override dimension reference
    $d->width += $embed['other']['dimension']->width;
    $d->dynamic = FALSE;

    // attach the alternated
    $embed['html'] .= $embed['other']['html'];
  }

  // if no given text, or if the video string = video link
  if (($text==$link) || ($text == False)) {
    $text = t('Source') . ': '.string_trim($text, 45); // make the link shorter here
  }
  
  // link to the stylesheet on first run
  if (!isset($css_done)) {
    $css = '<link rel="stylesheet" type="text/css" href="'.path().'/style/style.css"/>';
  }

  ob_start();
  require __DIR__ . '/style/template.tpl.php';
  $codeblock = ob_get_contents();
  ob_end_clean();

  return preg_replace('/[\t ]*[\r\n]+[\t ]*/', ' ', $css.$codeblock);
}

/**
 * determine the URL path of discuzcode-video installation
 *
 * @return string URL path to discuzcode-video without trailing slash
 */
function path() {
  static $path;
  if (!isset($path)) {
    $docroot_regex = '^'.preg_quote($_SERVER['DOCUMENT_ROOT'], '/');
    $path = preg_replace('/'.$docroot_regex.'/', '', __DIR__);
    if ($path == '/') $path = '';
  }
  return $path;
}

/**
 * Helper funcion to template. Render attributes for .videoblock
 *
 * @param mixed[] $embed embed definition
 * @return string HTTP attributes
 */
function render_block_attrs($embed) {

  // attributes to be rendered
  $classes = array();
  $styles = array();

  // shortcuts
  $d = &$embed['dimension'];

  // determine classes
  $classes[] = 'videoblock';
  if ($d->dynamic) {
    $classes[] = 'videoblock-dynamic';
  }

  // determine inline CSS style(s)
  // if scale model is no-scale, allow to "force dynamic"
  // by setting "dynamic" to TRUE
  if ($d->dynamic) {
    $styles[] = 'max-width:'.$d->width.'px';
  } else {
    $styles[] = 'width: '.$d->width.'px';
  }

  // render the attributes
  $class = implode(' ', $classes);
  $style = implode('; ', $styles) . (!empty($styles) ? ';' : '');
  return 'class="'.$class.'" style="'.$style.'"';
}

/**
 * Helper funcion to template. Render attributes for .videowrapper
 *
 * @param mixed[] $embed embed definition
 * @return string HTTP attributes
 */
function render_wrapper_attrs($embed) {

  // attributes to be rendered
  $classes = array();
  $styles = array();

  // shortcuts
  $d = &$embed['dimension'];

  // determine classes
  $classes[] = 'video-wrapper';
  if ($d->dynamic) {
    $classes[] = 'wrap-'.$d->scale_model;
  }

  // determine inline CSS style(s)
  if ($d->dynamic && ($d->scale_model == 'scale-width-height')) {
    $classes[] = 'padding-bottom: ' . ($d->factor * 100) . '%;';
  }

  // render the attributes
  $class = implode(' ', $classes);
  $style = implode('; ', $styles) . (!empty($styles) ? ';' : '');
  return 'class="'.$class.'" style="'.$style.'"';
}
