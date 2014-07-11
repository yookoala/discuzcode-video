<?php

/**
    discuzcode.func.video.php

    This is a php script for Discuz! forum.
    After install this script, your Discuz! forum will automatically turn
    several video links into embed flash player. You don't have to give
    provide users HTML capability and they still get inline video. The
    site is more hack prove and everybody is happy.
    
    @author Koala Yeung
    @version 4.2.6
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
            // basic url to video support
            $message=preg_replace_callback("/\[url\](.+?)\[\/url\]/i", '_discuzcode_video_callback', $message);
            $message=preg_replace_callback("/\[url=(https?|ftp){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/is", '_discuzcode_video_callback', $message);

            // ignvideo
            $message=preg_replace_callback("/\[ignvideo\](.+?)\[\/ignvideo\]/i", '_discuzcode_video_ignvideo_callback', $message);

            // [video] bbcode support
            $message=preg_replace_callback("/\[video\](.+?)\[\/video\]/is", "_discuzcode_video_html_callback", $message);
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


function _discuzcode_string_trim($string, $length) {
  $length = (int) $length;
  if ($length<16) return $string;
  if (strlen($string)>$length) {
    $str_head = substr($string, 0, $length-10);
    $str_tail = substr($string, -7, 7);
    return "$str_head...$str_tail";
  }
  return $string;
}

function _discuzcode_video_template($embed, $link=False, $text=False, $width=False, $height=False) {

  // if the video string = video link
  if ($text==$link) {
    $text = _discuzcode_string_trim($text, 45); // make the link shorter here
  }
  
  // experimental: check, in the embed code, the width of it
  preg_match("/width=\"([0-9]+)\"/", $embed, $result); $width_default = 480;
  $width=($width===False) ? (!empty($result) ? $result[1] : $width_default) : $width;
  $heightcode=($height===False) ? "":" height: {$height}px;";
  
  if (($link===False) AND ($text===False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode} border: solid 1px #000; background: #CCC;">
  <div style="background: #000;">$embed</div>
</div>
CODEBLOCK;
  } elseif (($link===False) AND ($text!==False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode} border: solid 1px #000; background: #CCC;">
  <div style="background: #000;">$embed</div>
  <div style="margin: 2px 4px; overflow-x: hidden;">$text</div>
</div>
CODEBLOCK;
  } else {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode} border: solid 1px #000; background: #CCC;">
  <div style="background: #000;">$embed</div>
  <div style="margin: 2px 4px; overflow-x: hidden;">Source: <a href="$link" style="color: #E00;" target="_blank">$text</a></div>
</div>
CODEBLOCK;
  }
  return str_replace(array("\r", "\n  ", "\n"), array("", "", ""), $codeblock);
}
			
// koala hack here for youtube and other video support
function _discuzcode_video_callback($matches) {
  
  if (sizeof($matches)==4) {
    $link = "{$matches[1]}://{$matches[2]}";
    $string = $matches[3];
  } else {
    $link   = $matches[1];  
    $string = $matches[1];
  }
  
  $url=parse_url(str_replace('&amp;', '&', $link));
  switch (TRUE) {
    case (strtolower($url["host"])=='youtube.com'):
    case preg_match('/[a-z]+?\.youtube\.com/', strtolower($url["host"])):
    if (preg_match('/^\/watch$/', $url["path"])) {
      parse_str($url["query"], $args); 
      $location = preg_replace('/([a-z]+?)\.youtube\.com/', '$1', strtolower($url["host"]));
      $string = "http://{$location}.youtube.com/watch?v={$args['v']}";
      $vid = $args["v"];
      
      // may add fmt specific dimension config here
      if (isset($args["fmt"]) && is_numeric($args["fmt"])) {
        $query_str = "&hl=en&fs=1&ap=%2526fmt%3D".$args["fmt"];
        $width = 576; $height = 354;
        switch ($args["fmt"]) {
          case 22:
            $string .= " <span style='color: #000; font-size: 10pt;'>(HD quality)</span>";
            break;
          case 35:
            $string .= " <span style='color: #000; font-size: 10pt;'>(high quality)</span>";
            break;
          case 18:
            $string .= " <span style='color: #000; font-size: 10pt;'>(high quality)</span>";
            break;
        }
      } else {
        $width = 576; $height = 354;
        $query_str = "&hl=en&fs=1";
      }
      
      $embed = sprintf('<object width="%d" height="%d">'.
      '<param name="movie" value="http://%s.youtube.com/v/%s%s"></param>'.
      '<param name="allowFullScreen" value="true"></param>'.
      '<param name="allowscriptaccess" value="always"></param>'.
      '<embed src="http://%s.youtube.com/v/%s%s" '.
      'type="application/x-shockwave-flash" allowscriptaccess="always" '.
      'allowfullscreen="true" width="%d" height="%d" quality="high"></embed>'.
      '</object>', $width, $height, 
      $location, $vid, htmlspecialchars($query_str),
      $location, $vid, $query_str,
      $width, $height);
      
      return _discuzcode_video_template($embed, $link, $string);
    }
    break;
    case (strtolower($url["host"])=='video.google.com'):
      parse_str($url["query"], $args);
      $args["hl"] = empty($args["hl"]) ? "zh-TW" : $args["hl"];
      $embed= sprintf('<embed id="VideoPlayback" '.
       'src="http://video.google.com/googleplayer.swf?docid=%s&hl=%s&fs=true" '.
       'style="width:600px;height:489px" '.
       'allowFullScreen="true" '.
       'allowScriptAccess="always" '.
       'type="application/x-shockwave-flash">'.
       '</embed>', $args["docid"], $args["hl"]);
      return _discuzcode_video_template($embed, $link, $string, 600);
    break;
    case (strtolower($url["host"])=='v.youku.com'):
      $regex = '/^\/v_show\/id_(.+?)\=\.html$/';
      if (preg_match($regex, $url["path"])) {
        $sid = preg_replace($regex, '$1', $url["path"]);
        $embed = sprintf('<embed '.
        'src="http://player.youku.com/player.php/sid/%s=/v.swf" '.
        'quality="high" width="480" height="400" align="middle" '.
        'allowScriptAccess="sameDomain" '.
        'type="application/x-shockwave-flash"></embed>', $sid);
        return _discuzcode_video_template($embed, $link, $string, 480);
      }
    break;
    case (strtolower($url["host"])=='www.tudou.com'):
    case (strtolower($url["host"])=='tudou.com'):
    if (preg_match('/^\/programs\/view\/.+?\/$/', $url["path"])) {
      $video_id=preg_replace('/^\/programs\/view\/(.+?)\/$/', '$1', $url["path"]);
      $embed = sprintf('<object width="400" height="300">'.
      '<param name="movie" value="http://www.tudou.com/v/%s"></param>'.
      '<param name="allowScriptAccess" value="always"></param>'.
      '<param name="wmode" value="transparent"></param>'.
      '<embed src="http://www.tudou.com/v/%s" type="application/x-shockwave-flash"'.
      ' width="400" height="300" allowFullScreen="true" wmode="transparent" allowScriptAccess="always"></embed>'.
      '</object>', $video_id, $video_id);
      
      return _discuzcode_video_template($embed, $link, $string, 400);
    } elseif (preg_match('/^\/playlist\/id\/.+?\/$/', $url["path"])) {
      $video_id=preg_replace('/^\/playlist\/id\/(.+?)\/$/', '$1', $url["path"]);
      
      $embed = sprintf('<object width="488" height="423"><param name="movie" '.
      'value="http://www.tudou.com/player/playlist.swf?lid=%s"></param>'.
      '<param name="allowscriptaccess" value="always">'.
      '<embed src="http://www.tudou.com/player/playlist.swf?lid=%s" '.
      'type="application/x-shockwave-flash" width="488" height="423"></embed>'.
      '</object>', $video_id, $video_id);
      return _discuzcode_video_template($embed, $link, $string, 488);
      
    } elseif (preg_match('/^\/playlist\/playindex.do$/', $url["path"])) {
      parse_str($url["query"], $args);
      if (!empty($args['lid'])) {
        $embed = sprintf('<object width="488" height="423"><param name="movie" '.
        'value="http://www.tudou.com/player/playlist.swf?lid=%s"></param>'.
        '<param name="allowscriptaccess" value="always">'.
        '<embed src="http://www.tudou.com/player/playlist.swf?lid=%s" '.
        'type="application/x-shockwave-flash" width="488" height="423"></embed>'.
        '</object>', $args["lid"], $args["lid"]);
        return _discuzcode_video_template($embed, $link, $string, 488);
      }
    }
    break;
    case (strtolower($url["host"])=='hk.video.yahoo.com'):
      $contents=file_get_contents($matches[1]);
      _discuzcode_video_callback_yahoo_url($matches[1]);
      preg_replace_callback('/\<input.+?value\=\'(\<object.+?)\'/', '_discuzcode_video_callback_yahoo', $contents);
      return _discuzcode_video_template(_discuzcode_video_callback_yahoo(), $matches[1], $string);
    break;
    case preg_match('/^www\.gametrailers\.com$/', strtolower($url["host"])):
      if (preg_match('/^\/player\/usermovies\/[0-9].+\.html$/', $url["path"])) {
        $umid=preg_replace('/^\/player\/usermovies\/([0-9].+)\.html$/', '$1', $url["path"]);
        $embed=sprintf('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '.
        'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" '.
        'id="gtembed" width="480" height="392">'.
        '<param name="allowScriptAccess" value="sameDomain" />'.
        '<param name="allowFullScreen" value="true" />'.
        '<param name="movie" value="http://www.gametrailers.com/remote_wrap.php?umid=%d"/> '.
        '<param name="quality" value="high" />'.
        '<embed src="http://www.gametrailers.com/remote_wrap.php?umid=%d" swLiveConnect="true" '.
        'name="gtembed" align="middle" allowScriptAccess="sameDomain" allowFullScreen="true" '.
        'quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'type="application/x-shockwave-flash" width="480" height="394"></embed></object>',
        $umid, $umid);
        return _discuzcode_video_template($embed, $link, $string, 480);
      }
    break;
    case (preg_match('/\.(wmv|avi|asx|mpg|mpeg)$/i', basename(strtolower($url["path"])))):
    case (preg_match('/^uploaded_videos\.php$/i', basename(strtolower($url["path"])))):
      $embed=sprintf('<OBJECT ID="MediaPlayer" WIDTH="425" HEIGHT="400" '.
      'CLASSID="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"'.
      'STANDBY="Loading Windows Media Player components..." TYPE="application/x-oleobject">'.
      '<PARAM NAME="FileName" VALUE="%s">'.
      '<PARAM NAME="autostart" VALUE="false">'.
      '<PARAM NAME="ShowControls" VALUE="true">'.
      '<PARAM NAME="ShowStatusBar" VALUE="false">'.
      '<PARAM NAME="ShowDisplay" VALUE="false">'.
      '<EMBED TYPE="application/x-mplayer2" SRC="%s" NAME="MediaPlayer"'.
      'WIDTH="425" HEIGHT="350" ShowControls="1" ShowStatusBar="1" ShowDisplay="0" autostart="0"></EMBED>'.
      '</OBJECT>', $link, $link);
      return _discuzcode_video_template($embed, $link, $string, 425);
    break;
    case preg_match('/[a-z]+?\.liveleak\.com/', strtolower($url["host"])):
      if (preg_match('/^\/view$/', $url["path"])) {
        parse_str($url["query"], $args);
        $embed=sprintf('<embed src="http://www.liveleak.com/player.swf" '.
        'width="450" height="370" type="application/x-shockwave-flash" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'flashvars="autostart=false&token=%s" scale="showall" '.
        'name="index"></embed>', $args['i']);
        return _discuzcode_video_template($embed, $link, $string, 450);
      }
    break;
    case preg_match('/www\.dailymotion\.com/', strtolower($url["host"])):
      if (preg_match('/^\/video\/.+?_.+?$/', $url["path"])) {
        $id=preg_replace('/^\/video\/(.+?)_.+?$/', '$1', $url["path"]);
        $embed=sprintf('<object width="420" height="339">'.
        '<param name="movie" value="http://www.dailymotion.com/swf/%s" />'.
        '<param name="allowFullScreen" value="true" />'.
        '<param name="allowScriptAccess" value="always" />'.
        '<embed src="http://www.dailymotion.com/swf/%s" '.
        'type="application/x-shockwave-flash" width="420" height="339" '.
        'allowFullScreen="true" allowScriptAccess="always"></embed></object>',
        $id, $id);
        return _discuzcode_video_template($embed, $link, $string, 420);
      }
    break;
    case preg_match('/[a-z]+?\.metacafe\.com/', strtolower($url["host"])):
      if (preg_match('/^\/watch\/\d+\/.+$/', $url["path"])) {
        $hash=preg_replace('/^\/watch\/(.+?)$/', '$1', $url["path"]);
        $hash=preg_replace("/\/$/", '', $hash);
        $embed=sprintf('<embed src="http://www.metacafe.com/fplayer/%s.swf" '.
        'width="400" height="345" wmode="transparent" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'type="application/x-shockwave-flash"></embed>', $hash);
        return _discuzcode_video_template($embed, $link, $string, 400);
      } elseif (preg_match('/^\/fplayer\/\d+\/.+\.swf$/', $url["path"])) {
        $hash=preg_replace('/^\/fplayer\/(.+?)\.swf$/', '$1', $url["path"]);
        $hash=preg_replace("/\/$/", '', $hash);
        $embed=sprintf('<embed src="http://www.metacafe.com/fplayer/%s.swf" '.
        'width="400" height="345" wmode="transparent" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'type="application/x-shockwave-flash"></embed>', $hash);
        return _discuzcode_video_template($embed, $link, $string, 400);
      }
    break;
    case preg_match('/[a-z]+?\.collegehumor\.com/', strtolower($url["host"])):
      if (preg_match('/^\/video\:\d+$/', $url["path"])) {
        $clipid=preg_replace('/^\/video\:(\d+?)$/', '$1', $url["path"]);
        $embed=sprintf('<object type="application/x-shockwave-flash" '.
        'data="http://www.collegehumor.com/moogaloop/'.
        'moogaloop.swf?clip_id=%d&fullscreen=1" '.
        'width="480" height="360" ><param name="allowfullscreen" value="true" />'.
        '<param name="movie" quality="best" value="http://www.collegehumor.com/'.
        'moogaloop/moogaloop.swf?clip_id=%d&fullscreen=1" /></object>',
        $clipid, $clipid);
        return _discuzcode_video_template($embed, $link, $string, 480);
      } elseif (preg_match('/^\/moogaloop\/moogaloop\.swf$/', $url["path"])) {
        parse_str($url["query"], $args); 
        $clipid=$args['clip_id'];
        $video_path = 'http://www.collegehumor.com/video:' . $clipid;
        $string = ($string === $link) ? $video_path : $string;
        $link = $video_path;
        $embed=sprintf('<object type="application/x-shockwave-flash" '.
        'data="http://www.collegehumor.com/moogaloop/'.
        'moogaloop.swf?clip_id=%d&fullscreen=1" '.
        'width="480" height="360" ><param name="allowfullscreen" value="true" />'.
        '<param name="movie" quality="best" value="http://www.collegehumor.com/'.
        'moogaloop/moogaloop.swf?clip_id=%d&fullscreen=1" /></object>',
        $clipid, $clipid);
        return _discuzcode_video_template($embed, $link, $string, 480);
       }
    break; 
    case preg_match('/www\.ku6\.com/', strtolower($url["host"])):
      if (preg_match('/^\/show\/.+?\.html$/', $url["path"])) {
        $vid=preg_replace('/^\/show\/(.+?)\.html$/', '$1', $url["path"]);
        $embed=sprintf('<embed src="http://img.ku6.com/common/V2.0.1.swf" '.
        'flashvars="vid=%s" width="460" height="390" '.
        'align="middle" allowScriptAccess="always" '.
        'type="application/x-shockwave-flash" '.
        'pluginspage="http://www.macromedia.com/go/getsflashplayer" /></object>',
        $vid, $vid);
        return _discuzcode_video_template($embed, $link, $string, 460);
      }
    break;
    case preg_match('/[a-z]+?\.builderau\.com\.au/', strtolower($url["host"])):
      if (preg_match('/^\/video\/play\/\d+/', $url["path"])) {
        $vid=(int) preg_replace('/^\/video\/play\/(\d+?)/', '$1', $url["path"]);
        $embed=sprintf('<object width="400" height="330"><param name="movie" '.
        'value="http://www.builderau.com.au/video/embed/%d"></param></param>'.
        '<param name="allowfullscreen" value="true"></param>'.
        '<embed src="http://www.builderau.com.au/video/embed/%d" '.
        'type="application/x-shockwave-flash" allowfullscreen="true" '.
        'width="400" height="330"></embed></object>', $vid, $vid);
        return _discuzcode_video_template($embed, $link, $string, 400);
      }
    break;
    case preg_match('/[a-z]+?\.gametrailers\.com/', strtolower($url["host"])):
      if (preg_match('/^\/player\/\d+?\.html$/', $url["path"])) {
        $id=preg_replace('/^\/player\/(\d+?)\.html$/', '$1', $url["path"]);
        $embed=sprintf('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '.
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
        'height="392"></embed></object>', $id, $id);
        return _discuzcode_video_template($embed, $link, $string, 480);
      }
    break;
    case preg_match('/share\.youthwant\.com\.tw/', strtolower($url["host"])):
      parse_str($url["query"], $args);
      if ($url["path"]==='/sh.php' && isset($args["id"])) {
        $embed=sprintf('<object classid=clsid:D27CDB6E-AE6D-11CF-96B8-444553540000 '.
        'codebase=http://download.macromedia.com/pub/shockwave/cabs'.
        '/flash/swflash.cab#version=6,0,40,0 width=450 height=359 >'.
        '<param name=movie value=http://share.youthwant.com.tw/r?m=%d />'.
        '<param name=wmode value=transparent />'.
        '<embed src=http://share.youthwant.com.tw/r?m=%d '.
        'type=application/x-shockwave-flash wmode=transparent '.
        'width=450 height=359 /></object>', $args["id"], $args["id"]);
        return _discuzcode_video_template($embed, $link, $string, 450);
      }
    break;
    case (preg_match('/\.(rm|rmvb)$/i', basename(strtolower($url["path"])))): 
      $embed=sprintf('<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="300" autostart="false" '.
      'controls="imagewindow" nojava="true" '.
      'console="c1183760810807" '.
      'pluginspage="http://www.real.com/"></embed><br>'.
      '<embed type="audio/x-pn-realaudio-plugin" '.
      'src="%s" '.
      'width="400" height="26" autostart="false" '.
      'nojava="true" controls="ControlPanel" '.
      'console="c1183760810807"></embed>', $link, $link);
      return _discuzcode_video_template($embed, $link, $string, 400);
    break;
    case preg_match('/www\.mtvjapan\.com/', strtolower($url["host"])):
      if (preg_match('/^\/flvplayer\/mcmsplayer\.swf$/', $url["path"])) {
        $embed=sprintf('<object classid=clsid:D27CDB6E-AE6D-11CF-96B8-444553540000 '.
        'codebase=http://download.macromedia.com/pub/shockwave/cabs'.
        '/flash/swflash.cab#version=6,0,40,0>'.
        '<embed src=%s '.
        'type=application/x-shockwave-flash '.
        'wmode=transparent width=650 height=338/></object>', $link, $link);
        return _discuzcode_video_template($embed, $link, $string, 650);
      }
    break;
    case preg_match('/movies\.ign\.com/', strtolower($url["host"])):
      if (preg_match('/^\/dor\/articles\/[0-9]+\/.+?\/videos\/.+?\.html$/', $url["path"])) {
        $regex = '/^\/dor\/articles\/([0-9]+)\/.+?\/videos\/(.+?)\.html$/';
        $article_id      = preg_replace($regex, '$1', $url["path"]);
        $article_id_misc = substr($article_id, 0, 3);
        $download_url    = sprintf("%s_flvlowwide.flv", preg_replace($regex, '$1', $url["path"]));
        $embed = sprintf("<embed src='http://videomedia.ign.com/ev/ev.swf' ".
        "flashvars='article_ID=%d&downloadURL=".
        "http://moviesmovies.ign.com/movies/video/article/%d/%d/%s&allownetworking=\"all\" ".
        "type='application/x-shockwave-flash' width='433' height='360'></embed>",
        $article_id, $article_id_misc, $article_id, $download_url);
        return _discuzcode_video_template($embed, $link, $string, 433);
      }
    break;
    case preg_match('/you\.video\.sina\.com\.cn/', strtolower($url["host"])):
      $regex = '/^\/b\/([0-9]+?)-([0-9]+?)\.html$/';
      if (preg_match($regex, $url["path"])) {
        $vid = preg_replace($regex, "$1", $url["path"]);
        $uid = preg_replace($regex, "$2", $url["path"]);
        $embed = sprintf('<object id="ssss" width="480" height="370" >'.
        '<param name="allowScriptAccess" value="always" />'.
        '<embed pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'src="http://vhead.blog.sina.com.cn/player/outer_player.swf?'.
        'auto=0&vid=%d&uid=%d" '.
        'type="application/x-shockwave-flash" name="ssss" '.
        'allowFullScreen="true" '.
        'allowScriptAccess="always" width="480" height="370">'.
        '</embed></object>', $vid, $uid);
        return _discuzcode_video_template($embed, $link, $string, 480);
      }
    break;
  }
  
  return $matches[0];
}

function _discuzcode_video_ignvideo_callback($matches) {
  parse_str(htmlspecialchars_decode($matches[1]), $args); 
  $ids = (empty($args["article_ID"]))? "" : "article_ID={$args["article_ID"]}";
  $ids = (empty($ids) && !empty($args["object_ID"])) ? 
         "object_ID={$args["object_ID"]}" : $ids;
  $download_url    = $args["downloadURL"];
  $link    = "http://media.video.ign.com/ev/ev.html?dlURL=$download_url&$ids";
  $string  = "http://www.ign.com";
  $embed = sprintf("<embed src='http://videomedia.ign.com/ev/ev.swf' ".
  "flashvars='%s&downloadURL=%s&allownetworking=\"all\"' ".
  "type='application/x-shockwave-flash' width='433' height='360'></embed>",
  $ids, $download_url);
  return _discuzcode_video_template($embed, $link, $string, 433, 380);
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

function _discuzcode_video_html_callback($matches) {
  // ** this function is experimental **
  // handles [video] [/video] html embed directly
 
  // change html entity back to html
  $embed = trim(html_entity_decode($matches[1]));
  
  // extract non-embed-code as text
  $text  = str_replace("\n", "<br />",
    trim(preg_replace("/(\<br\>|\<br\/\>|\<br[ ]+\/\>)/i", "", strip_tags($embed, '<a>'))));
  $text  = preg_replace("/[ \t]+/", " ", $text);
  
  // process spaces in the video embed
  $embed = str_replace(array("\n", "\r"), "", $embed);
  $embed = preg_replace("/((?<=\>)[ \t]+)/", "", $embed);
  
  // remove possible vulernable code in the video embed
  $embed = strip_tags($embed, '<object><param><embed>');

  // remove text before or after embed code
  if (!preg_match("/.+?\>$/", $embed)) $embed = preg_replace("/(.*\>).+?$/", "$1", $embed);
  if (!preg_match("/^<.+?/", $embed))  $embed = preg_replace("/^.+?(\<.*)/", "$1", $embed);
  
  // experimental: check, in the embed code, the width of it
  preg_match("/width=\"([0-9]+)\"/", $embed, $result);
  if (!empty($result)) {
    $width = $result[1];
    if (!empty($text)) {
      return _discuzcode_video_template($embed, False, $text, $width);
    } else {
      return _discuzcode_video_template($embed, False, False, $width);
    }
  } else {
    return "<div class=\"video\">$embed</div>";
  }
}
