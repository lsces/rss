<?php
namespace Bitweaver\Rss;

use Bitweaver\KernelTools;

/**
 * @version $Header$
 * @package rss
 */
/***************************************************************************
FeedCreator class v1.8.0-dev (development)
http://feedcreator.org
maintained by Mohammad Hafiz bin Ismail (info@mypapit.net)
feedcreator.org

originally (c) Kai Blankenhorn
www.bitfolge.de
kaib@bitfolge.de


v1.3 work by Scott Reynen (scott@randomchaos.com) and Kai Blankenhorn
v1.5 OPML support by Dirk Clemens
v1.7.2-mod on-the-fly feed generation by Fabian Wolf (info@f2w.de)
v1.7.2-ppt ATOM 1.0 support by Mohammad Hafiz bin Ismail (mypapit@gmail.com)

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

****************************************************************************


Changelog:

v1.7.2	10-11-04
	license changed to LGPL

v1.7.1
	fixed a syntax bug
	fixed left over debug code

v1.7	07-18-04
	added HTML and JavaScript feeds (configurable via CSS) (thanks to Pascal Van Hecke)
	added HTML descriptions for all feed formats (thanks to Pascal Van Hecke)
	added a switch to select an external stylesheet (thanks to Pascal Van Hecke)
	changed default content-type to application/xml
	added character encoding setting
	fixed numerous smaller bugs (thanks to S�en Fuhrmann of golem.de)
	improved changing ATOM versions handling (thanks to August Trometer)
	improved the UniversalFeedCreator's useCached method (thanks to S�en Fuhrmann of golem.de)
	added charset output in HTTP headers (thanks to S�en Fuhrmann of golem.de)
	added Slashdot namespace to RSS 1.0 (thanks to S�en Fuhrmann of golem.de)

v1.6	05-10-04
	added stylesheet to RSS 1.0 feeds
	fixed generator comment (thanks Kevin L. Papendick and Tanguy Pruvot)
	fixed RFC822 date bug (thanks Tanguy Pruvot)
	added TimeZone customization for RFC8601 (thanks Tanguy Pruvot)
	fixed Content-type could be empty (thanks Tanguy Pruvot)
	fixed author/creator in RSS1.0 (thanks Tanguy Pruvot)

v1.6 beta	02-28-04
	added Atom 0.3 support (not all features, though)
	improved OPML 1.0 support (hopefully - added more elements)
	added support for arbitrary additional elements (use with caution)
	code beautification :-)
	considered beta due to some internal changes

v1.5.1	01-27-04
	fixed some RSS 1.0 glitches (thanks to St�hane Vanpoperynghe)
	fixed some inconsistencies between documentation and code (thanks to Timothy Martin)

v1.5	01-06-04
	added support for OPML 1.0
	added more documentation

v1.4	11-11-03
	optional feed saving and caching
	improved documentation
	minor improvements

v1.3    10-02-03
	renamed to FeedCreator, as it not only creates RSS anymore
	added support for mbox
	tentative support for echo/necho/atom/pie/???

v1.2    07-20-03
	intelligent auto-truncating of RSS 0.91 attributes
	don't create some attributes when they're not set
	documentation improved
	fixed a real and a possible bug with date conversions
	code cleanup

v1.1    06-29-03
	added images to feeds
	now includes most RSS 0.91 attributes
	added RSS 2.0 feeds

v1.0    06-24-03
	initial release
***************************************************************************/

/**************************************************************************
*          A little setup                                                 *
**************************************************************************/

// your local timezone, set to "" to disable or for GMT
define("TIME_ZONE","");

/**
 * Version string.
 **/
define("FEEDCREATOR_VERSION", "FeedCreator bitweaver 5 repackage");

/**
 * @package rss
 * FeedCreator is the abstract base implementation for concrete
 * implementations that implement a specific format of syndication.
 *
 * @abstract
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 * @since 1.4
 */
class FeedCreator extends HtmlDescribable {

	/**
	 * Mandatory attributes of a feed.
	 */
	public $title, $description, $link;


	/**
	 * Optional attributes of a feed.
	 */
	public $syndicationURL, $image, $language, $copyright, $pubDate, $lastBuildDate, $editor, $editorEmail, $webmaster, $category, $docs, $ttl, $rating, $skipHours, $skipDays;

	/**
	* The url of the external xsl stylesheet used to format the naked rss feed.
	* Ignored in the output when empty.
	*/
	public	$xslStyleSheet = "";

	/**
	* The url of the external css stylesheet used to format the naked syndication feed.
	* Ignored in the output when empty.
		*/
	public $cssStyleSheet = '';


	/**
	 * @access private
	 */
	public $items = [];


	/**
	 * This feed's MIME content type.
	 * @since 1.4
	 * @access private
	 */
	public $contentType = "application/xml";


	/**
	 * This feed's character encoding.
	 * @since 1.6.1
	 *
	 * public $encoding = "ISO-8859-1"; //original :p
	 */
	public $encoding = "utf-8";

	/*
	 * Generator string
	 *
	 */

	 public $generator = "support@rdmcloud.uk";


