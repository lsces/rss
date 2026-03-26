<?php
namespace Bitweaver\Rss;

/**
 * @package rss
 * JSCreator is a class that writes a js file to a specific
 * location, overriding the createFeed method of the parent HtmlCreator.
 *
 * @author Pascal Van Hecke
 */
class JSCreator extends HtmlCreator {
	public $contentType = "text/javascript";

	/**
	 * writes the javascript
	 * @return    string    the scripts's complete text
	 */
	public function createFeed()
	{
		$feed = parent::createFeed();
		$feedArray = explode("\n",$feed);

		$jsFeed = "";
		foreach ($feedArray as $value) {
			$jsFeed .= "document.write('".trim(addslashes($value))."');\n";
		}
		return $jsFeed;
	}

	/**
	 * Overrrides parent to produce .js extensions
	 *
	 * @return string the feed cache filename
	 * @since 1.4
	 */
	public function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".js";
	}

}