<?PHP

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/Sandbox");

echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
date_default_timezone_set('UTC');//Use UTC time.

//Define the text
$sandboxtext = "{{subst:Sandbox reset}}";
$templatesandboxtext = "{{subst:Template sandbox reset}}";

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
        "Template:X1"=>$templatesandboxtext,
        "Template:X2"=>$templatesandboxtext,
        "Template:X3"=>$templatesandboxtext,
        "Template:X4"=>$templatesandboxtext,
        "Template:X5"=>$templatesandboxtext,
        "Template:X6"=>$templatesandboxtext,
        "Template:X7"=>$templatesandboxtext,
        "Template:X8"=>$templatesandboxtext,
        "Template:X9"=>$templatesandboxtext,
        "Template:X10"=>$templatesandboxtext,
        "Template:X11"=>$templatesandboxtext,
        "Template:X12"=>$templatesandboxtext,
        "Template:X13"=>$templatesandboxtext,
        "Template:X14"=>$templatesandboxtext,
        "Template:X15"=>$templatesandboxtext,
        "Template:X16"=>$templatesandboxtext,
        "Template:X17"=>$templatesandboxtext,
        "Template:X18"=>$templatesandboxtext,
        "Template:X19"=>$templatesandboxtext,
        "Template:X20"=>$templatesandboxtext,
        "Template:Template sandbox"=>$templatesandboxtext,
        "Template talk:X1"=>$templatesandboxtext,
        "Template talk:X2"=>$templatesandboxtext,
        "Template talk:X3"=>$templatesandboxtext,
        "Template talk:X4"=>$templatesandboxtext,
        "Template talk:X5"=>$templatesandboxtext,
        "Template talk:X6"=>$templatesandboxtext,
        "Template talk:X7"=>$templatesandboxtext,
        "Template talk:X8"=>$templatesandboxtext,
        "Template talk:X9"=>$templatesandboxtext,
        "Template talk:X10"=>$templatesandboxtext,
        "Template talk:X11"=>$templatesandboxtext,
        "Template talk:X12"=>$templatesandboxtext,
        "Template talk:X13"=>$templatesandboxtext,
        "Template talk:X14"=>$templatesandboxtext,
        "Template talk:X15"=>$templatesandboxtext,
        "Template talk:X16"=>$templatesandboxtext,
        "Template talk:X17"=>$templatesandboxtext,
        "Template talk:X18"=>$templatesandboxtext,
        "Template talk:X19"=>$templatesandboxtext,
        "Template talk:X20"=>$templatesandboxtext,
        "Template talk:Template sandbox"=>$templatesandboxtext,
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