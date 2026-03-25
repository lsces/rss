<?php
namespace Bitweaver\Rss;
use Bitweaver\BitBase;
use Bitweaver\KernelTools;
/**
 * @version $Header$
 * @package rss
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * $Id$
 */

/**
 * @package rss
 */
class RSSLib extends BitBase {

	protected $flag;
	protected $buffer;

	public function list_rss_modules($offset, $max_records, $sort_mode, $find) {

		if ($find) {
			$findesc= "%$find%";
			$mid = " where (`name` like ? or `description` like ?)";
			$bindvars= [ $findesc, $findesc ];
		} else {
			$mid = "";
			$bindvars= [];
		}

		$query = "select * from `".BIT_DB_PREFIX."rss_modules` $mid order by ".$this->mDb->convertSortmode($sort_mode);
		$query_cant = "select count(*) from `".BIT_DB_PREFIX."rss_modules` $mid";
		$result = $this->mDb->query($query,$bindvars,$max_records,$offset);
		$cant = $this->mDb->getOne($query_cant,$bindvars);
		$ret = [];

		while ($res = $result->fetchRow()) {
			$res["minutes"] = $res["refresh"] / 60;

			$ret[] = $res;
		}

		$retval = [];
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

	public function replace_rss_module($rss_id, $name, $description, $url, $refresh, $show_title, $show_pub_date) {
		$ret = FALSE;
		if( is_numeric( $rss_id ) ) {
			//if($this->rss_module_name_exists($name)) return false; // TODO: Check the name
			$refresh = 60 * $refresh;
	
			if ($rss_id) {
				$query = "update `".BIT_DB_PREFIX."rss_modules` set `name`=?,`description`=?,`refresh`=?,`url`=?,`show_title`=?,`show_pub_date`=? where `rss_id`=?";
				$bindvars= [ $name, $description, $refresh, $url, $show_title, $show_pub_date, $rss_id ];
			} else {
				// was: replace into, no clue why.
				$query = "insert into `".BIT_DB_PREFIX."rss_modules`(`name`,`description`,`url`,`refresh`,`content`,`last_updated`,`show_title`,`show_pub_date`)
					values(?,?,?,?,?,?,?,?)";
				$bindvars= [ $name, $description, $url, $refresh, '', 1000000, $show_title, $show_pub_date ];
			}
	
			$result = $this->mDb->query($query,$bindvars);
			$ret = true;
		}
		return $ret;
	}

	public function remove_rss_module($rss_id) {
		$ret = FALSE;
		if( is_numeric( $rss_id ) ) {
			$query = "delete from `".BIT_DB_PREFIX."rss_modules` where `rss_id`=?";
	
			$result = $this->mDb->query($query,array($rss_id));
			$ret = true;
		}
		return $ret;
	}

	public function get_rss_module($rss_id) {
		$ret = FALSE;
		if( is_numeric( $rss_id ) ) {
			$query = "select * from `".BIT_DB_PREFIX."rss_modules` where `rss_id`=?";
	
			$result = $this->mDb->query($query,array($rss_id));
	
			if (!$result->numRows())
				return false;
	
			$ret = $result->fetchRow();
		}
		return $ret;
	}

	public function startElementHandler($parser, $name, $attribs) {
		if ($this->flag) {
			$this->buffer .= '<' . $name . '>';
		}

		if ($name == 'item' || $name == 'items') {
			$this->flag = 1;
		}
	}

	public function endElementHandler($parser, $name) {
		if ($name == 'item' || $name == 'items') {
			$this->flag = 0;
		}

		if ($this->flag) {
			$this->buffer .= '</' . $name . '>';
		}
	}

	public function characterDataHandler($parser, $data) {
		if ($this->flag) {
			$this->buffer .= $data;
		}
	}

	public function refresh_rss_module($rss_id) {
		$info = $this->get_rss_module($rss_id);

		if ($info) {
			global $gBitSystem;
			$data = $this->rss_iconv( KernelTools::bit_http_request($info['url']));
			$now = $gBitSystem->getUTCTime();
			$query = "update `".BIT_DB_PREFIX."rss_modules` set `content`=?, `last_updated`=? where `rss_id`=?";
			$result = $this->mDb->query($query,array((string)$data,(int) $now, (int) $rss_id));
			return $data;
		} else {
			return false;
		}
	}

	public function rss_module_name_exists($name) {
		$query = "select `name` from `".BIT_DB_PREFIX."rss_modules` where `name`=?";

		$result = $this->mDb->query($query,array($name));
		return $result->numRows();
	}

	public function get_rss_module_id($name) {
		$query = "select `rss_id` from `".BIT_DB_PREFIX."rss_modules` where `name`=?";

		$id = $this->mDb->getOne($query,array($name));
		return $id;
	}

	public function get_rss_show_title($rss_id) {
		$ret = FALSE;
		if( is_numeric( $rss_id ) ) {
			$query = "select `show_title` from `".BIT_DB_PREFIX."rss_modules` where `rss_id`=?";
	
			$ret = $this->mDb->getOne($query,array($rss_id));
		}
		return $ret;
	}

	public function get_rss_show_pub_date($rss_id) {
		$ret = FALSE;
		if( is_numeric( $rss_id ) ) {
			$query = "select `show_pub_date` from `".BIT_DB_PREFIX."rss_modules` where `rss_id`=?";
	
			$show_pub_date = $this->mDb->getOne($query,array($rss_id));
			$ret = $show_pub_date;
		}
		return $ret;
	}

	public function rss_iconv($xmlstr, $tencod = "UTF-8") {
		if (preg_match("/<\?xml.*encoding=\"(.*)\".*\?>/", $xmlstr, $xml_head)) {
			$sencod = strtoupper($xml_head[1]);

			switch ($sencod) {
			case "ISO-8859-1":
				// Use utf8_encode a more standard function
				$xmlstr = mb_convert_encoding($xmlstr, "UTF-8", mb_detect_encoding($xmlstr));

				break;

			case "UTF-8":
			case "US-ASCII":
				// UTF-8 and US-ASCII don't need convertion
				break;

			default:
				// Not supported encoding, we must use iconv() or recode()
				if (function_exists('iconv')) {
					// We have iconv use it
					$new_xmlstr = @iconv($sencod, $tencod, $xmlstr);

					if ($new_xmlstr === FALSE) {
						// in_encod -> out_encod not supported, may be misspelled encoding
						$sencod = strtr($sencod, array(
							"-" => "",
							"_" => "",
							" " => ""
						));

						$new_xmlstr = @iconv($sencod, $tencod, $xmlstr);

						if ($new_xmlstr === FALSE) {
							// in_encod -> out_encod not supported, leave it
							$tencod = $sencod;

							break;
						}
					}

					$xmlstr = $new_xmlstr;
					// Fix an iconv bug, a few garbage chars beyound xml...
					$xmlstr = preg_replace("/(.*<\/rdf:RDF>).*/s", "\$1", $xmlstr);
				} elseif (function_exists('recode_string')) {
					// I don't have recode support could somebody test it?
					$xmlstr = @recode_string("$sencod..$tencod", $xmlstr);
				} else {
				// This PHP intallation don't have any EncodConvFunc...
				// somebody could create bit_iconv(...)?
				}
			}

			// Replace header, put the new encoding
			$xmlstr = preg_replace("/(<\?xml.*)encoding=\".*\"(.*\?>)/", "\$1 encoding=\"$tencod\"\$2", $xmlstr);
		}

		return $xmlstr;
	}
	
	public function get_short_desc( $text ){
		// first we can remove unwanted stuff like images and lists or whatever - this is rough
		$pattern = array(
			"!<img[^>]*>!is",
			//"!<ul.*?</ul>!is",
		);
		$text = preg_replace( $pattern, "", $text );
		
		$text = substr($text, 0, 1000);		
		
		// now we strip remaining tags and xs whitespace
		$text = trim( preg_replace( "!\s+!s", " ", strip_tags( $text )));
		
		// finally we try to extract sentences as well as we can
		// to add more characters to split sentences by add them after the last \? - you might want to add : or ;
		preg_match_all( "#([\.!\?\s\)]*)(.*?[a-zA-Z][2]\s*[\.\!\?]+\)?)#s", $text, $matches );
		
		return $matches[2];
	}
	
	public function get_short_descs( $items, $length=1 ){
		$shortdescs = Array();
		
		if ( !empty($items) ){
			foreach ($items as $item){
				//we try to trim each story to given number of sentences
				$sentences = $this->get_short_desc( $item->get_description() );
				
				$shortdesc = NULL;
				for ($n = 0; $n < $length; $n++){
					$space = ($n > 0)?" ":NULL;
					$shortdesc .= $space;
					$shortdesc .= ( !empty( $sentences[$n] ) && $sentences[$n] != NULL ) ? $sentences[$n] : NULL;
				}
				
				$shortdescs[] = $shortdesc;
			}
		}
		
		return $shortdescs;
	}

	public function parse_feeds( $pParamHash ){
		//set path to rss feed cache
		$cache_path = TEMP_PKG_PATH.'rss/simplepie';
		
		//we do this earlier instead of later because if we can't cache the source we shouldn't be pulling the rss feed.
		if( !is_dir( $cache_path ) && !KernelTools::mkdir_p( $cache_path ) ) {
			\Bitweaver\bit_error_log( 'Can not create the cache directory: '.$cache_path );
			
			return FALSE;
		}else{
			//load up parser SimplePie
			require_once( UTIL_PKG_INCLUDE_PATH.'simplepie/simplepie.php' );

			$ids = ( !is_array( $pParamHash['id'] ) ) ? explode( ",", $pParamHash['id'] ) : $pParamHash['id'];
			
			$urls = [];
			
			foreach ($ids as $id){
				if( @BitBase::verifyId( $id ) ) {
					$feedHash = $this->get_rss_module( $id );
					$urls[] = $feedHash['url'];
				}else{
					//todo assign this as an error
					//$repl = '<b>rss can not be found, id must be a number</b>';
				}
			}
			$feed = new SimplePie();
			 
			//Instead of only passing in one feed url, we'll pass in an array of multiple feeds
			$feed->set_feed_url( $urls );
			
			$feed->set_cache_location( $cache_path );
			
			//set cache time
			$cache_time = !empty($pParamHash['cache_time'])?$pParamHash['cache_time']:1;
			$feed->set_cache_duration( $cache_time );
			
			//not sure - we may want to eventually use this
			//$feed->set_stupidly_fast(TRUE);
			 
			// Initialize the feed object
			$feed->init();
			 
			// This will work if all of the feeds accept the same settings.
			$feed->handle_content_type();
			
			$items = $feed->get_items();
			
			return $items;
		}
	}	
}
