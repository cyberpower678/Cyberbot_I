<?php

ini_set('memory_limit','16M');

require_once( '/home/cyberpower678/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/BIL");

//START GENERATING TRANSCLUSIONS
$i = array();
$i = initPage('Template:Restricted use')->embeddedin( array( 6, 7 ) );

//END GENERATING TRANSCLUSIONS

//START GENERATING BAD IMAGE LIST
$bil = initPage('MediaWiki:Bad image list')->get_text();
preg_match_all('/\*\s\[\[\:(File\:(.*?))\]\]/i', $bil, $bad_images);
$bad_images = $bad_images[1];
print_r($bad_images);
//END GENERATING BAD IMAGE LIST

//START PROCESSING EACH TAGGED IMAGE
foreach( $i as $image ) {
    if( str_replace('File talk','File',$image) != $image ) {
        $image_object = initImage(str_replace('File talk','File',$image));
        $image_page_object = $image_object->get_page();
        $image_page = $image_page_object->get_text();

        $image_talk_page_object = initPage($image);
        $image_talk_page = $image_talk_page_object->get_text();

        //START REMOVAL FROM TALK PAGE
        $new_image_talk_page = str_ireplace('{{Restricted use}}','',$image_talk_page);
        $new_image_talk_page = str_ireplace('{{bad image}}','',$image_talk_page);
        $new_image_talk_page = str_ireplace('{{badimage}}','',$image_talk_page);
        //echo getTextDiff('unified', $image_talk_page, $new_image_talk_page);
        
        if( $image_talk_page == $new_image_talk_page ) continue;
        $image_talk_page_object->edit($new_image_talk_page,"Removing tag, moving to main image page",true);
        
        //START ADDITION TO MAIN PAGE
        if( preg_match('/\{\{Restricted use/i', $image_page ) ) continue;
        if( preg_match('/\{\{badimage/i', $image_page ) ) continue;
        if( preg_match('/\{\{bad image/i', $image_page ) ) continue;
        $new_image_page = "{{Restricted use}}";
        //echo getTextDiff('unified', $image_page, $new_image_page);
        
        //if( $image_page == $new_image_page ) continue;
        
        if( $image_object->get_exists() ) {
            $image_page_object->prepend($new_image_page,"Adding {{Restricted use}}",true);
            continue;
        }
        else echo "Image doesn't exist!\n\n";
    }
    
    if( in_array( str_replace('File talk','File',$image), $bad_images ) ) continue;
    
    $image_page_object = initPage($image);

    $image_page = $image_page_object->get_text();
    $new_image_page = str_ireplace('{{Restricted use}}','',$image_page);
    $new_image_page = str_ireplace('{{badimage}}','',$image_page);
    $new_image_page = str_ireplace('{{bad image}}','',$image_page);
    //echo getTextDiff('unified', $image_page, $new_image_page);
    
    if( $image_page == $new_image_page ) continue;
    
    $image_page_object->edit($new_image_page,"Removing tag, image is not on blacklist",true);
}
//END PROCESSING EACH IMAGE  

//START GOING THROUGH BIL
foreach( $bad_images as $bad_image ) {
    $image_object = initImage(str_replace('File talk','File',$bad_image));
    $image_page_object = $image_object->get_page();
    $image_page = $image_page_object->get_text();
    
        if( preg_match('/\{\{Restricted use/i', $image_page ) ) continue;
        if( preg_match('/\{\{badimage/i', $image_page ) ) continue;
        if( preg_match('/\{\{bad image/i', $image_page ) ) continue;
    
    $new_image_page = "{{Restricted use}}";
    //echo getTextDiff('unified', $image_page, $new_image_page);
        
    if( $image_page == $new_image_page ) continue;
    
    if( $image_object->get_exists() ) $image_page_object->prepend($new_image_page,"Adding {{Restricted use}}",true);
    else echo "Image doesn't exist!\n\n";

}
//END GOING THROUGH BIL