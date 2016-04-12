<?php

ini_set('memory_limit','512M');
echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";
require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );
$site->set_runpage( "User:Cyberbot I/Run/RfUBot" );

$RUN_FREQUENCY = 15;
$oldreportpages = array( "User:Snotbot/Requests for unblock report" );

$lasttable = "";

while( true ) {
    echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
    $reportpage = $site->initPage( "User:Cyberbot I/Requests for unblock report" );
    
    $blockedusers = $site->categorymembers( "Category:Requests for unblock" );
    $subcat_autoblock = $site->categorymembers( "Category:Requests for unblock-auto" );
    $subcat_chu = $site->categorymembers( "Category:Requests for username changes when blocked" );
    $subcat_onhold = $site->categorymembers( "Category:Unblock on hold" );
    $blockedusers = array_merge( $blockedusers, $subcat_onhold );
		
    $tablestr = "{{#switch:{{CURRENTDAY}}|{{subst:CURRENTDAY}}=<noinclude>This table lists [[:Category:Requests for unblock|current requests for unblock]] along with statistics about each case.  Questions or comments go to [[User talk:Cyberpower678]].  This table is updated as often as every $RUN_FREQUENCY minutes, and was last updated on ~~~~~.\n\n</noinclude>|#default=<big>{{red|This table is out of date.  Contact [[User talk:Cyberpower678]].}}</big>}}\n{|class=\"wikitable sortable\"\n!User\n!Unblock request time\n!Blocked by\n!When blocked\n!Block expiration\n!Last edit by\n!Last edit time\n!Last admin edit by\n!Last admin edit time\n!Subcategories";
    
    foreach( $blockedusers as $usertalk ) {
        $object = $site->initPage( $usertalk['title'] );
        $user = $object->get_title( false );
        $data = $object->get_text( true );
        $revisions = $object->history( 50 );
        
        $subcategories = " ";
        foreach( $subcat_autoblock as $subcat ) if( $subcat['title'] == $usertalk['title']) $subcategories .= "Autoblock "; 
        foreach( $subcat_chu as $subcat ) if( $subcat['title'] == $usertalk['title']) $subcategories .= "UsernameChange ";
        foreach( $subcat_onhold as $subcat ) if( $subcat['title'] == $usertalk['title']) $subcategories .= "OnHold ";
        $subcategories = trim( $subcategories );
        
        preg_match_all( '/\{\{\s*(?:unblock|unblock request|unblock on hold|unblock-un|unblock-spamun|unblockspamun|unblock-coiun|ucr|username change request|unblock-auto|unblock-ip)\s*\|.*?\}\}/si', $data, $unblockrequests );
        if( count( $unblockrequests[0] ) == 0 ) {
            echo "ERROR: Couldn't find an unblock reqeust on {$usertalk['title']}\n";
            continue;
        }
        $latestrequest = $unblockrequests[0][count( $unblockrequests[0]) - 1];
        preg_match_all( '/(\d{2}:\d{2}, .*?) \(UTC\)/i', $latestrequest, $requesttimestamps );
        if( count( $requesttimestamps[0] ) == 0 ) $requesttime = findBlockRequestTime( $object );
        else $requesttime = formatdate( $requesttimestamps[0][count( $requesttimestamps[0] ) - 1] );
        $lastedittime = formatdate( $revisions[0]['timestamp'] );
        $lastedituser = $revisions[0]['user'];
        
        $blockinfo = APIgetblockinfo( $user );
        if( !is_null( $blockinfo ) && !empty( $blockinfo ) ) {
            $blocker = $blockinfo['by'];
            $blocktime = formatdate( $blockinfo['when'] );
            if( $blockinfo['expiry'] == 'infinity' ) $blockexpiry = $blockinfo['expiry'];
            else $blockexpiry = formatdate( $blockinfo['expiry'] );
        } else {
            $blocker = null;
            $blocktime = null;
            $blockexpiry = null;
        }
        $lastadmin = null;
        $lastadmintime = null;
        foreach( $revisions as $edit ) {
            if( isAdmin( $edit['user'] ) ) {
                $lastadmin = $edit['user'];
                $lastadmintime = formatdate( $edit['timestamp'] );
                break;
            }
        }
        $tablestr .= "\n|-\n";
        $tablestr .= "|[[User talk:$user|$user]]";
        $tablestr .= "||";
        if( !is_null( $requesttime ) ) $tablestr .= "data-sort-value=\"".date( 'YmdHis', $requesttime )."\"|{{Time ago|".date( 'YmdHis', $requesttime )."}}";
        else $tablestr .= "No timestamp found";
        $tablestr .= "||";
        if( !is_null( $blocker ) ) $tablestr .= "[[User:$blocker|$blocker]]";
        else $tablestr .= "N/A";
        $tablestr .= "||";
        if( !is_null( $blocktime ) ) $tablestr .= "data-sort-value=\"".date( 'YmdHis', $blocktime )."\"|{{Time ago|".date( 'YmdHis', $blocktime )."}}";
        else $tablestr .= "N/A";
        if( $blockexpiry == "infinity" ) $tablestr .= "||indef";
        else {
            $tablestr .= "||";
            if( !is_null( $blockexpiry ) ) $tablestr .= "data-sort-value=\"".date( 'YmdHis', $blockexpiry )."\"|{{Time ago|".date( 'YmdHis', $blockexpiry )."}}";
            else $tablestr .= "N/A";
        }
        $tablestr .= "||";
        if( !is_null( $lastedituser ) ) $tablestr .= "[[User:$lastedituser|$lastedituser]]";
        else $tablestr .= "Unknown";
        $tablestr .= "||";
        if( !is_null( $lastedittime ) ) $tablestr .= "data-sort-value=\"".date( 'YmdHis', $lastedittime )."\"|{{Time ago|".date( 'YmdHis', $lastedittime )."}}";
        else $tablestr .= "Unknown";
        $tablestr .= "||";
        if( !is_null( $lastadmin ) ) $tablestr .= "[[User:$lastadmin|$lastadmin]]";
        else $tablestr .= "Unknown";
        $tablestr .= "||";
        if( !is_null( $lastadmintime ) ) $tablestr .= "data-sort-value=\"".date( 'YmdHis', $lastadmintime )."\"|{{Time ago|".date( 'YmdHis', $lastadmintime )."}}";
        else $tablestr .= "Unknown";
        $tablestr .= "||$subcategories"; 
    }
    $tablestr .= "\n|}";
    $tablestr .= "\n<includeonly><small>[[{$reportpage->get_title()}|This table]] is updated as often as every $RUN_FREQUENCY minutes.  Last updated {{Time ago|~~~~~}}.</small>\n</includeonly>";
    foreach( $oldreportpages as $page ) $site->initPage( $page )->edit( "<big>{{red|The page is now updated at [[User:Cyberbot I/Requests for unblock report]].  Please change links accordingly.  You can still see the table below.}}</big>\n{{User:Cyberbot I/Requests for unblock report}}", "Page now maintained elsewhere" );
    if( $tablestr != $lasttable ) {
        $lasttable = $tablestr;
        $reportpage->edit( $tablestr, "Bot updating unblock request table" ); 
    } else {
        echo "No changes since last time.\n";
        $lasttable = $tablestr;
    }
    echo "Done.  Sleeping...\n";
    sleep( $RUN_FREQUENCY*60 );
}

