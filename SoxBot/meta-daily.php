<?PHP

ini_set('memory_limit','16M');

require_once( '/data/project/cyberbot/Peachy/Init.php' );

$site = Peachy::newWiki( "meta" );

$site->set_runpage("User:Cyberbot_I/Run/Meta-daily");

date_default_timezone_set('UTC');//Use UTC time.

//Define the text
$sandboxtext = "{{/Please do not edit this line}}\n== Please edit below ==";
$hour = date("%H"); 
$minute = date("%i");

$currtext = initPage('Meta:Sandbox');
$currtext->edit($sandboxtext,'Clearing the sandbox (BOT EDIT)');
