<?php
/**
 * ACF Content Blocks API
 *
 * @package ACF_Content_Blocks
 */

/**
 * This function will instantiate a global variable containing the rows
 * of a content blocks field, after which, it will determine if another
 * row exists to loop through.
 *
 * @uses \ACF_Content_Blocks\API::have_content_blocks
 *
 * @param  string               $prefix ACF field group prefix.
 * @param  WP_Post|integer|null $post   The post of which the value is saved against.
 * @return boolean
 */
function have_content_blocks( $prefix = '', $post = null ) {
	return ACF_Content_Blocks\API::have_content_blocks( $prefix, $post );
}

/**
 * Alias of ACF the_row function.
 *
 * @uses \ACF_Content_Blocks\API::the_content_block
 *
 * @param  boolean $format_values Whether or not to format values.
 * @return array Current block data.
 */
function the_content_block( $format_values = false ) {
	return ACF_Content_Blocks\API::the_content_block( $format_values );
}

/**
 * Checks if current row in ACF loop is content block.
 *
 * @return boolean
 */
function is_content_block() {
	return ACF_Content_Blocks\API::is_content_block();
}

/**
 * Returns content block field value
 *
 * @uses \ACF_Content_Blocks\API::get_content_block_field
 *
 * @param  string  $selector     The field name or key.
 * @param  boolean $format_value Whether or not to format the value.
 * @return mixed
 */
function get_content_block_field( $selector, $format_value = true ) {
	return ACF_Content_Blocks\API::get_content_block_field( $selector, $format_value );
}

/**
 * Displays content block field value
 *
 * @uses \ACF_Content_Blocks\API::the_content_block_field
 *
 * @param  string  $selector      The field name or key.
 * @param  boolean $format_value  Whether or not to format the value.
 * @return void
 */
function the_content_block_field( $selector, $format_value = true ) {
	ACF_Content_Blocks\API::the_content_block_field( $selector, $format_value );
}

/**
 * Alias for ACF get_row_layout function
 *
 * @uses \ACF_Content_Blocks\API::get_content_block_name
 *
 * @param  string $context  Context.
 * @return string
 */
function get_content_block_name( $context = 'template' ) {
	return ACF_Content_Blocks\API::get_content_block_name( $context );
}

/**
 * Renders content blocks.
 *
 * @param  string $prefix  ACF field group prefix.
 * @return void
 */
function the_content_blocks( $prefix = '' ) {
	if ( have_content_blocks( $prefix ) ) {
		while ( have_content_blocks( $prefix ) ) {
			the_content_block();

			$content_block_name = get_content_block_name();

			get_template_part(
				apply_filters(
					'acb_content_block_get_template_part',
					'blocks/' . $content_block_name,
					$content_block_name,
					$prefix
				)
			);
		}
	}
}
