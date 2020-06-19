<?php
/**
 * Blocks Locator Page class
 *
 * @package ACF_Content_Blocks
 */

namespace ACF_Content_Blocks;

use Block_Details_List;
use Blocks_List;

/**
 * Blocks Locator class
 *
 * @package ACF_Content_Blocks
 */
class Blocks_Locator_Page {

	/**
	 * Render menu page for blocks list or for block detail.
	 */
	public static function render_acb_locator_menu_page() {
		if ( Utils::query_param_is_set( 'block' ) ) {
			self::render_acb_locator_block_detail_page();
		} else {
			self::render_acb_locator_blocks_list_page();
		}
	}

	/**
	 * Render Content blocks listing.
	 */
	private static function render_acb_locator_blocks_list_page() {
		$blocks = new Blocks_List();
		?>	
		<div class="wrap">
			<h2><?php echo esc_html__( 'Content Blocks', 'acf-content-blocks' ); ?></h2>
			<?php
				$blocks->prepare_items();
				$blocks->display();
			?>
		</div>
		<?php
	}

	/**
	 * Render Block details page with list of pages where content block is used.
	 */
	private static function render_acb_locator_block_detail_page() {
		$details = new Block_Details_List();
		$details->prepare_items();
		$block_name = Utils::get_content_block_title( $details->block_name );
		?>
		<div class="wrap">
			<h2><a href="<?php menu_page_url( 'content-blocks-locator' ); ?>"><?php echo esc_html__( 'Content Blocks', 'acf-content-blocks' ); ?></a> > <?php echo esc_html( $block_name ); ?></h2>
			<?php
				$details->display();
			?>
		</div>
		<?php
	}
}
