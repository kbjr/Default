<?php

/*
 * Software: jsk javascript libraries
 * Version:  0.3.2 Alpha
 * Author:   James Brumond
 * Created:  20 March, 2010
 * Updated:  4 May, 2010
 *
 * Copyright (c) 2010 James Brumond
 * Dual licensed under MIT and GPL Licenses.
 */


/*
 * @class JskController
 * @param void
 * @return void
 */
class JskController {

	private static $baseURL  = "http://github.com/kbjr/jsk/raw/master/";
	private static $pkgsFile = "packages";
	private static $expires  =  86400;
	private static $version  = "0.3.2-alpha";
	private static $ready    = "jsk JavaScript Libraries (by: James Brumond)\n  Version: %%version%%\n  Copyright 2010 James Brumond\n  Dual Licensed Under MIT and GPL.";
	private static $devNotes =  array();

/*
 * Add an item to the dev notes
 */
	private static function addDevNote($note) {
		self::$devNotes[] = $note;
	}

/*
 * Reads the contents of a remote file using cURL.
 */
	private static function readURL($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result === false) throw new Exception('cURL fail');
		return $result;
	}
	
/*
 * Gets the URL for a particular version of jsk by referencing
 * the versions file on github.
 */
	private static function getVersionPath($ver) {
		$versionsFile = explode("\n", self::readURL(self::$baseURL . "versions"));
		$versions = array();
		foreach ($versionsFile as $ln => $line) {
			$current = explode(':::', $line);
			if (count($current) != 2) continue;
			if ($current[0] == $ver) return $current[1];
		}
		return false;
	}

/*
 * Builds a URL for the current path.
 */
	private static function buildURI() {
		$protocol = explode('/', $_SERVER['SERVER_PROTOCOL']);
		$protocol = strtolower($protocol[0]);
		$host = $_SERVER['HTTP_HOST'];
		$path = dirname($_SERVER['SCRIPT_NAME']);
		$uri = $protocol . '://' . $host . $path;
		return $uri;
	}
	
/*
 * Handles twitter query requests.
 */
	private static function handleTwitterRequest() {
		if ($_GET['tQuery'] && $_GET['tQuery'] != '') {
			$query = $_GET['tQuery'];
			$curl = curl_init();
			curl_setopt ($curl, CURLOPT_URL, "http://search.twitter.com/search.atom?q=&from=" . urlencode($query) . "&amp;amp;amp;rpp=10");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec ($curl);
			curl_close ($curl);
			header('Content-Type: application/xml; charset=ISO-8859-1');
			die($result);
		}
	}

/*
 * Handles remote AJAX requests.
 */
	private static function handleRemoteAjax() {
		if ($_POST['remoteAjax'] && $_POST['remoteAjax'] != '') {
			$urls = explode(' ', $_POST['remoteAjax']);
			$response = array();
			$query = urldecode($_POST['ajaxQuery']);
			$_POST['ajaxMethod'] = strToUpper($_POST['ajaxMethod']);
			foreach ($urls as $url) {
				$curl = curl_init();
				if ($_POST['ajaxMethod'] == 'GET') {
					curl_setopt($curl, CURLOPT_URL, $url . "?" . $query);
				} else {
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_POST, substr_count($query, '&') + 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
				}
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				$response[] = base64_encode(curl_exec($curl));
			}
			$response = implode(chr(1), $response);
			header('Content-Type: text/plain; charset=UTF-8');
			die($response);
		}
	}

