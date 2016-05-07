#!/usr/bin/env php
<?php
error_reporting(6135); // errors and warnings
include dirname(__FILE__) . "/externals/JsShrink/jsShrink.php";

function add_apo_slashes($s) {
	return addcslashes($s, "\\'");
}

function add_quo_slashes($s) {
	$return = $s;
	$return = addcslashes($return, "\n\r\$\"\\");
	$return = preg_replace('~\0(?![0-7])~', '\\\\0', $return);
	$return = addcslashes($return, "\0");
	return $return;
}

function remove_lang($match) {
	global $translations;
	$idf = strtr($match[2], array("\\'" => "'", "\\\\" => "\\"));
	$s = ($translations[$idf] ? $translations[$idf] : $idf);
	if ($match[3] == ",") { // lang() has parameters
		return "$match[1]" . (is_array($s) ? "lang(array('" . implode("', '", array_map('add_apo_slashes', $s)) . "')," : "sprintf('" . add_apo_slashes($s) . "',");
	}
	return ($match[1] && $match[4] ? $s : "$match[1]'" . add_apo_slashes($s) . "'$match[4]");
}

function lang_ids($match) {
	global $lang_ids;
	$lang_id = &$lang_ids[stripslashes($match[1])];
	if ($lang_id === null) {
		$lang_id = count($lang_ids) - 1;
	}
	return ($_SESSION["lang"] ? $match[0] : "lang($lang_id$match[2]");
}

function put_file($match) {
	global $project;
	if (basename($match[2]) == '$LANG.inc.php') {
		return $match[0]; // processed later
	}
	$return = file_get_contents(dirname(__FILE__) . "/$project/$match[2]");
	if (basename($match[2]) != "lang.inc.php" || !$_SESSION["lang"]) {
		if (basename($match[2]) == "lang.inc.php") {
			$return = str_replace('function lang($idf, $number = null) {', 'function lang($idf, $number = null) {
	if (is_string($idf)) { // compiled version uses numbers, string comes from a plugin
		// English translation is closest to the original identifiers //! pluralized translations are not found
		$pos = array_search($idf, get_translations("en")); //! this should be cached
		if ($pos !== false) {
			$idf = $pos;
		}
	}', $return, $count);
			if (!$count) {
				echo "lang() not found\n";
			}
		}
		$tokens = token_get_all($return); // to find out the last token
		return "?>\n$return" . (in_array($tokens[count($tokens) - 1][0], array(T_CLOSE_TAG, T_INLINE_HTML), true) ? "<?php" : "");
	} elseif (preg_match('~\\s*(\\$pos = (.+\n).+;)~sU', $return, $match2)) {
		// single language lang() is used for plural
		return "function get_lang() {
	return '$_SESSION[lang]';
}

function lang(\$translation, \$number = null) {
	if (is_array(\$translation)) {
		\$pos = $match2[2]\t\t\t: " . (preg_match("~\\\$LANG == '$_SESSION[lang]'.* \\? (.+)\n~U", $match2[1], $match3) ? $match3[1] : "1") . '
		);
		$translation = $translation[$pos];
	}
	$translation = str_replace("%d", "%s", $translation);
	$number = format_number($number);
	return sprintf($translation, $number);
}
';
	} else {
		echo "lang() \$pos not found\n";
	}
}

function lzw_compress($string) {
	// compression
	$dictionary = array_flip(range("\0", "\xFF"));
	$word = "";
	$codes = array();
	for ($i=0; $i <= strlen($string); $i++) {
		$x = $string[$i];
		if (strlen($x) && isset($dictionary[$word . $x])) {
			$word .= $x;
		} elseif ($i) {
			$codes[] = $dictionary[$word];
			$dictionary[$word . $x] = count($dictionary);
			$word = $x;
		}
	}
	// convert codes to binary string
	$dictionary_count = 256;
	$bits = 8; // ceil(log($dictionary_count, 2))
	$return = "";
	$rest = 0;
	$rest_length = 0;
	foreach ($codes as $code) {
		$rest = ($rest << $bits) + $code;
		$rest_length += $bits;
		$dictionary_count++;
		if ($dictionary_count >> $bits) {
			$bits++;
		}
		while ($rest_length > 7) {
			$rest_length -= 8;
			$return .= chr($rest >> $rest_length);
			$rest &= (1 << $rest_length) - 1;
		}
	}
	return $return . ($rest_length ? chr($rest << (8 - $rest_length)) : "");
}

