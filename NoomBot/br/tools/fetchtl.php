<?php

require '/api/wapi.php';

$b = new wikibot();

require '/var/en.php';
$b->login($user, $pass)or botdie('Failed to login');

$a = array();
$tlpage = $b->getPage("User:Cyberbot I/TemplateList");
if (preg_match_all('/#(?: *)\{\{tl\|(.+)\}\}/', $tlpage, $m) > 0) {
	foreach ($m[1] as $t) {
		$a["Template:".$t] = true;
	}
}
$c = count($a);
$a = serialize($a);
$f = fopen("/var/templates.dat", 'w');
fwrite($f, $a);
fclose($f);
echo "Done, ".$c." bad templates loaded.".PHP_EOL;

?>