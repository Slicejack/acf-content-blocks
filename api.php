<?php
/**
 * ACF Content Blocks API
 *
 * @package ACF_Content_Blocks
 */

if ( ! class_exists( 'ACF_Content_Blocks' ) ) {
	require_once trailingslashit( __DIR__ ) . 'classes/class-acf-content-blocks.php';
}

/**
 * This function will instantiate a global variable containing the rows
 * of a content blocks field, after which, it will determine if another
 * row exists to loop through.
 *
 * @uses ACF_Content_Blocks::have_content_blocks
 *
 * @param WP_Post|integer|null $post The post of which the value is saved against.
 * @return boolean
 */
function have_content_blocks( $post = null ) {
	return ACF_Content_Blocks::have_content_blocks( $post );
}

/**
 * Alias of ACF the_row function.
 *
 * @uses ACF_Content_Blocks::the_content_block
 *
 * @param boolean $format_values Whether or not to format values.
 * @return array Current block data.
 */
function the_content_block( $format_values = false ) {
	return ACF_Content_Blocks::the_content_block( $format_values );
}

/**
 * Returns content block field value
 *
 * @uses ACF_Content_Blocks::get_content_block_field
 *
 * @param string  $selector     The field name or key.
 * @param boolean $format_value Whether or not to format the value.
 * @return mixed
 */
function get_content_block_field( $selector, $format_value = true ) {
	return ACF_Content_Blocks::get_content_block_field( $selector, $format_value );
}

/**
 * Displays content block field value
 *
 * @uses ACF_Content_Blocks::the_content_block_field
 *
 * @param string  $selector     The field name or key.
 * @param boolean $format_value Whether or not to format the value.
 */
function the_content_block_field( $selector, $format_value = true ) {
	ACF_Content_Blocks::the_content_block_field( $selector, $format_value );
}

/**
 * Alias for ACF get_row_layout function
 *
 * @param string $context Context.
 * @return string
 */
function get_content_block_name( $context = 'template' ) {
	return ACF_Content_Blocks::get_content_block_name( $context );
}
