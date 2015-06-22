<?PHP

# misc. functions from php.net and elsewhere

if (!function_exists('array_delete_by_val')) {
	function array_delete_by_value(&$arr, $val, $recurse = false) {
		foreach ($arr as $k => $v) {
			if ($v === $val) { unset($arr[$k]); break; }
			else if (is_array($v) && $recurse == true) array_delete_by_value($v, $val, true);
		}
	}
}
if (!function_exists('dump_to_file')) {
	function dump_to_file($file, $string) {
		$f = fopen($file, 'w');
		fwrite($f, str_replace(PHP_EOL, '\n', $string));
		fclose($f);
	}
}
if (!function_exists('array_join')) {
	function array_join($a, $sep, $join, Callable $fk = null, Callable $fv = null) {
		$str = "";
		foreach ($a as $k => $v) {
			if ($fk != null) $k = $fk($k);
			if ($fv != null) $v = $fv($v);
			$str .= ($str!=""?$sep:"").$k.$join.$v;
		}
		return $str;
	}
}
/**
 * The task class for NoomBots book reports.
 * @author Josh
 * @version 0.3
 * @copyright Copyright (c) 2012, Josh Grant. Creative commons BY-NC 3.0 license.
 *
 * Refactored to a class. Could use with changing wApi to use classes for pages/users sometime soon to.
 */
class BookReport {
	public $book; # book name
	private $w; # wapi ref
	private $report; # internal report ref
	private $scoring; # scoring table
	private $list;
	private $media;
	private $summary;
	private $hasAnchors = array();
	private $hasDisambig = array();
	private $hasDuplicates = array();
	private $hasRedirects = array();
	private $hasRedlinks = array();
	private $reportStr;
	private $db;
	const version = 0.4;

	public function __construct($book, wikibot $wapi, $list, $mysql) {
		global $db_db;
		global $db_table;
	
		$this->book = $book;
		$this->w = $wapi;
		$this->db = $mysql;
		$this->list = $list;
		$this->scoring = array('FA' => 10, 'FL' => 10, 'A' => 9, 'GA' => 8, 'B+' => 7, 'B' => 7, 'C' => 5, 'START' => 3, 'STUB' => 1, 'LIST' => 0, '' => 0, 'UNASSESSED' => -1, 'OTHER' => -1, 'NA' => -1, '???' => -1);

		$sql = "USE `cyberbot`;";
		mysqli_query($this->db, $sql);
	}

	public function getVersion() { return BookReports::version; }

	public function generateReport() {
		$l = $this->getLast();
		if ($l['time'] >= (time()-172800)) { # dbg 172800
			$this->stop = true;
			out("Book ".$this->book." has not expired yet; no report posted.", 3);
			return false;
		}

		$this->w->multi(false);

		# prerequisites
		if (!$this->canEditTalk()) throw new InvalidBook("Excluded from talk page.", 9);
		if (!$this->checkCategory()) throw new InvalidBook("The supplied book was invalid: does not appear in Community books.", 2);
		if (!$this->checkNamespace()) throw new InvalidBook("The supplied book was invalid: does not occur within the book (108) namepsace.", 5);

		# report declare
		$this->report = array('title' => "", 'subtitle' => "", 'sections' => array(), 'articles' => array(), 'unique' => array(), 'redirects' => array());
		$this->report['main'] = $this->w->getPage($this->book);
		if (!$this->checkEmpty()) throw new InvalidBook("Book content was blank.", 3); # isEmpty?

		if (!$this->parseTOC()) {
			throw new ParseErrorException("Parsed a book with 0 articles.", 4); # throw an error when bad parse occured
		}

		$this->allContent();
		$this->gradeArticles();
		$this->checkTemplates();
		$this->checkMedia();
		# ready to generate wiki-markup now
		return true;
	}

	private function canEditTalk() {
		return $this->w->excluded(substr_replace($this->book, "Book talk:", 0, 5));
	}

	private function checkCategory() {
		$x = $this->w->getPageCats($this->book);
		if (!key_exists('Category:Wikipedia books (community books)', $x)) {
			return false;
		}
		else return true;
	}

	private function checkNamespace() {
		if ($this->w->getpagens($this->book) != 108) {
			return false;
		}
		else return true;
	}

	private function checkEmpty() {
		if ($this->report['main'] == FALSE || $this->report['main'] == "" || $this->report['main'] == NULL) {
			return false;
		}
		else return true;
	}

