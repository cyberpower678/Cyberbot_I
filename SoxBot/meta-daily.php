<?PHP

ini_set('memory_limit','16M');

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "meta" );

$site->set_runpage("User:Cyberbot_I/Run/Meta-daily");

date_default_timezone_set('UTC');//Use UTC time.

//Define the text
$sandboxtext = "{{Meta:Sandbox/Please do not edit this line}}<!-- Please edit below this line. -->";
$hour = date("%H"); 
$minute = date("%i");

$currtext = initPage('Meta:Sandbox');
$currtext->edit($sandboxtext,'Clearing the sandbox (BOT EDIT)');
