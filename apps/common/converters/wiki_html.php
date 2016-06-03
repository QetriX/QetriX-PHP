<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.01 | Wiki to HTML Converter
 */

use com\qetrix\libs\Dict;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;

class Wiki_Html
{
	const NL = "\n";
	private $_page;

	function __construct()
	{
		$this->_page = QPage::getInstance();
	}

	/** @return QPage */
	function page()
	{
		return $this->_page;
	}

	public function convert($data, Dict $args)
	{
		$headingInitLevel = $args->get("headinginitlevel", 3); // If 3, then "== Heading ==" will be "<h3>Heading</h3>". If 4, the same will be "<h4>Heading</h4>"

		$listLevel = 0;
		$lineType = null;
		$toc = [];
		$imgal = ["page" => []];
		$linksNoFollow = true;

		$lineNumber = 0;
		$output = [];
		$lines = explode(self::NL, str_replace("\r", "", trim($data).self::NL));
		$newpar = 1; // was -1, changed 20140420

		foreach ($lines as $line) {
			$lineNumber++;

			if ($lineType != "code") {
				if ($line == "-----" || $line == "-----") {
					$output[] = "<hr />".self::NL;
					continue;
				}
				if ($line == "----" || $line == "----") {
					$output[] = "<div style=\"clear:both;\"></div>".self::NL;
					continue;
				}

				$sp = mb_strpos($line, " "); // Position of first space on the line
				$x = mb_strlen($line) > 1 ? mb_substr($line, 0, 2) : "";

				if ($x == "* " || $x == "**" || $x == "# " || $x == "##" || $x == "+ " || $x == "++" || $x == "- " || $x == "--" || $x == "◦ " || $x == "◦◦" || $x == "• " || $x == "••" || $x == "x.") { // LIST
					if ($newpar == 2) $output[] = "</p>".self::NL;
					$newpar = 0;
					$llvl = mb_strlen(mb_substr($line, 0, $sp));
					if ($llvl > $listLevel) { /// Higher level
						if ($listLevel == 0) {
							switch (mb_substr($line, 0, 2)) {
								case "* ":
								case "**":
								case "• ":
								case "••":
									$lineType = "ul";
									$output[] = "<".$lineType.">".self::NL;
									break;
								case "# ":
								case "##":
									$lineType = "ol";
									$output[] = "<".$lineType.">".self::NL;
									break;
								case "◦ ":
								case "◦◦":
									$lineType = "ol";
									$output[] = "<".$lineType." class=\"toc\">".self::NL;
									break;
								case "x.":
									$lineType = "ol";
									$output[] = "<".$lineType." class=\"ulletter\">".self::NL;
									break;
								case "+ ":
								case "++":
									$lineType = "ul";
									$output[] = "<".$lineType." class=\"ulplus\">".self::NL;
									break;
								case "- ":
								case "--":
									$lineType = "ul";
									$output[] = "<".$lineType." class=\"ulminus\">".self::NL;
									break;
								case "+-":
									$lineType = "ul";
									$output[] = "<".$lineType." class=\"ulplusminus\">".self::NL;
									break;
							}
						} else if ($lineType != null) $output[] = self::NL.str_repeat("	", $listLevel)."<".$lineType.">".self::NL;
						$listLevel = $llvl;

					} elseif ($llvl < $listLevel && $lineType != null) { /// Lower level
						$output[] = "</li>";
						for ($ll = $listLevel; $ll > $llvl; $ll--) $output[] = self::NL.str_repeat("\t", $ll - 1)."</".$lineType.">".self::NL.str_repeat("\t", $ll - 1)."</li>".self::NL;
						$listLevel = $llvl;

					} else { /// Same level
						$output[] = "</li>".self::NL;
					}

					$output[] = str_repeat("\t", $listLevel)."<li>".self::parseInlineWikiText(mb_substr($line, $sp));
					continue;
				}

				if ($listLevel > 0 && $lineType != null) { /// CLEAR LIST
					for ($ll = $listLevel; $ll > 0; $ll--) $output[] = ($ll < $listLevel ? str_repeat("\t", $ll) : "")."</li>".self::NL.str_repeat("\t", $ll - 1)."</".$lineType.">".self::NL;
					$listLevel = 0;
					$lineType = null;
					$newpar = 1;
					continue;
				}

				if (strpos($line, "\t") > 0) { /// TABLE BEGIN, tab can't be at the beginning
					if ($newpar == 2) $output[] = "</p>".self::NL;
					$newpar = 0;
					if ($lineType !== "tab") {
						$lineType = "tab";
						$output[] = '<table class="table">'.self::NL;
						$output[] = "<tr><th>".str_replace("\t", "</th><th>", $line)."</th></tr>".self::NL;
					} else {
						$output[] = "<tr><td>".str_replace("\t", "</td><td>", self::parseInlineWikiText($line))."</td></tr>".self::NL;
					}
					continue;
				}

				if ($lineType != null && $lineType == "tab") { /// TABLE END
					$output[] = "</table>".self::NL;
					$newpar = 1;
					$lineType = null;
					continue;
				}

				if (substr($line, 0, 8) == "[[Image:") { /// IMAGE
					$lines = explode("|", mb_substr($line, 8, -2));
					$image_name = substr($lines[0], 0, strrpos($lines[0], "."));
					$output[] = '<div class="img'.(in_array("left", $lines) ? " left" : (in_array("right", $lines) ? " right" : "")).'">';
					//if (file_exists(CD.substr($lines[0], 0, 2).'t/'.$lines[0])) $output[] = '<img src="'.D.'content/'.substr($lines[0], 0, 2).'t/'.$lines[0].'" alt="'.$lines[1].'" />'; else $output[] = '<form>'.($lines[1] != '' ? $lines[1] : $lines[0]).': <input type="file" name="file" /><input type="submit" value="Nahrát" /></form>';
					//vd(UD.substr($lines[0], 0, 2).'/'.$lines[0]);
					//Util::log($this->app()->pathContent().substr($lines[0], 0, 2)."/".$lines[0]);
					//Util::log($this->app()->pathContent().(isset($this->page()->get("pathcontentsub"]) ? $this->page()->get("pathcontentsub"] : "").substr($lines[0], 0, 2)."/".$lines[0]);
					if (file_exists($this->page()->pathAppContent().$args->get("pathcontentsub").substr($lines[0], 0, 2)."/".$lines[0])) {
						$fn = $this->page()->pathContent().$args->get("pathcontentsub").substr($lines[0], 0, 2)."/".$lines[0];
						if (in_array("thumb", $lines)) {
							$size = getimagesize($this->page()->pathAppContent().$args->get("pathcontentsub").substr($lines[0], 0, 2)."/".$lines[0]);
							$output[] = '<a href="'.$fn.'" class="thumb" onclick="return !showModal(this, '.$size[0].', '.$size[1].');"><img src="'.$this->page()->pathContent().$args->get("pathcontentsub").substr($lines[0], 0, 2).'/'.$image_name.'-thumb.jpg" alt="'.$lines[1].'" /><br>'.$lines[1].'</a>';
						} elseif (isset($lines[1]) && $lines[1] != "")
							$output[] = '<span><img src="'.$fn.'" alt="'.$lines[1].'" /><br>'.$lines[1].'</span>';
						else
							$output[] = '<img src="'.$this->page()->pathContent().$args->get("pathcontentsub").substr($lines[0], 0, 2)."/".$lines[0].'" alt="'.(isset($lines[1]) ? $lines[1] : "").'" />';
						$imgal["page"][] = ["fn" => $fn, "l" => isset($lines[1]) ? $lines[1] : "", "w" => isset($size) ? $size[0] : 0, "h" => isset($size) ? $size[1] : 0]; // Add image to gallery (enables next/prev image)
					} else $output[] = '<form title="'.$this->page()->pathContent().$args->get("pathcontentsub").substr($lines[0], 0, 2)."/".$lines[0].'"><label for="">'.(isset($lines[1]) && $lines[1] != '' ? $lines[1] : $lines[0]).':</label> <input id="" type="file" name="file" /><input type="submit" value="Upload" class="btn"></form>';
					$output[] = "</div>".self::NL;
					continue;
				}

				if (substr($line, 0, 2) == "{{") { // CODE BEGIN, netransformuje zadne wiki tagy v sobe obsazene
					$lineType = "code";
					$output[] = "<pre".(strlen($line) > 2 ? " class=\"".trim(substr($line, 2))."\"" : "").">";
					continue;
				}

				if (substr($line, 0, 2) == "??") {
					if ($lineType == "pre") $output[] = "</pre>".self::NL;
					else {
						$lineType = "pre";
						$newpar = 3;
						$output[] = "<pre".(strlen($line > 2) ? " class=\"".trim(substr($line, 2))."\"" : "").">";
					}
					continue;
				}

				if (substr($line, 0, 2) == "  ") { /// TAKY NECO - dve mezery
				}
				if (substr($line, 0, 2) == ">>") { /// Quote
					$output[] = '<div class="quote">'.trim(mb_substr($line, 2))."</div>".self::NL;
					continue;
				}
				if (substr($line, 0, 2) == "[]" && substr($line, -2) == "[]") { /// Box
					$output[] = '<div class="box">'.self::parseInlineWikiText(mb_substr($line, 2, -2))."</div>".self::NL;
					continue;
				}
				if (substr($line, 0, 3) == "[ ]" || substr($line, 0, 3) == "[] ") { /// CheckBox - Unchecked
					$output[] = '<input type="checkbox" name="chb[]" id="chb_'.$lineNumber.'" onchange="alert(this.checked);" /><label for="chb_'.$lineNumber.'">'.trim(mb_substr($line, 3)).'</label>'.self::NL;
					continue;
				}
				if (substr($line, 0, 3) == "[x]") { /// CheckBox - Checked
					$output[] = '<input type="checkbox" name="chb[]" id="chb_'.$lineNumber.'" checked="checked" onchange="alert(this.checked);" /><label for="chb_'.$lineNumber.'">'.trim(mb_substr($line, 3)).'</label>'.self::NL;
					continue;
				}
				if (substr($line, 0, 2) == "%%" && substr($line, -2) == "%%") { /// YouTube video
					$output[] = '<div class="youtube"><iframe width="600" height="338" src="//www.youtube.com/embed/'.substr($line, 2, -2).'" frameborder="0" allowfullscreen></iframe></div>';
					continue;
				}
				if (substr($line, 0, 3) == "!!!" && substr($line, -3) == "!!!") { /// Spoiler
					$output[] = '<div onclick="(this.style.height==\'auto\'?this.style.height=\'1.3em\':this.style.height=\'auto\');" class="spoiler">!! SPOILER !!<br />'.substr($line, 3, -3).'</div>'.self::NL;
					continue;
				}
				if (substr($line, 0, 2) == "==") {
					$h = trim(substr($line, $sp, -$sp));
					$hh = str_replace("[", "", str_replace("]", "", $h));
					$hhkey = Util::crKey($hh);
					$output[] = "<a name=\"".$hhkey."\"></a><h".($sp - 2 + $headingInitLevel)." id=\"toc_".$hhkey."\">".self::parseInlineWikiText($h)."</h".($sp - 2 + $headingInitLevel).">".self::NL; /// Hn
					$toc[] = str_repeat("#", $sp - 1)." [[#".$hhkey."|".$hh."]]"; // TODO: add $headingInitLevel
					$newpar = 1;
					continue;
				}

			} elseif (substr($line, -2) == "}}") { /// Code ends
				$output[] = "</pre>";
				$newpar = 1;
				$lineType = null;
				continue;

			} else { // Inside code
				$output[] = htmlSpecialChars($line).self::NL;
				//$output[] = $line.self::NL;
				continue;
			}

			if (trim($line) != "") {
				if ($newpar == 1) {
					$output[] = "<p>";
					$newpar = 2;
				} elseif ($newpar == 2 || $newpar === 0) $output[] = "<br>".self::NL;
				elseif ($newpar == 3) $output[] = self::NL;
				$output[] = self::parseInlineWikiText($line);
				if ($newpar === -1) $newpar = 0;
			} else {
				if ($newpar == 2) $output[] = "</p>".self::NL;
				$newpar = 1; /// new paragraph trigger
			}
		} // End of foreach
		if ($newpar == 2) $output .= "</p>".self::NL;

		if (count($toc) > 0) return preg_replace('/<h3/', "<div id=\"toc\">".self::convert(implode(self::NL, $toc), $args).self::NL."</div>".self::NL."<h3", implode("", $output), 1);
		return implode("", $output);
	}

