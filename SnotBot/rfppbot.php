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
$notprotected = "*'''Automated comment:''' {{yo|{user}}} One or more pages in this request have not been protected.";
$alreadyunprotected = "*'''Automated comment:''' One or more pages in this request appear to have already been unprotected.  Please confirm.";
$alreadyprotected = "*'''Automated comment:''' One or more pages in this request appear to already be protected. Please confirm.";
$requesterblocked = "*'''Automated comment:''' This user who requested protection has been blocked.";
$notunprotected = "*'''Automated comment:''' {{yo|{user}}} One or more pages in this request have not been unprotected.";
$normalized = "*'''Automated comment:''' {{yo|{user}}} This request has been formatted the old way.  I have attempted to fix the request using the new formatting.";
$unreadable = "*'''Automated comment:''' {{yo|{user}}} This request cannot be parsed.  Please ensure it follows formatting consistent with the current or previous methods of submission.";

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
    $alreadydenied = "*'''Automated comment:''' A request for protection/unprotection for one or more pages in this request was recently made, and was denied at some point within the last [[WP:RFPPA|".$ARCHIVE_LENGTH." days]].";
  
    
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
    echo "Sorting requests...\n\n";
    $rfppdata = str_replace( $protectionrequests, sortSection( $protectionrequests )."\n\n", $rfppdata );
    $rfppdata = str_replace( $unprotectionrequests, sortSection( $unprotectionrequests )."\n\n", $rfppdata );
    $rfppdata = str_replace( $editrequests, sortSection( $editrequests )."\n\n", $rfppdata );
    
    preg_match_all( '/(===?=.*?===?=.*?)(?===|$)/si', $protectionrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( normalize( $req ) && !checkComments( $req, str_replace( "{user}", getRequester( $req ), $normalized ) ) ) {
            $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester( $req ), $normalized )."~~~~\n\n", $rfppdata );
        }
        if( getFullPageTitle( $req ) !== false ) {
            if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
                preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
                $code = strtolower( $code[1] );
                if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                    $pendingrequests++;
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Discussion ongoing, not actioned yet\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "s", "semi", "pd", "pend", "p", "f", "full", "m", "move", "t", "salt", "fb", "feedback", "feed", "ap", "ispr", "ad", "isdo", "temp", "tp", "pc", "pc1", "pc2" ) ) ) {
                     if( isProtected( $req, $code ) ) {
                        echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled ".timeSinceLastEdit( $req )." minutes ago\n";
                        if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                     } else {
                         echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as protected, but not protected\n";
                         if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && !checkComments( $req, str_replace( "{user}", getRequestHandler($req), $notprotected ) ) ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequestHandler($req), $notprotected )."~~~~\n\n", $rfppdata );
                     }
                     checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                     continue;
                }
                if( in_array( $code, array( "w", "aiv", "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "do", "done" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as done ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                    $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                $pendingrequests++;
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
            }
            else {
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": No response yet\n";
                $pendingrequests++;
                if( isAlreadyProtected( $req ) && !checkComments( $req, $alreadyprotected ) && getProtectTime( $req ) > $PROTECT_BUFFER ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, page is already protected\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$alreadyprotected~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) );  
                    continue;    
                }
                if( isRecentlyDenied( $req, $archivedata ) && !checkComments( $req, $alreadydenied ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, another request was recently denied (prot)\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$alreadydenied~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) );  
                    continue;   
                }
                if( isRequesterBlocked( $req ) && !checkComments( $req, $requesterblocked ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, requester is blocked (prot)\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$requesterblocked~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) );  
                    continue;    
                }
                checkComments( $req, false, false, getRequester( $req ) );  
            }
        } else {
            if( !checkComments( $req, str_replace( "{user}", getRequester($req), $unreadable ) ) ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester($req), $unreadable )."~~~~\n\n", $rfppdata );
        }
    }
    preg_match_all( '/(==?==.*?==?==.*?)(?===|$)/si', $unprotectionrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( normalize( $req ) && !checkComments( $req, str_replace( "{user}", getRequester( $req ), $normalized ) ) ) {
            $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester( $req ), $normalized )."~~~~\n\n", $rfppdata );
        }
        if( getFullPageTitle( $req ) !== false ) {
            if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
                preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
                $code = strtolower( $code[1] );
                if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                    $pendingrequests++;
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Discussion ongoing, not actioned yet\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "s", "semi", "pd", "pend", "p", "f", "full", "m", "move", "t", "salt", "fb", "feedback", "feed", "ap", "ispr", "ad", "isdo", "temp", "tp", "pc", "pc1", "pc2" ) ) ) {
                     if( isProtected( $req, $code ) ) {
                        echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled ".timeSinceLastEdit( $req )." minutes ago\n";
                        if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                        continue;
                     } else {
                         echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as protected, but not protected\n";
                         if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && !checkComments( $req, str_replace( "{user}", getRequestHandler($req), $notprotected ) ) ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequestHandler($req), $notprotected )."~~~~\n\n", $rfppdata );
                     }
                     checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                }    
                if( in_array( $code, array( "u", "unpr", "au", "isun", "ad", "isdo" ) ) ) {
                    if( isUnprotected( $req ) ) {
                        echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled ".timeSinceLastEdit." minutes ago\n";
                        if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";   
                    }  else {
                        if( timeSinceLastEdit( $req ) > $PROTECT_BUFFER && !checkComments( $req, str_replace( "{user}", getRequestHandler($req), $notunprotected ) ) ) {
                            echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as unprotected, but not unprotected\n";
                            $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequestHandler($req), $notunprotected )."~~~~\n\n" );
                        }
                        checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                        continue;
                    }
                }
                if( in_array( $code, array( "w", "aiv", "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "do", "done" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as done ".timeSinceLastEdit()." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                    $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                $pendingrequests++;
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
            } else {
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": No response yet\n";
                $pendingrequests++;
                if( preg_match_all( '/\'\'\'(.*?)\'\'\'/i', $req, $protstrs ) ) {
                    foreach( $protstrs[1] as $protstr) {
                        $protstr = strtolower( $protstr );
                        if( strpos( $protstr, "{{" ) === false ) break;
                    }                                        
                }
                else $protstr = "";
                if( strpos( $protstr, "unprotect" ) !== false ) {
                    if( isAlreadyUnprotected( $req ) && !checkComments( $req, $alreadyunprotected ) && getProtectTime( $req ) > $PROTECT_BUFFER) {
                        echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, page is already unprotected\n";
                        $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$alreadyunprotected~~~~\n\n", $rfppdata );   
                        checkComments( $req, false, false, getRequester( $req ) );  
                        continue;
                    }
                } else {
                    if( isAlreadyProtected( $req ) && !checkComments( $req, $alreadyprotected ) && getProtectTime( $req ) > $PROTECT_BUFFER ) {
                        echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, page is already protected\n";
                        $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$alreadyprotected~~~~\n\n", $rfppdata );
                        checkComments( $req, false, false, getRequester( $req ) );
                        continue;    
                    }
                }
                if( isRecentlyDenied( $req, $archivedata ) && !checkComments( $req, $alreadydenied ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, another request was recently denied (unprot)\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$alreadydenied~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) );
                    continue;   
                }
                if( isRequesterBlocked( $req ) && !checkComments( $req, $requesterblocked ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, requester is blocked (unprot)\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$requesterblocked~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) );
                    continue;    
                }
                checkComments( $req, false, false, getRequester( $req ) ); 
            }
        } else {
            if( !checkComments( $req, str_replace( "{user}", getRequester($req), $unreadable ) ) ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester($req), $unreadable )."~~~~\n\n", $rfppdata );
        }    
    }
    preg_match_all( '/(==?==.*?=?===.*?)(?===|$)/si', $editrequests, $requestsalpha );
    foreach( $requestsalpha[0] as $req ) {
        if( normalize( $req ) && !checkComments( $req, str_replace( "{user}", getRequester( $req ), $normalized ) ) ) {
            $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester( $req ), $normalized )."~~~~\n\n", $rfppdata );
        }
        if( getFullPageTitle( $req ) !== false ) {
            if( strpos( strtolower( $req ), "{{rfpp" ) !== false ) {
                preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $req, strrpos( strtolower( $req ), "{{rfpp" ) ), $code );
                $code = strtolower( $code[1] );
                if( in_array( $code, array( "ch", "chck", "q", "ques", "n", "note" ) ) ) {
                    $pendingrequests++;
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Discussion ongoing, not actioned yet\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_TIMEOUT ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "w", "aiv", "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "notd", "no", "rate", "her" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Declined ".timeSinceLastEdit( $req )." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_DENIED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "do", "done" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Marked as done ".timeSinceLastEdit()." minutes ago\n";
                    if( timeSinceLastEdit( $req ) > $ARCHIVE_FULFILLED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                if( in_array( $code, array( "ar", "arch", "archive" ) ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Immediate archiving requested ".timeSinceLastEdit( $req )." minutes ago\n";
                    $tobearchived[] = trim( $req, "\n" )."\n\n";
                    checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
                    continue;
                }
                $pendingrequests++;
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Handled, template parameter unparseable, last edit ".timeSinceLastEdit($req)." minutes ago\n";
                if( timeSinceLastEdit( $req ) > $ARCHIVE_CONFUSED ) $tobearchived[] = trim( $req, "\n" )."\n\n";
                checkComments( $req, false, $code, getRequester( $req ), getRequestHandler( $req ) );
            } else {
                echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": No response yet\n";
                $pendingrequests++;
                if( isRequesterBlocked( $req ) && !checkComments( $req, $requesterblocked ) ) {
                    echo ( is_array( getFullPageTitle( $req ) ) ? implode(",", getFullPageTitle( $req )) : getFullPageTitle( $req ) ).": Not handled, requester is blocked (unprot)\n";
                    $rfppdata = str_replace( $req, trim( $req, "\n" )."\n$requesterblocked~~~~\n\n", $rfppdata );
                    checkComments( $req, false, false, getRequester( $req ) ); 
                    continue;    
                }
                checkComments( $req, false, false, getRequester( $req ) ); 
            }    
        } else {
            if( !checkComments( $req, str_replace( "{user}", getRequester($req), $unreadable ) ) ) $rfppdata = str_replace( $req, trim( $req, "\n" )."\n".str_replace( "{user}", getRequester($req), $unreadable )."~~~~\n\n", $rfppdata );
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
            echo "Archiving ".count( $tobearchived )." requests.\n";
            if( !$archive->edit( $newarchivedata, "Bot archiving ".count( $tobearchived )." old RFPP threads" ) ) goto processarchive;
        }
    } else {
        if( str_replace( "\n", "", $rfppdata ) != str_replace( "\n", "", $oldrfppdata ) ) {
            $summary = "Bot clerking, ".$pendingrequests." pending ";
            if( $pendingrequests == 1 ) $summary .= "request remains.";
            else $summary .= "requests remain.";
            if( !$rfpp->edit( $rfppdata, $summary ) ) continue;  
        }
    }
    
    echo "Sleeping for $RUN_FREQUENCY minutes...\n\n";
    sleep( 60*$RUN_FREQUENCY );
}

