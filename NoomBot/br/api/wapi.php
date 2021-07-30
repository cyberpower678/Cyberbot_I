<?PHP

/**
 * @file
 * API interface
 */
class wikibot {
	private $ch;

	public $user;

	private $echo;
	private $fileid;
	public $userflags = array();
	public $userid;
	private $multi;
	public $queue;
	private $cache = array();
	private $lastError = array();
	private $consumerkey;
	private $consumersecret;
	private $accesstoken;
	private $accesssecret;

	function __construct($echoresults = FALSE, $loglevel = 2) {
		date_default_timezone_set('UTC');
		define('WAPI_DEFFERED', -1, TRUE);
		$this->echo = $echoresults;
		$this->loglevel = $loglevel;
		$this->log('Initializing...', 0);
		$this->ch   = curl_init();
		$this->user   = "";
		$this->fileid = rand(1,1000);
		$this->api  = "https://en.wikipedia.org/w/api.php";
		$this->OAuth = "https://en.wikipedia.org/w/index.php?title=Special:OAuth";
		curl_setopt($this->ch,CURLOPT_USERAGENT,'wAPI/1.1.1 (Bot: Cyberbot I Operator: Cyberpower678)');
		curl_setopt($this->ch,CURLOPT_COOKIEFILE,'curl/wp.bot-'.$this->fileid.'.cookie');
		curl_setopt($this->ch,CURLOPT_COOKIEJAR,'curl/wp.bot-'.$this->fileid.'.cookie');
		curl_setopt($this->ch,CURLOPT_SSL_VERIFYPEER, 0);
	}

	function __destruct() {
		curl_close($this->ch);
		if (file_exists('curl/wp.bot-'.$this->fileid.'.cookie')) {
			@unlink('/tmp/wp.bot-'.$this->fileid.'.cookie');
		}
	}

	function log($string, $num = 1) {
		$pre = array(0 => '[MSG]   ', 1 => '[ERROR] ', 2 => '[WARN]  ', 3 => '[DEBUG] ');
		if ($this->echo == 1 && $this->loglevel >= $num) {
			echo $pre[$num].$string.''.PHP_EOL;
		}
	}
	
	function generateOAuthHeader( $method = 'GET', $url ) {
		$headerArr = array(
			        // OAuth information
					        'oauth_consumer_key' => $this->consumerkey,
					        'oauth_token' => $this->accesstoken,
					        'oauth_version' => '1.0',
					        'oauth_nonce' => md5( microtime() . mt_rand() ),
					        'oauth_timestamp' => time(),

					        // We're using secret key signatures here.
					        'oauth_signature_method' => 'HMAC-SHA1',
					    );
		$signature = $this->generateSignature( $method, $url, $headerArr  );
		$headerArr['oauth_signature'] = $signature; 

		$header = array();
		foreach ( $headerArr as $k => $v ) {
			$header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
		}
		$header = 'Authorization: OAuth ' . join( ', ', $header );
		unset( $headerArr ); 
		return $header;
	}
	
	function generateSignature( $method, $url, $params = array() ) {
	    $parts = parse_url( $url );

	    // We need to normalize the endpoint URL
	    $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
	    $host = isset( $parts['host'] ) ? $parts['host'] : '';
	    $port = isset( $parts['port'] ) ? $parts['port'] : ( $scheme == 'https' ? '443' : '80' );
	    $path = isset( $parts['path'] ) ? $parts['path'] : '';
	    if ( ( $scheme == 'https' && $port != '443' ) ||
	        ( $scheme == 'http' && $port != '80' ) 
	    ) {
	        // Only include the port if it's not the default
	        $host = "$host:$port";
	    }

	    // Also the parameters
	    $pairs = array();
	    parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
	    $query += $params;
	    unset( $query['oauth_signature'] );
	    if ( $query ) {
	        $query = array_combine(
	            // rawurlencode follows RFC 3986 since PHP 5.3
	            array_map( 'rawurlencode', array_keys( $query ) ),
	            array_map( 'rawurlencode', array_values( $query ) )
	        );
	        ksort( $query, SORT_STRING );
	        foreach ( $query as $k => $v ) {
	            $pairs[] = "$k=$v";
	        }
	    }

	    $toSign = rawurlencode( strtoupper( $method ) ) . '&' .
	        rawurlencode( "$scheme://$host$path" ) . '&' .
	        rawurlencode( join( '&', $pairs ) );
	    $key = rawurlencode( $this->consumersecret ) . '&' . rawurlencode( $this->accesssecret );
	    return base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
	}

