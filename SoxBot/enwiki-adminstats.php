<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/data/project/cyberbot/Peachy/Init.php' );
require_once('/data/project/cyberbot/database.inc');

if( defined( 'USESIMPLE' ) ) {
$site = Peachy::newWiki( "simple" );
$db = new Database( 'simplewiki.labsdb', $toolserver_username, $toolserver_password, 'simplewiki_p' );
} elseif( defined( 'USEWIKIDATA' ) ) {
$site = Peachy::newWiki( "wikidata" );
$db = new Database( 'wikidatawiki.labsdb', $toolserver_username, $toolserver_password, 'wikidatawiki_p' );
} elseif( defined( 'USECOMMONS' ) ) {
$site = Peachy::newWiki( "commons" );
$db = new Database( 'commonswiki.labsdb', $toolserver_username, $toolserver_password, 'commonswiki_p' );
} else {
$site = Peachy::newWiki( "soxbot" );
$db = new Database( 'enwiki.labsdb', $toolserver_username, $toolserver_password, 'enwiki_p' );
}

$site->set_runpage("User:Cyberbot I/Run/Adminstats");

$u = initPage('Template:Adminstats')->embeddedin( array( 2, 3 ) );

$admins = $site->allusers( null, array( 'sysop', 'accountcreator' ), null, false, array( 'blockinfo', 'groups', 'editcount', 'registration' ), null );

shuffle($u);
// = array("User talk:Cyberpower678");

foreach ($u as $name) {
	$issysop = false;
	preg_match("/User( talk)?:([^\/]*)/i", $name, $m);
	foreach( $admins as $admin ) {
		if( $m[2] == $admin['name'] ) {
			$issysop = true;
			print_r($admin);
		} elseif( $m[2] == 'Cyberpower678' ) {
			$issysop = true;
			print_r($admin);
		}
	}
	if( $issysop ) {
		process($m[2]);	
	}
	else {
		$out = "'''$m[2] is not an administrator or an account creator.<br>Therefore they have been disallowed the use of adminstats.'''";
		echo $out;
		echo "\n";
		$toedit = "Template:Adminstats/$m[2]";
		initPage( $toedit )->edit($out,"Adminstats are not allowed for this user.",true);
	}
}
function process ($rawuser) {
    global $site, $db;
		$user = initUser( $rawuser );
		$editcount = $user->get_editcount( false, $db );
		$livecount = $user->get_editcount( false, $db, true );
		
		$out = "{{Adminstats/Core\n|edits=$livecount\n|ed=$editcount\n";
		
		$uid = $db->select(
			'user',
			'user_id',
			array(
				'user_name' => $rawuser
			)
		);
		$uid = $uid[0]['user_id'];
		if( !$uid ) return;
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_type' => 'newusers'
			)
		);
		if( !$res ) return;
		
		$out .= "|created={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'delete',
				'log_type' => 'delete'
			)
		);
		if( !$res ) return;
		
		$out .= "|deleted={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'restore',
				'log_type' => 'delete'
			)
		);
		if( !$res ) return;
		
		$out .= "|restored={$res[0]['count']}\n";
		
		 
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'block',
				'log_type'=> 'block'
			)
		);
		if( !$res ) return;
		
		$out .= "|blocked={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'protect',
				'log_type' => 'protect'
			)
		);
		if( !$res ) return;
		
		$out .= "|protected={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'unprotect',
				'log_type' => 'protect'
			)
		);
		if( !$res ) return;
		
		$out .= "|unprotected={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'rights',
				'log_type' => 'rights'
			)
		);
		if( !$res ) return;
		
		$out .= "|rights={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'reblock',
				'log_type'=> 'block'
			)
		);
		if( !$res ) return;
		
		$out .= "|reblock={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'unblock',
				'log_type'=> 'block'
			)
		);
		if( !$res ) return;
		
		$out .= "|unblock={$res[0]['count']}\n";
				
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'modify',
				'log_type' => 'protect'
			)
		);
		if( !$res ) return;
		
		$out .= "|modify={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_action' => 'renameuser',
				'log_type' => 'renameuser'
			)
		);
		if( !$res ) return;
		
		$out .= "|rename={$res[0]['count']}\n";
		
		$res = $db->select(
			'logging_userindex',
			'count(log_action) AS count',
			array(
				'log_user' => $uid,
				'log_type' => 'import'
			)
		);
		if( !$res ) return;
		
		$out .= "|import={$res[0]['count']}\n";
				
		$out .= '|style={{{style|}}}}}';
		echo $out;
		echo "\n";
		$toedit = "Template:Adminstats/$rawuser";
		
		initPage( $toedit )->edit($out,"Updating Admin Stats",true);
}

function toDie($newdata) {
    $f=fopen('./adminstats.log',"a");
              fwrite($f,$newdata);
              fclose($f);  
}

?>