	/**
	 * Any additional elements to include as an assiciated array. All $key => $value pairs
	 * will be included unencoded in the feed in the form
	 *     <$key>$value</$key>
	 * Again: No encoding will be used! This means you can invalidate or enhance the feed
	 * if $value contains markup. This may be abused to embed tags not implemented by
	 * the FeedCreator class used.
	 */
	public $additionalElements = [];


	/**
	 * Adds an FeedItem to the feed.
	 *
	 * @param object FeedItem $item The FeedItem to add to the feed.
	 * @access public
	 */
	public function addItem($item) {
		$this->items[] = $item;
	}

	/**
	 *
	 *
	 *
	 **/
	 function version() {

	 	return FEEDCREATOR_VERSION." (".$this->generator.")";
	 }

	/**
	 * Truncates a string to a certain length at the most sensible point.
	 * First, if there's a '.' character near the end of the string, the string is truncated after this character.
	 * If there is no '.', the string is truncated after the last ' ' character.
	 * If the string is truncated, " ..." is appended.
	 * If the string is already shorter than $length, it is returned unchanged.
	 *
	 * @static
	 * @param string    string A string to be truncated.
	 * @param int        length the maximum length the string should be truncated to
	 * @return string    the truncated string
	 */
	public static function iTrunc($string, $length) {
		if (strlen($string)<=$length) {
			return $string;
		}

		$pos = strrpos($string,".");
		if ($pos>=$length-4) {
			$string = substr($string,0,$length-4);
			$pos = strrpos($string,".");
		}
		if ($pos>=$length*0.4) {
			return substr($string,0,$pos+1)." ...";
		}

		$pos = strrpos($string," ");
		if ($pos>=$length-4) {
			$string = substr($string,0,$length-4);
			$pos = strrpos($string," ");
		}
		if ($pos>=$length*0.4) {
			return substr($string,0,$pos)." ...";
		}

		return substr($string,0,$length-4)." ...";

	}


	/**
	 * Creates a comment indicating the generator of this feed.
	 * The format of this comment seems to be recognized by
	 * Syndic8.com.
	 */
	public function _createGeneratorComment() {
		return "<!-- generator=\"".$this->version()."\" -->\n";
	}

	/**
	 * Creates a string containing all additional elements specified in
	 * $additionalElements.
	 * @param	array	elements an associative array containing key => value pairs
	 * @param 	string	indentString	a string that will be inserted before every generated line
	 * @return	string	the XML tags corresponding to $additionalElements
	 */
	public function _createAdditionalElements($elements, $indentString="") {
		$ae = "";
		if (is_array( $elements )) {
			foreach ( $elements as $key => $value ) {
				$ae .= $indentString . "<$key>$value</$key>\n";
			}
		}
		return $ae;
	}

	public function _createStylesheetReferences() {
		$xml = "";
		if ($this->cssStyleSheet) $xml .= "<?xml-stylesheet href=\"".$this->cssStyleSheet."\" type=\"text/css\"?>\n";
		if ($this->xslStyleSheet) $xml .= "<?xml-stylesheet href=\"".$this->xslStyleSheet."\" type=\"text/xsl\"?>\n";
		return $xml;
	}

	/**
	 * Builds the feed's text.
	 * @abstract
	 * @return string|void    the feed's complete text
	 */
	public function createFeed() {
	}

	/**
	 * Generate a filename for the feed cache file. The result will be $_SERVER["SCRIPT_NAME"] with the extension changed to .xml.
	 * For example:
	 *
	 * echo $_SERVER["PHP_SELF"]."\n";
	 * echo FeedCreator::_generateFilename();
	 *
	 * would produce:
	 *
	 * /rss/latestnews.php
	 * latestnews.xml
	 *
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	public function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".xml";
	}

	/**
	 * @since 1.4
	 * @access private
	 */
	public function _redirect($filename) {
		// attention, heavily-commented-out-area

		// maybe use this in addition to file time checking
		//Header("Expires: ".date("r",time()+$this->_timeout));

		/* no caching at all, doesn't seem to work as good:
		Header("Cache-Control: no-cache");
		Header("Pragma: no-cache");
		*/

		// HTTP redirect, some feed readers' simple HTTP implementations don't follow it
		//Header("Location: ".$filename);

		// {{{ BITMOD
		//Header("Content-Type: ".$this->contentType."; charset=".$this->encoding."; filename=".basename($filename));
		Header( "Content-Type: " . $this->contentType . "; charset=" . $this->encoding . ";" );
		// BITMOD }}}
		Header("Content-Disposition: inline; filename=".basename($filename));
		readfile($filename );
		die();
	}

	/**
	 * Turns on caching and checks if there is a recent version of this feed in the cache.
	 * If there is, an HTTP redirect header is sent.
	 * To effectively use caching, you should create the FeedCreator object and call this method
	 * before anything else, especially before you do the time consuming task to build the feed
	 * (web fetching, for example).
	 * @since 1.4
	 * @param string	filename	optional	the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["SCRIPT_NAME"] with the extension changed to .xml (see _generateFilename()).
	 * @param int		timeout	optional	the timeout in seconds before a cached version is refreshed (defaults to 3600 = 1 hour)
	 */
	public function useCached($filename="", $timeout=3600) {
		$this->_timeout = $timeout;
		if ($filename == "") {
			$filename = $this->_generateFilename();
		}
		if (file_exists( $filename ) AND ( time() - filemtime( $filename ) < $timeout )) {
			$this->_redirect( $filename );
		}
	}

