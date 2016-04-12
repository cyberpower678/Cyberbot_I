<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once('/home/cyberpower678/Peachy/Init.php');

$wiki = Peachy::newWiki("soxbot");

$wiki->set_runpage("User:Cyberbot I/Run/Tally");
$oldout = "";

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	$open_rfxs = initPage("Template:Rfatally")->embeddedin( array( 4 ), 100 );

	//print_r($open_rfxs);
	/*$rfa_main_text = $rfa_main->get_text();
	preg_match_all('/\{\{Wikipedia:(.*?)\}\}/', $rfa_main_text, $open_rfxs);
	$open_rfxs = $open_rfxs[1];*/

	$tallys = array();
	foreach( $open_rfxs as $open_rfx ) {
		//$open_rfx = $open_rfx['title'];
		if( in_array( $open_rfx, array( 'Wikipedia:Requests for adminship/Front matter', 'Wikipedia:Requests for adminship/bureaucratship', 'Wikipedia:Requests for adminship' ) ) ) continue;
		if( !preg_match( '/Wikipedia:Requests for (admin|bureaucrat)ship/i', $open_rfx ) ) continue;
		//$open_rfx = str_replace(array('Wikipedia:Requests for adminship/','Wikipedia:Requests for bureaucratship/'),'',$open_rfx);
		
		$myRFA = new RFA( $wiki, $open_rfx);
		
		if ($myRFA->get_lasterror()) {
			echo $myRFA->get_lasterror();
			exit(1);
		}

		$tally = count($myRFA->get_support()).'/'.count($myRFA->get_oppose()).'/'.count($myRFA->get_neutral());
		
		$open_rfx = str_replace(array('Wikipedia:Requests for adminship/','Wikipedia:Requests for bureaucratship/'),'',$open_rfx);
		$tallys[$open_rfx] = $tally;
	}

	$out1 = "{{{{{|safesubst:}}}#switch: {{{1|{{SUBPAGENAME}}}}}\n";

	foreach( $tallys as $rfa => $tally ) {
		$out1 .= "|$rfa= ($tally)\n";
	}
	$out = "{{{{{|safesubst:}}}#switch:{{{finaltally|false}}}|true=".$out1."|#default= (?/?/?)\n}}\n|#default={{#ifeq:{{User:Cyberbot I/Run/Tally}}|disable|{{red|Tally disabled. [https://en.wikipedia.org/w/index.php?title=User:Cyberbot_I/Run/Tally&action=edit&editintro=&summary=Enabling+Task&nosummary=&prefix=&minor=yes Enable!]}}|".$out1;

	$out .= "|#default= (?/?/?)\n}}}}}}";

	if( $oldout != $out ) {
		initPage("Template:RfA tally", null, false, true)->edit("<span id=\"rfatally\">{{{{{|safesubst:}}}User:Cyberpower678/Tally|1={{{1|{{SUBPAGENAME}}}}}}}</span><noinclude>\n[[Category:Templates related to requests for adminship]]\n</noinclude>","Overriding existing code.  Cyberbot I maintains this task.");
		$tally_page = initPage("User:Cyberpower678/Tally")->edit($out,"Updating RFA tally");
		$oldout = $out;
	}
	unset( $open_rfxs );
	unset( $open_rfx );
	unset( $tallys );
}