/*
 * Handles requests via the XHRFrame library.
 */
	private static function handleXHRFrame() {
		if ($_GET['xhrframe']) {
			$url = $_GET['xhrframe'];
			$query = $_GET['postquery'];
			$headers = explode(' ', $_GET['headers']);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			if ($query) {
				curl_setopt($curl, CURLOPT_POST, substr_count($query, '&') + 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
			}
			$response = curl_exec($curl);
			list($headers, $content) = explode("\r\n\r\n", $response, 2);
			$response = base64_encode($headers) . chr(1) . base64_encode($content);
			header('Content-Type: text/plain; charset=UTF-8');
			die($response);
		}
	}

/*
 * Handles a version flag.
 */
	private static function versionFlag() {
		if ($_GET['version']) {
			header('Content-Type: text/plain; charset=UTF-8');
			die($GLOBALS['JSK_VERSION_INFO']);
		}
	}

/*
 * Handles a check flag.
 */
	private static function checkFlag() {
		if ($_GET['check']) {
			$ver = self::jskCheckUpdate();
			header('Content-Type: text/plain; charset=UTF-8');
			if ($ver === 0) die('Your copy of jsk is completely updated.');
			die('An update to the newest version is available (' . $ver['version'] . ').');
		}
	}

/*
 * Handles a libraries flag.
 */
	private static function librariesFlag() {
		if ($_GET['libraries']) {
			header('Content-Type: text/plain; charset=UTF-8');
			$result = $JSK_VERSION_INFO . "\n\n" . 'Available Libraries:';
			foreach ($pkgUrls as $code => $info) {
				if ($code == 'jsk') continue;
				$result .= "\n\n  ${info[name]}:\n    code: ${code}\n    url: ${info[url]}\n    version: ${info[version]}";
			}
			die($result);
		}
	}

/*
 * Checks for updates to the library.
 */
	private static function jskCheckUpdate($ignoreDevs = true) {
		$versionsFile = explode("\n", self::readURL(self::$baseURL . "versions"));
		$versions = array();
		foreach ($versionsFile as $ln => $line) {
			$current = explode(':::', $line);
			if (count($current) != 2) continue;
			$build = explode('-', $current[0]); $build = $build[count($build) - 1];
			if ($ignoreDevs) {
				if ($build == 'dev' || $build == 'alpha' || $build == 'beta') continue;
			}
			$versions[] = array( 'version' => $current[0], 'file' => $current[1] );
		}
		$num = count($versions);
		foreach ($versions as $i => $version) {
			if ($version['version'] == $GLOBALS['JSK_VERSION']) {
				if (($num - 1) == $i) {
					return 0;
				} else {
					return $versions[$num - 1];
				}
			}
		}
		return 0;
	}

/*
 * Automatically updates the library.
 */
	private static function jskAutoUpdate() {
		if ($_GET['update'] && $_GET['ver'] && $_GET['ver'] != '') {
			$ver = $_GET['ver'];
			if ($ver == 'force') {
				$ver = self::jskCheckUpdate(false);
				if ($ver === 0) {
					header('Content-Type: text/plain; charset=UTF-8');
					die('Your copy of jsk is completely updated.');
				}
				$ver = $ver['file'];
			} else {
				$ver = self::getVersionPath($ver);
				if ($ver === false) {
					header('Content-Type: text/plain; charset=UTF-8');
					die('That version does not exist or is not available for update.');
				}
			}
		} else {
			$ver = self::jskCheckUpdate();
			if ($ver === 0) {
				header('Content-Type: text/plain; charset=UTF-8');
				die('Your copy of jsk is completely updated.');
			}
			$ver = $ver['file'];
		}
		$content = self::readURL($ver);
		file_put_contents(__FILE__, $content);
		header('Content-Type: text/plain; charset=UTF-8');
		die('Update complete');
	}

/*
 * Gets the information on the packages including urls.
 */
	private static function getPackageInfo($cache) {
		// make sure the cache directory exists
		$cache .= 'pkgs';
		if (! is_dir($cache)) @mkdir($cache) or self::addDevNote('Could not create package info cache directory.');
		// see if there is a local cache of the package info
		$cache .= '/' . @date('Y-m-d');
		if (file_exists($cache)) {
			// cache file exists; read it
			$pkgs = unserialize(file_get_contents($cache));
		} else {
			// cache file does not exist; read the remote file
			$content = self::readURL(self::$baseURL . self::$pkgsFile);
			if (! $content) die("Could not read package file.");
			// removes //... style single-line comments
			$content = preg_replace('#\/\/(.*?)$#', '', $content);
			// removes /*...*/ style multiline comments
			$content = preg_replace('#\/\*(.*?)\*\/#s', '', $content);
			// make sure we have \n line endings
			$content = str_replace("\r\n", "\n", $content);
			// insert the base url
			$content = str_replace('%url%', self::$baseURL, $content);
			// start building the packages array
			$pkgs = array();
			while (preg_match('/([a-zA-Z]+)\s*:\s*\{(.*?)\}/s', $content, $match)) {
				$code = $match[1]; $data = $match[2];
				$pkgs[$code] = array();
				$data = explode("\n", $data);
				foreach ($data as $line) {
					preg_match('/([a-zA-Z]+)\s*:\s*(.*?)\s*$/', $line, $line);
					if (preg_match('/\s*\[(.*?)\]\s*/', $line[2], $list)) {
						$pkgs[$code][$line[1]] = array();
						$list = split('/\s*,\s*/', $list[1]);
						foreach ($list as $item) {
							if (strpos($item, ':') !== false) {
								$item = split('/\s*:\s*/', $item);
								$pkgs[$code][$line[1]][$item[0]] = $item[1];
							} else {
								$pkgs[$code][$line[1]][] = $item;
							}
						}
					} else {
						$pkgs[$code][$line[1]] = $line[2];
					}
				}
				$content = preg_replace('/([a-zA-Z]+)\s*:\s*\{(.*?)\}/s', '', $content, 1);
			}
			// put the packages info into the cache file
			@file_put_contents($cache, serialize($pkgs)) or self::addDevNote('Could not cache package data.');
		}
		
		//header("Content-Type: text/plain");
		//var_dump(serialize($pkgs)); die();
		return $pkgs;
	}

/*
 * Outputs the standard ready message.
 */
	private static function readyMsg() {
		header('Content-Type: text/plain; charset=UTF-8');
		$result = "jsk is running and ready :)\n\n" . str_replace('%%version%%', self::$version, self::$ready) . "\n\nAvailable Libraries:";
		$pkgUrls = self::getPackageInfo();
		foreach ($pkgUrls as $code => $info) {
			if ($code == 'jsk') continue;
			$result .= "\n\n  ${info[name]}\n    version: ${info[version]}\n    code: $code\n    requirements: " . (($info['needed']) ? 'yes' : 'no');
		}
		die($result);
	}

/*
 * Uses the pkgs option and/or the jsk-includes file to
 * determine what packages to load.
 */
	private static function loadPackages(&$pkgs) {
		$pkgs = @$_GET['pkgs'];
		if (! $pkgs || $pkgs == '') {
			$dir = dirname(__FILE__);
			if (file_exists($dir . '/jsk-includes')) {
				$includes = explode("\n", file_get_contents($dir . '/jsk-includes'));
				$pkgs = array();
				foreach($includes as $num => $line) {
					$line = trim($line);
					if (empty($line) || $line[0] == '#') continue;
					$pkgs[] = $line;
				}
				if (count($pkgs) == 0) self::readyMsg();
			} else { self::readyMsg(); }
		} else {
			$pkgs = explode(' ', $pkgs);
		}
	}

/*
 * Gets the path to the cache directory and today's cache file name
 * for this set of packages.
 */
	private static function findCacheFile($pkgs, &$cache, &$cacheFile) {
		$cache = dirname(__FILE__) . '/cache/';
		$pkgCode = md5(implode('+', $pkgs));
		$cacheFile = $cache . @date('Y-m-d') . '=' . $pkgCode . '.js';
		if (! is_dir($cache)) mkdir($cache);
	}

/*
 * Builds a new file for this set of packages, or, in error, deafults
 * to the latest cache file for this stack if available.
 */
	private static function buildContent($pkgs, $cache, $cacheFile, &$content) {
		if (file_exists($cacheFile)) {
			$content = self::readFile($cacheFile);
		} else {
			try {
				$pkgUrls = self::getPackageInfo($cache);
	
			// Build opening to file
				$result = ($_GET['debug'] !== null) ?
					self::readURL($pkgUrls['root']['debug'][0]) . "\n" . self::readURL($pkgUrls['root']['debug'][1]) :
					self::readURL($pkgUrls['root']['url']);
				$result .= "window.jskPath='${path}';window.jskFile='" . basename(__FILE__) . "';\n";
				$result .= ($_GET['debug'] !== null) ? 'jsk.showWarnings=true;' . "\n\n" : ' jsk.showWarnings=false;';
	
			// Add packages
				foreach ($pkgs as $i => $pkg) {
					if (! $pkgUrls[$pkg]) {
						header('Content-Type: text/plain; charset=UTF-8');
						die('bad package code: ' . implode('.', $pkgs) . "\n'" . $pkg . '\' is not a valid package id.' . "\n\n" . $JSK_VERSION_INFO);
					}
					$fileContent = ($_GET['debug'] !== null) ? self::readURL($pkgUrls[$pkg]['debug']) : self::readURL($pkgUrls[$pkg]['url']);
					$result .= "\n" . $fileContent;
		
				// Load any extra needed files
					if ($pkgUrls[$pkg]['needed'] !== null) {
						foreach($pkgUrls[$pkg]['needed'] as $filename => $filepath) {
							$local = dirname(__FILE__) . '/' . $filename;
							if (! file_exists($local)) {
								touch($local);
								file_put_contents($local, self::readURL($filepath));
							}
						}
					}
				}
	
			// Create the cache file
				touch($cacheFile);
				file_put_contents($cacheFile, $result);

		// Upon error reading a file, default to the latest
		// cache file if available.
			} catch (Execption $e) {
				$regex = '/([0-9]{4}-[0-9]{2}-[0-9]{2})=' . $pkgCode . '\.js/';
				$files = array(); $handle = opendir($cache);
				while (false !== ($file = readdir($handle))) {
					preg_match($regex, $file, $match);
					if ($match[1]) {
						$files[] = $match[1];
					}
				}
				closedir($handle); sort($files);
				$file = $files[count($files) - 1] . '=' . $pkgCode . '.js';
				$result = self::readFile($file);
			}
			$content = $result;
		}
	}

/*
 * Reads a local file.
 */
	private static function readFile($file) {
		return file_get_contents($file);
	}

/*
 * Checks for gzip capability and compresses content
 */
	private static function compress(&$content) {
		$encoding = explode(',', $_SERVER['HTTP_ACCEPT_ENCODING']);
		foreach($encoding as $i => $j) $encoding[$i] = trim($j);
		if (in_array('gzip', $encoding)) {
			header("Content-Encoding: gzip");
			$content = gzencode($content, 9, FORCE_GZIP);
		} elseif (in_array('deflate', $encoding)) {
			header("Content-Encoding: deflate");
			$content = gzencode($content, 9, FORCE_DEFLATE);
		}
	}

/*
 * Output the gathered code after minifying, if requested.
 */
	private static function outputCode($code, $min = true) {
		header("Pragma: public");
		header("Cache-Control: maxage=".self::$expires);
		header('Expires: ' . @gmdate('D, d M Y', @time() + self::$expires) . ' 00:00:00 GMT');
		header('Content-Type: text/javascript; charset=UTF-8');
		$code = trim(($min == true) ? JSMin::minify($code) : $code);
		if (count(self::$devNotes) > 0) {
			$notes = '// ' . implode("\n// ", self::$devNotes) . "\n";
			$code = $notes . $code;
		}
		self::compress($code);
		print $code;
	}

/*
 * Runs flag/option checks.
 */
	private static function runFlagChecks() {
		self::checkFlag();
		self::versionFlag();
		self::librariesFlag();
		self::handleTwitterRequest();
		self::handleRemoteAjax();
		self::handleXHRFrame();
	}

/*
 * The public method of the singleton.
 */
	public static function initialize() {
		self::runFlagChecks();
		self::loadPackages($pkgs);
		self::findCacheFile($pkgs, $cache, $cacheFile);
		self::buildContent($pkgs, $cache, $cacheFile, $content);
		self::outputCode($content, (!$_GET['debug']));
	}

}