	/**
	 * Saves this feed as a file on the local disk. After the file is saved, a redirect
	 * header may be sent to redirect the user to the newly created file.
	 * @since 1.4
	 *
	 * @param	string	filename	optional	the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["SCRIPT_NAME"] with the extension changed to .xml (see _generateFilename()).
	 * @param	boolean	redirect	optional	send an HTTP redirect header or not. If true, the user will be automatically redirected to the created file.
	 */
	public function saveFeed($filename="", $displayContents=true) {
		if ($filename=="") {
			$filename = $this->_generateFilename();
		}
		if (!is_dir( dirname( $filename ) )) {
			KernelTools::mkdir_p( dirname( $filename ) );
		}
		$feedFile = fopen( $filename, "w+" );
		if ($feedFile) {
			fputs( $feedFile, $this->createFeed() );
			fclose( $feedFile );
			if ($displayContents) {
				$this->_redirect( $filename );
			}
		} else {
			echo "<br /><b>Error creating feed file, please check write permissions.</b><br />";
		}
	}

	/**
	 * Outputs this feed directly to the browser - for on-the-fly feed generation
	 * @since 1.7.2-mod
	 *
	 * still missing: proper header output - currently you have to add it manually
	 */
	public function outputFeed() {
		echo $this->createFeed();
	}

	public function setEncoding($encoding="utf-8") {
		$this->encoding = "utf-8";

	}

	/**
		 * Creates a string containing all additional namespace specified
		 */

	public function addNamespace($ns,$uri)
	{
		$array = array_combine(array($ns),array($uri));
		$this->namespace = array_merge($array,$this->namespace);
	}

	public function _createNamespace() {
			$ns = "";

			if (is_array($this->namespace)) {
				foreach($this->namespace AS $key => $value) {
					$ns.= " xmlns:$key=\"$value\"";
				}
			}
			return $ns;
	}

	/**
	*
	* Additional namespace for custom modules and tags
	*
	* $key=>$value pair will match namespace xmlns:$key="$value" in tags
	* EXPERIMENTAL!
	*
	*/
	public $namespace = [];
}

/**
 * @package rss
 * RSSCreator10 is a FeedCreator that implements RDF Site Summary (RSS) 1.0.
 *
 * @see http://www.purl.org/rss/1.0/
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator10 extends FeedCreator {

	/**
	 * Builds the RSS feed's text. The feed will be compliant to RDF Site Summary (RSS) 1.0.
	 * The feed will contain all items previously added in the same order.
	 * @return    string    the feed's complete text
	 */
	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		if ($this->cssStyleSheet=="") {
			$cssStyleSheet = "http://www.w3.org/2000/08/w3c-synd/style.css";
		}
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<rdf:RDF\n";
		$feed.= "    xmlns=\"http://purl.org/rss/1.0/\"\n";
		$feed.= "    xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
		$feed.= "    xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n";
		$feed.= "    xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
		$feed.= "    <channel rdf:about=\"".$this->syndicationURL."\">\n";
		$feed.= "        <title>".htmlspecialchars($this->title)."</title>\n";
		$feed.= "        <description>".htmlspecialchars($this->description)."</description>\n";
		$feed.= "        <link>".$this->link."</link>\n";
		if ($this->image!=null) {
			$feed.= "        <image rdf:resource=\"".$this->image->url."\" />\n";
		}
		$now = new FeedDate();
		$feed .= "       <dc:date>" . htmlspecialchars( $now->iso8601() ) . "</dc:date>\n";
		$feed .= "        <items>\n";
		$feed .= "            <rdf:Seq>\n";
		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$feed .= "                <rdf:li rdf:resource=\"" . htmlspecialchars( $this->items[$i]->link ) . "\"/>\n";
		}
		$feed .= "            </rdf:Seq>\n";
		$feed .= "        </items>\n";
		$feed .= "    </channel>\n";
		if ($this->image != null) {
			$feed .= "    <image rdf:about=\"" . $this->image->url . "\">\n";
			$feed .= "        <title>" . $this->image->title . "</title>\n";
			$feed .= "        <link>" . $this->image->link . "</link>\n";
			$feed .= "        <url>" . $this->image->url . "</url>\n";
			$feed .= "    </image>\n";
		}
		$feed .= $this->_createAdditionalElements( $this->additionalElements, "    " );

		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$feed .= "    <item rdf:about=\"" . htmlspecialchars( $this->items[$i]->link ) . "\">\n";
			//$feed.= "        <dc:type>Posting</dc:type>\n";
			$feed .= "        <dc:format>text/html</dc:format>\n";
			if ($this->items[$i]->date != null) {
				$itemDate = new FeedDate( $this->items[$i]->date );
				$feed .= "        <dc:date>" . htmlspecialchars( $itemDate->iso8601() ) . "</dc:date>\n";
			}
			if ($this->items[$i]->source != "") {
				$feed .= "        <dc:source>" . htmlspecialchars( $this->items[$i]->source ) . "</dc:source>\n";
			}
			if ($this->items[$i]->author != "") {
				$feed .= "        <dc:creator>" . htmlspecialchars( $this->items[$i]->author ) . "</dc:creator>\n";
			}
			$feed.= "        <title>".htmlspecialchars(strip_tags(strtr($this->items[$i]->title,"\n\r","  ")))."</title>\n";
			$feed.= "        <link>".htmlspecialchars($this->items[$i]->link)."</link>\n";
			$feed.= "        <description>".htmlspecialchars($this->items[$i]->description)."</description>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			$feed.= "    </item>\n";
		}
		$feed .= "</rdf:RDF>\n";
		return $feed;
	}

}

