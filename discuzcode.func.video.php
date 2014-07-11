<?php

/**
    discuzcode.func.video.php

    This is a php script for Discuz! forum.
    After install this script, your Discuz! forum will automatically turn
    several video links into embed flash player. You don't have to give
    provide users HTML capability and they still get inline video. The
    site is more hack prove and everybody is happy.
    
    @author Koala Yeung
    @version 3.2
**/

/**
    ------------
    Installation
    ------------
    1) login to your Discuz! installation FTP/SFTP
    2) goes to <your installation dir>/include
    3) open discuzcode.func.php
    4) find a function named 'discuzcode'
    5) inside the function, find a line that start with: 'if(!$bbcodeoff && $allowbbcode) {'
    6) before the line you found in step 5, add these line
 
        // koala hack here for youtube and other video support
        if (!function_exists('_discuzcode_video_callback')) {
            require_once(dirname(__FILE__).'/discuzcode-video/discuzcode.func.video.php');
        }
        if (function_exists('_discuzcode_video_callback')) {
            $message=preg_replace_callback("/\[url\](.+?)\[\/url\]/i", '_discuzcode_video_callback', $message);
        } else {
          exit('Fatal Error! File "'.dirname(__FILE__).'/discuzcode-video/discuzcode.func.video.php'.'" is missed!');
        }

    7) save and exit
    8) copy this file to the <your installation dir>/include
    9) done. test it.
**/


