<?PHP

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/Sandbox");

echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
date_default_timezone_set('UTC');//Use UTC time.

//Define the text
$sandboxtext = "{{Please leave this line alone (sandbox heading)}}<!--\n*               Welcome to the sandbox!              *\n*            Please leave this part alone            *\n*           The page is cleared regularly            *\n*     Feel free to try your editing skills below     *\n■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■-->";
$sandboxtalktext = "{{Please leave this line alone (sandbox heading)}}<!--\n*               Welcome to the sandbox!              *\n*            Please leave this part alone            *\n*           The page is cleared regularly            *\n*     Feel free to try your editing skills below     *\n■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■-->";
$xXtext = "<noinclude>\nThis sandbox is itself a template. This sandbox is for experimenting with templates.\n{{Please leave this line alone (template sandbox heading)}}\n\n\nIf you defined parameters such as <tt><nowiki>{{Template sandbox|First|Second|name=\"Named\"}}</nowiki></tt>:\n;First:{{{1}}}\n;Second:{{{2}}}\n;Name:{{{name}}}\n\n----</noinclude>\n";
$tstext = "<noinclude>\nThis sandbox is itself a template. This sandbox is for experimenting with templates.\n{{Please leave this line alone (template sandbox heading)}}\n\n\nIf you defined parameters such as <tt><nowiki>{{Template sandbox|First|Second|name=\"Named\"}}</nowiki></tt>:\n;First:{{{1}}}\n;Second:{{{2}}}\n;Name:{{{name}}}\n\n----</noinclude>\n";
$tutorialtext = "{{Please leave this line alone (sandbox heading)}}<!--\n*               Welcome to the sandbox!              *\n*            Please leave this part alone            *\n*           The page is cleared regularly            *\n*     Feel free to try your editing skills below     *\n■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■-->";
$tutorialtalktext = "{{Please leave this line alone (sandbox talk heading)}}\n<!-- Hello!  Feel free to try your formatting and editing skills below this line.  As this page is for editing experiments, this page will automatically be cleaned every 12 hours. -->";

$hour = date("H"); 
$minute = date("i");

$pagetext = array (
	"Wikipedia:Sandbox"=>$sandboxtext,
    "User:Sandbox"=>$sandboxtext,  
);
if( ($hour == 00 || $hour == 12 || $hour  == 24) ) {
    $pagetext = array_merge( $pagetext, array (
        "Wikipedia talk:Sandbox"=>$sandboxtalktext,
        "User talk:Sandbox"=>$sandboxtalktext,
        "Template:X1"=>$xXtext,
        "Template:X2"=>$xXtext,
        "Template:X3"=>$xXtext,
        "Template:X4"=>$xXtext,
        "Template:X5"=>$xXtext,
        "Template:X6"=>$xXtext,
        "Template:X7"=>$xXtext,
        "Template:X8"=>$xXtext,
        "Template:X9"=>$xXtext,
        "Template:X10"=>$xXtext,
        "Template:X11"=>$xXtext,
        "Template:X12"=>$xXtext,
        "Template:Template sandbox"=>$tstext,
        "Template talk:X1"=>$tutorialtalktext,
        "Template talk:X2"=>$tutorialtalktext,
        "Template talk:X3"=>$tutorialtalktext,
        "Template talk:X4"=>$tutorialtalktext,
        "Template talk:X5"=>$tutorialtalktext,
        "Template talk:X6"=>$tutorialtalktext,
        "Template talk:X7"=>$tutorialtalktext,
        "Template talk:X8"=>$tutorialtalktext,
        "Template talk:X9"=>$tutorialtalktext,
        "Template talk:X10"=>$tutorialtalktext,
        "Template talk:X11"=>$tutorialtalktext,
        "Template talk:X12"=>$tutorialtalktext,
        "Template talk:Template sandbox"=>$tutorialtalktext,
        "Wikipedia:Tutorial (Editing)/sandbox"=>$tutorialtext,
        "Wikipedia:Tutorial (Formatting)/sandbox"=>$tutorialtext,
        "Wikipedia:Tutorial (Wikipedia links)/sandbox"=>$tutorialtext,
        "Wikipedia:Tutorial (External links)/sandbox"=>$tutorialtext,
        "Wikipedia:Tutorial (Keep in mind)/sandbox"=>$tutorialtext,
        "Wikipedia talk:Tutorial (Editing)/sandbox"=>$tutorialtalktext,
        "Wikipedia talk:Tutorial (Formatting)/sandbox"=>$tutorialtalktext,
        "Wikipedia talk:Tutorial (Wikipedia links)/sandbox"=>$tutorialtalktext,
        "Wikipedia talk:Tutorial (External links)/sandbox"=>$tutorialtalktext,
        "Wikipedia talk:Tutorial (Keep in mind)/sandbox"=>$tutorialtalktext
    ));    
}
foreach( $pagetext as $pagetitle=>$text) {
	$page = initPage($pagetitle, null, false);
	echo "Time to clean $pagetitle!\n";
	$page->edit($text,'Clearing the sandbox ([[WP:BOT|BOT]] EDIT)');
}