function put_file_lang($match) {
	global $lang_ids, $project, $langs;
	if ($_SESSION["lang"]) {
		return "";
	}
	$return = "";
	foreach ($langs as $lang => $val) {
		include dirname(__FILE__) . "/adminer/lang/$lang.inc.php"; // assign $translations
		$translation_ids = array_flip($lang_ids); // default translation
		foreach ($translations as $key => $val) {
			if ($val !== null) {
				$translation_ids[$lang_ids[$key]] = implode("\t", (array) $val);
			}
		}
		$return .= '
		case "' . $lang . '": $compressed = "' . add_quo_slashes(lzw_compress(implode("\n", $translation_ids))) . '"; break;';
	}
	$translations_version = crc32($return);
	return '$translations = &$_SESSION["translations"];
if ($_SESSION["translations_version"] != ' . $translations_version . ') {
	$translations = array();
	$_SESSION["translations_version"] = ' . $translations_version . ';
}

function get_translations($lang) {
	switch ($lang) {' . $return . '
	}
	$translations = array();
	foreach (explode("\n", lzw_decompress($compressed)) as $val) {
		$translations[] = (strpos($val, "\t") ? explode("\t", $val) : $val);
	}
	return $translations;
}

if (!$translations) {
	$translations = get_translations($LANG);
}
';
}

function short_identifier($number, $chars) {
	$return = '';
	while ($number >= 0) {
		$return .= $chars[$number % strlen($chars)];
		$number = floor($number / strlen($chars)) - 1;
	}
	return $return;
}

// based on http://latrine.dgx.cz/jak-zredukovat-php-skripty
function php_shrink($input) {
	global $VERSION;
	$special_variables = array_flip(array('$this', '$GLOBALS', '$_GET', '$_POST', '$_FILES', '$_COOKIE', '$_SESSION', '$_SERVER', '$http_response_header', '$php_errormsg'));
	$short_variables = array();
	$shortening = true;
	$tokens = token_get_all($input);
	
	// remove unnecessary { }
	//! change also `while () { if () {;} }` to `while () if () ;` but be careful about `if () { if () { } } else { }
	$shorten = 0;
	$opening = -1;
	foreach ($tokens as $i => $token) {
		if (in_array($token[0], array(T_IF, T_ELSE, T_ELSEIF, T_WHILE, T_DO, T_FOR, T_FOREACH), true)) {
			$shorten = ($token[0] == T_FOR ? 4 : 2);
			$opening = -1;
		} elseif (in_array($token[0], array(T_SWITCH, T_FUNCTION, T_CLASS, T_CLOSE_TAG), true)) {
			$shorten = 0;
		} elseif ($token === ';') {
			$shorten--;
		} elseif ($token === '{') {
			if ($opening < 0) {
				$opening = $i;
			} elseif ($shorten > 1) {
				$shorten = 0;
			}
		} elseif ($token === '}' && $opening >= 0 && $shorten == 1) {
			unset($tokens[$opening]);
			unset($tokens[$i]);
			$shorten = 0;
			$opening = -1;
		}
	}
	$tokens = array_values($tokens);
	
	foreach ($tokens as $i => $token) {
		if ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
			$short_variables[$token[1]]++;
		}
	}
	
	arsort($short_variables);
	$chars = implode(range('a', 'z')) . '_' . implode(range('A', 'Z'));
	// preserve variable names between versions if possible
	$short_variables2 = array_splice($short_variables, strlen($chars));
	ksort($short_variables);
	ksort($short_variables2);
	$short_variables += $short_variables2;
	foreach (array_keys($short_variables) as $number => $key) {
		$short_variables[$key] = short_identifier($number, $chars); // could use also numbers and \x7f-\xff
	}
	
	$set = array_flip(preg_split('//', '!"#$%&\'()*+,-./:;<=>?@[\]^`{|}'));
	$space = '';
	$output = '';
	$in_echo = false;
	$doc_comment = false; // include only first /**
	for (reset($tokens); list($i, $token) = each($tokens); ) {
		if (!is_array($token)) {
			$token = array(0, $token);
		}
		if ($tokens[$i+2][0] === T_CLOSE_TAG && $tokens[$i+3][0] === T_INLINE_HTML && $tokens[$i+4][0] === T_OPEN_TAG
			&& strlen(add_apo_slashes($tokens[$i+3][1])) < strlen($tokens[$i+3][1]) + 3
		) {
			$tokens[$i+2] = array(T_ECHO, 'echo');
			$tokens[$i+3] = array(T_CONSTANT_ENCAPSED_STRING, "'" . add_apo_slashes($tokens[$i+3][1]) . "'");
			$tokens[$i+4] = array(0, ';');
		}
		if ($token[0] == T_COMMENT || $token[0] == T_WHITESPACE || ($token[0] == T_DOC_COMMENT && $doc_comment)) {
			$space = "\n";
		} else {
			if ($token[0] == T_DOC_COMMENT) {
				$doc_comment = true;
				$token[1] = substr_replace($token[1], "* @version $VERSION\n", -2, 0);
			}
			if ($token[0] == T_VAR) {
				$shortening = false;
			} elseif (!$shortening) {
				if ($token[1] == ';') {
					$shortening = true;
				}
			} elseif ($token[0] == T_ECHO) {
				$in_echo = true;
			} elseif ($token[1] == ';' && $in_echo) {
				if ($tokens[$i+1][0] === T_WHITESPACE && $tokens[$i+2][0] === T_ECHO) {
					next($tokens);
					$i++;
				}
				if ($tokens[$i+1][0] === T_ECHO) {
					// join two consecutive echos
					next($tokens);
					$token[1] = ','; // '.' would conflict with "a".1+2 and would use more memory //! remove ',' and "," but not $var","
				} else {
					$in_echo = false;
				}
			} elseif ($token[0] === T_VARIABLE && !isset($special_variables[$token[1]])) {
				$token[1] = '$' . $short_variables[$token[1]];
			}
			if (isset($set[substr($output, -1)]) || isset($set[$token[1][0]])) {
				$space = '';
			}
			$output .= $space . $token[1];
			$space = '';
		}
	}
	return $output;
}

