<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once( '/data/project/cyberbot/Peachy/Init.php' );
require_once('/data/project/cyberbot/database.inc');

$site = Peachy::newWiki( "urwikiI" );

$site->set_runpage( "صارف:Cyberbot_I/Run/Adminstats" );

$db = new Database( 'urwiki.labsdb', $toolserver_username, $toolserver_password, 'urwiki_p' );

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
function process ($user) {
    global $site, $db;
		
		$rawuser = $user->username();

    $editcount = $user->get_editcount( false, $db );

    $livecount = $user->get_editcount( false, $db, true );
		
		$out = "{{انتظامی شماریات/Core\n|edits=$livecount\n|ed=$editcount\n";
		
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
		$toedit = "سانچہ:انتظامی شماریات/$rawuser";
		
		initPage( $toedit )->edit($out,"تجدید انتظامی شماریات",true);
}

function toDie($newdata) {
    $f=fopen('./adminstats.log',"a");
              fwrite($f,$newdata);
              fclose($f);  
}
?>