	private function parseTOC() {
		$ex = explode("\n", $this->report['main']);
		$p = "";
		foreach ($ex as $line) {
			$trim = trim($line);
			if (preg_match('/{{( *)saved(?:_| )book/', $line) > 0) {
				$p = "template";
			}
			else if ($p == "template" && preg_match("/.*}}.*/", $line) > 0) {
				$p = "";
			}
			else if ($p != "template" && substr($line, 0, 1) == ';') {
				$p = substr($line, 1);
				$this->report['sections'][$p] = array('articles' => array());
			}
			else if ($p != "template" && substr($line, 0, 1) == ':') {
				$a = $a2 = $this->sanitize(substr($line, 1));
				if (strpos($a, '|') != false) {
					$tmp = explode('|', $a);
					$a = $tmp[0];
					if (!$tmp[1]) $a2 = $a;
					else $a2 = $tmp[1];
				}
				if ($p == "") {
					$p = "__nosect__";
					$this->report['sections'][$p] = array('articles' => array());
				}
				# The articles section is $p, The true link to the article is $a, The formatted name as it appears in the book is $a2
				# The redirected name is 'redirect'
				# The keys must be the real name for internal consistency w/ api
				$this->report['articles'][$a] = array('section' => $p, 'formatted_name' => $a2, 'real' => $a, 'oreal' => $a, 'issues' => array());
				$this->report['sections'][$p]['articles'][$a] = array();
				$this->report['unique'][] = $a;
			}
			# higher prio. to template matching
			else if ($p == "template" && preg_match('/\|(?: *)((?:sub)*title)(?: *)=(.+)/', $trim, $t) > 0) {
				if ($t[1] == "title") { $this->report['title'] = trim($t[2]); }
				if ($t[1] == "subtitle") { $this->report['subtitle'] = trim($t[2]); }
			}
			else if ($p != "template" && (substr($trim, 0, 2) == "==" || substr($trim, 1, 3) == "==")) {
				if ($this->report['title'] == "" && substr($line, 0, 2) == "==") {
					$this->report['title'] = $this->sanitize(substr($trim, 2, (strlen($trim)-4)));
				}
				else if ($this->report['subtitle'] == "" && substr($trim, 0, 3) == "===") {
					$this->report['subtitle'] = $this->sanitize(substr($trim, 3, (strlen($trim)-6)));
				}
			}
			else if ($line == "" || $line == NULL) { }
			else {
			}
		}

		if (count($this->report['articles']) == 0) {
			return false;
		}
		else return true;
	}

	private function sanitize($string) { # sanitize a wikilink, remove formatting
		$string = ltrim($string, ":");
		$string = preg_replace('/={2,3}(.+)={2,3}/', '$1', $string); # remove header format
		$string = preg_replace('/\[\[(.+)\]\]/', '$1', $string); # remove wikilink format
		$string = preg_replace('/;(.+)/', '$1', $string); # remove indent
		$c = 0;
		$string = preg_replace('/(.*)#.*/', '$1', $string, -1, $c); # remove anchors
		if ($c > 0) $this->hasAnchors[] = "[[".$string."]]";
		$string = trim($string); # causes linkConcat failures but works in api
		return $string;
	}

	private function linkConcat($arr, $prefix = "", $key=null, $use_val=false) {
		$str = "";
		foreach ($arr as $val => $a) {
			if ($key != null && is_array($a)) {
				if ($str != "") $str .= '|';
				if (!isset($a[$key])) {
					out("Failed to LinkConcat() something! In book ".$this->book.":", 2);
				}
				$str .= $prefix.$a[$key];
			}
			else if ($use_val == true) {
				if ($str != "") $str .= '|';
				$str .= $prefix.$a;
			}
			else {
				if ($str != "") $str .= '|';
				$str .= $prefix.$val;
			}
		}
		return $str;
	}

	private function allContent($articles = false) {
		if ($articles == false) $articles = $this->report['articles'];
		$actions = array('action' => 'query',
						'prop' => 'revisions',
						'rvprop' => 'content',
						'titles' => $this->linkConcat($articles));
		$allp = $this->w->query($actions, true);
		foreach ($allp['query']['pages'] as $pageid => $revinfo) {
			if ($pageid == -1 || isset($revinfo['invalid'])) {
				$this->hasRedlinks[] = $revinfo['title'];
				$this->report['articles'][$revinfo['title']]['issues'][] = "Page does not exist.";	
				$this->report['articles'][$revinfo['title']]['notexist'] = true;
			}
			else {
				if (!isset($allp['query']['pages'][$pageid]['revisions'])) {
					print_r($allp['query']['pages'][$pageid]);
				}
				else {$tst = $this->checkRedirect($allp['query']['pages'][$pageid]['revisions'][0]['*']);
					if ($tst !== FALSE) {
						$this->hasRedirects = true;
						$this->report['articles'][$revinfo['title']]['real'] = $tst;
						$this->report['articles'][$revinfo['title']]['isRedirect'] = true;
						$this->report['redirects'][$tst] = $revinfo['title'];
						array_delete_by_value($this->report['unique'], $revinfo['title']);
						$this->report['unique'][] = $tst;
					}
				}
			}
		}
	}

