<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );
require_once('/home/cyberpower678/database.inc');

if( defined( 'USESIMPLE' ) ) {
$site = Peachy::newWiki( "simple" );
$db = mysqli_connect( 'simplewiki.labsdb', $toolserver_username, $toolserver_password, 'simplewiki_p' );
} elseif( defined( 'USEWIKIDATA' ) ) {
$site = Peachy::newWiki( "wikidata" );
$db = mysqli_connect( 'wikidatawiki.labsdb', $toolserver_username, $toolserver_password, 'wikidatawiki_p' );
} elseif( defined( 'USECOMMONS' ) ) {
$site = Peachy::newWiki( "commons" );
$db = mysqli_connect( 'commonswiki.labsdb', $toolserver_username, $toolserver_password, 'commonswiki_p' );
} else {
$site = Peachy::newWiki( "soxbot" );
$db = mysqli_connect( 'enwiki.labsdb', $toolserver_username, $toolserver_password, 'enwiki_p' );
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

mysqli_close( $db );

function process ($rawuser) {
    global $site, $db;
	$user = initUser( $rawuser );
	$editcount = $user->get_editcount( false, $db );
	$livecount = $user->get_editcount( false, $db, true );
	
	$out = "{{Adminstats/Core\n|edits=$livecount\n|ed=$editcount\n";
	
	    if( $result = mysqli_query( $db, "SELECT user_id FROM user WHERE `user_name` = '{$rawuser}';" ) ) {
        $uid = mysqli_fetch_assoc( $result );
        $uid = $uid['user_id'];
        mysqli_free_result( $result );
    } else return;
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'newusers';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
        
    $out .= "|created=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'delete' AND `log_action` = 'delete';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|deleted=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'delete' AND `log_action` = 'revision';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|revdel=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'delete' AND `log_action` = 'event';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|eventdel=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'delete' AND `log_action` = 'restore';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|restored=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'block' AND `log_action` = 'block';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|blocked=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'protect' AND `log_action` = 'protect';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|protected=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'protect' AND `log_action` = 'unprotect';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|unprotected=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'rights' AND `log_action` = 'rights';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|rights=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'block' AND `log_action` = 'reblock';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|reblock=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'block' AND `log_action` = 'unblock';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|unblock=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'protect' AND `log_action` = 'modify';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|modify=$res\n";
    
     if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'abusefilter';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|filter=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'merge';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|merge=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'massmessage';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|massmessage=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'renameuser' AND `log_action` = 'renameuser';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;

    $out .= "|rename=$res\n";
    
    if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'import';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|import=$res\n";
  
    if( defined( 'USECOMMONS' ) {
     if( $result = mysqli_query( $db, "SELECT count(log_action) AS count FROM logging_userindex WHERE `log_user` = '{$uid}' AND `log_type` = 'pagetranslation';" ) ) {
        $res = mysqli_fetch_assoc( $result );
        $res = $res['count'];
        mysqli_free_result( $result );
    } else return;
    
    $out .= "|ta=$res\n";
    }
    
	$out .= '|style={{{style|}}}}}';
    unset( $res );
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
