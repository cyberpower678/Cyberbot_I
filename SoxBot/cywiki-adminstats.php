<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/data/project/cyberbot/Peachy/Init.php' );
require_once('/data/project/cyberbot/database.inc');

$site = Peachy::newWiki( "cywikiI" );

$db = mysqli_connect( "cywiki.labsdb", $toolserver_username, $toolserver_password, 'cywiki_p' );

$site->set_runpage("Defnyddiwr:Cyberbot I/Run/Adminstats");

$u = initPage('Nodyn:Adminstats')->embeddedin( array( 2, 3 ) );

$admins = $site->allusers( null, array( 'sysop' ), null, false, array( 'blockinfo', 'groups', 'editcount', 'registration' ), null );

shuffle($u);
// = array("Sgwrs Defnyddiwr:Cyberpower678");

foreach ($u as $name) {
	$issysop = false;
  preg_match("/(Sgwrs )?Defnyddiwr:([^\/]*)/i", $name, $m);
	foreach( $admins as $admin ) {
		if( in_array( $m[2], $admin ) ) {
			$issysop = true;
		}
	}
	if( $issysop ) {
		process($m[2]);	
	}
	else {
		$out = "'''Nid $m[2] yn weinyddwr.<br>Felly maent wedi eu gwahardd y defnydd o adminstats.'''";
		echo $out;
		echo "\n";
		$toedit = "Nodyn:Adminstats/$m[2]";
		initPage( $toedit )->edit($out,"Ni chaniateir Adminstats ar gyfer y defnyddiwr.",true);
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
			
	$out .= '|style={{{style|}}}}}';
    unset( $res );
	echo $out;
    echo "\n";
    $toedit = "Nodyn:Adminstats/$rawuser";
    
    initPage( $toedit )->edit($out,"Diweddaru Ystadegau Gweinyddol",true);
}

function toDie($newdata) {
    $f=fopen('./adminstats.log',"a");
              fwrite($f,$newdata);
              fclose($f);  
}

?>