function isAdmin( $user ) {
    global $site;
    $user = $site->initUser( $user );
    if( !is_null( $user->get_usergroups() ) && in_array( "sysop", $user->get_usergroups() ) ) return true;
    return false;
}

function APIgetblockinfo( $user ) {
    global $site;
    $user = $site->initUser( $user );
    if( !$user->is_blocked() ) return null;
    return $user->get_blockinfo();   
}

function formatdate( $t ) {
    return strtotime( $t );    
}

function findBlockRequestTime( $usertalk ) {
    if( !$usertalk->get_exists() ) return null;
    $unblockregex = '/\{\{\s*(?:unblock|unblock request|unblock on hold|unblock-un|unblock-spamun|unblockspamun|unblock-coiun|ucr|username change request|unblock-auto|unblock-ip)\s*\|.*?\}\}/si';
    $howfarback = 10;
    while( true ) {
        $foundit = null;
        $revs = $usertalk->history( $howfarback, "older", true );
        foreach( array_reverse( $revs ) as $rev ) {
            $itsinthere = preg_match( $unblockregex, $rev['*'], $itsinthere );
            if( !$itsinthere ) {
                $foundit = false;
                continue;
            }
            if( $itsinthere && $foundit === false ) {
                return strtotime( $rev['timestamp'] );
            }
            if( $itsinthere && is_null( $foundit ) ) {
                break;
            }
        }
        if( $howfarback == 10 ) $howfarback = 100;
        else {
            echo "Unable to find timestamp or request revision on {$usertalk->get_title()}\n";
            return null;
        }
    }
}  
?>