/**
 * @package rss
 * RSSCreator091 is a FeedCreator that implements RSS 0.91 Spec, revision 3.
 *
 * @see http://my.netscape.com/publish/formats/rss-spec-0.91.html
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator091 extends FeedCreator {

	/**
	 * Stores this RSS feed's version number.
	 * @access private
	 */
	public $RSSVersion;
	public $itunes;
	public $_timeout;

	public function addNamespace($ns,$uri) {
		parent::addNamespace($ns,$uri);
	}

	public function RSSCreator091() {
		$this->_setRSSVersion("0.91");
		$this->contentType = "application/rss+xml";
	}

	/**
	 * Sets this RSS feed's version number.
	 * @access private
	 */
	public function _setRSSVersion($version) {
		$this->RSSVersion = $version;
	}

	/**
	 * Builds the RSS feed's text. The feed will be compliant to RDF Site Summary (RSS) 1.0.
	 * The feed will contain all items previously added in the same order.
	 * @return    string    the feed's complete text
	 */
	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		if ($this->RSSVersion == "2.0" || $this->RSSVersion == "1.0" ) {
			$feed.= "<rss version=\"".$this->RSSVersion."\" ". $this->_createNamespace(). ">\n";
		} else {
			$feed.= "<rss version=\"".$this->RSSVersion."\"";
		}
		// handler references for our rss tag
		if (isset( $this->media )) {
			$feed .= " xmlns:media=\"http://search.yahoo.com/mrss/\"";
		}
		if (isset( $this->itunes )) {
			$feed .= " xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\"";
		}
		// we're done with our rss tag
		$feed .= ">\n";
		$feed .= "    <channel>\n";
		$feed .= "        <title>" . FeedCreator::iTrunc( htmlspecialchars( $this->title ), 100 ) . "</title>\n";
		$this->descriptionTruncSize = 500;
		$feed .= "        <description>" . $this->getDescription() . "</description>\n";
		$feed .= "        <link>" . $this->link . "</link>\n";
		$now = new FeedDate();
		$feed .= "        <lastBuildDate>" . htmlspecialchars( $now->rfc822() ) . "</lastBuildDate>\n";
		$feed.= "        <generator>". $this->version()."</generator>\n";

		if ($this->image != null) {
			$feed .= "        <image>\n";
			$feed .= "            <url>" . $this->image->url . "</url>\n";
			$feed .= "            <title>" . FeedCreator::iTrunc( htmlspecialchars( $this->image->title ), 100 ) . "</title>\n";
			$feed .= "            <link>" . $this->image->link . "</link>\n";
			if ($this->image->width != "") {
				$feed .= "            <width>" . $this->image->width . "</width>\n";
			}
			if ($this->image->height != "") {
				$feed .= "            <height>" . $this->image->height . "</height>\n";
			}
			if ($this->image->description != "") {
				$feed .= "            <description>" . $this->image->getDescription() . "</description>\n";
			}
			$feed .= "        </image>\n";
		}
		if ($this->language != "") {
			$feed .= "        <language>" . $this->language . "</language>\n";
		}
		if ($this->copyright != "") {
			$feed .= "        <copyright>" . FeedCreator::iTrunc( htmlspecialchars( $this->copyright ), 100 ) . "</copyright>\n";
		}
		if ($this->editor != "") {
			$feed .= "        <managingEditor>" . FeedCreator::iTrunc( htmlspecialchars( $this->editor ), 100 ) . "</managingEditor>\n";
		}
		if ($this->webmaster != "") {
			$feed .= "        <webMaster>" . FeedCreator::iTrunc( htmlspecialchars( $this->webmaster ), 100 ) . "</webMaster>\n";
		}
		if ($this->pubDate != "") {
			$pubDate = new FeedDate( $this->pubDate );
			$feed .= "        <pubDate>" . htmlspecialchars( $pubDate->rfc822() ) . "</pubDate>\n";
		}
		if ($this->category != "") {
			$feed .= "        <category>" . htmlspecialchars( $this->category ) . "</category>\n";
		}
		if ($this->docs != "") {
			$feed .= "        <docs>" . FeedCreator::iTrunc( htmlspecialchars( $this->docs ), 500 ) . "</docs>\n";
		}
		if ($this->ttl != "") {
			$feed .= "        <ttl>" . htmlspecialchars( $this->ttl ) . "</ttl>\n";
		}
		if ($this->rating != "") {
			$feed .= "        <rating>" . FeedCreator::iTrunc( htmlspecialchars( $this->rating ), 500 ) . "</rating>\n";
		}
		if ($this->skipHours != "") {
			$feed .= "        <skipHours>" . htmlspecialchars( $this->skipHours ) . "</skipHours>\n";
		}
		if ($this->skipDays != "") {
			$feed .= "        <skipDays>" . htmlspecialchars( $this->skipDays ) . "</skipDays>\n";
		}
		if (isset( $this->media ) && is_array( $this->media )) {
			if (isset( $this->media['thumbnail'] )) {
				$feed .= "        <media:thumbnail url='" . $this->media['thumbnail'] . "'/>\n";
			}
		}
		if (isset( $this->itunes ) && is_array( $this->itunes )) {
			if (isset( $this->itunes['thumbnail'] )) {
				$feed .= "        <itunes:image href='" . $this->itunes['thumbnail'] . "'/>\n";
			}
			// itunes expects an explicit setting. we default to no because we're not into this facist crap, but if you need to set it to yes you can.
			$itunesExplicit = isset( $this->itunes['explicit'] ) ? $this->itunes['explicit'] : "no";
			$feed .= "        <itunes:explicit>" . $itunesExplicit . "</itunes:explicit>\n";
		}

		if ($this->RSSVersion == "2.0" || $this->RSSVersion == "1.0" ) {
			$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		}

		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$feed .= "        <item>\n";
			$feed .= "            <title>" . FeedCreator::iTrunc( htmlspecialchars( strip_tags( $this->items[$i]->title ) ), 100 ) . "</title>\n";
			$feed .= "            <link>" . htmlspecialchars( $this->items[$i]->link ) . "</link>\n";
			$feed .= "            <description>" . $this->items[$i]->getDescription() . "</description>\n";

			if ($this->items[$i]->author!="") {
				if ($this->items[$i]->authorEmail!="") {
					$feed.= "            <author> " . htmlspecialchars($this->items[$i]->authorEmail) . " (".htmlspecialchars($this->items[$i]->author).")</author>\n";
				} else {
				      $feed.= "            <author> no_email@example.com (".htmlspecialchars($this->items[$i]->author).")</author>\n";
				}
			}
			/*
			// on hold
			if ($this->items[$i]->source!="") {
					$feed.= "            <source>".htmlspecialchars($this->items[$i]->source)."</source>\n";
			}
			*/
			if ($this->items[$i]->category!="") {
				$feed.= "            <category ";

				if (!empty($this->items[$i]->categoryScheme)) {

					$feed.=" domain=\"". htmlspecialchars($this->items[$i]->categoryScheme)."\"";
				}
				$feed.=">".htmlspecialchars($this->items[$i]->category)."</category>\n";
			}
			if ($this->items[$i]->comments != "") {
				$feed .= "            <comments>" . htmlspecialchars( $this->items[$i]->comments ) . "</comments>\n";
			}
			if ($this->items[$i]->date != "") {
				$itemDate = new FeedDate( $this->items[$i]->date );
				$feed .= "            <pubDate>" . htmlspecialchars( $itemDate->rfc822() ) . "</pubDate>\n";
			}


			if ($this->items[$i]->guid!="") {
				$feed.= "            <guid isPermaLink=\"false\">".htmlspecialchars($this->items[$i]->guid)."</guid>\n";
			} else {
			$feed.= "            <guid isPermaLink=\"false\">".htmlspecialchars($this->items[$i]->link)."</guid>\n";

			}

			if ($this->RSSVersion == "2.0" || $this->RSSVersion == "1.0" ) {
				$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			}

			if ($this->RSSVersion == "2.0" && $this->items[$i]->enclosure != NULL)
			{
				$feed.= "            <enclosure url=\"";
				$feed.= $this->items[$i]->enclosure->url;
				$feed.= "\" length=\"";
				$feed.= $this->items[$i]->enclosure->length;
				$feed.= "\" type=\"";
				$feed.= $this->items[$i]->enclosure->type;
				$feed.= "\"/>\n";
		        }



			$feed.= "        </item>\n";
		}
		$feed .= "    </channel>\n";
		$feed .= "</rss>\n";
		return $feed;
	}

}