	private function parseInlineWikiText($text)
	{
		$text = trim(htmlspecialchars($text));
		$text = str_replace("&lt;/&gt;", "<br />", $text);

		$text = preg_replace("/\'\'\'(.*?)\'\'\'/", "<strong>$1</strong>", $text);
		$text = preg_replace("/\'\'(.*?)\'\'/", "<em>$1</em>", $text);
		$text = preg_replace('/\-\-(.*?)\-\-/', '<span style="text-decoration:line-through;">$1</span>', $text);
		$text = preg_replace('/\-\-(.*?)\-\-/', '<span style="text-decoration:line-through;">$1</span>', $text);
		$text = preg_replace('/&gt;(.*?)\n/', "<blockquote>$1</blockquote>", $text);

		if (strpos($text, "[[Image:") !== false) {
			preg_match_all('/\[\[Image\:(.+?)(\|(.*?)(\|thumb)?(\|left|\|right)?)?\]\]/', $text, $matches, PREG_SET_ORDER);
			foreach ($matches as $m) {
				$x = explode("|", substr($m[0], 8, strlen($m[0]) - 10));
				$str = "<div class=\"image\">";
				if (in_array("thumb", $x) !== false) {
					$str .= "<a href=\"".$this->page()->pathContent().Util::crKey(mb_substr($x[0], 0, 2))."/".$x[0]."\" class=\"thumb".(in_array("left", $x) !== false ? " left" : "")."\">";
					$str .= "<img src=\"".$this->page()->pathContent().Util::crKey(mb_substr($x[0], 0, 2))."/".$x[0]."-thumb.jpg\" alt=\"\"";
				} else
					$str .= "<img src=\"".$this->page()->pathContent().Util::crKey(mb_substr($x[0], 0, 2))."/".$x[0]."\" alt=\"\"";
				$str .= " />";
				if (count($x) > 1 && $x[1] != "thumb" && $x[1] != "left" && $x[1] != "right") $str .= "<br />".$x[1];
				if (in_array("thumb", $x) !== false) $str .= "</a>";
				$str .= "</div>";
				$text = str_replace($m[0], $str, $text);
			}
			//$text = preg_replace('/\[\[Image:(.*?)\|(.*?)\]\]/', '<img src="$1" alt="$2" title="$2" />', $text);
			//$text = preg_replace('/\[\[Image:(.*?)\]\]/', '<img src="$1" alt="" />', $text);
		}

		$text = preg_replace('/\[\[\#([^\]]+)\|([^\]]+)\]\]/', '<a href="#$1" title="$2" rel="nofollow">$2</a>', $text);
		$text = preg_replace('/\[\[([^\]]+)\|([^\]]+)\]\]/', '<a href="'.$this->page()->pathBase().'$1" title="$2" rel="nofollow">$2</a>', $text);
		$text = preg_replace_callback('/\[\[([^\]]+)\]\]([a-z]*)([\W]|$)/', function ($matches) {
			return '<a href="'.$this->page()->pathBase().mb_strToLower($matches[1]).'" title="'.$matches[1].'" rel="nofollow">'.$matches[1].$matches[2].'</a>'.$matches[3];
		}, $text);
		$text = preg_replace('/\[(.*?)\@(.*?) (.*?)\]/', '<a href="mailto:$1@$2" title="$3">$3</a>', $text);
		$text = preg_replace('/\[(.*?)\@(.*?)\]/', '<a href="mailto:$1@$2" title="$1">$1@$2</a>', $text);
		$text = preg_replace('/\[\/([^]]*?) ([^]]*?)\]/', '<a href="/$1" title="$2">$2</a>', $text);
		$text = preg_replace('/\[https\:\/\/([^]]*?) ([^]]*?)\]/', '<a onclick="return !window.open(this.href);" href="https://$1" title="$2">$2</a>', $text);
		$text = preg_replace('/\[https\:\/\/([^]]*?)\]/', '<a onclick="return !window.open(this.href);" href="https://$1" title="$1">$1</a>', $text);
		$text = preg_replace('/\[http\:\/\/([^]]*?) ([^]]*?)\]/', '<a onclick="return !window.open(this.href);" href="http://$1" title="$2">$2</a>', $text);
		$text = preg_replace('/\[http\:\/\/([^]]*?)\]/', '<a onclick="return !window.open(this.href);" href="http://$1" title="$1">$1</a>', $text);

		$text = preg_replace('/(?<= \w) /i', '&nbsp;', $text); // Prevents single letter words ("orphans") to stay at the end of the row

		return $text;
	}
}
