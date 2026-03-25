<?php
/**
 * @package rss
 * @subpackage modules
 */

/**
 * required setup
 */
use Bitweaver\RSS\RSSLib;
global $rsslib;
$rsslib = new RSSLib;

extract( $moduleParams );

$listHash = [];
$listHash['id'] = $module_params['id'];
$listHash['cache_time'] = !empty($cache_time)?$cache_time:1;

	
if ( $items = $rsslib->parse_feeds( $listHash ) ){

	$_template->assign( 'modRSSItems', $items );	

	//if we want short descriptions get them
	$shortdescs = [];	
	if ( !empty($module_params['desc_length']) && is_numeric($module_params['desc_length']) && !empty($items)){
		$shortdescs = $rsslib->get_short_descs( $items, $module_params['desc_length'] );
	}

	$_template->assign( 'short_desc', $shortdescs );	
	
	//if desc is set and no desc_length is given then we present the full description/content of each item
	$hideDesc = TRUE;
	if (!empty($module_params['desc']) && empty($module_params['desc_length']) ){
		$hideDesc = FALSE;
	}
	
	$_template->assign( 'hideDesc', $hideDesc );
	
	$max = !empty( $module_params['max'] ) ? $module_params['max'] : 10;
	$_template->assign( 'max', $max );
}
