<?php
namespace Bitweaver\Rss;

/**
 * @package rss
 * An FeedHtmlField describes and generates
 * a feed, item or image html field (probably a description). Output is
 * generated based on $truncSize, $syndicateHtml properties.
 * @author Pascal Van Hecke <feedcreator.class.php@vanhecke.info>
 * @version 1.6
 */
class FeedHtmlField {
	/**
	 * Mandatory attributes of a FeedHtmlField.
	 */
	public $rawFieldContent;

	/**
	 * Optional attributes of a FeedHtmlField.
	 *
	 */
	public $truncSize;
	public $syndicateHtml;

	/**
	 * Creates a new instance of FeedHtmlField.
	 * @param  $string: if given, sets the rawFieldContent property
	 */
	public function __construct($parFieldContent) {
		if ($parFieldContent) {
			$this->rawFieldContent = $parFieldContent;
		}
	}

	/**
	 * Creates the right output, depending on $truncSize, $syndicateHtml properties.
	 * @return string    the formatted field
	 */
	public function output() {
		// when field available and syndicated in html we assume
		// - valid html in $rawFieldContent and we enclose in CDATA tags
		// - no truncation (truncating risks producing invalid html)
		if (!$this->rawFieldContent) {
			$result = "";
		}	elseif ($this->syndicateHtml) {
			// Clean HTML before wrapping in CDATA
			$cleaned = $this->sanitizeHtml( $this->rawFieldContent );
			$result = "<![CDATA[$cleaned]]>";
		} else {
			$result = ( $this->truncSize and is_int( $this->truncSize ) ) ? FeedCreator::iTrunc( htmlspecialchars( $this->rawFieldContent ), $this->truncSize ) : htmlspecialchars( $this->rawFieldContent );
		}
		return $result;
	}

	/**
	* Sanitize HTML for RSS syndication - keep content, strip Bootstrap wrappers
	*/
	private function sanitizeHtml( $html ) {
		// Remove Bootstrap wrapper divs
		$html = preg_replace( '/<div[^>]*class="(container|row|col-[^"]*)"[^>]*>/', '', $html );
		$html = preg_replace( '/<\/div>/', '', $html );
		
		// Keep formatting, images, links; strip everything else
		return preg_replace(
			'/\s+/', 
			' ', 
			strip_tags( $html, '<p><br><img><strong><em><a><blockquote><ul><ol><li>' )
		);
	}
}
