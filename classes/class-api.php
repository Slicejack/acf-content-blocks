<?php
/**
 * API class
 *
 * @package ACF_Content_Blocks
 */

namespace ACF_Content_Blocks;

/**
 * API class
 */
class API {

	/**
	 * This function will instantiate a global variable containing the rows
	 * of the content blocks field, after which, it will determine if another
	 * row exists to loop through.
	 *
	 * @param  string                $prefix  ACF field group prefix.
	 * @param  \WP_Post|integer|null $post    The post of which the value is saved against.
	 * @return boolean
	 */
	public static function have_content_blocks( $prefix = '', $post = null ) {
		if ( null === $post ) {
			global $post;
		} else {
			$post = get_post( $post ); // WPCS: override ok.
		}

		if ( empty( $post ) ) {
			return false;
		}

		if ( $prefix && substr( $prefix, -1 ) !== '_' ) {
			$prefix .= '_';
		}

		return have_rows( $prefix . 'acb_content_blocks', $post->ID );
	}

	/**
	 * Alias of ACF the_row function.
	 *
	 * @param  boolean $format_values  Whether or not to format values.
	 * @return array  Current block data.
	 */
	public static function the_content_block( $format_values = false ) {
		return the_row( $format_values );
	}

	/**
	 * Returns content block field value
	 *
	 * @param  string  $selector      The field name or key.
	 * @param  boolean $format_value  Whether or not to format the value.
	 * @return mixed
	 */
	public static function get_content_block_field( $selector, $format_value = true ) {
		$use_preset = get_sub_field( 'acb_use_preset' );

		if ( empty( $use_preset ) ) {
			$content_block = get_sub_field( 'acb_content_block', $format_value );
			return isset( $content_block[ $selector ] ) ? $content_block[ $selector ] : false;
		}

		$preset_id = get_sub_field( 'acb_preset' );

		return get_field( 'acb_content_blocks_0_' . $selector, $preset_id, $format_value );
	}

	/**
	 * Displays content block field value
	 *
	 * @param  string  $selector      The field name or key.
	 * @param  boolean $format_value  Whether or not to format the value.
	 * @return void
	 */
	public static function the_content_block_field( $selector, $format_value = true ) {
		$value = self::get_content_block_field( $selector, $format_value );

		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		echo $value; // WPCS: xss ok.
	}

	/**
	 * Alias for ACF get_row_layout function
	 *
	 * @param  string $context  Context.
	 * @return string
	 */
	public static function get_content_block_name( $context = 'template' ) {
		$name = get_row_layout();

		if ( 'template' === $context ) {
			$name = str_replace( '_', '-', $name );
		}

		return $name;
	}

}