function isAlreadyUnprotected( $req ) {
    global $site;
    if( preg_match_all( '/\'\'\'(.*?)\'\'\'/i', $req, $protstrs ) ) {
        foreach( $protstrs[1] as $protstr) {
            $protstr = strtolower( $protstr );
            if( strpos( $protstr, "{{" ) === false ) break;
        }                                        
    }
    else return false;
    $pagename = getFullPageTitle( $req );
    if( is_array( $pagename ) ) {
        foreach( $pagename as $tpage ) {
            $t = true;
            $page = $site->initPage( $tpage, null, false );
            if( $page->get_exists() ) {
                $protection = $page->get_protection();
                if( strpos( $protstr, "move" ) !== false ) {
                    if( in_array_recursive( "move", $protection ) ) {
                        foreach( $protection as $p ) {
                            if( $p['type'] == 'move' ) $t = false;
                        }
                    }
                }
                if( in_array_recursive( "edit", $protection ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' ) $t = false;
                    }
                }
                if( isPCprotected( $tpge ) ) $t = false;
                
            }
            else {
                $protection = $site->initPage( $tpage );
                if( strpos( $protstr, "creat" ) !== false || strpos( $protstr, "salt" ) !== false ) {
                    if( in_array_recursive( "create", $protection ) ) {
                        foreach( $protection as $p ) {
                            if( $p['type'] == 'create' ) $t = false;
                        }
                    }
                }
            }
            if( $t ) return true;
        }
        return false;
    }
    if( $pagename == "" || $pagename === false ) return false;
    $page = $site->initPage( $pagename, null, false );
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
    if( is_array( $pagename ) ) {
        foreach( $pagename as $tpage ) {
            $t = false;
            $page = $site->initPage( $tpage, null, false );
            $logs = $site->logs( "protect", false, $tpage );
            if( !$page->get_exists() ) $t = true;
            $r = $page->get_protection();
            if( empty( $r ) ) $t = true;
            if( !$t ) return false;    
        }
        return true;
    }
    if( $pagename == "" || $pagename === false ) return true;
    $page = $site->initPage( $pagename, null, false );
    $logs = $site->logs( "protect", false, $pagename );
    if( !$page->get_exists() ) return true;
    $r = $page->get_protection();
    if( empty( $r ) ) return true;
    return false;    
}