/**
 * @package rss
 * RSSCreator20 is a FeedCreator that implements RDF Site Summary (RSS) 2.0.
 *
 * @see http://backend.userland.com/rss
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator20 extends RSSCreator091 {

    function RSSCreator20() {
        parent::_setRSSVersion("2.0");
    }

}

/**
 * @package rss
 * PIECreator01 is a FeedCreator that implements the emerging PIE specification,
 * as in http://intertwingly.net/wiki/pie/Syntax.
 *
 * @deprecated
 * @since 1.3
 * @author Scott Reynen <scott@randomchaos.com> and Kai Blankenhorn <kaib@bitfolge.de>
 */
class PIECreator01 extends FeedCreator {

	public function PIECreator01() {
		$this->encoding = "utf-8";
	}

	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed version=\"0.1\" xmlns=\"http://example.com/newformat#\">\n";
		$feed.= "    <title>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</title>\n";
		$this->truncSize = 500;
		$feed .= "    <subtitle>" . $this->getDescription() . "</subtitle>\n";
		$feed .= "    <link>" . $this->link . "</link>\n";
		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$feed .= "    <entry>\n";
			$feed .= "        <title>" . FeedCreator::iTrunc( htmlspecialchars( strip_tags( $this->items[$i]->title ) ), 100 ) . "</title>\n";
			$feed .= "        <link>" . htmlspecialchars( $this->items[$i]->link ) . "</link>\n";
			$itemDate = new FeedDate( $this->items[$i]->date );
			$feed .= "        <created>" . htmlspecialchars( $itemDate->iso8601() ) . "</created>\n";
			$feed .= "        <issued>" . htmlspecialchars( $itemDate->iso8601() ) . "</issued>\n";
			$feed .= "        <modified>" . htmlspecialchars( $itemDate->iso8601() ) . "</modified>\n";
			$feed .= "        <id>" . htmlspecialchars( $this->items[$i]->guid ) . "</id>\n";
			if ($this->items[$i]->author != "") {
				$feed .= "        <author>\n";
				$feed .= "            <name>" . htmlspecialchars( $this->items[$i]->author ) . "</name>\n";
				if ($this->items[$i]->authorEmail != "") {
					$feed .= "            <email>" . $this->items[$i]->authorEmail . "</email>\n";
				}
				$feed .= "        </author>\n";
			}
			$feed .= "        <content type=\"text/html\" xml:lang=\"en-us\">\n";
			$feed .= "            <div xmlns=\"http://www.w3.org/1999/xhtml\">" . $this->items[$i]->getDescription() . "</div>\n";
			$feed .= "        </content>\n";
			$feed .= "    </entry>\n";
		}
		$feed .= "</feed>\n";
		return $feed;
	}
}