/*
 * END OF JSKCONTROLLER CLASS
 */



/*
 * START OF JSMIN CODE
 * See the file JSMIN.LICENSE for information on this class.
 */

class JSMin {
  const ORD_LF    = 10;
  const ORD_SPACE = 32;

  protected $a           = '';
  protected $b           = '';
  protected $input       = '';
  protected $inputIndex  = 0;
  protected $inputLength = 0;
  protected $lookAhead   = null;
  protected $output      = '';

  // -- Public Static Methods --------------------------------------------------

  public static function minify($js) {
    $jsmin = new JSMin($js);
    return $jsmin->min();
  }

  // -- Public Instance Methods ------------------------------------------------

  public function __construct($input) {
    $this->input       = str_replace("\r\n", "\n", $input);
    $this->inputLength = strlen($this->input);
  }

  // -- Protected Instance Methods ---------------------------------------------

  protected function action($d) {
    switch($d) {
      case 1:
        $this->output .= $this->a;
      case 2:
        $this->a = $this->b;
        if ($this->a === "'" || $this->a === '"') {
          for (;;) {
            $this->output .= $this->a;
            $this->a       = $this->get();
            if ($this->a === $this->b) {
              break;
            }
            if (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated string literal.');
            }
            if ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            }
          }
        }
      case 3:
        $this->b = $this->next();
        if ($this->b === '/' && (
            $this->a === '(' || $this->a === ',' || $this->a === '=' ||
            $this->a === ':' || $this->a === '[' || $this->a === '!' ||
            $this->a === '&' || $this->a === '|' || $this->a === '?')) {
          $this->output .= $this->a . $this->b;
          for (;;) {
            $this->a = $this->get();
            if ($this->a === '/') {
              break;
            } elseif ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            } elseif (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated regular expression literal.');
            }
            $this->output .= $this->a;
          }
          $this->b = $this->next();
        }
    }
  }

  protected function get() {
    $c = $this->lookAhead;
    $this->lookAhead = null;
    if ($c === null) {
      if ($this->inputIndex < $this->inputLength) {
        $c = substr($this->input, $this->inputIndex, 1);
        $this->inputIndex += 1;
      } else {
        $c = null;
      }
    }
    if ($c === "\r") {
      return "\n";
    }
    if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
      return $c;
    }
    return ' ';
  }

  protected function isAlphaNum($c) {
    return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
  }

  protected function min() {
    $this->a = "\n";
    $this->action(3);
    while ($this->a !== null) {
      switch ($this->a) {
        case ' ':
          if ($this->isAlphaNum($this->b)) {
            $this->action(1);
          } else {
            $this->action(2);
          }
          break;
        case "\n":
          switch ($this->b) {
            case '{': case '[': case '(': case '+': case '-':
              $this->action(1);
              break;
            case ' ':
              $this->action(3);
              break;
            default:
              if ($this->isAlphaNum($this->b)) {
                $this->action(1);
              }
              else {
                $this->action(2);
              }
          }
          break;
        default:
          switch ($this->b) {
            case ' ':
              if ($this->isAlphaNum($this->a)) {
                $this->action(1);
                break;
              }
              $this->action(3);
              break;
            case "\n":
              switch ($this->a) {
                case '}': case ']': case ')': case '+': case '-': case '"': case "'":
                  $this->action(1);
                  break;
                default:
                  if ($this->isAlphaNum($this->a)) {
                    $this->action(1);
                  }
                  else {
                    $this->action(3);
                  }
              }
              break;
            default:
              $this->action(1);
              break;
          }
      }
    }

    return $this->output;
  }

  protected function next() {
    $c = $this->get();
    if ($c === '/') {
      switch($this->peek()) {
        case '/':
          for (;;) {
            $c = $this->get();
            if (ord($c) <= self::ORD_LF) {
              return $c;
            }
          }
        case '*':
          $this->get();
          for (;;) {
            switch($this->get()) {
              case '*':
                if ($this->peek() === '/') {
                  $this->get();
                  return ' ';
                }
                break;
              case null:
                throw new JSMinException('Unterminated comment.');
            }
          }
        default:
          return $c;
      }
    }
    return $c;
  }

  protected function peek() {
    $this->lookAhead = $this->get();
    return $this->lookAhead;
  }
}

// -- Exceptions ---------------------------------------------------------------
class JSMinException extends Exception {}

/*
 * END OF JSMIN CODE
 */



JskController::initialize();

?>
