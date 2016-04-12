<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/home/cyberpower678/Peachy/Init.php' );
require_once('/home/cyberpower678/database.inc');

$site = Peachy::newWiki( "urwikiI" );

$site->set_runpage( "صارف:Cyberbot_I/Run/Adminstats" );

$db = mysqli_connect( 'urwiki.labsdb', $toolserver_username, $toolserver_password, 'urwiki_p' );

$u = initPage('سانچہ:انتظامی شماریات')->embeddedin( array( 2, 3 ) );

$flaggedusers = $site->allusers( null, array( 'sysop' ), null, false, array( 'blockinfo', 'groups', 'editcount', 'registration' ), null );

shuffle($u);
//$u = array("Cyberpower678");

foreach ($u as $name) {
	$isflagged = false;
	$m = initUser($name);
	foreach( $flaggedusers as $flaggeduser ) {
		if( $m->username() == $flaggeduser['name'] ) {
			$isflagged = true;
			print_r($flaggeduser);
		}
	}
	if( $isflagged ) {
		process($m);	
	}
	else {
		$out = "'''".$m->username()." منتظم نہیں ہے،<br>لہذا وہ انتظامی شماریات کے استعمال کے مجاز نہیں ہیں۔'''";
		echo $out;
		echo "\n";
		$toedit = "سانچہ:انتظامی شماریات/".$m->username()."";
		initPage( $toedit )->edit($out,"انتظامی شماریات اس صارف کے لیے مجاز نہیں ہے۔",true);
	}
}

mysqli_close( $db );

function process ($user) {
    global $site, $db;
		
	$rawuser = $user->username();

    $editcount = $user->get_editcount( false, $db );

    $livecount = $user->get_editcount( false, $db, true );
		
	$out = "{{انتظامی شماریات/Core\n|edits=$livecount\n|ed=$editcount\n";
	
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
	$toedit = "سانچہ:انتظامی شماریات/$rawuser";
	
	initPage( $toedit )->edit($out,"تجدید انتظامی شماریات",true);
}

function toDie($newdata) {
    $f=fopen('./adminstats.log',"a");
              fwrite($f,$newdata);
              fclose($f);  
}
?>