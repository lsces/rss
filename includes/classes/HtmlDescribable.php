<?php
namespace Bitweaver\Rss;

/**
 * @package rss
 * An HtmlDescribable is an item within a feed that can have a description that may
 * include HTML markup.
 */
class HtmlDescribable {
	/**
	 * Indicates whether the description field should be rendered in HTML.
	 */
	public $descriptionHtmlSyndicated;

	/**
	 * Indicates whether and to how many characters a description should be truncated.
	 */
	public $descriptionTruncSize;
	public $description;

	/**
	 * Returns a formatted description field, depending on descriptionHtmlSyndicated and
	 * $descriptionTruncSize properties
	 * @return    string    the formatted description
	 */
	public function getDescription() {
		$descriptionField = new FeedHtmlField( $this->description );
		$descriptionField->syndicateHtml = $this->descriptionHtmlSyndicated;
		$descriptionField->truncSize = $this->descriptionTruncSize;
		return $descriptionField->output();
	}

}
