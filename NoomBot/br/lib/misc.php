<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	if ($errno == 8) {
		out($errstr.' in '.$errfile.' line '.$errline, 2);
		#print_r(trimlong(debug_backtrace())); # dbg
	}
	else if ($errno == 2) out("Please FIX: there's a null variable somewhere in {$errfile}:{$errline}", 2);
	else {
		# throw exception and halt execution
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}
set_error_handler("exception_error_handler");

function checkLogPointer() {
	global $_log;
	global $_logPointer;
	global $_logFileName;
	global $_logDir;
	global $_logLevel;

	if ($_log) {
		$tmpfn = $_logDir."CB-BR-".date('d-m-y').".log";
		if ($tmpfn != $_logFileName) {
			$_logFileName = $tmpfn;
			$_logPointer = null;
			$_logPointer = fopen($_logFileName, 'a');
			flock($_logPointer, LOCK_EX);
			fwrite($_logPointer, "[GENERAL] Cyberbot I; log started, logging level $_logLevel".PHP_EOL);
		}
	}
}

function out($msg, $priority = 3) {
	global $_forked;
	global $_logPointer;
	global $_logLevel;
	global $_log;
	global $db_pass;
	global $pass;

	$msg = str_replace($db_pass, 'redacted', $msg);
	$msg = str_replace($pass, 'redacted', $msg);
	$msg = str_replace('josh', 'redacted', $msg);
	
	$p = "";
	if ($priority == 1) { $p = "[ERROR]"; }
	elseif ($priority == 2) { $p = "[WARNING]"; }
	elseif ($priority == 3) { $p = "[MSG]"; }
	elseif ($priority == 4) { $p = "[DEBUG]"; }
	else { $p = "[MESSAGE]"; }
	$msg = preg_replace('/\n/', "\n$p ", $msg);
	if (!$_forked) echo $p." ".$msg.PHP_EOL;
	if ($_log == true && $priority <= $_logLevel) {
		checkLogPointer();
		fwrite($_logPointer, $p." ".$msg.PHP_EOL);
	}
}
function botdie($why) {
	ob_start();
	debug_print_backtrace();
	$debug = ob_get_clean();
	out("Bot encountered a fatal error:", 1);
	out($why, 1);
	out("Stack trace:", 1);
	out($debug, 1);
	die();
}
function checkargs() {
	global $argv;
	global $argc;

	$opts = array('nots' => false, 'startat' => false);
	if (in_array('nots', $argv)) {
		$opts['nots'] = true;
	}
	$k = array_search('startat', $argv);
	if ($k != false) {
		$opts['startat'] = $argv[$k+1];
	}

	return $opts;
}

function sanitize($string) {
	$string = preg_replace('/={2,3}(.+)={2,3}/', '$1', $string);
	$string = preg_replace('/\[\[(.+)\]\]/', '$1', $string);
	$string = preg_replace('/;(.+)/', '$1', $string);
	return $string;
}

function realLink($piped) {
	if (strpos($piped, '|') != false) {
		$e = explode($piped);
		return $e[0];
	}
	else return $piped;
}

function concat($array) {
	$str = "";
	foreach ($array as $val => $junk) {
		if ($str != "") $str .= '|';
		$str .= $val;
	}
	return $str;
}

function valcheck(&$val, &$key) {
	if (is_string($val)) {
		if (strlen($val) > 64) {
			$val = substr($val, 0, 64).'...';
		}
		str_replace(array('\n', PHP_EOL), '', $val);
	}
	if (is_string($key)) {
		if (strlen($key) > 64) {
			$key = substr($key, 0, 64)."...";
		}
		str_replace(array('\n', PHP_EOL), '', $key);
	}
}

function trimlong($array) {
	array_walk_recursive($array, 'valcheck');
	return $array;
}

function check_redirect($body) {
	if (preg_match('/^#REDIRECT (\[\[.*\]\]/)', $body, $m)) {
		$r = sanitize($r);
		$r = realLink($r);
		return $r;
	}
	else return FALSE;
}


?>
