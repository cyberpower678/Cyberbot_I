<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );
$oldsoxbot = "";
$oldcommons = "";

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	foreach( array( 'soxbot', 'commons' ) as $bot ) {
		$site = Peachy::newWiki( $bot );

		if( $bot == "soxbot" ) {
			$site->set_runpage("User:Cyberbot I/Run/AR");
		}
		else {
			$site->set_runpage("User:Cyberbot I/Run/AR");
		}

		$admins = $site->allusers( null, array( 'sysop' ), null, false, array( 'blockinfo', 'groups', 'editcount', 'registration' ), null );
		
		if( $bot == "commons" ) print_r($admins);
		
		if( count($admins) < 1000 && $bot == "soxbot" ) {
			("Error... less than 1000 admins\n\n");
			exit(1);
		}
		if( count($admins) < 5 && $bot == "commons" ) {
			echo("Error... less than 5 admins\n\n");
			exit(1);
		}
		
		$data = '';
		foreach( $admins as $admin ) {
			$data .= 'adminrights[\''.str_ireplace(
			array(
				'+',
				'\\',
				'\'',
				'(',
				')',
				'%21',
				'%2C',
				'%3A',
			),
			array(
				'%20',
				'\\\\',
				'%27',
				'%28',
				'%29',
				'!',
				',',
				':',
			),
			urlencode($admin['name'])).'\']=1;'."\n";
		}
		if( count($admins) != (count(explode("\n",$data)) - 1) ) { die("Error?"); }
		echo $data;
		if( $bot = "soxbot" ) {
			if( $oldsoxbot != $data ) {
				$page = initPage( "User:Cyberbot I/adminrights-admins.js" );
				$page->edit( $data, 'Updating admins list', true );
				$oldsoxbot = $data;
			}
		} else {
			if( $oldcommons != $data ) {
				$page = initPage( "User:Cyberbot I/adminrights-admins.js" );
				$page->edit( $data, 'Updating admins list', true );
				$oldcommons = $data;
			}
		}
	}

	// Take a break before starting over
	sleep( 600 );
}