function isRequesterBlocked( $req ) {
    global $site;
    $endloc = strpos( $req, "(UTC)" );
    if( $endloc === false ) return false;
    $startloc = strrpos(strtolower( $req ), "[[user", $endloc-strlen( $req ) );
    preg_match( '/\[\[User(?: talk)?:(.*?)(?:\||\]\])/i', substr( $req, $startloc, $endloc-$startloc ), $requester );
    if( isset( $requester[1] ) ) $requester = trim( $requester[1] );
    else return false;
    $blocked = $site->initUser( $requester )->is_blocked();
    return $blocked;
}

function getRequester( $req ) {
    global $site;
    $endloc = strpos( $req, "(UTC)" );
    if( $endloc === false ) return false;
    $startloc = strrpos(strtolower( $req ), "[[user", $endloc-strlen( $req ) );
    preg_match( '/\[\[User(?: talk)?:(.*?)(?:\||\]\])/i', substr( $req, $startloc, $endloc-$startloc ), $requester );
    if( isset( $requester[1] ) ) $requester = trim( $requester[1] );
    else return false;
    return $requester;
}

function getRequestHandler( $req ) {
    $startloc = strrpos( strtolower( $req ), "{{rfpp" );
    preg_match( '/\[\[User(?: talk)?:(.*?)(?:\||\]\])/i', substr( $req, $startloc ), $handler );
    if( isset( $handler[1] ) ) $handler = trim( $handler[1] );
    else return false;
    return $handler;
}

