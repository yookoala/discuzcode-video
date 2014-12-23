<?php

/**
 * @file common.func.php
 *
 * All the commonly used functions are defined here.
 */


/***********************************************************
* simplified interface
************************************************************/

function _discuzcode_video_replace($message) {

  // basic url to video support
  $message=preg_replace_callback("/\[url\](.+?)\[\/url\]/i",
    '_discuzcode_video_callback', $message);
  $message=preg_replace_callback("/\[url=(https?|ftp){1}:\/\/([^\[\"']+?)\](.+?)\[\/url\]/is",
    '_discuzcode_video_callback', $message);

  // ignvideo
  $message=preg_replace_callback("/\[ignvideo\](.+?)\[\/ignvideo\]/i",
    '_discuzcode_video_ignvideo_callback', $message);

  // [video] bbcode support
  $message=preg_replace_callback("/\[video\](.+?)\[\/video\]/is", 
    "_discuzcode_video_html_callback", $message);

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
  
  switch (TRUE) {

    case ($embed = Widgetarian\Widgetfy\Translator::translate($link)) !=NULL:
      return _discuzcode_video_template(
        $embed['html'], $embed['link'], $string, $embed['width'], $embed['height']);
    break;
    case (strtolower($url["host"])=='www.twitvid.com'):
      if (preg_match("/^\/[A-Z0-9]{5}?/", $url["path"])) {
        $vid = preg_replace("/^\/([A-Z0-9]{5})?/", "$1", $url["path"]);
        $embed = sprintf('<iframe title="Twitvid video player" class="twitvid-player" '.
          'type="text/html" width="624" height="468" '.
          'src="//www.twitvid.com/embed.php?guid=%s&autoplay=0" frameborder="0"></iframe>', $vid);
        return _discuzcode_video_template($embed, $link, $string);
      }
    break;
    case (strtolower($url["host"])=='www.facebook.com'):
      $args = FALSE;
      if ($url["path"] == "/video/video.php") {
        parse_str($url["query"], $args); 
      } elseif (preg_match("/^\!\/video\/video\.php\?/", $url["fragment"])) {
        parse_str(preg_replace("/^\!\/video\/video\.php\?/", "", $url["fragment"]), $args);
      } elseif ($url["path"] == "/video.php") {
        parse_str($url["query"], $args); 
      } elseif (preg_match("/^\!\/video\.php\?/", $url["fragment"])) {
        parse_str(preg_replace("/^\!\/video\/video\.php\?/", "", $url["fragment"]), $args);
      } elseif ($url["path"] == "/photo.php") {
        parse_str($url["query"], $args);
      } elseif (preg_match("/^\!\/photo\.php\?/", $url["fragment"])) {
        parse_str(preg_replace("/^\!\/photo\.php\?/", "", $url["fragment"]), $args);
      }

      if ($args !== FALSE) {
        if (isset($args["v"])) {
          $vid = $args["v"];
          $embed = sprintf('<div id="fb-root"></div> <script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/zh_HK/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>
<div class="fb-post" data-href="https://www.facebook.com/video.php?v=%s" data-width="466"></div>
', $vid);

          return _discuzcode_video_template($embed, $link, $string, 466);
        }
      }
    break;
    case (strtolower($url["host"])=='nicovideo.jp'):
    case preg_match('/[a-z]+?\.nicovideo\.jp/', strtolower($url["host"])):
      if (preg_match('/\/watch\/sm\d+/', $url["path"])) {
        $vid = preg_replace('/\/watch\/sm(\d+)/', '$1', $url["path"]);
        $locale = preg_replace('/([a-z]+?)\.nicovideo\.jp/', '$1', strtolower($url["host"]));
        $embed = sprintf('<iframe width="648" height="217" '.
        'src="//tw.nicovideo.jp/thumb/sm%s" scrolling="no" '.
        'style="border:solid 1px #CCC;" frameborder="0"></iframe>', $vid);
        if (($locale == "tw") || ($string !== $link)) return _discuzcode_video_template($embed, $link, $string, 650, 220);
        return _discuzcode_video_template($embed, FALSE, FALSE, 650, 220);
      }
    break;
    case (strtolower($url["host"])=='veoh.com'):
    case preg_match('/[a-z]+?\.veoh.com/', strtolower($url["host"])):
      if (preg_match('/^\/watch\/\w+$/', $url["path"])) {
        $vid = preg_replace('/^\/watch\/(\w+)/', '$1', $url["path"]);
        $embed = sprintf('<object width="615" height="512" id="veohFlashPlayer" '.
          'name="veohFlashPlayer"><param name="movie" '.
          'value="//www.veoh.com/swf/webplayer/WebPlayer.swf?'.
          'version=AFrontend.5.7.0.1396&permalinkId=%s&'.
          'player=videodetailsembedded&videoAutoPlay=0&id=anonymous">'.
          '</param><param name="allowFullScreen" value="true">'.
          '</param><param name="allowscriptaccess" value="always"></param>'.
          '<embed src="//www.veoh.com/swf/webplayer/WebPlayer.swf?'.
          'version=AFrontend.5.7.0.1396&permalinkId=%s&'.
          'player=videodetailsembedded&videoAutoPlay=0&id=anonymous" '.
          'type="application/x-shockwave-flash" '.
          'allowscriptaccess="always" allowfullscreen="true" '.
          'width="615" height="512" id="veohFlashPlayerEmbed" '.
          'name="veohFlashPlayerEmbed"></embed></object>', $vid, $vid);
        return _discuzcode_video_template($embed, FALSE, FALSE, 615, 512);
      }
    break;

    case (preg_match('/^(player|v)\.youku\.com$/i', $url["host"])):

      if (strtolower($url["host"])=='player.youku.com') {
        //$regex = '/^\/player\.php\/sid\/([a-zA-Z0-9]+)\=\/v.swf$/';
        $regex = '/^\/player\.php\/sid\/([a-zA-Z0-9]+)(\=\/v\.swf|\/v\.swf)$/';
      } elseif (strtolower($url["host"])=='v.youku.com') {
        $regex = '/^\/v_show\/id_(.+?)(\=|)\.html/';
      }

      if (preg_match($regex, $url["path"])) {
        $sid = preg_replace($regex, '$1', $url["path"]);
        if (strtolower($url["host"])=='player.youku.com') {
          if ($string == $link) 
            $string = "http://v.youku.com/v_show/id_{$sid}=.html";
          $link = "http://v.youku.com/v_show/id_{$sid}=.html";
        }
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
      $embed = sprintf('<embed src="http://www.tudou.com/v/%s/v.swf" '.
         'type="application/x-shockwave-flash" allowscriptaccess="always" '.
         'allowfullscreen="true" wmode="opaque" width="480" height="400"></embed>',
         $video_id
      );
      return _discuzcode_video_template($embed, $link, $string, 480);
    } elseif (preg_match('/^\/playlist\/id\/.+?\/$/', $url["path"])) {
      $video_id=preg_replace('/^\/playlist\/id\/(.+?)\/$/', '$1', $url["path"]);
      $embed = sprintf('<object width="488" height="423"><param name="movie" '.
      'value="http://www.tudou.com/player/playlist.swf?lid=%s"></param>'.
      '<param name="allowscriptaccess" value="always">'.
      '<embed src="http://www.tudou.com/player/playlist.swf?lid=%s" '.
      'type="application/x-shockwave-flash" width="488" height="423"></embed>'.
      '</object>', $video_id, $video_id);
      return _discuzcode_video_template($embed, $link, $string);
      
    } elseif (preg_match('/^\/playlist\/playindex.do$/', $url["path"])) {
      parse_str($url["query"], $args);
      if (!empty($args['lid'])) {
        $embed = sprintf('<object width="546" height="472">'.
        '<param name="movie" value="http://www.tudou.com/l/%s"></param>'.
        '<param name="allowFullScreen" value="true"></param>'.
        '<param name="allowscriptaccess" value="always"></param>'.
        '<param name="wmode" value="opaque"></param>'.
        '<embed src="http://www.tudou.com/l/%s"'.
        ' type="application/x-shockwave-flash"'.
        ' allowscriptaccess="always"'.
        ' allowfullscreen="true"'.
        ' wmode="opaque"'.
        ' width="546" height="472"></embed>'.
        '</object>', $args["lid"], $args["lid"]);

        return _discuzcode_video_template($embed, $link, $string);
      }
    }
    break;
    case (strtolower($url["host"])=='www.56.com'):
    if (preg_match('/^\/u\d+\/v_(\w+)\.html$/', $url["path"], $path_matches)) {
      $vid = $path_matches[1];
      $embed = '<embed src="http://player.56.com/cpm_'.$vid.'.swf" type="application/x-shockwave-flash" '.
        'width="560" height="470" allowfullscreen="true" allownetworking="all" allowscriptaccess="always"></embed>';
      return _discuzcode_video_template($embed, $link, $string);
    }
    break;
    case (strtolower($url["scheme"]) == "mms"):
    case (preg_match('/\.(wmv|avi|asx|mpg|mpeg)$/i', basename(strtolower($url["path"])))):
    case (preg_match('/^uploaded_videos\.php$/i', basename(strtolower($url["path"])))):
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
    case (preg_match('/\.(ogg)$/i', basename(strtolower($url["path"])))):
      $embed = sprintf('<video width=600 src="%s" controls=true>Sorry, your browser has the following problem(s):
<ul><li>It does not support playing <a href="http://www.theora.org/" target="_blank">OGG Theora</a>; or</li>
<li>It does notthe HTML5 &lt;video&gt; element.</li></ul> Please upgrade to a browser such as <a
href="http://www.getfirefox.com">Firefox 3.6</a>.</video>', $link);
      return _discuzcode_video_template($embed, $link, $string, 600);
    break;
    case preg_match('/vimeo\.com/', strtolower($url["host"])):
      $regex = '/^\/([0-9]+)$/';
      if (preg_match($regex, $url["path"])) {
        $vid = preg_replace($regex, "$1", $url["path"]);

        // try to retrieve api respond with the help of cache
        $cache = _local_file_cache_get("cache_discuzcode_vimeoapi", $vid);
        if ($cache["has_cache"] === FALSE) {
          $api_respond = file_get_contents("//vimeo.com/api/v2/video/$vid.php");
          $api_respond = unserialize($api_respond);
          _local_file_cache_set("cache_discuzcode_vimeoapi", $vid, $api_respond);
        } else {
          $api_respond = $cache["value"];
        }

        if (($api_respond !== FALSE) && !empty($api_respond)) {
          $width  = !empty($api_respond[0]["width"]) ? $api_respond[0]["width"] : 600;
          $height = !empty($api_respond[0]["height"]) ? $api_respond[0]["height"] : 340;
          if ($width > 800) {
            $height = ceil((800 / $width) * $height);
            $width = 800;
          }

         $embed = sprintf('<iframe src="//player.vimeo.com/video/%s" width="%d" height="%d" '.
            'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
            $vid, $width, $height);

          return _discuzcode_video_template($embed, $link, $string, $width);
        }
      }
    break;
    case preg_match('/www\.sonypictures\.com/', strtolower($url["host"])):
      // http://www.sonypictures.com/previews/movies/thekaratekid/clips/1580/
      $regex = '/^\/previews\/movies\/(.+?)\/clips\/([0-9]+|[0-9]+\/)$/';
      if (preg_match($regex, $url["path"])) {
        $movie_hash = preg_replace($regex, "$1", $url["path"]);
        $vid        = preg_replace($regex, "$2", $url["path"]);
        $width      = 600; // original 400
        $height     = 338; // original 225
        $embed = "<object width='$width' height='$height' ".
        "id='flash58974' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'>".
        "<param name='movie' value='//flash.sonypictures.com/video/universalplayer/sharedPlayer.swf'></param>".
        "<param name='allowFullscreen' value='true'></param>".
        "<param name='allowNetworking' value='all'></param>".
        "<param name='allowScriptAccess' value='always'></param>".
        "<param name='flashvars' value='clip=$vid&feed=http%3A//www.sonypictures.com/previews/movies/$movie_hash.xml'></param>".
        "<embed src='//flash.sonypictures.com/video/universalplayer/sharedPlayer.swf' ".
        "width='$width' height='$height' type='application/x-shockwave-flash' ".
        "flashvars='clip=$vid&feed=http%3A//www.sonypictures.com/previews/movies/$movie_hash.xml' ".
        "allowNetworking='all' allowscriptaccess='always' allowfullscreen='true'></embed></object>";
        return _discuzcode_video_template($embed, $link, $string, $width);
      }
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
    case preg_match('/www\.kickstarter\.com/', strtolower($url["host"])):
      if (preg_match('/^\/projects\/.+?$/', $url["path"])) {
        $path=preg_replace('/^\/projects\/(.+?)$/', '$1', $url["path"]);
       $embed=sprintf('<iframe width="640" height="480" '.
          'src="//www.kickstarter.com/projects/%s/widget/video.html" '.
          'frameborder="0" scrolling="no"> </iframe> '.
          '<iframe width="220" height="480" '.
          'src="//www.kickstarter.com/projects/%s/widget/card.html" '.
          'frameborder="0" scrolling="no"> </iframe>',
          $path, $path);
        return $embed;
      }
    break;
    case preg_match('/www\.dailymotion\.com/', strtolower($url["host"])):
      if (preg_match('/^\/video\/.+?_.+?$/', $url["path"])) {
        $id=preg_replace('/^\/video\/(.+?)_.+?$/', '$1', $url["path"]);
        $embed=sprintf('<object width="420" height="339">'.
        '<param name="movie" value="//www.dailymotion.com/swf/%s" />'.
        '<param name="allowFullScreen" value="true" />'.
        '<param name="allowScriptAccess" value="always" />'.
        '<embed src="//www.dailymotion.com/swf/%s" '.
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
        $embed=sprintf('<embed src="//www.metacafe.com/fplayer/%s.swf" '.
        'width="400" height="345" wmode="transparent" '.
        'pluginspage="http://www.macromedia.com/go/getflashplayer" '.
        'type="application/x-shockwave-flash"></embed>', $hash);
        return _discuzcode_video_template($embed, $link, $string, 400);
      } elseif (preg_match('/^\/fplayer\/\d+\/.+\.swf$/', $url["path"])) {
        $hash=preg_replace('/^\/fplayer\/(.+?)\.swf$/', '$1', $url["path"]);
        $hash=preg_replace("/\/$/", '', $hash);
        $embed=sprintf('<embed src="//www.metacafe.com/fplayer/%s.swf" '.
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
        'data="//www.collegehumor.com/moogaloop/'.
        'moogaloop.swf?clip_id=%d&fullscreen=1" '.
        'width="480" height="360" ><param name="allowfullscreen" value="true" />'.
        '<param name="movie" quality="best" value="//www.collegehumor.com/'.
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
        'data="//www.collegehumor.com/moogaloop/'.
        'moogaloop.swf?clip_id=%d&fullscreen=1" '.
        'width="480" height="360" ><param name="allowfullscreen" value="true" />'.
        '<param name="movie" quality="best" value="//www.collegehumor.com/'.
        'moogaloop/moogaloop.swf?clip_id=%d&fullscreen=1" /></object>',
        $clipid, $clipid);
        return _discuzcode_video_template($embed, $link, $string, 480);
       }
    break; 
    case preg_match('/[a-z]+?\.dorkly\.com/', strtolower($url["host"])):
      $regex='/^\/video\/(\d+)\/(.+?)$/';
      if (preg_match($regex, $url["path"])) {
        $clipid=preg_replace($regex, '$1', $url["path"]);
        $embed=sprintf('<iframe src="//www.dorkly.com/e/%s" '.
          'width="600" height="338" frameborder="0" '.
          'webkitAllowFullScreen allowFullScreen></iframe>',
          $clipid);
        return _discuzcode_video_template($embed, $link, $string, 600);
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
        'value="//www.builderau.com.au/video/embed/%d"></param></param>'.
        '<param name="allowfullscreen" value="true"></param>'.
        '<embed src="//www.builderau.com.au/video/embed/%d" '.
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
        'value="//www.gametrailers.com/remote_wrap.php?mid=%d"/>'.
        '<param name="quality" value="high" /> '.
        '<embed src="//www.gametrailers.com/remote_wrap.php?mid=%d" '.
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
      'pluginspage="//www.real.com/"></embed><br>'.
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
        $embed = sprintf("<embed src='//videomedia.ign.com/ev/ev.swf' ".
        "flashvars='article_ID=%d&downloadURL=".
        "//moviesmovies.ign.com/movies/video/article/%d/%d/%s&allownetworking=\"all\" ".
        "type='application/x-shockwave-flash' width='433' height='360'></embed>",
        $article_id, $article_id_misc, $article_id, $download_url);
        return _discuzcode_video_template($embed, $link, $string, 433);
      } elseif (preg_match('/^\/dor\/objects\/[0-9]+\/.+?\/videos\/.+?\.html$/', $url["path"])) {
        $regex = '/^\/dor\/objects\/([0-9]+)\/.+?\/videos\/(.+?)\.html$/';
        $object_id = preg_replace($regex, '$1', $url["path"]);
        $vgroup_id = preg_replace($regex, '$2', $url["path"]);
        $embed = sprintf("<object id='ignplayer' width='480' height='270' ".
        "data='//media.ign.com/ev/embed.swf' type='application/x-shockwave-flash'>".
        "<param name='movie' value='//media.ign.com/ev/embed.swf' />".
        "<param name='allowfullscreen' value='true' />".
        "<param name='allowscriptaccess' value='always' />".
        "<param name='bgcolor' value='#000000' />".
        "<param name='flashvars' value='vgroup=%s&object=%s'/>".
        "</object>",
        $vgroup_id, $object_id);
        return _discuzcode_video_template($embed, $link, $string);
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
    case strtolower($url["host"]) == 'tv.on.cc':
    
      // parse flashvars of tv.on.cc player
      parse_str($url['query'], $args);
      $t = floor(microtime(TRUE) * 1000);
      $mid = substr($args['i'], 0, -1) . strtolower(substr($args['i'], -1, 1));
      $flashvars = array(
        'today' => date('Ymdhis'),
        'tvc'   => 1,
        'playMode' => 0,
        'autoplay' => 0,
        'bumper' => 0,
        'theme' => 'white',
        'mid'   => $mid,
        'mdate' => $args['d'],
        'msect' => $args['s'],
        'ssect' => $args['ss'],
        'tvcCount' => 0,
      );
      $flashvars_query = http_build_query($flashvars);

      // compile the embed code
      $embed = sprintf('<object id="player_flash" width="600" height="381" '.
        'name="player_flash" type="application/x-shockwave-flash" '.
        'data="http://tv.on.cc/player.swf?t=%d&msect=%d&ssect=%d" '.
        'style="visibility: visible;">'.
        '<param name="allowFullScreen" value="true">'.
        '<param name="allowScriptAccess" value="always">'.
        '<param name="wmode" value="opaque">'.
        '<param name="hasPriority" value="true">'.
        '<param name="flashvars" '.
        'value="%s">
      </object>',
        $t, $flashvars['msect'], $flashvars['ssect'], $flashvars_query
      );
      return _discuzcode_video_template($embed, $link, $string, 600);
    break;
    case strtolower($url["host"]) == 'vlog.xuite.net':
      if (preg_match('/^\/play\/(\w+\=\=)$/', $url['path'], $path_matches)) {
        $embed = '<iframe marginwidth="0" marginheight="0" src="//vlog.xuite.net/embed/'.
          $path_matches[1].
          '?ar=0&as=0" width="640" height="360" scrolling="no" frameborder="0"></iframe>';
        return _discuzcode_video_template($embed, $link, $string, 640, 360);
      }
    break;
    case strtolower($url["host"]) == 'store.steampowered.com':
      if (preg_match('/^\/app\/(\d+)[\/]*$/', $url['path'], $path_matches)) {
        $embed = '<iframe src="//store.steampowered.com/widget/'.
          $path_matches[1].'/" width="646" height="190" frameborder="0"></iframe>';
        return _discuzcode_video_template($embed, $link, $string, 646, 190);
      }
    break;
  }
  
  return $matches[0];
}

/**
* interface for discuz or other program to use
* help turns [ignvideo] tags to ign video
*/
function _discuzcode_video_ignvideo_callback($matches) {
  parse_str(htmlspecialchars_decode($matches[1]), $args); 
  $ids = (empty($args["article_ID"]))? "" : "article_ID={$args["article_ID"]}";
  $ids = (empty($ids) && !empty($args["object_ID"])) ? 
         "object_ID={$args["object_ID"]}" : $ids;
  $download_url    = $args["downloadURL"];
  $link    = "http://media.video.ign.com/ev/ev.html?dlURL=$download_url&$ids";
  $string  = "http://www.ign.com";
  $embed = sprintf("<embed src='//videomedia.ign.com/ev/ev.swf' ".
  "flashvars='%s&downloadURL=%s&allownetworking=\"all\"' ".
  "type='application/x-shockwave-flash' width='433' height='360'></embed>",
  $ids, $download_url);
  return _discuzcode_video_template($embed, $link, $string, 433, 380);
}

/**
* interface for discuz or other program to use
* will change the text back to html if the tags are
* <object>, <param> or <embed>
*/
function _discuzcode_video_html_callback($matches) {
  
  // check local configurations
  if (_DISCUZCODE_VIDEO_TAG_SUPPORT_ === TRUE) {
   
    // change html entity back to html
    $embed = trim(html_entity_decode($matches[1]));
    
    // extract non-embed-code as text
    $text  = str_replace("\n", "<br />",
      trim(preg_replace("/(\<br\>|\<br\/\>|\<br[ ]+\/\>)/i", "", strip_tags($embed, '<a>'))));
    $text  = preg_replace("/[ \t]+/", " ", $text);
    
    // process spaces in the video embed
    $embed = str_replace(array("\n", "\r"), "", $embed);
    $embed = preg_replace("/((?<=\>)[ \t]+)/", "", $embed);

    // turn some miss placed smiley image back
    $embed = str_replace(array(
      '&lt<img src="images/smilies/default/titter.gif" smilieid="9" border="0" alt="" />',
      '<img src="images/smilies/default/biggrin.gif" smilieid="3" border="0" alt="" />',
    ), 
    array(
      "<p",
      ":D",
    ), $embed);

    // remove spacing to prevent parsing problem
    $embed = preg_replace("/ [ ]+/", " ", $embed);
    
    // remove possible vulernable code in the video embed
    $allowed = '<object><param><embed><video><source>';
    $embed = strip_tags($embed, $allowed);
  
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
}


/********************************************************
* helper functions
*********************************************************/


/**
* helper
* parse a ted.com video page for
* 1. download link of mp4 file of a talk (with opengraph meta)
* 2. thumbnail image of the video (with opengraph meta)
* 2. get the talk_id of a talk (from javascript in the page)
*
* note: there's another route - parse the javascript function
*       embedVideoPlayer(swfURL, version) in the page
*       there is an array variable 'flashVars' in it
*       that store all the parameters needed to generate
*       flash player in the page.
*
*       you man also get width and height of the video
*       there
*
* note2: not all variables are provided by javascript
*        the thumbnail url is unknown to it
*/
function _discuzcode_get_ted_video_stat($link) {

  // create video stat array
  $video_stat = array();
  
  // flag success
  $success = FALSE;

  /*
  $html = file_get_contents($link);
  preg_match('/flashVars[ \r\n\t]*\=[ \r\n\t]*\{(.+?)\};/s', $html, $matches1);
  if (!empty($matches1)) {
    preg_match('/si[ ]*\:[ ]*\"(.+?)\"/', $matches1[1], $matches2);
    if (!empty($matches2)) {
      $videos_stats = json_decode(urldecode($matches2[1]));
      if (!empty($videos_stats)) {
        // sort the video by bitrate, desc
        $sort_func = create_function('$a, $b', 'if ($a->bitrate == $b->bitrate) return 0; return ($a->bitrate < $b->bitrate) ? 1 : -1;');
        uasort($videos_stats, $sort_func);

        // use the one with the best bitrate
        $stat = (array) array_shift($videos_stats);
        var_dump($stat); exit;
      }
    }
  }

  var_dump($matches2);
  exit;
  */

  // suppress warning from DOMDocument
  $current_error_reporting = error_reporting(); error_reporting(E_ERROR | E_PARSE);

  // get and parse the html
  try {
    $html = file_get_contents($link);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
  } catch (Exception $e) {
    return FALSE;
  }
  $items = $doc->getElementsByTagName('meta');

  // revert error reporting
  error_reporting($current_error_reporting);

  // search each meta tags to find the one with property 'og:video'
  foreach ($items as $item) {
    if ($item->hasAttribute('property')) {
      if ($item->getAttribute('property') == 'og:video') {
        $video_stat["url"] = $item->getAttribute('content');
        if ($success) break; $success = TRUE;
      }
      if ($item->getAttribute('property') == 'og:image') {
        $video_stat["image"] = $item->getAttribute('content');
        if ($success) break; $success = TRUE;
      }
    }
  }

  // refine information
  if ($success) {

    // search for talk id from javascript
    $regex = "/\\$\.trackAction\('share', 'talks', '(\d+)'\)/";
    preg_match($regex, $html, $matches);
    $video_stat['talk_id'] = $matches[1];
  
    // get better thumbnail, if available
    $regex = '/^http:\/\/download\.ted\.com\/talks\/(.+?)\-(\d+k)\.mp4$/';

    if (preg_match($regex, $video_stat["url"])) {
  
      // suppress warning from file download
      $current_error_reporting = error_reporting(); error_reporting(E_ERROR | E_PARSE);

      $vid = preg_replace($regex, '$1', $video_stat["url"]);
      $image = "//images.ted.com/images/ted/tedindex/embed-posters/{$vid}-embed.jpg";

      if (($fh = fopen($image, "r")) !== FALSE) {
        $video_stat["image"] = $image;
      } else {
        $vid = str_replace('_', '-', $vid);
        $image = "//images.ted.com/images/ted/tedindex/embed-posters/{$vid}.embed_thumbnail.jpg";
        fclose($fh);
        if (($fh = fopen($image, "r")) !== FALSE) {
          $video_stat["image"] = $image;
        }
      }
  
      // revert error reporting
      error_reporting($current_error_reporting);

    }
 
    return $video_stat;
  }

  return FALSE;
}


/**
* helper, generates ted.com embed codes
*/
function _discuzcode_format_ted_embed_by_video_url($video_stat, $width, $height) {
  $regex = '/^http:\/\/download\.ted\.com\/talks\/(.+?)\-(\d+k)\.mp4$/';

  if (preg_match($regex, $video_stat["url"])) {
    $vw = $width  - 14;
    $vh = $height - 86;
    $flashvars = "vu={$video_stat["url"]}&su={$video_stat["image"]}&vw={$vw}&vh={$vh}&ti={$video_stat["talk_id"]}";

    $embed = "<object width=\"{$width}\" height=\"{$height}\">".
      "<param name=\"movie\" value=\"//video.ted.com/assets/player/swf/EmbedPlayer.swf\"></param>".
      "<param name=\"allowFullScreen\" value=\"true\" />".
      "<param name=\"allowScriptAccess\" value=\"always\"/>".
      "<param name=\"wmode\" value=\"transparent\"></param>".
      "<param name=\"bgColor\" value=\"#ffffff\"></param>".
      "<param name=\"flashvars\"".
      " value=\"{$flashvars}\" />".
      "<embed src=\"//video.ted.com/assets/player/swf/EmbedPlayer.swf\"".
      " pluginspace=\"http://www.macromedia.com/go/getflashplayer\"".
      " type=\"application/x-shockwave-flash\"".
      " wmode=\"transparent\"".
      " bgColor=\"#ffffff\"".
      " width=\"{$width}\" height=\"{$height}\"".
      " allowFullScreen=\"true\" allowScriptAccess=\"always\"".
      " flashvars=\"{$flashvars}\"></embed>".
      "</object>";

    return $embed;
  }
  return FALSE;

}


/**
* helper function. get the url of this file
*/
function _discuzcode_video_script_url() {
  static $url;
  if (!isset($url)) {
    $regex = sprintf("/^%s/", preg_quote($_SERVER["DOCUMENT_ROOT"], "/"));
    $url = preg_replace($regex, "", __FILE__);
    if ($url == __FILE__) $url = FALSE;
  }
  return $url;
}

/**
* helper function make a non-unicode string shortter
*/
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

/**
* helper function
* file cache for api call results
*/
function _local_file_cache_get($prefix, $id) {
  $cache_filename = str_replace('/', '', $prefix."_".md5($id));
  if (file_exists("/tmp/$cache_filename")) {
    return array(
      "has_cache"=>TRUE,
       "value"=>unserialize(file_get_contents("/tmp/$cache_filename")));
  }
  return array("has_cache"=>FALSE);
}

/**
* helper function
* file cache for api call results
*/
function _local_file_cache_set($prefix, $id, $value) {
  $result = FALSE;
  $cache_filename = str_replace('/', '', $prefix."_".md5($id));
  $fh = fopen("/tmp/$cache_filename", "w+");
  if ($fh !== FALSE) $result = fwrite($fh, serialize($value));
  fclose($fh);
  chmod("/tmp/$cache_filename", 0600);
  return $result;
}

/********************************************************
* language
*********************************************************/

function t($string, $locale="zh-tw") {
  static $lang;

  if (!isset($lang)) {
    $lang["zh-tw"]["Source"] = "來源";
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

  // if the video string = video link
  if (($text==$link) || ($text == False)) {
    $text = _discuzcode_string_trim($text, 45); // make the link shorter here
  }
  
  // experimental: check, in the embed code, the width of it
  preg_match("/width=\"([0-9]+)\"/", $embed, $result); $width_default = 480;
  $width=($width===False) ? (!empty($result) ? $result[1] : $width_default) : $width;
  $heightcode=($height===False) ? "":" height: {$height}px;";
  $source_text = t("source"); 
 
  if (($link===False) AND ($text===False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px;{$heightcode} border: solid 1px #000; background: #CCC;">
  <div style="background: #000;">$embed</div>
</div>
CODEBLOCK;
  } elseif (($link===False) AND ($text!==False)) {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #000; background: #CCC;">
  <div style="background: #000;{$heightcode}">$embed</div>
  <div style="margin: 2px 4px; overflow-x: hidden;">$text</div>
</div>
CODEBLOCK;
  } else {
    $codeblock = <<<CODEBLOCK
<div class="videoblock" style="width: {$width}px; border: solid 1px #000; background: #CCC;">
  <div style="background: #000;{$heightcode}">$embed</div>
  <div style="margin: 2px 4px; overflow-x: hidden;">$source_text: <a href="$link" style="color: #E00;" target="_blank">$text</a></div>
</div>
CODEBLOCK;
  }
  return str_replace(array("\r", "\n  ", "\n"), array("", "", ""), $codeblock);
}


