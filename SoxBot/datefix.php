<?php

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "simple" );

$site->set_runpage("User:Cyberbot I/Run/Datefixer");

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	$tosearch = array(
		'{{wikify}}',
		'{{orphan}}',
		'{{uncategorized}}',
		'{{uncategorised}}',
		'{{uncat}}',
		'{{uncategorizedstub}}',
		'{{cleanup}}',
		'{{clean-up}}',
		'{{unreferenced}}',
		'{{nosources}}',
		'{{unsourced}}',
		'{{source}}',
		'{{expand}}',
		'{{work in progress}}',
		'{{merge}}',
		'{{fact}}',
		'{{citation needed}}',
		'{{prove}}',
		'{{copy}}',
		'{{encopypaste}}',
		'{{en copy paste}}',
		'{{NPOV}}',
		'{{npov}}',
		'{{POV-check}}',
		'{{POV-Check}}',
		'{{POV}}',
	);

	$toreplace = array(
		'{{wikify|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{orphan|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{uncat|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{uncat|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{uncat|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{uncategorizedstub|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{cleanup|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{cleanup|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{nosources|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{nosources|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{nosources|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{nosources|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{expand|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{expand|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{merge|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{fact|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{fact|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{fact|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{encopypaste|1={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{encopypaste|1={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{encopypaste|1={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{NPOV|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{NPOV|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{NPOV|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{NPOV|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
		'{{NPOV|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}',
	);

	$togetembeddedin = array(
		'Template:Wikify',
		'Template:Orphan',
		'Template:Uncategorized',
		'Template:Uncategorizedstub',
		'Template:Cleanup',
		'Template:Unreferenced',
		'Template:Expand',
		'Template:Merge',
		'Template:Fact',
		'Template:Copy',
		'Template:NPOV',
	);

	$p = array();

	foreach( $togetembeddedin as $template ) {
		$pages = initPage( $template )->embeddedin(0);
		$p = array_merge( $p, $pages );
	}

	$p = array_unique($p);
	$out = '';

	$c = 1;
	foreach ($p as $pg) {
		$page = initPage($pg);
		$text = $page->get_text();
		
		$newtext = str_ireplace($tosearch, $toreplace, $text);
					
		if( $newtext != $text ) {
				
            $page->edit($newtext,"Dating maintenance tags (bot edit)",true);
				
			$c++;
			sleep(3);
		}
	}
}

?>