function isRecentlyDenied( $req, $archivedata ) {
    preg_match( '/(\{\{[l|p]\w+\|.*?\}\})/i', $req, $header );
    if( strpos( $archivedata, $header[1] ) !== false ) {
        $start = strpos( $archivedata, $header[1] );
        $end = strpos( substr( $archivedata, $start ), "\n==" );
        preg_match( '/\{\{RFPP\s*\|\s*(.*?)\s*(?:\||\}\})/i', substr( $archivedata, $start, $end ), $code );
        $code = strtolower( $code[1] );
        if( in_array( $code, array( "w", "aiv", "d", "deny", "nea", "nact", "np", "npre", "nhr", "nhrt", "dr", "disp", "ut", "usta", "b", "bloc", "tb", "tabl", "nu", "noun", "cr", "nucr", "notd", "no", "rate", "her" ) ) ) return true;
    } 
    return false;   
}

function getProtectTime( $pagetitle ) {
    global $site;
    $lastprotectaction = 0;
    $pagename = getFullPageTitle( $pagetitle );
    if( is_array( $pagename ) ) {
        foreach( $pagename as $page ) {
            $logs = $site->logs( "protect", false, $page );
            if( isset( $logs[0]['timestamp'] ) && $lastprotectaction < strtotime( $logs[0]['timestamp'] ) ) $lastprotectaction = strtotime( $logs[0]['timestamp'] );
            $logs = $site->logs( "stable", false, $page );
            if( isset( $logs[0]['timestamp'] ) && $lastprotectaction < strtotime( $logs[0]['timestamp'] ) ) $lastprotectaction = strtotime( $logs[0]['timestamp'] );
            $dt = time() - $lastprotectaction;
        }
        return round( $dt/60.0, 0 );    
    }
    if( $pagename = "" || $pagename === false ) return 0;
    $logs = $site->logs( "protect", false, $pagename );
    if( isset( $logs[0]['timestamp'] ) ) $lastprotectaction = strtotime( $logs[0]['timestamp'] );
    $logs = $site->logs( "stable", false, $pagename );
    if( isset( $logs[0]['timestamp'] ) && $lastprotectaction < strtotime( $logs[0]['timestamp'] ) ) $lastprotectaction = strtotime( $logs[0]['timestamp'] );
    $dt = time() - $lastprotectaction;
    return round( $dt/60.0, 0 );    
}

