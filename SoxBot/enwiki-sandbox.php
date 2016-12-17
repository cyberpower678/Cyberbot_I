<?PHP

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/Sandbox");

echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
date_default_timezone_set('UTC');//Use UTC time.

//Define the text
$sandboxtext = "{{subst:Template sandbox reset}}";

$hour = date("H"); 
$minute = date("i");

$pagetext = array (
	"Wikipedia:Sandbox"=>$sandboxtext,
    "User:Sandbox"=>$sandboxtext,  
);
if( ($hour == 00 || $hour == 12 || $hour  == 24) ) {
    $pagetext = array_merge( $pagetext, array (
        "Wikipedia talk:Sandbox"=>$sandboxtext,
        "User talk:Sandbox"=>$sandboxtext,
        "Template:X1"=>$sandboxtext,
        "Template:X2"=>$sandboxtext,
        "Template:X3"=>$sandboxtext,
        "Template:X4"=>$sandboxtext,
        "Template:X5"=>$sandboxtext,
        "Template:X6"=>$sandboxtext,
        "Template:X7"=>$sandboxtext,
        "Template:X8"=>$sandboxtext,
        "Template:X9"=>$sandboxtext,
        "Template:X10"=>$sandboxtext,
        "Template:X11"=>$sandboxtext,
        "Template:X12"=>$sandboxtext,
        "Template:X13"=>$sandboxtext,
        "Template:X14"=>$sandboxtext,
        "Template:X15"=>$sandboxtext,
        "Template:X16"=>$sandboxtext,
        "Template:X17"=>$sandboxtext,
        "Template:X18"=>$sandboxtext,
        "Template:X19"=>$sandboxtext,
        "Template:X20"=>$sandboxtext,
        "Template:Template sandbox"=>$sandboxtext,
        "Template talk:X1"=>$sandboxtext,
        "Template talk:X2"=>$sandboxtext,
        "Template talk:X3"=>$sandboxtext,
        "Template talk:X4"=>$sandboxtext,
        "Template talk:X5"=>$sandboxtext,
        "Template talk:X6"=>$sandboxtext,
        "Template talk:X7"=>$sandboxtext,
        "Template talk:X8"=>$sandboxtext,
        "Template talk:X9"=>$sandboxtext,
        "Template talk:X10"=>$sandboxtext,
        "Template talk:X11"=>$sandboxtext,
        "Template talk:X12"=>$sandboxtext,
        "Template talk:X13"=>$sandboxtext,
        "Template talk:X14"=>$sandboxtext,
        "Template talk:X15"=>$sandboxtext,
        "Template talk:X16"=>$sandboxtext,
        "Template talk:X17"=>$sandboxtext,
        "Template talk:X18"=>$sandboxtext,
        "Template talk:X19"=>$sandboxtext,
        "Template talk:X20"=>$sandboxtext,
        "Template talk:Template sandbox"=>$sandboxtext,
        "Wikipedia:Tutorial (Editing)/sandbox"=>$sandboxtext,
        "Wikipedia:Tutorial (Formatting)/sandbox"=>$sandboxtext,
        "Wikipedia:Tutorial (Wikipedia links)/sandbox"=>$sandboxtext,
        "Wikipedia:Tutorial (External links)/sandbox"=>$sandboxtext,
        "Wikipedia:Tutorial (Keep in mind)/sandbox"=>$sandboxtext,
        "Wikipedia talk:Tutorial (Editing)/sandbox"=>$sandboxtext,
        "Wikipedia talk:Tutorial (Formatting)/sandbox"=>$sandboxtext,
        "Wikipedia talk:Tutorial (Wikipedia links)/sandbox"=>$sandboxtext,
        "Wikipedia talk:Tutorial (External links)/sandbox"=>$sandboxtext,
        "Wikipedia talk:Tutorial (Keep in mind)/sandbox"=>$sandboxtext
    ));    
}
foreach( $pagetext as $pagetitle=>$text) {
	$page = initPage($pagetitle, null, false);
	echo "Time to clean $pagetitle!\n";
	$page->edit($text,'Clearing the sandbox ([[WP:BOT|BOT]] EDIT)');
}