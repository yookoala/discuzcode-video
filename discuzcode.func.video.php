<?php

/**
 * @file discuzcode.func.video.php
 *
 * This is a php script for Discuz! forum.
 * After install this script, your Discuz! forum will automatically turn
 * several video links into embed flash player. You don't have to give
 * provide users HTML capability and they still get inline video. The
 * site is more hack prove and everybody is happy.
 * requires PHP 5 or above
 * 
 * @author Koala Yeung
 * @version 7.x
 **/

/**
 * ------------
 * Installation
 * ------------
 * 1) login to your Discuz! installation FTP/SFTP
 * 2) goes to <your installation dir>/include
 * 3) open discuzcode.func.php
 * 4) find a function named 'discuzcode'
 * 5) inside the function, find a line that start with: 'if(!$bbcodeoff && $allowbbcode) {'
 * 6) before the line you found in step 5, add this line
 * 
 *     require(dirname(__FILE__).'/discuzcode-video/discuzcode.func.video.php');
 * 
 * 7) save and exit
 * 8) copy this folder to the <your installation dir>/include
 * 9) done. test it.
 **/

// configurations
// --------------
// If False, this script will not change [video] tag
// into html. This can prevent Flash to hijack your cookies
// If you trust your users enough, change it to TRUE
define('_DISCUZCODE_VIDEO_TAG_SUPPORT_', TRUE);

// discuz security check
if(!defined('IN_DISCUZ')) {
  exit('Access Denied');
}

// include the files
require_once __DIR__ . '/lib/widgetfy/autoload.php';
require_once __DIR__ . '/common.func.php';

// replace links with embed code
$message = _discuzcode_video_replace($message);