function isAlreadyProtected( $req ) {
    global $site;
    if( preg_match_all( '/\'\'\'(.*?)\'\'\'/i', $req, $protstrs ) ) {
        foreach( $protstrs[1] as $protstr) {
            $protstr = strtolower( $protstr );
            if( strpos( $protstr, "{{" ) === false ) break;
        }                                        
    } else return false;
    $pagename = getFullPageTitle( $req );
    if( is_array( $pagename ) ) {
        foreach( $pagename as $tpage ) {
            $page = $site->initPage( $tpage, null, false );
            $t = false;
            if( $page->get_exists() ) {
                $protection = $page->get_protection();
                if( strpos( $protstr, "semi" ) !== false ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'autoconfirmed' ) $t = true;
                    }
                }
                if( strpos( $protstr, "full" ) !== false ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'sysop' ) $t = true;
                    }   
                }
                if( strpos( $protstr, "template" ) !== false ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'templateeditor' ) $t = true;
                    }   
                }
                if( strpos( $protstr, "move" ) !== false ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'move' ) $t = true;
                    }
                }
                if( strpos( $protstr, "pending" ) !== false && isPCprotected( $tpage ) ) $t = true;
            }
            else {
                $protection = $page->get_protection();
                if( strpos( $protstr, "creat" ) !== false || strpos( $protstr, "salt" ) !== false ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'create' ) $t = true;
                    }
                }
            }
            if( $t == true ) return true;
        }
        return false; 
    }
    if( $pagename == "" || $pagename === false ) return false;
    $page = $site->initPage( $pagename, null, false );
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

function getRequestTimeUnix( $req ) {
    if( preg_match( '/\d{2}:\d{2}, \d{1,2} (?:January|February|March|April|May|June|July|August|September|October|November|December) \d{4} \(UTC\)/i', $req, $edittimes ) ) return strtotime( $edittimes[0] );
    else return 0;
}

function isProtected( $req, $code ) {
    global $site;
    $pagename = getFullPageTitle( $req );
    if( is_array( $pagename ) ) {
        foreach( $pagename as $tpage ) {
            $page = $site->initPage( $tpage, null, false );
            if( $page->get_exists() ) {
                $protection = $page->get_protection();
                $t = false;
                if( in_array( $code, array( "s", "semi", "ap", "ad", "ispr", "isdo" ) ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'autoconfirmed' ) $t = true;
                    }
                }
                if( in_array( $code, array( "p", "full", "f", "ap", "ad", "ispr", "isdo" ) ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'sysop' ) $t = true;
                    }
                }
                if( in_array( $code, array( "tp", "temp", "ap", "ad", "ispr", "isdo" ) ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'edit' && $p['level'] == 'templateeditor' ) $t = true;
                    }
                }
                if( in_array( $code, array( "m", "move", "ap", "ad", "ispr", "isdo" ) ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'move' ) $t = true;
                    }
                }
                if( in_array( $code, array( "pd", "pend", "pc", "pc1", "pc2", "ap", "ad", "ispr", "isdo" ) ) ) {
                    $t = isPCprotected( $tpage );
                }
                if( $t == false ) return false;
            } else {
                $t = false;
                $protection = $page->get_protection();
                if( in_array( $code, array( "t", "salt", "ap", "ad", "ispr", "isdo" ) ) ) {
                    foreach( $protection as $p ) {
                        if( $p['type'] == 'create' ) $t = true;
                    }
                }
                if( $t == false ) return false;
            }
        }
        return true;
    }
    if( $pagename == "" || $pagename === false ) return true;
    $page = $site->initPage( $pagename, null, false );
    if( $page->get_exists() ) {
        $protection = $page->get_protection();
        if( in_array( $code, array( "s", "semi", "ap", "ad", "ispr", "isdo" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'autoconfirmed' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "p", "full", "f", "ap", "ad", "ispr", "isdo" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'sysop' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "tp", "temp", "ap", "ad", "ispr", "isdo" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'edit' && $p['level'] == 'templateeditor' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "m", "move", "ap", "ad", "ispr", "isdo" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'move' ) return true;
            }
            return false;
        }
        if( in_array( $code, array( "pd", "pend", "pc", "pc1", "pc2", "ap", "ad", "ispr", "isdo" ) ) ) {
            return isPCprotected( $pagename );
        }
    } else {
        $protection = $page->get_protection();
        if( in_array( $code, array( "t", "salt", "ap", "ad", "ispr", "isdo" ) ) ) {
            foreach( $protection as $p ) {
                if( $p['type'] == 'create' ) return true;
            }
            return false;
        }
    }
    return false;
}