/**
 * AtomCreator10 is a FeedCreator that implements the atom specification,
 * as in http://www.atomenabled.org/developers/syndication/atom-format-spec.php
 * Please note that just by using AtomCreator10 you won't automatically
 * produce valid atom files. For example, you have to specify either an editor
 * for the feed or an author for every single feed item.
 *
 * Some elements have not been implemented yet. These are (incomplete list):
 * author URL, item author's email and URL, item contents, alternate links,
 * other link content types than text/html. Some of them may be created with
 * AtomCreator10::additionalElements.
 *
 * @see FeedCreator#additionalElements
 * @since 1.7.2-mod (modified)
 * @author Mohammad Hafiz Ismail (mypapit@gmail.com)
 */
 class AtomCreator10 extends FeedCreator {

	public function AtomCreator10() {
		$this->contentType = "application/atom+xml";
		$this->encoding = "utf-8";

	}

	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed xmlns=\"http://www.w3.org/2005/Atom\"";
		if ($this->language!="") {
			$feed.= " xml:lang=\"".$this->language."\"";
		}
		$feed.= ">\n";
		$feed.= "    <title>".htmlspecialchars($this->title)."</title>\n";
		$feed.= "    <subtitle>".htmlspecialchars($this->description)."</subtitle>\n";
		$feed.= "    <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->link)."\"/>\n";
		$feed.= "    <id>".htmlspecialchars($this->link)."</id>\n";
		$now = new FeedDate();
		$feed.= "    <updated>".htmlspecialchars($now->iso8601())."</updated>\n";
		if ($this->editor!="") {
			$feed.= "    <author>\n";
			$feed.= "        <name>".$this->editor."</name>\n";
			if ($this->editorEmail!="") {
				$feed.= "        <email>".$this->editorEmail."</email>\n";
			}
			$feed.= "    </author>\n";
		}
		if ($this->category!="") {


					$feed.= "    <category term=\"" . htmlspecialchars($this->category) . "\" />\n";
		}
		if ($this->copyright!="") {
					$feed.= "    <rights>".FeedCreator::iTrunc(htmlspecialchars($this->copyright),100)."</rights>\n";
		}
		$feed.= "    <generator>".$this->version()."</generator>\n";


		$feed.= "    <link rel=\"self\" type=\"application/atom+xml\" href=\"". htmlspecialchars($this->syndicationURL). "\" />\n";
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <entry>\n";
			$feed.= "        <title>".htmlspecialchars(strip_tags($this->items[$i]->title))."</title>\n";
			$feed.= "        <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->items[$i]->link)."\"/>\n";
			if ($this->items[$i]->date=="") {
				$this->items[$i]->date = time();
			}
			$itemDate = new FeedDate($this->items[$i]->date);
			$feed.= "        <published>".htmlspecialchars($itemDate->iso8601())."</published>\n";
			$feed.= "        <updated>".htmlspecialchars($itemDate->iso8601())."</updated>\n";


			$tempguid = $this->items[$i]->link;
			if ($this->items[$i]->guid!="") {
				$tempguid = $this->items[$i]->guid;
			}

			$feed.= "        <id>". htmlspecialchars($tempguid)."</id>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			if ($this->items[$i]->author!="") {
				$feed.= "        <author>\n";
				$feed.= "            <name>".htmlspecialchars($this->items[$i]->author)."</name>\n";
				if ($this->items[$i]->authorEmail!="") {
				$feed.= "            <email>".htmlspecialchars($this->items[$i]->authorEmail)."</email>\n";
				}

				if ($this->items[$i]->authorURL!="") {
								$feed.= "            <uri>".htmlspecialchars($this->items[$i]->authorURL)."</uri>\n";
				}

				$feed.= "        </author>\n";
			}

			if ($this->items[$i]->category!="") {
				$feed.= "        <category ";

				if ($this->items[$i]->categoryScheme!="") {
				   $feed.=" scheme=\"".htmlspecialchars($this->items[$i]->categoryScheme)."\" ";
				}

				$feed.=" term=\"" . htmlspecialchars($this->items[$i]->category) . "\" />\n";
			}

			if ($this->items[$i]->description!="") {


			/*
			 * ATOM should have at least summary tag, however this implementation may be inaccurate
			 */
			 	$tempdesc = $this->items[$i]->getDescription();
			 	$temptype="";


				if ($this->items[$i]->descriptionHtmlSyndicated){
					$temptype=" type=\"html\"";
					$tempdesc = $this->items[$i]->getDescription();

				}

				if (empty($this->items[$i]->descriptionTruncSize)) {
					$feed.= "        <content". $temptype . ">". $tempdesc ."</content>\n";
				}


				$feed.= "        <summary". $temptype . ">". $tempdesc ."</summary>\n";
			} else {

				$feed.= "	 <summary>no summary</summary>\n";

			}

			if ($this->items[$i]->enclosure != NULL) {
				$feed.="        <link rel=\"enclosure\" href=\"". $this->items[$i]->enclosure->url ."\" type=\"". $this->items[$i]->enclosure->type."\"  length=\"". $this->items[$i]->enclosure->length ."\"";

				if ($this->items[$i]->enclosure->language != ""){
					 $feed .=" xml:lang=\"". $this->items[$i]->enclosure->language . "\" ";
				}

				if ($this->items[$i]->enclosure->title != ""){
					 $feed .=" title=\"". $this->items[$i]->enclosure->title . "\" ";
				}

				$feed .=" /> \n";



			}
			$feed.= "    </entry>\n";
		}
		$feed.= "</feed>\n";
		return $feed;
	}


}

