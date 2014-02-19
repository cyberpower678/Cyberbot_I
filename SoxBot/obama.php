<?php

ini_set('memory_limit','16M');

require_once( '/data/project/cyberbot/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/Obama");

$request = $site->get_http()->get( 'http://www.pollingreport.com/obama_job.htm' );

echo "Processing data...\n";
$body = explode('<table border="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="700" cellpadding="0" id="AutoNumber34" height="8120">',$request);
$body = explode('</table>',$body[1]);
$body = $body[0];
preg_match_all('/'.
			      '\<font face="Verdana" color="#004080" style="font-size: 8pt; font-weight: 700"\>\s*(.*?)(\<\/font\>\s*\<font face="Verdana" color="#999999" style="font-size: 7pt"\>(.*?))?\<\/font\>\<\/span\>\<\/td\>\s*\<td width="86" align="center" height="21"\>\s*\<span( style="font-weight: 700")? lang="en-us"\>\s*\<font face="Verdana"( color="#004080")? style="font-size: 8pt(; font-weight: 700)?"( color="#004080")?\>(\d{1,3})\<\/font\>\<\/span\>\<\/td\>\s*\<td width="87" align="center" height="21"\>\s*\<span( style="font-weight: 700")? lang="en-us"\>\s*\<font face="Verdana"( color="#004080")? style="font-size: 8pt(; font-weight: 700)?"( color="#004080")?\>(\d{1,3})\<\/font\>\<\/span\>\<\/td\>\s*\<td width="94" align="center" height="21"\>\<span lang="en-us"\>\s*\<font face="Verdana" style="font-size: 8pt"( color="#808080")?\>(.*?)\<\/font\><\/span\>\<\/td\>\s*\<td width="29" height="21"\>\<span lang="en-us"\>\<font face="Arial" size="4"\>&nbsp;\<\/font\>\<\/span\>\<\/td\>\s*\<td width="190" height="21"\>\s*\<p align="left"\>\<span lang="en-us"\>\s*\<font face="Verdana" color="#004080" style="font-size: 8pt"\>(.*?)\<\/font\>\<\/span\>\<\/td\>/i',$body, $m);
//print_r($m);
$dates = array_reverse($m[16]);
$sup = $m[8];
$opp = $m[13];
print_r($dates);
$approve = array();
$disapprove = array();
$neutral = array();
echo "Compiling data points...\n";
foreach( $dates as $key => $value ) {
    $date = preg_match('/^(\d*)\/(.*?)\/(\d*)$/',$value,$r);
    $d = $r[2];
    $m = $r[1];
    $y = $r[3];
    $d = preg_replace('/(\d*)\s?-\s?\d*/','\1',$d);
    $d = explode('/',$d);
    $d = $d[0];
    if( $d % 3 != 0 ) { continue; }
    $diff = (strtotime(date("Y-m-d"))-strtotime("20$y-$m-$d")) / (60 * 60 * 24);
    if( $diff > 3000 || $diff < 0 ) { continue; }
    $approve[] = $diff.','.($sup[$key]*10);
    $disapprove[] = $diff.','.($opp[$key]*10);
    echo $diff.','.($opp[$key])."\n";
    $neutral[] = $diff.','.((100 - $sup[$key] - $opp[$key])*10);
}
echo "Saving new data...\n";
$code = file_get_contents('/home/cyberpower678/public_html/Barack_Obama_approval_ratings.svg');
$code = preg_replace('/\<polyline stroke="#4A7EBB" points="(.*?)"\/\>/ms','<polyline stroke="#4A7EBB" points="'.implode(" \n",$approve).'"/>',$code);
$code = preg_replace('/\<polyline stroke="#BE4B48" points="(.*?)"\/\>/ms','<polyline stroke="#BE4B48" points="'.implode(" \n",$disapprove).'"/>',$code);
$code = preg_replace('/\<polyline stroke="#98B954" points="(.*?)"\/\>/ms','<polyline stroke="#98B954" points="'.implode(" \n",$neutral).'"/>',$code);

file_put_contents('/home/cyberpower678/public_html/Barack_Obama_approval_ratings.svg', $code);

$image = initImage( "File:Barack_Obama_approval_ratings.svg" );

$image->upload('/home/cyberpower678/public_html/Barack_Obama_approval_ratings.svg','','Automated upload of graph');

?>