	private function checkRedirect($str) {
		if (preg_match('/^#REDIRECT (\[\[.*?\]\])/', $str, $m)) {
			$r = $m[1];
			$r = sanitize($r);
			$r = $this->realLink($r);
			return $r;
		}
		else return FALSE;
	}

	private function realLink($piped) {
		if (strpos($piped, '|') != false) {
			$e = explode($piped);
			return $e[0];
		}
		else return $piped;
	}

	private function gradeArticles() {
		$actions = array('action' => 'query',
						'prop' => 'categories',
						'cllimit' => 5000,
						'titles' => $this->linkConcat($this->report['articles'], "Talk:", "real"));
		$cats = $this->w->query($actions, true);
		foreach($cats['query']['pages'] as $pageid => $pinfo) {
			$reala = str_replace("Talk:", "", $pinfo['title']);
			if (isset($this->report['redirects'][$reala])) { # solve internal redirects
				$reala = $this->report['redirects'][$reala];
			}

			if (isset($pinfo['categories'])) {
				foreach($pinfo['categories'] as $k => $cat) {
					if (preg_match('/Category:(?:[a-zA-Z]+-importance)?(?: )?(.*)-Class .* articles/i', $cat['title'], $m)) { # easier to do this than match the template
						if ($m[1] == "B+") $m[1] = "B";
						$linkfriendly = $m[1];
						$m[1] = strtoupper(trim($m[1]));
						if (isset($this->scoring[$m[1]])) {
							$score = $this->scoring[$m[1]];
							if (isset($this->report['articles'][$reala]['rating'])) {
								if ($this->report['articles'][$reala]['rating'] < $score) {
									$this->report['articles'][$reala]['rating'] = $score;
									$this->report['articles'][$reala]['ratingStr'] = $linkfriendly;
								}
							}
							else {
								$this->report['articles'][$reala]['ratingStr'] = $linkfriendly;
								$this->report['articles'][$reala]['rating'] = $score;
							}
						}
					}
					if ($cat['title'] == "Category:Dismabiguation pages") {
						$this->hasDisambig[] = "[[".$reala."]]";
						$this->report['articles'][$reala]['issues'][] = "Page resulted in a disambiguation.";
					}
				}
			}
		}
	}

	private function checkTemplates() {
		# This function no longer provides a counted number of templates
		# since 0.3 to save time

		$actions = array('action' => 'query', 'prop' => 'templates', 'titles' => $this->linkConcat($this->report['articles'], "", "real"), 'tllimit' => 5000);
		$x = $this->w->query($actions, true);
		foreach ($x['query']['pages'] as $p) {
			$reala = $p['title'];
			if (isset($this->report['redirects'][$reala])) { # solve internal redirects
				$reala = $this->report['redirects'][$reala];
			}
			if(isset($p['templates'])) {
				foreach ($p['templates'] as $t) {
					if (array_key_exists($t['title'], $this->list)) {
						$this->report['articles'][$reala]['issues'][] = $this->getTemplateLink($t);
					}
				}
			}
		}
	}

	private function getTemplateLink($t) {
		$t = str_replace("Template:", "", $t['title']);
		return "{{tl|".$t."}}";
	}

	private function checkMedia() {
		$actions = array('action' => 'query', 'prop' => 'images', 'titles' => $this->linkConcat($this->report['articles'], "", "real"), 'imlimit' => 5000);
		$x = $this->w->query($actions, true);
		$cache = array();

		foreach ($x['query']['pages'] as $p) {
			$reala = $p['title'];
			if (isset($this->report['redirects'][$reala])) { # solve internal redirects
				$reala = $this->report['redirects'][$reala];
			}
			if (isset($p['images'])) {
				foreach ($p['images'] as $i) {
					$this->report['articles'][$reala]['media'][] = $i['title'];
					$cache[$i['title']] = array();
				}
			}
		}
		$cl = array_chunk($cache, 500, true);
		foreach ($cl as $c) {
			$actions = array('action' => 'query', 'prop' => 'categories', 'titles' => $this->linkConcat($c), 'cllimit' => 5000);
			$x = $this->w->query($actions, true);
			foreach ($x['query']['pages'] as $p) {
				if (isset($p['missing'])) $cache[$p['title']] = array('free' => true);
				else {
					$cache[$p['title']] = array('free' => true);
					if (isset($p['categories'])) {
						foreach ($p['categories'] as $c) {
							if ($c['title'] == "Category:All non-free media" || $c['title'] == "Category:All_non-free_media") {
								$cache[$p['title']]['free'] = FALSE;
							}
						}
						if ($cache[$p['title']]['free'] != false) unset($cache[$p['title']]); # dont track it if free, mem usage
					}
				}
			}
		}
		$this->media = $cache;
	}