/**
 * @package rss
 * AtomCreator03 is a FeedCreator that implements the atom specification,
 * as in http://www.intertwingly.net/wiki/pie/FrontPage.
 * Please note that just by using AtomCreator03 you won't automatically
 * produce valid atom files. For example, you have to specify either an editor
 * for the feed or an author for every single feed item.
 *
 * Some elements have not been implemented yet. These are (incomplete list):
 * author URL, item author's email and URL, item contents, alternate links,
 * other link content types than text/html. Some of them may be created with
 * AtomCreator03::additionalElements.
 *
 * @see FeedCreator#additionalElements
 * @since 1.6
 * @author Kai Blankenhorn <kaib@bitfolge.de>, Scott Reynen <scott@randomchaos.com>
 */
class AtomCreator03 extends FeedCreator {

	public function AtomCreator03() {
		$this->contentType = "application/atom+xml";
		$this->encoding = "utf-8";
	}

	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed version=\"0.3\" xmlns=\"http://purl.org/atom/ns#\"";
		if ($this->language!="") {
			$feed.= " xml:lang=\"".$this->language."\"";
		}
		$feed .= ">\n";
		$feed .= "    <title>" . htmlspecialchars( $this->title ) . "</title>\n";
		$feed .= "    <tagline>" . htmlspecialchars( $this->description ) . "</tagline>\n";
		$feed .= "    <link rel=\"alternate\" type=\"text/html\" href=\"" . htmlspecialchars( $this->link ) . "\"/>\n";
		$feed .= "    <id>" . htmlspecialchars( $this->link ) . "</id>\n";
		$now = new FeedDate();
		$feed .= "    <modified>" . htmlspecialchars( $now->iso8601() ) . "</modified>\n";
		if ($this->editor != "") {
			$feed .= "    <author>\n";
			$feed .= "        <name>" . $this->editor . "</name>\n";
			if ($this->editorEmail != "") {
				$feed .= "        <email>" . $this->editorEmail . "</email>\n";
			}
			$feed .= "    </author>\n";
		}
		$feed.= "    <generator>".$this->version()."</generator>\n";
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <entry>\n";
			$feed.= "        <title>".htmlspecialchars(strip_tags($this->items[$i]->title))."</title>\n";
			$feed.= "        <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->items[$i]->link)."\"/>\n";
			if ($this->items[$i]->date=="") {
				$this->items[$i]->date = time();
			}
			$itemDate = new FeedDate($this->items[$i]->date);
			$feed.= "        <created>".htmlspecialchars($itemDate->iso8601())."</created>\n";
			$feed.= "        <issued>".htmlspecialchars($itemDate->iso8601())."</issued>\n";
			$feed.= "        <modified>".htmlspecialchars($itemDate->iso8601())."</modified>\n";
			$feed.= "        <id>".htmlspecialchars($this->items[$i]->link)."</id>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			if ($this->items[$i]->author!="") {
				$feed.= "        <author>\n";
				$feed.= "            <name>".htmlspecialchars($this->items[$i]->author)."</name>\n";
				$feed.= "        </author>\n";
			}
			if ($this->items[$i]->description!="") {
				$feed.= "        <summary>".htmlspecialchars( strip_tags($this->items[$i]->description) )."</summary>\n";

			}
			$feed .= "    </entry>\n";
		}
		$feed .= "</feed>\n";
		return $feed;
	}

}

