<?php

/**
 * @file common.inc.php
 *
 * Main include file for usage
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

// discuz security check
if(!defined('IN_DISCUZ')) {
  exit('Access Denied');
}

// include the files
require_once __DIR__ . '/lib/widgetfy/autoload.php';
require_once __DIR__ . '/functions.inc.php';

// replace links with embed code
$message = yookoala\discuzcode\replace($message, array(
  'width' => 640, // You may change the default width here
));