function normalize( &$req ) {
    global $rfppdata;
    if( preg_match( '/====\s*\{\{([l|p]\w+)\|(.*?)\}\}\s*====/i', $req, $header ) ) {
        $rfppdata = str_replace( $req, $req = preg_replace( '/====\s*\{\{([l|p]\w+)\|(.*?)\}\}\s*====/i', "=== [[".getFullPageTitle( $req )."]] ===\n*{{".trim( $header[1] )."|".trim( $header[2] )."}}\n\n", $req ), $rfppdata );
        return true;
    }  
    return false; 
}

function sortSection( &$section ) {
    preg_match_all( '/(===?=.*?===?=.*?)(?===|$)/si', $section, $requestsalpha );
    $new_array = array();
    foreach( $requestsalpha[0] as $req ) {
        $new_array[getRequestTimeUnix( $req )] = trim($req);  
    }
    ksort( $new_array );
    $section = preg_replace( '/(===?=.*?===?=.*?)(?===|$)/si', "", $section );
    $section = trim( $section );
    $section .= "\n\n".implode( "\n\n", $new_array );
    return $section;
}

function checkComments( &$req, $searchValue = false, $code = "", $requester = false, $handler = false ) {
    global $rfppdata, $notprotected, $notunprotected, $requesterblocked, $unreadable;
    if( $handler === false ) $handler = "";
    if( $searchValue === false ) {
        $req2 = $req;  
        $req2 = str_replace( str_replace( "{user}", $requester, $unreadable ), "<s>".str_replace( "*","",str_replace( "{user}", $requester, $unreadable ) )."</s>", $req );
        if( strpos( $req, str_replace( "{user}", $handler, $notprotected ) ) !== false && strpos( $req, "<s>".str_replace( "*","",str_replace( "{user}", $handler, $notprotected ))."</s>" ) === false ) {
            if( isProtected( $req, $code ) ) {
                $req2 = str_replace( str_replace( "{user}", $handler, $notprotected ), "<s>".str_replace( "*","",str_replace( "{user}", $handler, $notprotected ))."</s>", $req );   
            } 
        }
        if( strpos( $req, str_replace( "{user}", $handler, $notunprotected ) ) !== false && strpos( $req, "<s>".str_replace( "*","",str_replace( "{user}", $handler, $notunprotected ))."</s>" ) === false ) {
            if( isUnprotected( $req ) ) {
                $req2 = str_replace( str_replace( "{user}", $handler, $notunprotected ), "<s>".str_replace( "*","",str_replace( "{user}", $handler, $notunprotected ))."</s>", $req );   
            } 
        }
        if( strpos( $req, $requesterblocked ) !== false && strpos( $req, "<s>".str_replace( "*","",$requesterblocked)."</s>" ) === false ) {
            if( !isRequesterBlocked( $req ) ) {
                $req2 = str_replace( $requesterblocked, "<s>".str_replace( "*","",$requesterblocked)."</s>", $req );   
            } 
        }
        $rfppdata = str_replace( $req, $req2, $rfppdata );
        $req = $req2;
        unset( $req2 );
        return true;
    } else {
        if( strpos( $req, $searchValue ) !== false ) return true;
        else return false;
    }
}