	private function countArticle($a) {
		$c = 0;
		foreach($this->report['unique'] as $u) {
			if ($a == $u) $c++;
		}
		return $c;
	}

	private function getLast() {
		$escaped = mysqli_real_escape_string($this->db, $this->book);
		$sql = "SELECT * FROM `bookreports` WHERE `book`='".$escaped."' LIMIT 1;";
		$r = mysqli_query($this->db, $sql);
		if (mysqli_num_rows($r) === 0) {
			$sql = "INSERT INTO `bookreports` (`book`) VALUES ('".$escaped."');";
			$sql = mysqli_query($this->db, $sql);
			if ($sql === FALSE) {
				out("MySQL error when attempting to INSERT into the cache: ".mysqli_error($this->db), 1);
			}
			$r = array('time' => 0);
			$this->id = mysqli_insert_id($this->db);
			if ($this->id === 0 || $this->id === false) {
				$sql = "SELECT LAST_INSERT_ID();";
				$id = mysqli_fetch_array(mysql_query($this->db, $sql));
				$this->id = $id['LAST_INSERT_ID()'];
			}
		}
		else if ($r === FALSE) {
			out("MySQL error when attempting to SELECT from the cache: ".mysqli_error($this->db), 1);
		}
		else {
			$r = mysqli_fetch_array($r);
			if ($r === false || $r === 0) {
				out("MySQL error when attempting to get the last insert id: ".var_dump($r, true), 1);
			}
			else {
				$this->id = $r['id'];
			}
		}
		return $r;
	}

	public function generateMarkup() {
		$report = "{{book report start|".$this->report['title']."|".$this->report['subtitle']."}}";
		$score_count = array('FA' => 0, 'FL' => 0, 'A' => 0, 'GA' => 0, 'B' => 0, 'C' => 0, 'Start' => 0, 'Stub' => 0, 'List' => 0, 'Unassessed' => 0, 'Other' => 0, 'NA' => 0);
		$mean = 0;
		$n = 1;
		$n = 0;
		$c = 0;
		$prob = 0;
		$ls = ""; # last section
		foreach ($this->report['sections'] as $s => $pl) {
			if ($s == "__nosect__") $s = "No section";
			foreach ($pl['articles'] as $link => $null) {
				$c++;
				$local = $this->report['articles'][$link];
				$local['non-free'] = array();
				$prefix = "";
				if ($this->countArticle($link) > 1) { 
					$this->hasDuplicates = true;
					$local['issues'][] = "Page duplicated within this book.";
				}
				if (isset($local['isRedirect'])) $local['issues'][] = "Page resulted in a redirect to [[".$local['real']."]]";
				if ($ls!=$s) {
					$ls = $s;
					$prefix = "|chapter=".$s;
				}

				# check it's media versus cached media list
				if (isset($local['media'])) {
					foreach($local['media'] as $m) {
						if (isset($this->media[$m])) {
							if ($this->media[$m]['free'] != true) $local['non-free'][] = "[[:".$m."]]";
						}
					}
				}

				$problemStr = "|problems=";
				$nonfreeStr = "|non-free=";

				if (count($local['issues']) > 0) $prob++;
				foreach($local['issues'] as $p) {
					$problemStr .= "* ".$p.PHP_EOL;
				}
				foreach($local['non-free'] as $nf) {
					$nonfreeStr .= "* ".$nf.PHP_EOL;
				}

				trim($nonfreeStr);
				trim($problemStr);

				$report .= PHP_EOL."{{book report|".$local['oreal']."|".$local['formatted_name']."|".(isset($local['ratingStr'])?$local['ratingStr']:'Unassessed').$prefix.$problemStr.$nonfreeStr."}}";
				if (isset($local['rating']) && $local['rating'] > 0) {
					$n++;
					$mean += $local['rating'];
				}
				if (isset($local['ratingStr']) && array_key_exists($local['ratingStr'], $score_count)) $score_count[$local['ratingStr']]++;
				if (!isset($local['ratingStr'])) $score_count['Unassessed']++;
			}
		}
		$mean = round($mean/$n,1);
		
		# Book report end prob parameters:
		$pp = array();
		if ($this->report['title'] == "") $pp['notitle'] = "1";
		if (count($this->hasAnchors) > 0) $pp['sectionlinks'] = $this->specialJoin($this->hasAnchors);
		if ($this->hasDisambig) $pp['disambiguations'] = $this->specialJoin($this->hasDisambig);
		if ($this->hasDuplicates) $pp['duplicates'] = $this->specialJoin($this->hasDuplicates);
		if ($this->hasRedirects) $pp['redirects'] = $this->specialJoin($this->hasRedirects);
		if ($this->hasRedlinks) $pp['redlinks'] = $this->specialJoin($this->hasRedlinks);
		if (count($pp) > 0) $report .= PHP_EOL."{{book report end|".array_join($score_count, "|", "=")."|".array_join($pp, "|", "=")."}}";
		else $report .= PHP_EOL."{{book report end|".array_join($score_count, "|", "=")."}}";
		$this->summary = array("report" => $this->book, "avg" => $mean, "unassessed" => $score_count['Unassessed'], "cleanup" => $prob, "articles" => $c);
		$this->reportStr = $report;
		return $report;
	}

