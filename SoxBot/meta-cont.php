<?PHP

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "meta" );

$site->set_runpage("User:Cyberbot_I/Run/Meta-cont");

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	date_default_timezone_set('UTC');//Use UTC time.

	//Define the text
	$sandboxtext = "{{Meta:Sandbox/Please do not edit this line}}<!-- Please edit below this line. -->";
	$hour = date("%H"); 
	$minute = date("%i");

	$currtext = initPage('Meta:Sandbox');
	if (strpos($currtext->get_text(), $sandboxtext) === false) {
		echo "Time to clean the sandbox!\n";
		$currtext->edit($sandboxtext,'Clearing the sandbox (BOT EDIT)');
	}
	else {
		echo "Sandbox still clean.\n";
	}
	sleep(300);
}
