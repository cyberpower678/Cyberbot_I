<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once('/data/project/cyberbot/Peachy/Init.php');

$wiki = Peachy::newWiki("soxbot");
$wiki2 = Peachy::newWiki("cyberbotii");
$wiki3 = Peachy::newWiki("cyberbottrial");

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	echo "Updating Readiness\n\n";

	$out = "{{{{{|safesubst:}}}#switch:{{{{{|safesubst:}}}CURRENTDAY}}|{{subst:CURRENTDAY}}=enable|#default=disable}}";

	$output = "User:Cyberbot I/Status";

	$wiki->initPage( $output )->edit( $out, "Updating readiness of bot." );

	$out = "{{{{{|safesubst:}}}#switch:{{{{{|safesubst:}}}CURRENTDAY}}|{{subst:CURRENTDAY}}=enable|#default=disable}}";

	$output = "User:Cyberbot II/Status";

	$wiki2->initPage( $output )->edit( $out, "Updating readiness of bot." );

	$out = "{{{{{|safesubst:}}}#switch:{{{{{|safesubst:}}}CURRENTDAY}}|{{subst:CURRENTDAY}}=enable|#default=disable}}";

	$output = "User:Cyberbot Trial Bot/Status";

	$wiki3->initPage( $output )->edit( $out, "Updating readiness of bot." );

	echo "Done\n\n";
}