	private function specialJoin($a) {
		$r = "";
		if (count($a) > 0) return $r;
		foreach ($a as $k => $v) {
			$r .= ($r==""?"*".$v:PHP_EOL."*".$v);
		}
		return $r;
	}
	
	public function postReport() {
		$w = "Created";
		if (!isset($this->reportStr)) {
			$r = $this->generateReport();
			if ($r === false) return;
			$this->generateMarkup();
		}
		$l = $this->getLast();
		if (!isset($l['avg'])) $l['avg'] = -999;
		$abs = $this->summary['avg']-$l['avg'];
				
		if (abs($abs) >= 0) {
			$tp = substr_replace($this->book, "Book talk:", 0, 5);
			$t = $this->maintalk = $this->w->getPage($tp);
			if ($t == "" || $t == null) {
				$t = '{{WBOOKS|class=book}}'.PHP_EOL.$this->reportStr;
			}
			elseif (preg_match('/{{book report start.*}}(\n.*)*{{book report end.*}}/i', $t, $bugfix)) {
				$t = preg_replace('/{{book report start.*}}(\n.*)*{{book report end.*}}/i', $this->reportStr, $t);
				$w = 'Updated';
			}
			else {
				if (preg_match('/({{.*}}|\n( *)\|.*|{{.*|\n|}})*}}/', $t, $banners)) {
					//({{.*}}|\n\|.*|{{.*|\n|}})*
					//{{(\n{{.*|.|\n)*(\n}}|)(\n|}}|\|.*)}}
					$t = str_replace($banners[0], $banners[0].PHP_EOL.$this->reportStr, $t);
				}
				else { # fallback method
					out('Display error on [[".$book."]]... ', 2);
					$t = $this->reportStr.PHP_EOL.$t;
				}
			}

			$r = true;
			$r = $this->w->edit($tp, $t, $w.' [[User:Cyberbot I/Book Reports|book report]] for '.$this->book.'; Average rating: '.$this->summary['avg'].'; '.$this->summary['unassessed'].' article(s) unassessed; '.$this->summary['cleanup'].' articles needing cleanup.', null, ($w == 'Created'?NULL:TRUE));
			#$r = $this->w->edit("User:Noommos/tests", $t, $w.' [[User:NoomBot/Book Reports|book report]] for '.$this->book.'; Average rating: '.$this->summary['avg'].'; '.$this->summary['unassessed'].' article(s) unassessed; '.$this->summary['cleanup'].' articles needing cleanup.', null, ($w == 'Created'?NULL:TRUE));
			if ($r != true) {
				out("API Error on action=edit for ".$this->book.": ".$r, 1);
			}

			$sql = "UPDATE `bookreports` SET `avg`='".$this->summary['avg']."', `time`=".time().", `unassess`='".$this->summary['unassessed']."', `md5hash`='', `art`=".$this->summary['articles'].", `probart`=".$this->summary['cleanup']." WHERE `id`=".$this->id.";";
			$r = mysqli_query($this->db, $sql);
			if ($r == false) {
				out("MySQL error when attempting to UPDATE the cache: ".mysql_error($this->db), 1);
			}
		}
		else {
			out("Insiginifcant change in book ".$this->book.", skipping... ");
		}
		sleep(10);
	}

}