function getFullPageTitle( $req ) {
    preg_match_all( '/\{\{([l|p]\w+)\|(.*?)\}\}/i', $req, $header );
    if( count($header[0]) > 1 ) {
        $returnArray = array();
        foreach( $header[0] as $t=>$p ) {
            $template = trim( $header[1][$t] );
            $pagename = trim( $header[2][$t] );
            $namespace = "";
            if( $template == "lat" ) $namespace = "Talk:";
            elseif( $template == "ld" ) $namespace = "Draft:";
            elseif( $template == "ldt" ) $namespace = "Draft talk:";
            elseif( $template == "lt" ) $namespace = "Template:";
            elseif( $template == "ltt" ) $namespace = "Template talk:";
            elseif( $template == "lw" ) $namespace = "Wikipedia:";
            elseif( $template == "lwt" ) $namespace = "Wikipedia talk:";
            elseif( $template == "lafd" ) $namespace = "Wikipedia:Articles for deletion/";
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
            elseif( $template == "lm" ) $namespace = "MediaWiki:";
            elseif( $template == "lmt" ) $namespace = "MediaWiki talk:";
            elseif( $template == "lf" ) $namespace = "File:";
            elseif( $template == "lft" ) $namespace = "File talk:";
            elseif( $template == "lttxt" ) $namespace = "TimedTex:";
            elseif( $template == "ltxtt" ) $namespace = "TimedText talk:";
            elseif( $template == "lmd" ) $namespace = "Module:";
            elseif( $template == "lmdt" ) $namespace = "Module talk:";
            elseif( $template == "ln" ) {
                $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) ).":";
                $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
            }
            elseif( $template == "lnt" ) {
                $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) )." talk:";
                $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
            }
            elseif( $template == "pagelinks" ) {
                if( strpos( $pagename, ":" ) !== false ) $namespace = substr( $pagename, 0, strpos( $pagename, ":" ) ).":";
                else $namespace = "";
                if( strpos( $pagename, ":" ) !== false ) $pagename = substr( $pagename, strpos( $pagename, ":" )+1 );
            }
            if( preg_match( '/^(Talk|Template|Wikipedia|User|Category|Portal|Help|Book|MediaWiki)( talk)?:/i', $pagename, $garbage ) ) $pagename = substr( $pagename, strpos( $pagename, "|" ) );
            $returnArray[] = $namespace.$pagename;
        }
        return $returnArray;    
    }
    if( count( $header[0] ) == 0 ) return false;
    $template = trim( $header[1][0] );
    $pagename = trim( $header[2][0] );
    $namespace = "";
    if( $template == "lat" ) $namespace = "Talk:";
    elseif( $template == "ld" ) $namespace = "Draft:";
    elseif( $template == "ldt" ) $namespace = "Draft talk:";
    elseif( $template == "lt" ) $namespace = "Template:";
    elseif( $template == "ltt" ) $namespace = "Template talk:";
    elseif( $template == "lw" ) $namespace = "Wikipedia:";
    elseif( $template == "lwt" ) $namespace = "Wikipedia talk:";
    elseif( $template == "lafd" ) $namespace = "Wikipedia:Articles for deletion/";
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
    elseif( $template == "lm" ) $namespace = "MediaWiki:";
    elseif( $template == "lmt" ) $namespace = "MediaWiki talk:";
    elseif( $template == "lf" ) $namespace = "File:";
    elseif( $template == "lft" ) $namespace = "File talk:";
    elseif( $template == "lttxt" ) $namespace = "TimedTex:";
    elseif( $template == "ltxtt" ) $namespace = "TimedText talk:";
    elseif( $template == "lmd" ) $namespace = "Module:";
    elseif( $template == "lmdt" ) $namespace = "Module talk:";
    elseif( $template == "ln" ) {
        $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) ).":";
        $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
    }
    elseif( $template == "lnt" ) {
        $namespace = substr( $pagename, 0, strpos( $pagename, "|" ) )." talk:";
        $pagename = substr( $pagename, strpos( $pagename, "|" )+1 );
    }
    elseif( $template == "pagelinks" ) {
        if( strpos( $pagename, ":" ) !== false ) $namespace = substr( $pagename, 0, strpos( $pagename, ":" ) ).":";
        else $namespace = "";
        if( strpos( $pagename, ":" ) !== false ) $pagename = substr( $pagename, strpos( $pagename, ":" )+1 );
    }
    if( preg_match( '/^(Talk|Template|Wikipedia|User|Category|Portal|Help|Book|MediaWiki)( talk)?:/i', $pagename, $garbage ) ) $pagename = substr( $pagename, strpos( $pagename, "|" ) );
    return $namespace.$pagename;
} 
?>