	function get($to, $format = 'php') {
		$reqtime = microtime(1);
		$to = $to.'&format='.$format;
		curl_setopt($this->ch, CURLOPT_URL, $to);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array( $this->generateOAuthHeader( 'GET', $to ) ) );
		$ret = curl_exec($this->ch);
		$this->log('GET: '.$to.' ('.(microtime(1) - $reqtime).'s)', 3);
		try {
			$ret = unserialize(trim($ret))or trigger_error("unserialize() failed", E_USER_ERROR);
		}
		catch(Exception $e) {
			echo "[EXCEPTION] caught on get request: $to".PHP_EOL;
			
			if ($ret == false) {
				$ret = array('error' => array('code' => curl_errno($this->ch), 'info' => curl_error($this->ch)));
				echo (curl_errno($this->ch).": ".curl_error($this->ch)).PHP_EOL;
			}
			else {
				echo (trim($ret)).PHP_EOL;
				$ret = array('error' => array('code' => -1, 'info' => 'Failed to unserialize'));
			}
		}
		return $ret;
	}

	function post($to, $array, $format = 'php') {
		$fields = $array;
		$reqtime = microtime(1);
		$to = $to.'?format='.$format;
		curl_setopt($this->ch,CURLOPT_URL,$to);
		curl_setopt($this->ch,CURLOPT_TIMEOUT,120);
		curl_setopt($this->ch,CURLOPT_CONNECTTIMEOUT,30);
		curl_setopt($this->ch,CURLOPT_POST,1);
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->ch,CURLOPT_POSTFIELDS, $fields);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array( $this->generateOAuthHeader( 'POST', $to ) ) );
		$ret = curl_exec($this->ch);
		$this->log('POST: '.$to.' ('.(microtime(1) - $reqtime).'s)', 3);
		return unserialize(trim($ret));
	}

	function sanitize($string) {
		return urlencode($string);
	}

	function excluded($string, $user = null) {
		if ($user == null) $user = $this->user;
		if (preg_match('/\{\{(nobots|bots\|allow=none|bots\|deny=all|bots\|optout=all|bots\|deny=.*?'.preg_quote($user,'/').'.*?)\}\}/iS',$string)) { return false; }
		else return true;
	}

	function editable($string) {
		return $this->excluded($string);
	}

	function query($params, $is_post = false, $name = '') {
		if ($this->multi == TRUE) {
			$this->queue[] = array('name' => $name, 'post' => $is_post, 'params' => $params);
			return WAPI_DEFFERED;
		}
		else {
			if ($is_post == false) { $ret = $this->get($this->api.$params);   }
			if ($is_post == true ) { $ret = $this->post($this->api, $params); }
			if (!is_array($ret)) {
				debug_print_backtrace();
				var_dump(curl_error($this->ch));
				var_dump($ret);
				return array(false);
			}
			if (key_exists("error", $ret)) {
				throw new ApiErrorException('['.$ret['error']['code'].'] '.$ret['error']['info'], 1);
				return array(false);
			}
			else return $ret;
		}
	}

	function multi($multi = TRUE) {
		$this->multi = $multi;
		return true;
	}

	function multi_exec() {
		$mh = curl_multi_init();
		$allq = array();
		if (!is_array($this->queue)) { return FALSE; }
		else {
			foreach ($this->queue as $q) {
				$m = curl_init();
				$q['params']['format'] = 'php';
				curl_setopt($m, CURLOPT_URL, $this->api);
				curl_setopt($m, CURLOPT_TIMEOUT, 120);
				curl_setopt($m, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($m, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($m, CURLOPT_USERAGENT, $this->ch);

				if ($q['post'] == TRUE) {
					curl_setopt($m, CURLOPT_POST, 1);
					curl_setopt($m, CURLOPT_POSTFIELDS, $q['params']);
					curl_setopt($m, CURLOPT_HTTPHEADER, array( $this->generateOAuthHeader( 'POST', $this->api ) ) );
				}
				if ($q['post'] == FALSE) {
					//curl_setopt($m, CURLOPT_URL, $this->api.$q['params']);
					curl_setopt($m, CURLOPT_POSTFIELDS, $q['params']);
					curl_setopt($this->ch, CURLOPT_HTTPHEADER, array( $this->generateOAuthHeader( 'GET', $this->api ) ) );
				}
				$allq[] = array('name' => $q['name'], 'data' => null, 'obj' => $m);
				curl_multi_add_handle($mh, $m);
			}
			// stack ready
			$active = null;
			do {
				$mhs = curl_multi_exec($mh, $active);
			} while ($mhs == CURLM_CALL_MULTI_PERFORM);
			while ($active && $mhs == CURLM_OK) {
				if (curl_multi_select($mh) != -1) {
					do {
						$mhs = curl_multi_exec($mh, $active);
					} while ($mhs == CURLM_CALL_MULTI_PERFORM);
				}
			}

			foreach ($allq as $handle => $data) {
				$allq[$handle]['data'] = unserialize(curl_multi_getcontent($data['obj']));
			}

			// close stack and return a new array
			$return = array();
			foreach ($allq as $handle => $data) {
				$return[$handle] = array('name' => $data['name'], 'data' => $data['data']);
				curl_multi_remove_handle($mh, $data['obj']);
			}
			curl_multi_close($mh);
			return $return;
		}
	}

	function login($user, $consumerkey, $consumersecret, $accesstoken, $accesssecret) {
		
		$error = "";
		$url = $this->OAuth . '/identify';
		$this->consumerkey = $consumerkey;
		$this->consumersecret = $consumersecret;
		$this->accesstoken = $accesstoken;
		$this->accesssecret = $accesssecret;

	    curl_setopt( $this->ch, CURLOPT_URL, $url );
	    curl_setopt( $this->ch, CURLOPT_HTTPHEADER, array( $this->generateOAuthHeader( 'GET', $url ) ) );
	    curl_setopt( $this->ch, CURLOPT_HTTPGET, 1 );
	    curl_setopt( $this->ch, CURLOPT_POST, 0 );
	    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
	    curl_setopt( $this->ch, CURLOPT_USERAGENT,'wAPI/1.1 (Bot: '.$user.' Operator: Cyberpower678 Contact: English Wikipedia Email )');
	    $data = curl_exec( $this->ch );
	    if ( !$data ) {
	        $error = 'Curl error: ' . htmlspecialchars( curl_error( $this->ch ) );
	        goto loginerror;
	    }
	    $err = json_decode( $data );
	    if ( is_object( $err ) && isset( $err->error ) && $err->error === 'mwoauthdatastore-access-token-not-found' ) {
	        // We're not authorized!
	        $error = "Missing authorization or authorization failed";
	        goto loginerror;
	    }

	    // There are three fields in the response
	    $fields = explode( '.', $data );
	    if ( count( $fields ) !== 3 ) {
	        $error = 'Invalid identify response: ' . htmlspecialchars( $data );
	        goto loginerror;
	    }

	    // Validate the header. MWOAuth always returns alg "HS256".
	    $header = base64_decode( strtr( $fields[0], '-_', '+/' ), true );
	    if ( $header !== false ) {
	        $header = json_decode( $header );
	    }
	    if ( !is_object( $header ) || $header->typ !== 'JWT' || $header->alg !== 'HS256' ) {
	        $error = 'Invalid header in identify response: ' . htmlspecialchars( $data );
	        goto loginerror;
	    }

	    // Verify the signature
	    $sig = base64_decode( strtr( $fields[2], '-_', '+/' ), true );
	    $check = hash_hmac( 'sha256', $fields[0] . '.' . $fields[1], $this->consumersecret, true );
	    if ( $sig !== $check ) {
	        $error = 'JWT signature validation failed: ' . htmlspecialchars( $data );
	        goto loginerror;
	    }

	    // Decode the payload
	    $payload = base64_decode( strtr( $fields[1], '-_', '+/' ), true );
	    if ( $payload !== false ) {
	        $payload = json_decode( $payload );
	    }
	    if ( !is_object( $payload ) ) {
	        $error = 'Invalid payload in identify response: ' . htmlspecialchars( $data );
	        goto loginerror;
	    }

	    if( $user == $payload->username ) {
	        return true;
	    }
	    else {
loginerror: if( !empty( $error ) ) $this->setLastError(array('component' => 'oauth', 'code' => '-1', 'msg' => $error));
	        else $this->setLastError(array('component' => 'oauth', 'code' => '-1', 'msg' => "The bot logged into the wrong username."));
	        return false;
	    }
	}

	private function setLastError($errordetails) {
		$this->lastError = $errordetails;
	}
	public function getLastError() {
		return $this->lastError;
	}
	
	function getuserflags($user) {
		if ($user == $this->user) {
			$flags = $this->query('?action=query&meta=userinfo&uiprop=groups');
			return $flags['query']['userinfo']['groups'];
		}
		else {
			$flags = $this->query('?action=query&list=users&ususers='.$user.'&usprop=groups');
			if (!isset($flags['query']['users'][0]['groups'])) mail("maximilian.doerr@gmail.com", "[WIKIBOT] Warning", "Had a problem with groups for user ".$user.":".PHP_EOL.var_dump($flags, true));
			return $flags['query']['users'][0]['groups'];
		}
	}

	function isuserblocked($user) {
		$x = $this->query('?action=query&list=blocks&bkusers='.$user);
		foreach ($x['query']['blocks'] as $xn) {
			if ($xn['user'] == $user) { return true; }
		}
		return false;
	}

	function getpagelimit() {
		$ret = 50;
		foreach($this->userflags as $flag) {
			if ($flag = 'bot' || $flag = 'sysop' || $flag = 'researcher') {
				$ret = 500;
			}
		}
		return $ret;
	}

	function getToken($page = null, $type = 'csrf') {
		if ($type === 'edit') {
			$type = 'csrf';
		}

		$actions = array('action' => 'query', 'meta' => 'tokens', 'type' => $type);
		$x = $this->query($actions, true);
		$t = $x['query']['tokens'][$type.'token'];
		if (!$t) return false;
		else return $t;
	}

	function logout() {
		$api = $this->query('?action=logout');
		print_r($api);
	}

	function edit($page, $content, $summary, $section = NULL, $minor = 0, $bot = 1, $noEC = 0, $recreate = 0, $createonly = 0, $nocreate = 0) {
		$params = array('token' => '', 'action' => 'edit', 'assert' => 'user', 'title' => str_replace(' ', '_', $page), 'text' => $content, 'summary' => $summary, 
($section?'section':'nosection') => ($section?$section:''), ($minor == 1?'minor':'notminor') => 1, ($bot == 1?'bot':'nobot') => 1, ($recreate == 1?'recreate':'norecreate') => 1, ($createonly == 1?'createonly':'nocreateonly') => 1, ($nocreate == 1?'nocreate':'nonocreate') => 1);

		if ($noEC == 1) {
			$params['basetimestamp'] = time();
		}
		else {
			$gettoken = $this->getToken($page, 'edit');
			$params['token'] = $gettoken;
		}

		$api = $this->query($params, true);
		if ($api['edit']['result'] != 'Success') { return $api['edit']['result']; }
		else return true;
	}

	function getPage($page, $name = '') {
		$api = $this->query('?action=query&prop=revisions&rawcontinue=1&titles='.$this->sanitize($page).'&rvprop=content&rvlimit=1', FALSE, $name);
		if ($api === WAPI_DEFFERED) { return; }
		else {
			foreach($api['query']['pages'] as $page) {
				if (isset($page['missing'])) { return false; }
				else {
					if (isset($page['invalid'])) return "";
					else {
						foreach($page['revisions'] as $rev) {
							return $rev['*'];
						}
					}
				}
			}
		}
	}

	function paramJoin($list, $key=false, $use_k=false) {
		$s = "";
		foreach($list as $k => $v) {
			if ($s == "") $p = "";
			else $p = "|";
			
			if ($key !== false) $a = $v[$key];
			elseif ($use_k) $a = $k;
			else $a = $v;
			
			$s .= $p.$a;
		}
		
		return $s;
	}
	
	function getPageChunked($list) {
		$limit = $this->getpagelimit();
		$c = true;
		$offset = 0;
		$r = array();
		
		while($c) {
			$params = array('action' => 'query', 'rawcontinue' => 1, 'prop' => 'revisions', 'titles' => $this->paramJoin(array_splice($list, $offset, $limit)), 'rvprop' => 'content', 'rvlimit' => 1);
			$xu = $this->query($params, true);
			$x = array();
			foreach ($xu['query']['pages'] as $id => $page) {
				if (isset($page['missing']) || isset($page['invalid']) || $id < 0) $x[$page['title']] = false;
				else {
					foreach($page['revisions'] as $rev) {
						$x[$page['title']] = $rev['*'];
					}
				}
			}
			
			$offset += $limit;
			if ($offset > count($list)) $continue = false;
		}
		
		return $r;
	}
	
	function getPageCached($page, $name = '') {
		if (isset($this->cache[$page])) return $this->cache[$page];
		else {
			$api = $this->query('?action=query&rawcontinue=1&prop=revisions&titles='.$page.'&rvprop=content&rvlimit=1', FALSE, $name);
			if ($api === WAPI_DEFFERED) { return; }
			else {
				foreach($api['query']['pages'] as $page) {
					if (isset($page['missing'])) { return false; }
					else {
						foreach($page['revisions'] as $rev) {
							$this->cache[$page] = $rev['*'];
							return $rev['*'];
						}
					}
				}
			}
		}
	}

	function getpagens($page) {
		$x = $this->query('?action=query&format=php&titles='.$this->sanitize($page).'');
		foreach ($x['query']['pages'] as $ns) {
			return $ns['ns'];
		}
	}


	function checkDisable() {
		$runpage = $this->getPage($this->user."/Disable");
		if (trim($runpage) == 'TRUE') { return TRUE; }
		else { return FALSE; }
	}

	function delete($title, $reason = NULL) {
		$params = array( 'action' => 'delete', 'title' => $title, 'token' => $this->getToken($title, 'delete'), ($reason != NULL?'reason':'noreason') => $reason);
		$api = $this->query($params, TRUE);
		return $api;
	}

	function categorymembers ($category,$subcat=false) {
		$continue = '';
		$pages = array();
		while (true) {
			$res = $this->query('?action=query&list=categorymembers&rawcontinue=1&cmtitle='.$this->sanitize($category).'&cmlimit='.$this->getpagelimit().$continue);
			if (isset($x['error'])) {
				return false;
			}
			foreach ($res['query']['categorymembers'] as $x) {
				$pages[] = $x['title'];
			}
			if (empty($res['query-continue']['categorymembers']['cmcontinue'])) {
				if ($subcat) {
					foreach ($pages as $p) {
						if (substr($p,0,9)=='Category:') {
							$pages2 = $this->categorymembers($p,true);
							$pages = array_merge($pages,$pages2);
						}
					}
				}
				return $pages;
			}
			else {
				$continue = '&cmcontinue='.$this->sanitize($res['query-continue']['categorymembers']['cmcontinue']);
			}
		}
	}

	function subcats($cat) {
		$r = array();
		$x = $this->categorymembers($cat, TRUE);
		foreach ($x as $subcat) {
			if (substr($subcat,0,9)=='Category:') {
				$r[] = $subcat;
			}
		}
		return $r;
	}

	function getPageCats($page) {
		if (is_array($page)) {
			$s = "";
			foreach ($page as $p) {
				$s .= ($s==""?"":"|").$this->sanitize($p);
			}
			$page = $s;
		}
		else $page = $this->sanitize($page);
		$x = $this->query('?action=query&prop=categories&titles='.$page);
		//print_r($x);
		$r = array();
		foreach ($x['query']['pages'] as $page) {
			foreach ($page['categories'] as $cat) {
				$r[$cat['title']] = $cat['title'];
			}
		}
		return $r;
	}

	function shutoff($page) {
		$sp = $this->getpage($page);
		$sp = preg_replace('/\<\!\-\-.*\-\-\>/', '', $sp);
		$sp = trim($sp);
		$sp = explode('=', $sp);
		if ($sp[0] == 'stop' && $sp[1] == 'true') {
			return TRUE;
		}
		else { return FALSE; }
	}

	function whatlinkshere ($page,$extra=null) {
		$continue = '';
		$pages = array();
		while (true) {
			$res = $this->query('?action=query&rawcontinue=1&list=backlinks&bltitle='.$this->sanitize($page).'&bllimit=500&format=php'.$continue.$extra);
			if (isset($res['error'])) {
				return false;
			}
			foreach ($res['query']['backlinks'] as $x) {
				$pages[] = $x['title'];
			}
			if (empty($res['query-continue']['backlinks']['blcontinue'])) {
				return $pages;
			} else {
				$continue = '&blcontinue='.$this->sanitize($res['query-continue']['backlinks']['blcontinue']);
			}
		}
	}

	function transclusions($page, $extra='') {
		$continue = '';
		$pages = array();
		while (true) {
			$res = $this->query('?action=query&rawcontinue=1&list=embeddedin&eititle='.$page.'&eilimit='.$this->getpagelimit().$continue.$extra);
			if (isset($res['error'])) {
				return false;
			}
			foreach($res['query']['embeddedin'] as $x) {
				$pages[] = $x['title'];
			}
			if (empty($res['query-continue']['embeddedin']['eicontinue'])) {
				return $pages;
			}
			else {
				$continue = '&eicontinue='.$this->sanitize($res['query-continue']['embeddedin']['eicontinue']);
			}
		}
	}

	function solveredirect($page, $text = NULL) {
		if ($text == NULL) {
			$page = $this->getPage($page);
		}
		else {
			$page = $text;
		}
		if (preg_match('/^#REDIRECT (\[\[.*\]\])/', $page, $m)) {
			return str_replace(']', '', str_replace('[', '', $m[1]));
		}
		else return FALSE;
	}

	function noomlog($str) {
		//pass by reference
		$ts = date('H:i:s');
		$date = date('F j');
		$mylog = $this->getpage('User:Cyberbot I/Log');
		if (!preg_match('/== '.$date.' ==/', $mylog)) {
			$str = '== '.$date.' =='.PHP_EOL.'*('.$ts.') '.$str;
			$mylog = $str.PHP_EOL.$mylog;
		}
		else {
			$mylog = preg_replace('/== '.$date.' ==/', '== '.$date.' =='.PHP_EOL.'*('.$ts.') '.$str, $mylog);
		}
		$this->edit('User:Cyberbot I/Log', $mylog, 'Logging message', TRUE, TRUE);
	}
	
	function exturlusage($extlink, $namespace = 0) {
		$params = array('action' => 'query', 'rawcontinue' => 1, 'list' => 'exturlusage', 'euquery' => $extlink, 'eunamespace' => $namespace, 'eulimit' => 5000, 'format' => 'php');
		$continue = true;
		$r = array();
		while ($continue) {
			$x = $this->query($params, true);
			$r = array_merge($r, $x['query']['exturlusage']);

			if (isset($x['query-continue']['exturlusage']['euoffset'])) {
				$params['euoffset'] = $x['query-continue']['exturlusage']['euoffset'];
				echo $x['query-continue']['exturlusage']['euoffset'].PHP_EOL;
			}
			else $continue = false;
		}
		
		return $r;
	}
	
	public function setOpt($optName, $optVal, $reset = false) {
		$t = $this->getToken(null, 'options');
		$actions = array('action' => 'options', 'token' => $t, 'optionname' => $optName, 'optionvalue' => $optVal);
		if ($reset) $actions['reset'] = "";
		$x = $this->query($actions, true);
		
		if ($x['options'] == 'success') return true;
		else {
			return false;
		}
	}
	
	public function getNamespace($namespace) {
		$actions = array('action' => 'query', 'meta' => 'siteinfo', 'siprop' => 'namespaces');
		$x = $this->query($actions, true);
		foreach($x['query']['namespaces'] as $nsid => $ns) {
			if ($ns['canonical'] == $namespace) return $ns['*'];
		}
		return false;
	}
	
}

class APIErrorException extends ErrorException {

}
?>
