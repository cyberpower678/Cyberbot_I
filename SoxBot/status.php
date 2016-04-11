<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once('/home/cyberpower678/Peachy/Init.php');

$wiki = Peachy::newWiki("soxbot");
$wiki2 = Peachy::newWiki("cyberbotii");
//$wiki3 = Peachy::newWiki("cyberbottrial");

$out = "";
$oldout = "";

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	echo "Updating Readiness\n\n";

	$out = "{{{{{|safesubst:}}}#switch:{{{{{|safesubst:}}}CURRENTDAY}}|".date( 'j' )."=enable|#default=disable}}";
    if( $oldout != $out ) {
		$output = "User:Cyberbot I/Status";

		$wiki->initPage( $output )->edit( $out, "Updating readiness of bot." );

		$output = "User:Cyberbot II/Status";

		$wiki2->initPage( $output )->edit( $out, "Updating readiness of bot." );

		//$output = "User:Cyberbot Trial Bot/Status";

		//$wiki3->initPage( $output )->edit( $out, "Updating readiness of bot." );
		$oldout = $out;
	}

	echo "Done\n\n";

	// Take a break before starting over
	sleep( 600 );
}
