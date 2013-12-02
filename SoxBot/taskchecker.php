<?php

ini_set('memory_limit','16M');

echo "----------STARTING UP SCRIPT----------\nStart Timestamp: ".date('r')."\n\n";

require_once('/data/project/cyberbot/Peachy/Init.php');

$wikien = Peachy::newWiki("soxbot");
$wikisimp = Peachy::newWiki("simple");
$wikicommons = Peachy::newWiki("commons");
$wikimeta = Peachy::newWiki("meta");
$wikicy = Peachy::newWiki("cywikiI");
$wikiur = Peachy::newWiki("urwikiI");
$wikidata = Peachy::newWiki("wikidata");

while(true) {
	echo "----------RUN TIMESTAMP: ".date('r')."----------\n\n";
	echo "Getting status of enwiki tasks.\n";
	$metacont = $wikien->initPage("User:Cyberbot I/Run/Meta-cont")->get_text();
	$metadaily = $wikien->initPage("User:Cyberbot I/Run/Meta-daily")->get_text();
	$datefixer = $wikien->initPage("User:Cyberbot I/Run/Datefixer")->get_text();
	$arcommons = $wikien->initPage("User:Cyberbot I/Run/ARcommons")->get_text();
	$adminstatscommons = $wikien->initPage("User:Cyberbot I/Run/Adminstatscommons")->get_text();
	$adminstatssimp = $wikien->initPage("User:Cyberbot I/Run/Adminstatssimp")->get_text();
	$adminstatscy = $wikien->initPage("User:Cyberbot I/Run/Adminstatscy")->get_text();
	$adminstatsur = $wikien->initPage("User:Cyberbot I/Run/Adminstatsur")->get_text();
	$adminstatswikidata = $wikien->initPage("User:Cyberbot I/Run/Adminstatswikidata")->get_text();

	echo "Updating simple.wikipedia tasks.\n";

	$wikisimp->initPage("User:Cyberbot I/Run/Adminstats")->edit($adminstatssimp,"Setting task status to $adminstatssimp.",true);
	$wikisimp->initPage("User:Cyberbot I/Run/Datefixer")->edit($datefixer,"Setting task status to $datefixer.",true);

	echo "Updating commons.wikipedia tasks.\n";

	$wikicommons->initPage("User:Cyberbot I/Run/AR")->edit($arcommons,"Setting task status to $arcommons.",true);
	$wikicommons->initPage("User:Cyberbot I/Run/Adminstats")->edit($adminstatssimp,"Setting task status to $adminstatscommons.",true);

	echo "Updating www.wikidata tasks.\n";
	$wikidata->initPage("User:Cyberbot I/Run/Adminstats")->edit($adminstatswikidata,"Setting task status to $adminstatswikidata.",true);

	echo "Updating meta.wikimedia tasks.\n";

	$wikimeta->initPage("User:Cyberbot I/Run/Meta-daily")->edit($metadaily,"Setting task status to $metadaily.",true);
	$wikimeta->initPage("User:Cyberbot I/Run/Meta-cont")->edit($metacont,"Setting task status to $metacont.",true);

	echo "Updating cy.wikipedia tasks.\n";

	$wikicy->initPage("Defnyddiwr:Cyberbot I/Run/Adminstats")->edit($adminstatscy,"Setting task status to $adminstatscy.",true);

	echo "Updating ur.wikipedia tasks.\n\n";

	$wikiur->initPage("صارف:Cyberbot_I/Run/Adminstats")->edit($adminstatsur,"Setting task status to $adminstatsur.",true);
}