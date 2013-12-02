<?php

ini_set('memory_limit','5G');
echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";
require_once( '/data/project/cyberbot/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );
$site->set_runpage( "User:Cyberbot I/Run/AfDBot" );

$blacklist = array( "Wikipedia:Articles for deletion/Log/Today", "Wikipedia:Articles for deletion/Log/Yesterday", "Wikipedia:XfD today" );

$checkedafds = array();
$checkedpages = array();
$lastredlinked = array();   //the redlinked AfD templates which were found in the last run, only display if they were found in 2 consecutive runs
$afdscores = array();       //{"AfD page title":float score} - Score for how urgently an afd needs attention
$afdstats = array();        //{"AfD page title":[delete,keep,merge,redirect,speedykeep,speedydelete,other,creationdate,closingdate]}
$frequency = 1;            //in minutes, how often to run
$cyclereset = (int)(240/$frequency);//cycle resets every 4 hours, for 6 hours make it 360/frequency, for 12 hours make it 720/frequency, for 24 hours make it 1440/frequency
$cyclecounter = 0;
$voteregex = '/\'{3}?.*?\'{3}?.*?(?:(?:\[\[User.*?\]\].*?\(UTC\))|(?:\{\{unsigned.*?\}\})|(?:<!--\s*Template:Unsigned\s*-->))/i';

while( true ) {
    echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
    $cyclecounter++;
    if( $cyclecounter == 1 ) {
        echo "Doing a full check.\n";
        $checkedafds = array();
        $checkedpages = array();
        $afdscores = array();
    }
    if( $cyclecounter >= $cyclereset ) $cyclecounter = 0;
    $afdpages = $site->categorymembers( "Category:AfD debates", false, null, -1 );
    $logpages = getLogPages();
    $targetredirects = array();
    $closedwrong = array();
    $manualcat = array();
    foreach( $afdpages as $afd ) {
        $object = $site->initPage( $afd['title'] );
        if( in_array( $afd['title'], $blacklist ) ) continue;
        if( substr( $afd['title'], 0, 36 ) == "Wikipedia:Articles for deletion/Log/" ) continue;
        if( in_array( $afd['title'], $checkedafds ) ) continue;
        if( !($object->get_exists()) ) {
            echo "Whoa.  $afd doesn't exist...?\n";
            continue;
        }
        if( $afd['title'] == $object->get_title( false ) ) $checkedafds[] = $object->get_title();
        $checkedafds[] = $afd['title'];
        $afddata = $object->get_text( true );
        $afddata = preg_replace( '/<(s|strike|del)>.*?<\/(s|strike|del)>/i', '', $afddata );
        if( in_string( "[[category:afd debates]]", strtolower( $afddata ) ) ) {
            $manualcat[] = $afd['title'];
            $checkedafds = array_diff( $checkedafds, array( $afd['title'] ) );
        }
        if( in_string( "The following discussion is an archived debate of the proposed deletion of the article below", $afddata ) || in_string( "This page is an archive of the proposed deletion of the article below.", $afddata ) || in_string( "'''This page is no longer live.'''", $afddata ) ) {
            if( !closeitup( $object ) ) {
                $closedwrong[] = $afd['title'];
                $checkedafds = array_diff( $checkedafds, array( $afd['title'] ) );
            }
        } else {
            $creationdate = getCreationDate( $object );   
            $logpagename = logPageName($creationdate);
            if( !in_array( $logpagename, $logpages ) ) {
                $logpagedata = $site->initPage( $logpagename )->get_text( true );
                if( str_ireplace( str_replace( "_", " ", $afd['title'] ), "", str_replace( "_", " ", $logpagedata ) ) == str_replace( "_", " ", $logpagedata ) ) {
                    transclude( $afd['title'], logPageName( time() ) );
                }
            }
            else {
                if( str_ireplace( str_replace( "_", " ", $afd['title'] ), str_replace( "_", " ", $logpages[$logpagename] ) ) == str_replace( "_", " ", $logpages[$logpagename] ) ) {
                    if( time() - $creationdate > 600 ) {
                        transclude( $afd['title'], logPageName( time() ) );
                    }
                    else {
                        $checkedafds = array_diff( $checkedafds, array( $afd['title'] ) );
                    } 
                } 
            }
        }
        if( $cyclecounter == 1 ) {
             $afdscore = 0;
            $afdstat = array( 0, 0, 0, 0, 0, 0, 0, 0, 0 );
            preg_match_all( $voteregex, substr( $afddata, strpos( $afddata, "==" ) ), $votes );
            $votecount = 0;
            foreach( $votes[0] as $vote ) {
                $votecount += parsevote(substr($vote, 2, strpos($vote, "'", 3)-1));
                $votetype = parsevotetype(substr($vote, 2, strpos($vote, "'", 3)-1));
                if( !is_null( $votetype ) ) $afdstat[$votetype]++;
            }
            switch( $votecount ) {
                case 0:
                $afdscore += 200;
                break;
                case 1:
                $afdscore += 150;
                break;
                case 2:
                $afdscore += 50;
                break;
                case 3:
                $afdscore += 0;
                break;
                case 4:
                $afdscore -= 50;
                break;
                default:
                $afdscore -= 1000;
                break;
            }
            if( strlen( $afddata ) <= 2000 ) $afdscore += 25;
            elseif( strlen( $afddata ) <= 5000 ) $afdscore += 10;
            elseif( strlen( $afddata ) <= 10000 ) $afdscore -= 10;
            else $afdscore -= 25;
            
            $afdcats = $object->get_categories();
            $timesrelisted = 0;
            foreach( $afdcats as $c ) {
                if( $c == "Category:Relisted AfD debates" ) {
                    preg_match_all( '/(\d{2}:\d{2}, \d{1,2} [A-Za-z]* \d{4}) \(UTC\)\<\/small\>\<\!\-\- from Template\:Relist \-\-\>/i', $afddata, $relists );
                    $timesrelisted = count( $relists[0] );
                    $afdclosingdate = formatdate( $relists[1][$timesrelisted-1].' + 7 days');
                    break;
                }
            }
            $temp = $object->history( 1, "newer" );
            $afdcreationdate = formatdate( $temp[0]['timestamp'] );
            unset($temp);
            if( $timesrelisted == 0 ) $afdclosingdate = $afdcreationdate + (60*60*24*7);
            $hourstillclose = ($afdclosingdate-time())/3600;
            $afdstat[7] = $afdcreationdate;
            $afdstat[8] = $afdclosingdate;
            if( $hourstillclose >= 120 ) $afdscore -= 1000;
            else $afdscore += (190 - (3.0 * $hourstillclose ) );
            
            if( $timesrelisted > 3 ) $timesrelisted = 3;
            $afdscore += ( $timesrelisted * 15 );
            
            $afdscores[$afd['title']] = array( $afdscore, date( 'YmdHis', $afdclosingdate ), $votecount, strlen(( $afddata )), $timesrelisted );
            $afdstats[$afd['title']] = $afdstat; 
        }
        $targetpagestrresult = preg_match( '/\=\=\=\s*\[\[(.*?)\]\]\s*\=\=\=/i', $afddata, $targetpagestr );
        if( !$targetpagestrresult ) {
            echo "Error: Can't find target page for \"{$afd['title']}\"\n";
            continue;
        }
        $targetpage = $site->initPage( $targetpagestr[1] );
        if( !$targetpage->get_exists() ) {
            echo "Target page is {$targetpage->get_title( false )}, but it doesn't exist.";
            continue;
        }
        $redir = false;
        if( $targetpage->redirectFollowed() ) $redir = true;
        $targetpagedata = $targetpage->get_text( true );
        $templateonpage = preg_match( '/\{\{\s*((Article for deletion\/dated)|(AfDM))/si', str_replace( '_', ' ', $targetpagedata ), $templateonpage );
        if( !$templateonpage ) {
            if( $redir ) {
                $targetredirects[] = $afd['title'];
                $checkedafds = array_diff( $checkedafds, array( $afd['title'] ) );
            }
            else {
                if( strpos( $afddata, "The following discussion is an archived debate of the proposed deletion of the article below" ) || strpos( $afddata, "This page is an archive of the proposed deletion of the article below." ) || strpos( $afddata, "'''This page is no longer live.'''" ) ) {
                    if( !in_array( $afd['title'], $closedwrong ) ) $closedwrong[] = $afd['title'];
                    else {
                        $itworked = addAfdTemplate( $targetpage, $object );
                        if( !$itworked ) $checkedafds = array_diff( $checkedafds, array( $afd['title'] ) );
                    }
                }
            }
        }
    }
    if( $cyclecounter == 1 ) {
        $urgentstr = "__NOTOC__\nBelow are the top 25 [[WP:AFD|AfD]] discussions which are most urgently in need of attention from !voters.  The urgency for each AfD is calculated based on various statistics, including current number of votes, time until closing date, number of times relisted, overall discussion length, etc.  This page is updated by a [[User:Cyberbot I|bot]] roughly every 6 hours, and was last updated on ~~~~~.\n\n";
        $urgentstr .= "{|class=\"wikitable\"\n!AfD\n!Time to close\n!Votes\n!Size (bytes)\n!Relists\n!Score";
        //There is probably some PHP function for this like array_multisort(), but I can't quite figure out how to get the correct results, so I'm using my own written sorter instead.
        $temp = array();
        while( count( $afdscores ) > count( $temp ) && count( $temp ) < 25 ) {
            $loopvalue = -100000000;
            foreach( $afdscores as $id=>$afd ) {
                if( $afd[0] > $loopvalue ) {
                    if( isset( $maxvalue ) && $loopvalue <= $maxvalue ) {
                        $loopvalue = $afd[0];
                        $loopid = $id;
                        $loopafd = $afd;
                    }
                    elseif( !isset( $maxvalue ) ) {
                        $loopvalue = $afd[0];
                        $loopid = $id;
                        $loopafd = $afd;
                    }
                    else continue;
                }          
            }
            $maxvalue = $loopvalue;
            $temp[$loopid] = $loopafd;
            unset( $afdscores[$loopid] );
        }
        $afdscores = $temp;
        unset( $loopvalue );
        unset( $loopid );
        unset( $loopafd );
        unset( $maxvalue );
        unset( $temp );
        unset( $id );
        unset( $afd );
        foreach( $afdscores as $afd=>$content ) {
            if( strpos( $afd, "User:Snotbot" )!== false || strpos( $afd, "User:Cyberbot" ) !== false ) continue;
            $urgentstr .= "\n|-\n";
            $urgentstr .= "|[[#".preg_replace( '/\(\d.+? nomination\)/i', '', str_replace( "Wikipedia:Articles for deletion/", "", $afd ) )."|".str_replace( "Wikipedia:Articles for deletion/", "", $afd )."]]";
            $urgentstr .= "||{{Time ago|".$content[1]."}}";
            $urgentstr .= "||".$content[2];
            $urgentstr .= "||".$content[3];
            $urgentstr .= "||".$content[4];
            $urgentstr .= "||'''".round( $content[0], 2 )."'''";    
        } 
        $urgentstr .= "\n|}\n\n";
        foreach ($afdscores as $afd=>$content ) $urgentstr .= "{{".$afd."}}\n";
        $site->initPage( "User:Snotbot/AfD's requiring attention" )->edit( "<big>{{red|The page is now updated at [[User:Cyberbot I/AfD's requiring attention]].  Please change links accordingly.  You can still see the table below.}}\n{{User:Cyberbot I/AfD's requiring attention}}", "Redirecting to new page location." );
        $site->initPage( "User:Cyberbot I/AfD's requiring attention" )->edit( $urgentstr, "Updating list of AfD's which require urgent attention." );  
        $statstr =  "Below is a table summarizing the currently open [[WP:AFD|AfD]] discussions, their current vote tallies, how long they've been open, and how long until they close.  The table is sortable by any parameter.  Contact [[User:Cyberpower678|Cyberpower678]] with questions.  This table was last updated on ~~~~~, and is generally updated once every 6-8 hours.\n\n";
        $statstr .= "{|class=\"wikitable sortable\"\n!AfD\n!Votes\n!%Delete\n!D\n!K\n!M\n!R\n!SK\n!SD\n!Other\n!Time open\n!Time until close\n";
        ksort( $afdstats );
        foreach( $afdstats as $afd=>$content ) {
            $statstr .= "\n|-\n";
            $statstr .= "|[[".$afd."|".processTitle( $afd )."]]";      
            $totalvotes = $content[0]+$content[1]+$content[2]+$content[3]+$content[4]+$content[5]+$content[6];
            $statstr .= "||$totalvotes";
            if( $totalvotes > 0 ) $statstr .= "||".round( 100.0*$content[0]/$totalvotes, 0 )."%";
            else $statstr .= "||0%";
            for( $i = 0; $i < 7; $i++ ) {
                if( $content[$i] == max( $content[0], $content[1], $content[2], $content[3], $content[4], $content[5], $content[6] ) ) {
                    $statstr .= "||style=\"background-color:#9f9\"|".$content[$i];
                }
                else $statstr .= "||".$content[$i];
            }
            $statstr .= "||{{ntsh|".round( time() - $content[7], 0 )."}}".formatcreationdate( time() - $content[7] );
            if( time() - $content[8] > 0 ) $statstr .= "||style=\"background-color:#f99\"|{{ntsh|".round( (time() - $content[8] ), 0 )."}}".formatclosingdate( time() - $content[8] );
            else $statstr .= "||{{ntsh|".round( ( time() - $content[8] ), 0 )."}}".formatclosingdate( time() - $content[8] );
        }
        $statstr .= "\n|}\n\n";
        $site->initPage( "User:Snotbot/Current AfD's" )->edit( "<big>{{red|The page is now updated at [[User:Cyberbot I/Current AfD's]].  Please change links accordingly.  You can still see the table below.}}\n{{User:Cyberbot I/Current AfD's}}", "Redirecting to new page location." );
        $site->initPage( "User:Cyberbot I/Current AfD's" )->edit( $statstr, "Updating list of current AfD's." );  
    }
    $deletionpages = $site->categorymembers( "Category:Articles for deletion", false, null, -1 );
    $noafd = array();
    $redlinked = array();
    $outdated = array();
    $wrongnamespace = array();
    foreach( $deletionpages as $page ) {
        $object = $site->initPage( $page['title'] );
        if( in_array( $page['title'], $checkedpages ) ) continue;
        if( !$object->get_exists() ) continue;
        if( $object->get_namespace( true ) != 0 ) {
            if( substr( $object->get_title(), 0, 29 ) == "Template:Article for deletion" ) continue;
            $wrongnamespace[] = $page['title'];
            continue;
        }
        $object->purge();
        $data = $object->get_text();
        $templateparamresult = preg_match( '/\{\{\s*((Article for deletion\/dated)|(AfDM))\|.*?page\s*=(?P<page>.*?)(?:\||\}\})/i', $data, $templateparam );
        $good = null;
        if( $templateparamresult ) {
            $afdpage = $site->initPage( "Wikipedia:Articles for deletion/".$templateparam['page'] );
            $good = goodAfd( $afdpage );
            if( $good == 0 ) $redlinked[] = $page['title'];
            elseif( $good == 1 ) $outdated[] = $page['title'];
        }
        else {
            $afdpage = $site->initPage( "Wikipedia:Articles for deletion/".$page['title'] );
            $good = goodAfd( $afdpage );
            if( $good == 0 ) $noafd[] = $page['title'];
            if( $good == 1 ) $outdated[] = $page['title'];
        }
        if( $good == 2 ) $checkedpages[] = $page['title'];
    }
    $redlinked2 = array();
    foreach( $redlinked as $r ) if( isset( $lastredlinked ) && in_array( $r, $lastredlinked ) ) $redlinked2[] = $r;
    $lastredlinked = $redlinked;
    updateReportPage( $noafd, $redlinked2, $outdated, $wrongnamespace, $targetredirects, $closedwrong, $manualcat );
    $timetowait = $frequency*60;
    echo "Sleeping $timetowait seconds...";
    sleep($timetowait);
}

function updateReportPage( $noafd, $redlinked, $outdated, $wrongnamespace, $targetredirects, $closedwrong, $manualcat ) {
    global $site;
    $printstr = "This list was created by an automated process and is updated regularly. Please report any bugs at [[User talk:Cyberpower678]].  Last updated on ~~~~~.\n<!--Start-->";
    if( !empty( $noafd ) ) {
        $printstr .= "\n==Articles tagged for deletion with no deletion discussion==\n";
        foreach( $noafd as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $redlinked ) ) {
        $printstr .= "\n==Articles with redlinked AfD templates==\n";
        foreach( $redlinked as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $outdated ) ) {
        $printstr .= "\n==Articles with links to closed AfD's==\n";
        foreach( $outdated as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $wrongnamespace ) ) {
        $printstr .= "\n==AfD templates in the wrong namespace==\n";
        foreach( $wrongnamespace as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $targetredirects ) ) {
        $printstr .= "\n==AfD's pointing to redirects whose target has no AfD template==\n";
        foreach( $targetredirects as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $closedwrong) ) {
        $printstr .= "\n==AfD's which are apparently closed but remain in CAT:AFD==\n";
        foreach( $closedwrong as $i ) $printstr .= "*[[$i]]\n";   
    }
    if( !empty( $manualcat ) ) {
        $printstr .= "\n==AfD's which were manually added into CAT:AFD==\n";
        foreach( $manualcat as $i ) $printstr .= "*[[$i]]\n";   
    }
    $currentdata = $site->initPage( "User:Cyberbot I/AfD report" )->get_text();
    $temp1 = explode( "<!--Start-->", $currentdata );
    $temp2 = explode( "<!--Start-->", $printstr );
    if( $temp1[1]."\n" == $temp2[1] || $temp1[1] == $temp2[1] ) {
        echo "No change to report page since last time.\n";
        return;
    }
    $totalproblems = count( $noafd ) + count( $redlinked ) + count( $outdated ) + count( $wrongnamespace ) + count( $targetredirects ) + count( $closedwrong );
    $site->initPage( "User:Snotbot/AfD report" )->edit( "<big>{{red|The page is now updated at [[User:Cyberbot I/AfD report]].  Please change links accordingly.  You can still see the table below.}}\n{{User:Cyberbot I/AfD report}}", "Redirecting to new page location." );
    $site->initPage( "User:Cyberbot I/AfD report" )->edit( $printstr, "Updating report table, $totalproblems problematic articles" );
}

function goodAfd( $page ) {
    if( !$page->get_exists() ) return 0;
    $data = $page->get_text();
    if( strpos( $data, "The following discussion is an archived debate of the proposed deletion of the article below" ) !== false || strpos( $data, "This page is an archive of the proposed deletion of the article below." ) !== false || strpos( $data, "'''This page is no longer live.'''" ) !== false ) return 1;
    return 2;
}

function formatclosingdate( $t ) {
    if( $t >= 0 ) {
        if( $t < 60 ) return round( $t, 0 )." seconds ago";
        if( $t < 3600 ) return round( $t/60.0, 0 )." minutes ago";
        if( $t < 86400 ) return round( $t/3600.0, 0 )." hours ago";
        return round( $t/86400.0, 0 )." days ago";
    } else {
        if( -$t < 60 ) return round( -$t, 0 )." seconds";
        if( -$t < 3600 ) return round( -$t/60.0, 0 )." minutes";
        if( -$t < 86400 ) return round( -$t/3600.0, 0 )." hours";
        return round( -$t/86400.0, 0 )." days";
    }    
}

function formatcreationdate( $t ) {
    if( $t < 60 ) return round( $t, 0 )." seconds";
    if( $t < 3600 ) return round( $t/60.0, 0 )." minutes";
    if( $t < 86400 ) return round( $t/3600.0, 0 )." hours";
    return round( $t/86400.0, 0 )." days";
}

function processTitle( $t ) {
    $t = str_replace( "Wikipedia:Articles for deletion/", "", $t );
    $t = preg_replace( '/\(\d.+? nomination\)/i', '', $t );
    if( strlen( $t ) > 64 ) $t = substr_replace( $t, "...", 61 );
    return $t;
}

function warnculprit( $page ) {
    global $site;
    $templateonpage = '/\{\{\s*((Article for deletion\/dated)|(AfDM))/si';
    $revs = $page->history( 100, "newer", true );
    $foundit = false;
    $culprit = null;
    $culpritrevid = null;
    foreach( $revs as $rev ) {
        $itsinthere = preg_match( $templateonpage, $rev['*'], $itsinthere );
        if( $itsinthere ) {
            $foundit = true;
            continue;
        }
        if( !$itsinthere && $foundit ) {
            $culprit = $rev['user'];
            $culpritrevid = $rev['revid'];
            $foundit = false;
            continue;
        }
    }
    if( !is_null( $culprit ) ) {
        $sysops = $site->allusers( null, array( 'sysop' ) );
        $user = $site->initUser( $culprit );
        $usertalk = $site->initPage( "User talk:$culprit" );
        if( !$user->is_ip() ) $editcount = $user->get_editcount();
        elseif ( $user->is_ip() ) $editcount = 0;
        else {
            echo "Error: Non-existent culprit?\n";
            return false;
        }
        if( in_array_recursive( $culprit, $sysops ) || $editcount > 10000 ) return false;
        
        echo "Warning User:$culprit";
        
        if( $usertalk->get_exists() ) {
            $data = $usertalk->get_text( true );
            $level = 1;
            if( strpos( $data, "<!-- Template:uw-afd1 -->" ) ) $level = 2;
            if( strpos( $data, "<!-- Template:uw-afd2 -->" ) ) $level = 3;
            if( strpos( $data, "<!-- Template:uw-afd3 -->" ) ) $level = 4;
            if( strpos( $data, "<!-- Template:uw-afd4 -->" ) ) $level = 4;
            $printstr = "{{subst:uw-afd$level|{$page->get_title()}|This is an automated message from a [[WP:BOT|bot]] about {{diff|{$page->get_title()}|prev|$culpritrevid|this edit}}, where you removed the deletion template from an article before the deletion discussion was complete.  If this message is in error, please [[User talk:Cyberbot I|report it]].~~~~}}";
            $usertalk->newsection( $printstr, "Removing AfD template", "Issuing level $level warning about removing AfD template from articles before the discussion is complete." );
        }
        else {
            $level = 1;
            $printstr = "==Welcome==\n{{subst:Welcome}}\n\n==Removing AfD template==\n";
            $printstr .= "{{subst:uw-afd$level|{$page->get_title()}|This is an automated message from a [[WP:BOT|bot]] about {{diff|{$page->get_title()}|prev|$culpritrevid|this edit}}, where you removed the deletion template from an article before the deletion discussion was complete.  If this message is in error, please [[User talk:Cyberbot I|report it]].~~~~}}";
            $usertalk->edit( $printstr, "Issuing level $level warning about removing AfD template from articles before the discussion is complete." ); 
        }
        return true;    
    }
    return false;
}

function addAfdTemplate( $page, $afdpage ) {
    global $site;
    $history = $afdpage->history( 1, "newer" );
    $afdcreationdate = formatdate( $history[0]['timestamp'] );
    if( (time() - $afdcreationdate)/60 <=10 ) {
        echo "No AfD template on {$page->get_title( false )}, but AfD <10 minutes old, so skipping for now.\n";
        return false;
    }
    $pagedata = $page->get_text();
    $attemptcheckresult = preg_match( '/\<\!\-\- Please do not remove or change this AfD message until the issue is settled \-\-\>.*?\<\!\-\- End of AfD message, feel free to edit beyond this point \-\-\>/i', $pagedata, $attemptcheck );
    if( $attemptcheckresult ) {
        $newpagedata = str_replace( $attemptcheck[0], "{{subst:Article for deletion|".str_replace( "Wikipedia:Articles for deletion/", "", $afdpage->get_title() )."}}\n", $pagedata );
    }
    else {
        if( in_array( "Category:Articles for deletion", $page->get_categories() ) ) {
            echo "ERROR: Page is in CAT:AFD, but I can't find the AfD template on the page for some reason...\n";
            return true;
        }
        $newpagedata = "{{subst:Article for deletion|".str_replace( "Wikipedia:Articles for deletion/", "", $afdpage->get_title() )."}}\n".$pagedata;
    }
    $page->edit( $newpagedata, "No AfD template, but article is [[{$afdpage->get_title()}|still at AfD]].  Bot adding template." );
    warnculprit($page);
    return true;
}

function parsevotetype( $v ) {
    $v = strtolower( $v );
    if( strpos( $v, "comment" ) ) return null;
    elseif( strpos( $v, "note" ) ) return null;
    elseif( strpos( $v, "merge" ) ) return 2;
    elseif( strpos( $v, "redirect" ) ) return 3;
    elseif( strpos( $v, "speedy keep" ) ) return 4;
    elseif( strpos( $v, "speedy delete" ) ) return 5;
    elseif( strpos( $v, "keep" ) ) return 1;
    elseif( strpos( $v, "delete" ) ) return 0;
    else return 6;
}

function parsevote( $v ) {
    $v=strtolower($v);
    if( strpos( $v, "comment" ) ) return 0;
    elseif( strpos( $v, "note" ) ) return 0;
    elseif( strpos( $v, "merge" ) ) return 1;
    elseif( strpos( $v, "redirect" ) ) return 1;
    elseif( strpos( $v, "keep" ) ) return 1;
    elseif( strpos( $v, "delete" ) ) return 1;
    elseif( strpos( $v, "transwiki" ) ) return 1;
    elseif( strpos( $v, "userfy" ) || strpos( $v, "userfied" ) || strpos( $v, "incubat" ) ) return 1;
    else return 0;
}

function transclusions( $page ) {
    global $site;
    $data = $site->embeddedin( $page, array(4), -1 );
    foreach( $data as $dat ) if( str_ireplace( "Wikipedia:Articles for deletion/Log/", "", $dat ) != $dat ) $links[] = $dat;
    if( in_array( "Wikipedia:Articles for deletion/Log/Today", $links ) ) {
        $links = array_diff( $links, array("Wikipedia:Articles for deletion/Log/Today") );
    }
    if( in_array( "Wikipedia:Articles for deletion/Log/Yesterday", $links ) ) {
        $links = array_diff( $links, array("Wikipedia:Articles for deletion/Log/Yesterday") );
    }
    return $links;
}

function transclude( $page, $logpagename ) {
    global $site, $blacklist;
    $object = $site->initPage( $page );
    if( $object->get_namespace( true ) != 4 ) {
        echo "ERROR: Non-transcluded AfD located in wrong namespace: $page\n";
        return false;
    }
    if( in_array( $page , $blacklist ) ) {
        echo "ERROR: Trying to transclude blacklisted page: $page\n";
        return false;  
    }
    if( substr( $page, 0, 36 ) == "Wikipedia:Articles for deletion/Log/" ) {
        echo "ERROR: Trying to transclude a log page: $page\n";
        return false;
    }    
    $t = transclusions( $page );
    if( count($t) > 0 ) {
        echo "ERROR: Attempting to transclude a page that is already transcluded to one or more log pages. \"$page\" is transcluded to ".implode( '||', $t )."\n";
        return false;
    }
    $logpage = $site->initPage( $logpagename );
    $logpagedata = $logpage->get_text();
    $pagedata = $object->get_text();
    
    $insertionindex = strripos( $logpagedata, "<!-- Add new entries to the TOP of the following list -->" );
    if( !$insertionindex ) {
        echo "ERROR: Can't find comment to locate insertion index on log page: $logpagename\n";
        return false;
    }
    
    $insertionindex += 58;
    $newlogpagedata = substr_replace( $logpagedata, "{{".$page."}}\n", $insertionindex, 0 );
    
    $logpage->edit( $newlogpagedata, "Bot automatically transcluding [[$page]]." );
    $object->edit( $pagedata."\n*<small>'''Automated comment:''' This AfD was not correctly transcluded to the log ([[WP:AFDHOWTO|step 3]]).  I have transcluded it to [[{$logpage->get_title()}]].  ~~~~</small><!--Cyberbot I relist-->", "Automated comment: AfD was not correctly transcluded." );   
}

function formatdate( $d ) {
    return strtotime( $d );
}

function getCreationDate( $page ) {
    global $site;
    $dates = array();
    $cats = $page->get_categories();
    $data = $page->get_text();
    foreach( $cats as $c ) {
        $object = $site->initPage( $c );
        if( $object->get_title() == 'Category:Relisted AfD debates' ) {
            preg_match_all( '/(\d{2}:\d{2}, \d{1,2} [A-Za-z]* \d{4}) \(UTC\)\<\/small\>\<\!\-\- from Template:Relist \-\-\>/i', $data, $relists );
            if( $relists ) {
                $dates[] = formatdate( $relists[1][count($relists[1]) - 1] );
                break;
            }
        }   
    }
    preg_match_all( '/\*\'\'\'Automated comment:\'\'\' This AfD was not correctly transcluded to the log \(\[\[WP:AFDHOWTO\|step 3\]\]\)\.  I have transcluded it to \[\[Wikipedia\:Articles for deletion\/Log\/(\d{4}) ([A-Za-z]*) (\d{1,2})\]\].*?\<\!\-\-(Snotbot|Cyberbot I) relist\-\-\>/i', $data, $botrelist );
    if( is_array( $botrelist[1] ) && !empty( $botrelist[1] ) ) {
        $dates[] = formatdate( $botrelist[1][count($botrelist[1]) - 1]." ".$botrelist[2][count($botrelist[2]) - 1]." ".$botrelist[3][count($botrelist[3]) - 1] );    
    }
    $history = $page->history( 1, "newer" );
    $dates[] = formatdate( $history[0]['timestamp'] );
    sort( $dates );
    return $dates[count($dates) - 1];
}

function closeitup( $afd ) {
    $afddata = $afd->get_text();
    if( in_string( "{{REMOVE THIS TEMPLATE WHEN CLOSING THIS AfD", $afddata ) ) {
        $afddata = preg_replace( '/\{\{REMOVE THIS TEMPLATE WHEN CLOSING THIS AfD\|?.*?\}{2,}\n?/i', '', $afddata );
        $afd->edit( $afddata, "Removing AfD template from closed AfD." );
        return true;
    }
    return false;
}

//Returns log page titles 
function getLogPages() {
    global $site;
    $now = time();
    $pages = array();
    for( $i=0; $i < 12; $i++ ) {
        $page = $site->initPage( logPageName( $now - ( $i * (24*3600) ) ) );
        $pages[ $page->get_title() ] = $page->get_text();
    }
    return $pages;
}

//Returns to the correct subpage name
function logPageName( $dt ) {
    return "Wikipedia:Articles for deletion/Log/".date( 'Y F j', $dt );
    
}