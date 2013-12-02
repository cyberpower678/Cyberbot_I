<?php

ini_set('memory_limit','5G');
echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";
require_once( '/data/project/cyberbot/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );
$site->set_runpage( "User:Cyberbot I/Run/RfPPBot" );

$ARCHIVE_FULFILLED = 120;
$ARCHIVE_DENIED = 360;
$ARCHIVE_CONFUSED = 720;
$ARCHIVE_TIMEOUT = 2880;
$MIN_ARCHIVE = 3;
$PROTECT_BUFFER = 10;
$RUN_FREQUENCY = 15;
$ARCHIVE_LENGTH = 7;
$BACKLOG_ADD_LIMIT = 10;
$BACKLOG_REMOVE_LIMIT = 4;
$RFPP_PAGE = "Wikipedia:Requests for page protection";
$ARCHIVE_PAGE = "Wikipedia:Requests for page protection/Rolling archive";

while( true ) {
    echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
    $config = $site->initPage( "User:Cyberbot I/RFPP" )->get_text( true );
    preg_match( '/\n\|ARCHIVE_FULFILLED\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $ARCHIVE_FULFILLED = $param1[1];
    preg_match( '/\n\|ARCHIVE_DENIED\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $ARCHIVE_DENIED = $param1[1];
    preg_match( '/\n\|ARCHIVE_CONFUSED\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $ARCHIVE_CONFUSED = $param1[1];
    preg_match( '/\n\|ARCHIVE_TIMEOUT\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $ARCHIVE_TIMEOUT = $param1[1];
    preg_match( '/\n\|PROTECT_BUFFER\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $PROTECT_BUFFER = $param1[1];
    preg_match( '/\n\|RUN_FREQUENCY\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $RUN_FREQUENCY = $param1[1];
    preg_match( '/\n\|ARCHIVE_LENGTH\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $ARCHIVE_LENGTH = $param1[1];
    preg_match( '/\n\|BACKLOG_ADD_LIMIT\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $BACKLOG_ADD_LIMIT = $param1[1];
    preg_match( '/\n\|BACKLOG_REMOVE_LIMIT\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $BACKLOG_REMOVE_LIMIT = $param1[1];
    preg_match( '/\n\|ARCHIVE_PAGE\s*=\s*\"(.*?)\"/i', $config, $param1 );
    if( isset( $param1[1] ) ) {
        $ARCHIVE_PAGE = $param1[1];
        $archive = $site->initPage( $ARCHIVE_PAGE );
    }
    preg_match( '/\n\|MIN_ARCHIVE\s*=\s*(\d+)/i', $config, $param1 );
    if( isset( $param1[1] ) ) $MIN_ARCHIVE = $param1[1];    
    
    $rfpp = $site->initPage( $RFPP_PAGE );
		$archive = $site->initPage( $ARCHIVE_PAGE );
		$archivedata = $archive->get_text( true );
    $tobearchived = array();
    $pendingrequests = 0;
    $rfppdata = $rfpp->get_text( true );
    $oldrfppdata = $rfppdata;
    $protectionrequests = $rfpp->get_text( false, "Current requests for increase in protection level" );
    $unprotectionrequests = $rfpp->get_text( false, "Current requests for reduction in protection level" );
    $editrequests = $rfpp->get_text( false, "Current requests for edits to a protected page" );
    
    preg_match_all( '/(====.*?====.*?)(?===|$)/si', $protectionrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
            preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
            $code = strtolower( $code[1] );
            if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                $pendingrequests++;
                echo getFullPageTitle( $req ).": Discussion ongoing, not actioned yet\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "s", "semi", "pd", "pend", "p", "f", "full", "m", "move", "t", "salt", "fb", "feedback", "feed", "ap", "ispr", "ad", "isdo", "temp", "tp" ) ) ) {
                 if( isProtected( $req, $code ) ) {
                    echo getFullPageTitle( $req ).": Handled ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                 } else {
                     echo getFullPageTitle( $req ).": Marked as protected, but not protected\n";
                     if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && strpos( $req, "'''Automated comment:'''" ) === false ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' Page has not been protected.~~~~\n\n", $rfppdata );
                 }
                 continue;
            }
            if( in_array( $code, array( "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                echo getFullPageTitle( $req ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "do", "done" ) ) ) {
                echo getFullPageTitle( $req ).": Marked as done ".timeSinceLastEdit( $req )." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                echo getFullPageTitle( $req ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            $pendingrequests++;
            echo getFullPageTitle( $req ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
            if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
        }
        else {
            echo getFullPageTitle( $req ).": No response yet\n";
            $pendingrequests++;
            if( isAlreadyProtected( $req ) && strpos( $req, "'''Automated comment:'''" ) === false && getProtectTime( $req ) > $PROTECT_BUFFER ) {
                echo getFullPageTitle( $req ).": Not handled, page is already protected\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This page appears to already be protected. Please confirm.~~~~\n\n", $rfppdata );
                continue;    
            }
            if( isRecentlyDenied( $req, $archivedata ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                echo getFullPageTitle( $req ).": Not handled, another request was recently denied (prot)\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' A request for protection/unprotection was recently made for this page, and was denied at some point within the last [[WP:RFPPA|".$ARCHIVE_LENGTH." days]].~~~~\n\n", $rfppdata );
                continue;   
            }
            if( isRequesterBlocked( $req ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                echo getFullPageTitle( $req ).": Not handled, requester is blocked (prot)\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This user who requested protection has been blocked.~~~~\n\n", $rfppdata );
                continue;    
            }  
        }
    }
    preg_match_all( '/(====.*?====.*?)(?===|$)/si', $unprotectionrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
            preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
            $code = strtolower( $code[1] );
            if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                $pendingrequests++;
                echo getFullPageTitle( $req ).": Discussion ongoing, not actioned yet\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "s", "semi", "pd", "pend", "p", "f", "full", "m", "move", "t", "salt", "fb", "feedback", "feed", "ap", "ispr", "ad", "isdo", "temp", "tp" ) ) ) {
                 if( isProtected( $req, $code ) ) {
                    echo getFullPageTitle( $req ).": Handled ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                 } else {
                     echo getFullPageTitle( $req ).": Marked as protected, but not protected\n";
                     if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && strpos( $req, "'''Automated comment:'''" ) === false ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' Page has not been protected.~~~~\n\n", $rfppdata );
                 }
                 continue;
            }
            if( in_array( $code, array( "u", "unpr", "au", "isun", "ad", "isdo" ) ) ) {
                if( isUnprotected( $req ) ) {
                    echo getFullPageTitle( $req ).": Handled ".timeSinceLastEdit." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                }  else {
                    if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && strpos( $req, "'''Automated comment:'''" ) === false ) {
                        echo getFullPageTitle( $req ).": Marked as unprotected, but not unprotected\n";
                        $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' Page has not been unprotected.~~~~\n\n" );
                    }
                    continue;
                }
            }
            if( in_array( $code, array( "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                echo getFullPageTitle( $req ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "do", "done" ) ) ) {
                echo getFullPageTitle( $req ).": Marked as done ".timeSinceLastEdit()." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                echo getFullPageTitle( $req ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            $pendingrequests++;
            echo getFullPageTitle( $req ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
            if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
        } else {
            echo getFullPageTitle( $req ).": No response yet\n";
            $pendingrequests++;
            if( preg_match( '/\'\'\'(.*?)\'\'\'/i', $req, $protstr ) ) $protstr = strtolower( $protstr[1] );
            else $protstr = "";
            if( strpos( $protstr, "unprotect" ) !== false ) {
                if( isAlreadyUnprotected( $req ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                    echo getFullPageTitle( $req ).": Not handled, page is already unprotected\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This page appears to have already been unprotected.  Please confirm.~~~~\n\n", $rfppdata );     
                    continue;
                }
            } else {
                if( isAlreadyProtected( $req ) && strpos( $req, "'''Automated comment:'''" ) === false && getProtectTime( $req ) > $PROTECT_BUFFER ) {
                    echo getFullPageTitle( $req ).": Not handled, page is already protected\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This page appears to already be protected. Please confirm.~~~~\n\n", $rfppdata );
                    continue;    
                }
            }
            if( isRecentlyDenied( $req, $archivedata ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                echo getFullPageTitle( $req ).": Not handled, another request was recently denied (unprot)\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' A request for protection/unprotection was recently made for this page, and was denied at some point within the last [[WP:RFPPA|".$ARCHIVE_LENGTH." days]].~~~~\n\n", $rfppdata );
                continue;   
            }
            if( isRequesterBlocked( $req ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                echo getFullPageTitle( $req ).": Not handled, requester is blocked (unprot)\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This user who requested unprotection has been blocked.~~~~\n\n", $rfppdata );
                continue;    
            } 
        }
    }
    preg_match_all( '/(====.*?====.*?)(?===|$)/si', $editrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
            preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
            $code = strtolower( $code[1] );
            if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                $pendingrequests++;
                echo getFullPageTitle( $req ).": Discussion ongoing, not actioned yet\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                echo getFullPageTitle( $req ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "do", "done" ) ) ) {
                echo getFullPageTitle( $req ).": Marked as done ".timeSinceLastEdit()." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                echo getFullPageTitle( $req ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                $tobearchived[] = trim( $req, "\n" )."\n\n";
                continue;
            }
            $pendingrequests++;
            echo getFullPageTitle( $req ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
            if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
        } else {
            echo getFullPageTitle( $req ).": No response yet\n";
            $pendingrequests++;
            if( isRequesterBlocked( $req ) && strpos( $req, "'''Automated comment:'''" ) === false ) {
                echo getFullPageTitle( $req ).": Not handled, requester is blocked (unprot)\n";
                $rfppdata = str_replace( $req, trim( $req, "\n" )."\n*'''Automated comment:''' This user who requested this edit has been blocked.~~~~\n\n", $rfppdata );
                continue;    
            } 
        }
    }
    echo $pendingrequests." pending requests.\n";
    if( $pendingrequests >= $BACKLOG_ADD_LIMIT ) {
        echo "Backlogged\n";
        $rfppdata = str_replace( "{{noadminbacklog}}", "{{adminbacklog}}", $rfppdata );
    }
    if( $pendingrequests <= $BACKLOG_REMOVE_LIMIT ) {
        echo "Not backlogged\n";
        $rfppdata = str_replace( "{{adminbacklog}}", "{{noadminbacklog}}", $rfppdata );
    }
processarchive:
		$archive = $site->initPage( $ARCHIVE_PAGE );
		$archivedata = $archive->get_text( true );
    preg_match_all( '/(\n==[^=].*?==.*?)(?=(?:\n==[^=])|$)/is', $archivedata, $oldarchivesections );
    $newarchivesections = array();
    foreach( $oldarchivesections[0] as $sect ) {
        preg_match( '/\n==\s*(.*?)\s*==/i', $sect, $datestr );
        $datestr = trim( $datestr[1] );
        $dt = strtotime( $datestr );
        if( (time() - $dt)/86400.0 < $ARCHIVE_LENGTH ) $newarchivesections[] = $sect; 
    }
    if( count( $tobearchived ) >= $MIN_ARCHIVE ) {
        foreach( $tobearchived as $req ) $rfppdata = str_replace( trim( $req ), "", $rfppdata );
        $today = time();
        if( count( $newarchivesections ) == 0 ) $newarchivesections = array_merge( array( "\n==".date( 'd F Y', $today )."==\n\n".implode( "", $tobearchived )."\n" ), $newarchivesections );
        else {
            preg_match( '/\n==\s*(.*?)\s*==/i', $newarchivesections[0], $temp );
            $newestdate = strtotime( trim( $temp[1] ) );
            if( date( 'Ymd', $newestdate ) == date( 'Ymd' ) ) {
                preg_match( '/\n==\s*.*?\s*==\n+/i', $newarchivesections[0], $headerend );
                $headerend = strpos( $newarchivesections[0], $headerend[0] ) + strlen( $headerend[0] );
                $newarchivesections[0] = substr( $newarchivesections[0], 0, $headerend )."\n".implode( "", $tobearchived )."\n".substr( $newarchivesections[0], $headerend );
            } else $newarchivesections = array_merge( array( "\n==".date( 'd F Y', $today )."==\n\n".implode( "", $tobearchived )."\n" ), $newarchivesections );
        }
    }
    $newarchivedata = "{{/Header}}\n{{TOC limit|2}}\n".implode( "\n", $newarchivesections );
    $newarchivedata = preg_replace( '/\n{3,}/i', "\n\n", $newarchivedata );
    $rfppdata = preg_replace( '/\n{3,}/i', "\n\n", $rfppdata );
    
    if( count( $tobearchived ) >= $MIN_ARCHIVE ) {
        if( str_replace( "\n", "", $rfppdata ) != str_replace( "\n", "", $oldrfppdata ) ) {
            $summary = "Bot clerking, archiving ".count( $tobearchived )." threads, ".$pendingrequests." pending ";
            if( $pendingrequests == 1 ) $summary .= "request remains.";
            else $summary .= "requests remain.";
            if( !$rfpp->edit( $rfppdata, $summary ) ) continue;  
        }
        if( $newarchivedata != $archivedata ) {
            $archive->edit( $newarchivedata, "Bot archiving ".count( $tobearchived )." old RFPP threads" );
            echo "Archiving ".count( $tobearchived )." requests.\n";
        }
    } else {
        if( str_replace( "\n", "", $rfppdata ) != str_replace( "\n", "", $oldrfppdata ) ) {
            $summary = "Bot clerking, ".$pendingrequests." pending ";
            if( $pendingrequests == 1 ) $summary .= "request remains.";
            else $summary .= "requests remain.";
            if( !$rfpp->edit( $rfppdata, $summary ) ) goto processarchive;  
        }
    }
    sleep( 60*$RUN_FREQUENCY );
}

function isAlreadyUnprotected( $req ) {
    global $site;
    $pagename = getFullPageTitle( $req );
    if( $pagename == "" ) return false;
    $page = $site->initPage( $pagename );
    if( preg_match( '/\'\'\'(.*?)\'\'\'/i', $req, $protstr ) ) $protstr = strtolower( $protstr[1] );
    else return false;
    if( $page->get_exists() ) {
        $protection = $page->get_protection();
        if( strpos( $protstr, "move" ) !== false ) {
            if( in_array_recursive( "move", $protection ) ) {
                foreach( $protection as $p ) {
                    if( $p['type'] == 'move' ) return false;
                }
            }
            return true;
        }
        if( in_array_recursive( "edit", $protection ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' ) return false;
            }
        }
        if( isPCprotected( $pagename ) ) return false;
        
        return true;
    }
    else {
        $protection = $site->initPage( $pagename );
        if( strpos( $protstr, "creat" ) !== false || strpos( $protstr, "salt" ) !== false ) {
            if( in_array_recursive( "create", $protection ) ) {
                foreach( $protection as $p ) {
                    if( $p['type'] == 'create' ) return false;
                }
            }
            return true;
        }
    }
    return false;  
}

function isUnprotected( $req ) {
    global $site;
    $pagename = getFullPageTitle( $req );
    if( $pagename = "" ) return true;
    $page = $site->initPage( $pagename );
    if( !$page->get_exists() ) return true;
    $r = $page->get_protection();
    if( empty( $r ) ) return true;
    return false;    
}

function isRequesterBlocked( $req ) {
    global $site;
    $endloc = strpos( $req, "(UTC)" );
    $startloc = strrpos(strtolower( $req ), "[[user", $endloc );
    preg_match( '/\[\[User(?: talk)?:(.*?)(?:\||\]\])/i', $req, $requester );
    $requester = trim( $requester[1] );
    $blocked = $site->initUser( $requester )->is_blocked();
    return $blocked;
}

function isRecentlyDenied( $req, $archivedata ) {
    preg_match( '/=\s*(\{\{l\w+\|.*?\}\})\s*=/i', $req, $header );
    if( strpos( $archivedata, $header[1] ) !== false ) {
        $start = strpos( $archivedata, $header[1] );
        $end = strpos( substr( $archivedata, $start ), "\n==" );
        preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $archivedata, $start, $end ), $code );
        $code = strtolower( $code[1] );
        if( in_array( $code, array( "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "nu", "noun", "cr", "nucr", "notd", "no", "rate", "her" ) ) ) return true;
    } 
    return false;   
}

function getProtectTime( $pagetitle ) {
    global $site;
    $pagetitle = getFullPageTitle( $pagetitle );
    $data = $site->apiQuery( array( 'action'=>'query',
        'list'=>'logevents',
        'leaction'=>'protect/protect',
        'lelimit'=>1,
        'leprop'=>'timestamp|user|details',
        'letitle'=>$pagetitle
    ));
    $dt = strtotime( $data['query']['logevents'][0]['timestamp'] );
    return round( $dt/60.0, 0 );    
}

function isAlreadyProtected( $req ) {
    global $site;
    $pagename = getFullPageTitle( $req );
    if( $pagename == "" ) return false;
    $page = $site->initPage( $pagename );
    if( preg_match( '/\'\'\'(.*?)\'\'\'/i', $req, $protstr ) ) $protstr = strtolower( $protstr[1] );
    else return false;
    if( $page->get_exists() ) {
        $protection = $page->get_protection();
        if( strpos( $protstr, "semi" ) !== false ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'autoconfirmed' ) return true;
            }
            return false;
        }
        if( strpos( $protstr, "full" ) !== false ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'sysop' ) return true;
            }   
            return false;
        }
    if( strpos( $protstr, "template" ) !== false ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'templateeditor' ) return true;
            }   
            return false;
        }
        if( strpos( $protstr, "move" ) !== false ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'move' ) return true;
            }
            return false;
        }
        if( strpos( $protstr, "pending" ) !== false && isPCprotected( $pagename ) ) return true;
        else return false;
    }
    else {
        $protection = $page->get_protection();
        if( strpos( $protstr, "creat" ) !== false || strpos( $protstr, "salt" ) !== false ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'create' ) return true;
            }
            return false;
        }
    }
    return false;
}

function isPCprotected( $pagetitle ) {
    global $site;
    $tArray = array (
        'action'=>'query',
        'prop'=>'flagged',
        'titles'=>$pagetitle
    );
    $data = $site->apiQuery( $tArray );
    foreach( $data['query']['pages'] as $d ) {
        if( isset( $d['flagged'] ) ) return true;

    }
    return false;
}

function timeSinceLastEdit( $req ) {
    preg_match_all( '/\d{2}:\d{2}, \d{1,2} (?:January|February|March|April|May|June|July|August|September|October|November|December) \d{4} \(UTC\)/i', $req, $edittimes );
    $dts = array();
    foreach( $edittimes[0] as $e ) $dts[] = strtotime( $e );
    return round( (time() - max( $dts ))/60.0, 0 );
}

function isProtected( $req, $code ) {
    global $site;
    $pagename = getFullPageTitle( $req );
    if( $pagename == "" ) return true;
    $page = $site->initPage( $pagename );
    if( $page->get_exists() ) {
        $protection = $page->get_protection();
        if( in_array( $code, array( "s", "semi" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'autoconfirmed' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "p", "full" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'sysop' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "tp", "temp" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'templateeditor' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "m", "move" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'move' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "pd", "pend" ) ) ) {
            return isPCprotected( $pagename );
        }
    } else {
        $protection = $page->get_protection();
        if( in_array( $code, array( "t", "salt" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'create' ) return true;
            }
            return false;
        }
    }
    return true;
}

function getFullPageTitle( $req ) {
    preg_match( '/=\s*\{\{(l\w+)\|(.*?)\}\}\s*=/i', $req, $header );
    $template = trim( $header[1] );
    $pagename = trim( $header[2] );
    $namespace = "";
    if( $template == "lat" ) $namespace = "Talk:";
    elseif( $template == "lt" ) $namespace = "Template:";
    elseif( $template == "ltt" ) $namespace = "Template talk:";
    elseif( $template == "lw" ) $namespace = "Wikipedia:";
    elseif( $template == "lwt" ) $namespace = "Wikipedia talk:";
    elseif( $template == "lu" ) $namespace = "User:";
    elseif( $template == "lut" ) $namespace = "User talk:";
    elseif( $template == "lc" ) $namespace = "Category:";
    elseif( $template == "lct" ) $namespace = "Category talk:";
    elseif( $template == "lp" ) $namespace = "Portal:";
    elseif( $template == "lpt" ) $namespace = "Portal talk:";
    elseif( $template == "lh" ) $namespace = "Help:";
    elseif( $template == "lht" ) $namespace = "Help talk:";
    elseif( $template == "lb" ) $namespace = "Book:";
    elseif( $template == "lbt" ) $namespace = "Book talk:";
    elseif( $template == "lmt" ) $namespace = "MediaWiki talk:";
    elseif( $template == "lf" ) $namespace = "File:";
    elseif( $template == "lft" ) $namespace = "File talk:";
    elseif( $template == "ln" ) {
        $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) ).":";
        $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
    }
    elseif( $template == "lnt" ) {
        $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) )." talk:";
        $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
    }
    if( preg_match( '/^(Talk|Template|Wikipedia|User|Category|Portal|Help|Book|MediaWiki)( talk)?:/i', $pagename, $garbage ) ) $pagename = substr( $pagename, strpos( $pagename, "|" ) );
    return $namespace.$pagename;
} 
?>