function minify_css($file) {
	return lzw_compress(preg_replace('~\\s*([:;{},])\\s*~', '\\1', preg_replace('~/\\*.*\\*/~sU', '', $file)));
}

function minify_js($file) {
	if (function_exists('jsShrink')) {
		$file = jsShrink($file);
	}
	return lzw_compress($file);
}

function compile_file($match) {
	global $project;
	$file = "";
	list(, $filenames, $callback) = $match;
	if ($filenames != "") {
		foreach (explode(";", $filenames) as $filename) {
			$file .= file_get_contents(dirname(__FILE__) . "/$project/$filename");
		}
	}
	if ($callback) {
		$file = call_user_func($callback, $file);
	}
	return '"' . add_quo_slashes($file) . '"';
}

$project = "core";



$file = file_get_contents(dirname(__FILE__) . "/$project/index.php");

$file = preg_replace_callback('~\\b(include|require) "([^"]*)";~', 'put_file', $file);
//$file = str_replace('include "../adminer/include/coverage.inc.php";', '', $file);

$file = preg_replace_callback('~\\b(include|require) "([^"]*)";~', 'put_file', $file); // bootstrap.inc.php


$file = str_replace("\r", "", $file);

/*$file = str_replace('<script type="text/javascript" src="static/editing.js"></script>' . "\n", "", $file);
$file = str_replace('<script type="text/javascript" src="../externals/jush/modules/jush-textarea.js"></script>' . "\n", "", $file);
$file = str_replace('<script type="text/javascript" src="../externals/jush/modules/jush-txt.js"></script>' . "\n", "", $file);
$file = str_replace('<script type="text/javascript" src="../externals/jush/modules/jush-<?php echo $jush; ?>.js"></script>' . "\n", "", $file);
$file = str_replace('<link rel="stylesheet" type="text/css" href="../externals/jush/jush.css">' . "\n", "", $file);*/

$file = preg_replace_callback("~compile_file\\('([^']+)'(?:, '([^']*)')?\\)~", 'compile_file', $file); // integrate static files

$file = preg_replace("~<\\?php\\s*\\?>\n?|\\?>\n?<\\?php~", '', $file);
//$file = php_shrink($file);

$filename = $project .".php";
$file = str_replace("index.php", $filename, $file);
file_put_contents($filename, $file);
echo "$filename created (" . strlen($file) . " B).\n";
