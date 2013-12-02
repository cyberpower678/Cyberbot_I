<?PHP
 
require_once( '/data/project/cyberbot/Peachy/Init.php' );

$site = Peachy::newWiki( "soxbot" );

$site->set_runpage("User:Cyberbot I/Run/Current Events");
 
$pages_to_make = array(
	array(
		'base_name' => 'Portal:Current events/',
		'date_format' => 'Y F j',
		'how_far_ahead' => '+1 day',
		'what_to_post' => 'User:Cyberbot I/Templates/Current Events',
		'parameters' => array(
			'year' => 'Y',
			'month' => 'm',
			'day' => 'j',
		),
		'verify_non_existance' => true,
		'summary' => 'Creating page for tomorrow\'s current events'
	),
);

foreach( $pages_to_make as $project ) {
	$page_for_the_day = $project['base_name'] . date( $project['date_format'], strtotime($project['how_far_ahead']) );
	echo "The page for the day is \"$page_for_the_day\".\n\n";
	
	$newpage = initPage( $page_for_the_day );

	if( $project['verify_non_existance'] ) {
		echo "Checking for existance...\n\n";
		$existance = $newpage->get_id();
		
		if( $newpage->exists() ) {
			echo "Uh oh, the page exists already... Better bail out.\n\n";
			continue;
		}
		else {
			echo "Looks like it doesn't exist, I'll make the page.\n\n";
		}
	}
	
	if( count($project['parameters']) > 0 ) {
		$what_to_post = "{{subst:" . $project['what_to_post'];
		foreach( $project['parameters'] as $key => $param ) {
			if( 
				$key == 'year' ||
				$key == 'month' ||
				$key == 'day'
			) {
				$param = date( $param, strtotime($project['how_far_ahead']) );
			}
			$what_to_post .= "|$key=$param";
		}
		$what_to_post .= "}}";
	}
	else {
		$what_to_post = "{{subst:" . $project['what_to_post'] . "}}";
	}
	
	echo $what_to_post . "\n\n";
	
	$newpage->edit($what_to_post,$project['summary']);
}



