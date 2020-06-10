<?php
/**
 * Main plugin class
 *
 * @package ACF_Content_Blocks
 */

namespace ACF_Content_Blocks;

use Block_Details_List;
use Blocks_List;

class Blocks_Locator {

	/*
	* Render menu page for blocks list or for block detail.
	*/
	public static function render_acb_locator_menu_page() {
		
		if( Utils::query_param_is_set( 'block' ) ) {
			self::render_acb_locator_block_detail_page();	
		} else {
			self::render_acb_locator_blocks_list_page();
		}
	}

	/*
	* Render Content blocks listing.
	*/
	private static function render_acb_locator_blocks_list_page() {
		$blocks = new Blocks_List();
		
		echo '<h2>Content Blocks</h2>';
		$blocks->prepare_items();
		$blocks->display();	
	}

	/*
	* Render Block details page with list of pages where conetent block is used.
	*/
	private static function render_acb_locator_block_detail_page() {
		$details = new Block_Details_List();

		echo '<h2>Pages that use block</h2>';
		$details->prepare_items();
		$details->display();
	}
}