/**
 * @package rss
 * MBOXCreator is a FeedCreator that implements the mbox format
 * as described in http://www.qmail.org/man/man5/mbox.html
 *
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class MBOXCreator extends FeedCreator {

	public function MBOXCreator() {
		$this->contentType = "text/plain";
		$this->encoding = "ISO-8859-15";
	}

	public function qp_enc($input = "", $line_max = 76) {
		$hex = [ '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F' ];
		$lines = preg_split("/(?:\r\n|\r|\n)/", $input);
		$eol = "\r\n";
		$escape = "=";
		$output = "";
		foreach ( $lines as $line ) {
			//$line = rtrim($line); // remove trailing white space -> no =20\r\n necessary
			$linlen = strlen( $line );
			$newline = "";
			for ( $i = 0; $i < $linlen; $i++ ) {
				$c = substr( $line, $i, 1 );
				$dec = ord( $c );
				if (( $dec == 32 ) && ( $i == ( $linlen - 1 ) )) { // convert space at eol only
					$c = "=20";
				} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) { // always encode "\t", which is *not* required
					$h2 = floor( $dec / 16 );
					$h1 = floor( $dec % 16 );
					$c = $escape . $hex["$h2"] . $hex["$h1"];
				}
				if (( strlen( $newline ) + strlen( $c ) ) >= $line_max) { // CRLF is not counted
					$output .= $newline . $escape . $eol; // soft line break; " =\r\n" is okay
					$newline = "";
				}
				$newline .= $c;
			} // end of for
			$output .= $newline . $eol;
		}
		return trim( $output );
	}

	/**
	 * Builds the MBOX contents.
	 * @return    string    the feed's complete text
	 */
	public function createFeed() {
		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$from = ( $this->items[$i]->author != "" ) ? $this->items[$i]->author : $this->title;
			$itemDate = new FeedDate( $this->items[$i]->date );
			$feed = "From " . strtr( MBOXCreator::qp_enc( $from ), " ", "_" ) . " " . date( "D M d H:i:s Y", $itemDate->unix() ) . "\n";
			$feed .= "Content-Type: text/plain;\n";
			$feed .= "	charset=\"" . $this->encoding . "\"\n";
			$feed .= "Content-Transfer-Encoding: quoted-printable\n";
			$feed .= "Content-Type: text/plain\n";
			$feed .= "From: \"" . MBOXCreator::qp_enc( $from ) . "\"\n";
			$feed .= "Date: " . $itemDate->rfc822() . "\n";
			$feed .= "Subject: " . MBOXCreator::qp_enc( FeedCreator::iTrunc( $this->items[$i]->title, 100 ) ) . "\n";
			$feed .= "\n";
			$body = chunk_split( MBOXCreator::qp_enc( $this->items[$i]->description ) );
			$feed .= preg_replace( "~\nFrom ([^\n]*)(\n?)~", "\n>From $1$2\n", $body );
			$feed .= "\n";
			$feed .= "\n";
		}
		return $feed;
	}

	/**
	 * Generate a filename for the feed cache file. Overridden from FeedCreator to prevent XML data types.
	 * @return string the feed cache filename
	 * @since 1.4
	 */
	public function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".mbox";
	}

}

/**
 * @package rss
 * OPMLCreator is a FeedCreator that implements OPML 1.0.
 *
 * @see http://opml.scripting.com/spec
 * @author Dirk Clemens, Kai Blankenhorn
 * @since 1.5
 */
class OPMLCreator extends FeedCreator {

	public function OPMLCreator() {
		$this->encoding = "utf-8";
	}

	public function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<opml xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" version=\"1.0\">\n";
		$feed.= "    <head>\n";
		$feed.= "        <title>".htmlspecialchars($this->title)."</title>\n";
		if ($this->pubDate!="") {
			$date = new FeedDate($this->pubDate);
			$feed.= "         <dateCreated>".$date->rfc822()."</dateCreated>\n";
		}
		if ($this->lastBuildDate != "") {
			$date = new FeedDate( $this->lastBuildDate );
			$feed .= "         <dateModified>" . $date->rfc822() . "</dateModified>\n";
		}
		if ($this->editor != "") {
			$feed .= "         <ownerName>" . $this->editor . "</ownerName>\n";
		}
		if ($this->editorEmail != "") {
			$feed .= "         <ownerEmail>" . $this->editorEmail . "</ownerEmail>\n";
		}
		$feed .= "    </head>\n";
		$feed .= "    <body>\n";
		for ( $i = 0; $i < count( $this->items ); $i++ ) {
			$feed .= "    <outline type=\"rss\" ";
			$title = htmlspecialchars( strip_tags( strtr( $this->items[$i]->title, "\n\r", "  " ) ) );
			$feed .= " title=\"" . $title . "\"";
			$feed .= " text=\"" . $title . "\"";
			//$feed.= " description=\"".htmlspecialchars($this->items[$i]->description)."\"";
			$feed .= " url=\"" . htmlspecialchars( $this->items[$i]->link ) . "\"";

			if ($this->items[$i]->syndicationURL !="") {
				$feed.= " xmlUrl=\"" . $this->items[$i]->syndicationURL . "\"";
			}

			$feed .= "/>\n";
		}
		$feed .= "    </body>\n";
		$feed .= "</opml>\n";
		return $feed;
	}

}