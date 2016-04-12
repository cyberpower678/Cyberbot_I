<?php

/**
 * Bootstrap for bookreports.
 * @author Josh
 * @version 0.3
 * @copyright Copyright (c) 2012, Josh Grant. Creative commons BY-NC 3.0 license.
 *
 * Bootstrapper to auto-execute a bookreports queue.
 */

date_default_timezone_set('UTC');

require 'lib/misc.php';
require 'api/wapi.php';
require 'lib/exceptions.php';
require 'lib/class_BookReport.php';
require 'var/en.php';
require 'var/db.php';
global $m;
require 'var/cache.php';
require 'var/log.php';

if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	botdie("Running on an outdated PHP_VERSION: ".PHP_VERSION);
}
$b = new wikibot();
$b->login($user, $consumerKey, $consumerSecret, $accessToken, $accessSecret)or botdie('Failed to login');

if (!isset($m)) $m = mysql_connect($db_host, $db_user, $db_pass)or botdie('Failed to connect to MySQL');

if ($log) {
	$_log = true;
	$_logDir = $logd;
	$_logPointer = fopen($_logDir."CB-BR-".date('d-m-y').".log", 'a');
}

$tlist = unserialize(file_get_contents("var/templates.dat"));

$args = checkargs();
out("Cyberbot I starting BookReport queue. Task version v".BookReport::version);

if (!$args['nots']) {
$b->edit("Template:Book_report_time", date("H:i\, d F Y \(\U\T\C\)")."<noinclude>
{{Documentation|content=
[[Category:Wikipedia bot-related templates]] 
}}</noinclude>", "Updating timestamp", null, true, true);
}

$books = $b->categorymembers('Category:Wikipedia books (community books)')or botdie("Failed to enumerate category list.");
##$books = array("Book:Backstreet Boys"); # Book:Yoga
foreach($books as $book) {
	if ($args['startat'] != false && $book != $args['startat']) continue;
	elseif ($args['startat'] != false && $book == $args['startat']) {
		$args['startat'] = false;
		out("Resuming from book $book...");
	}
	out("Generating report for ".$book."...");
	try {
		$bookobj = new BookReport($book, $b, $tlist, $m);
		$bookobj->postReport();
	}
	catch (Exception $e) {
		out("Encountered an error: ".$e->getMessage()." in ".$e->getFile()." on line ".$e->getLine(), 1);
		out($e->getTraceAsString(), 1);
	}
}
?>