if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

			
// koala hack here for youtube and other video support
function _discuzcode_video_callback($matches) {
  if (!preg_match('/^\http:\/\//', $matches[1])) $matches[1]='http://'.$matches[1];
  $string=(!empty($matches[2])) ? $matches[2] : $matches[1];
  
  $url=parse_url($matches[1]);
  switch (TRUE) {
    case (strtolower($url["host"])=='youtube.com'):
    case preg_match('/[a-z]+?\.youtube\.com/', strtolower($url["host"])):
    if (preg_match('/^\/watch$/', $url["path"])) {
      parse_str($url["query"], $args); 
      $codeblock='<div style="width: 425px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<object width="425" height="350"><param name="movie" '.
      'value="http://www.youtube.com/v/%s"></param><param name="wmode" '.
      'value="transparent"></param><embed src="http://www.youtube.com/v/%s"'.
      ' type="application/x-shockwave-flash" wmode="transparent" '.
      'width="425" height="350"></embed></object></div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";
      return sprintf($codeblock, $args["v"], $args["v"], $matches[1], $string);
    }
    break;
    case (strtolower($url["host"])=='www.tudou.com'):
    case (strtolower($url["host"])=='tudou.com'):
    if (preg_match('/^\/programs\/view\/.+?\/$/', $url["path"])) {
      $video_id=preg_replace('/^\/programs\/view\/(.+?)\/$/', '$1', $url["path"]);
      $codeblock='<div style="width: 400px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<object width="400" height="300">'.
      '<param name="movie" value="http://www.tudou.com/v/%s"></param>'.
      '<param name="allowScriptAccess" value="always"></param>'.
      '<param name="wmode" value="transparent"></param>'.
      '<embed src="http://www.tudou.com/v/%s" type="application/x-shockwave-flash"'.
      ' width="400" height="300" allowFullScreen="true" wmode="transparent" allowScriptAccess="always"></embed>'.
      '</object></div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";
      return sprintf($codeblock, $video_id, $video_id, $matches[1], $string);
    } elseif (preg_match('/^\/playlist\/id\/.+?\/$/', $url["path"])) {
      $video_id=preg_replace('/^\/playlist\/id\/(.+?)\/$/', '$1', $url["path"]);
/*
      $codeblock='<div style="width: 488px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<object width="488" height="423">'.
      '<param name="movie" value="http://www.tudou.com/player/playlist.swf?lid=%d"></param>'.
      '<param name="allowscriptaccess" value="always">'.
      '<embed src="http://www.tudou.com/player/playlist.swf?lid=%d" '.
      'type="application/x-shockwave-flash" width="488" height="423"></embed>'.
      '</object></div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";
*/
      $codeblock='<div style="width: 488px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<object width="488" height="423"><param name="movie" '.
      'value="http://www.tudou.com/player/playlist.swf?lid=%s"></param>'.
      '<param name="allowscriptaccess" value="always">'.
      '<embed src="http://www.tudou.com/player/playlist.swf?lid=%s" '.
      'type="application/x-shockwave-flash" width="488" height="423"></embed></object></div>'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";

      return sprintf($codeblock, $video_id, $video_id, $matches[1], $string);
    } elseif (preg_match('/^\/playlist\/playindex.do$/', $url["path"])) {
      parse_str($url["query"], $args);
      if (!empty($args['lid'])) {
        $codeblock='<div style="width: 488px; border: solid 1px #000; '.
        'background: #CCC;"><div style="background: #000;">'.
        '<object width="488" height="423">'.
        '<param name="movie" value="http://www.tudou.com/player/playlist.swf?lid=%d"></param>'.
        '<param name="allowscriptaccess" value="always">'.
        '<embed src="http://www.tudou.com/player/playlist.swf?lid=%d" '.
        'type="application/x-shockwave-flash" width="488" height="423"></embed>'.
        '</object></div>'.
        '<div style="margin: 2px 4px;">'.
        'Source: <a href="%s" style="color: #E00;" '.
        'target="_blank">%s</a></div></div>'."\n";
        return sprintf($codeblock, $args["lid"], $args["lid"], $matches[1], $string);
      }
    }
    break;
    case (strtolower($url["host"])=='hk.video.yahoo.com'):
      $contents=file_get_contents($matches[1]);
      _discuzcode_video_callback_yahoo_url($matches[1]);
      #preg_replace_callback('/\<input.+?[\r\n\t ]+?name\="embedsource".*?[\r\n\t ]+?value\="(.+?)"/', '_discuzcode_video_callback_yahoo', $contents);
      preg_replace_callback('/\<input.+?value\=\'(\<object.+?)\'/', '_discuzcode_video_callback_yahoo', $contents);
      $codeblock='<div style="width: 425px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      _discuzcode_video_callback_yahoo().
      '</div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">'.
      $string.
      '</a></div></div>'."\n";
      return $codeblock;
    break;
    case (preg_match('/\.(wmv|avi|asx|mpg|mpeg)$/i', basename(strtolower($url["path"])))):
    case (preg_match('/^uploaded_videos\.php$/i', basename(strtolower($url["path"])))):
      $codeblock='<div style="width: 425px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<OBJECT ID="MediaPlayer" WIDTH="425" HEIGHT="400" CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"'.
      'STANDBY="Loading Windows Media Player components..." TYPE="application/x-oleobject">'.
      '<PARAM NAME="FileName" VALUE="%s">'.
      '<PARAM name="autostart" VALUE="false">'.
      '<PARAM name="ShowControls" VALUE="true">'.
      '<param name="ShowStatusBar" value="false">'.
      '<PARAM name="ShowDisplay" VALUE="false">'.
      '<EMBED TYPE="application/x-mplayer2" SRC="%s" NAME="MediaPlayer"'.
      'WIDTH="425" HEIGHT="350" ShowControls="1" ShowStatusBar="1" ShowDisplay="0" autostart="0"> </EMBED>'.
      '</OBJECT></div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";
      return sprintf($codeblock, $matches[1], $matches[1], $matches[1], $string);
    break;
    case preg_match('/[a-z]+?\.liveleak\.com/', strtolower($url["host"])):
      if (preg_match('/^\/view$/', $url["path"])) {
        parse_str($url["query"], $args);
        $codeblock='<div style="width: 450px; border: solid 1px #000; '.
        'background: #CCC;"><div style="background: #000;">'.
        '<embed src="http://www.liveleak.com/player.swf" '.
        'width="450" height="370" type="application/x-shockwave-flash" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'flashvars="autostart=false&token=%s" scale="showall" '.
        'name="index"></embed>'.
        '</div>'.
        '<div style="margin: 2px 4px;">'.
        'Source: <a href="%s" style="color: #E00;" '.
        'target="_blank">%s</a></div></div>'."\n";
        return sprintf($codeblock, $args['i'], $matches[1], $matches[1]);
      }
    break;
    case preg_match('/[a-z]+?\.metacafe\.com/', strtolower($url["host"])):
      if (preg_match('/^\/watch\/\d+\/.+$/', $url["path"])) {
        $hash=preg_replace('/^\/watch\/(.+?)$/', '$1', $url["path"]);
        $hash=preg_replace("/\/$/", '', $hash);
        $codeblock='<div style="width: 400px; border: solid 1px #000; '.
        'background: #CCC;"><div style="background: #000;">'.
        '<embed src="http://www.metacafe.com/fplayer/%s.swf" '.
        'width="400" height="345" wmode="transparent" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'type="application/x-shockwave-flash"></embed>'.
        '</div>'.
        '<div style="margin: 2px 4px;">'.
        'Source: <a href="%s" style="color: #E00;" '.
        'target="_blank">%s</a></div></div>'."\n";
        return sprintf($codeblock, $hash, $matches[1], $matches[1]);
       }
    break;
    case preg_match('/[a-z]+?\.gametrailers\.com/', strtolower($url["host"])):
      if (preg_match('/^\/player\/\d+?\.html$/', $url["path"])) {
        $id=preg_replace('/^\/player\/(\d+?)\.html$/', '$1', $url["path"]);
        $codeblock='<div style="width: 480px; border: solid 1px #000; '.
        'background: #CCC;"><div style="background: #000;">'.
        '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '.
        'codebase="http://download.macromedia.com/pub/shockwave/cabs/'.
        'flash/swflash.cab#version=8,0,0,0" id="gtembed" width="480" '.
        'height="392"><param name="allowScriptAccess" value="sameDomain" /> '.
        '<param name="allowFullScreen" value="true" /> '.
        '<param name="movie" '.
        'value="http://www.gametrailers.com/remote_wrap.php?mid=%d"/>'.
        '<param name="quality" value="high" /> '.
        '<embed src="http://www.gametrailers.com/remote_wrap.php?mid=%d" '.
        'swLiveConnect="true" name="gtembed" align="middle" '.
        'allowScriptAccess="sameDomain" allowFullScreen="true" '.
        'quality="high" pluginspage="http://www.macromedia.com/go/getflash'.
        'player" type="application/x-shockwave-flash" width="480" '.
        'height="392"></embed> </object>'.
        '</div>'.
        '<div style="margin: 2px 4px;">'.
        'Source: <a href="%s" style="color: #E00;" '.
        'target="_blank">%s</a></div></div>'."\n";
        return sprintf($codeblock, $id, $id, $matches[1], $matches[1]);
      }
    break;
    case (preg_match('/\.(rm|rmvb)$/i', basename(strtolower($url["path"])))): 
      $codeblock='<div style="width: 400px; border: solid 1px #000; '.
      'background: #CCC;"><div style="background: #000;">'.
      '<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="300" autostart="false" '.
      'controls="imagewindow" nojava="true" '.
      'console="c1183760810807" '.
      'pluginspage="http://www.real.com/"></embed><br>'.
      '<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="26" autostart="false" '.
      'nojava="true" controls="ControlPanel" '.
      'console="c1183760810807"></embed>'.
      '</div>'.
      '<div style="margin: 2px 4px;">'.
      'Source: <a href="%s" style="color: #E00;" '.
      'target="_blank">%s</a></div></div>'."\n";
      return sprintf($codeblock, $matches[1], $matches[1], $matches[1], $string);
    break;
  }
  
  return $matches[0];
}

function _discuzcode_video_callback_yahoo_url($url=FALSE) {
  static $_url;
  if ($url!==FALSE) $_url=$url;
  return $_url;
}

function _discuzcode_video_callback_yahoo($matches=FALSE) {
  static $_video_code;
  if (!isset($_video_code)) $_video_code=array();
  if (!isset($_video_code[_discuzcode_video_callback_yahoo_url()])) {
    if (!empty($matches[1])) $_video_code[_discuzcode_video_callback_yahoo_url()]=$matches[1];
  }
  return $_video_code[_discuzcode_video_callback_yahoo_url()];
}

