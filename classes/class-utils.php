<?php
/**
 * Utils class
 *
 * @package ACF_Content_Blocks
 */

namespace ACF_Content_Blocks;

/**
 * Utilities class
 */
class Utils {

	/**
	 * Checks if the post type of the current admin screen is "acb_block_preset".
	 *
	 * @return boolean
	 */
	public static function is_block_preset_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = ( function_exists( 'get_current_screen' ) ) ? get_current_screen() : array();

		return ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type );
	}

	/**
	 * Returns the array of registered content blocks.
	 *
	 * @return array
	 */
	public static function get_acf_content_blocks() {
		$field_groups   = array();
		$content_blocks = array();

		$field_group_posts = get_posts(
			array(
				'post_type'      => 'acf-field-group',
				'posts_per_page' => 100,
				'orderby'        => 'menu_order title',
				'order'          => 'asc',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'   => 'content_block',
						'value' => 1,
						'type'  => 'NUMERIC',
					),
				),
				'post_status'    => 'any',
			)
		);

		foreach ( $field_group_posts as $field_group_post ) {
			$field_groups[] = acf_get_field_group( $field_group_post->ID );
		}

		$field_groups = array_filter(
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			apply_filters( 'acf/get_field_groups', $field_groups ),
			function( $field_group ) {
				return ( 1 === $field_group['content_block'] );
			}
		);

		foreach ( $field_groups as $field_group ) {
			$field_group_key  = $field_group['key'];
			$field_group_hash = str_replace( 'group_', '', $field_group_key );

			$content_blocks[] = (object) array(
				'key'   => $field_group_key,
				'hash'  => $field_group_hash,
				'title' => apply_filters( 'acb_content_block_title', $field_group['title'] ),
				'name'  => strtolower( str_replace( ' ', '_', $field_group['title'] ) ),
			);
		}

		return $content_blocks;
	}

	/**
	 * Renders the admin notice with a given message.
	 *
	 * @param  string  $message        Notice message.
	 * @param  string  $type           Notice type.
	 * @param  boolean $is_dismissible Set to true to apply closing icon.
	 * @return void
	 */
	public static function render_admin_notice( $message, $type = 'info', $is_dismissible = false ) {
		$classes       = array( 'notice' );
		$allowed_types = array( 'error', 'warning', 'success', 'info' );

		if ( in_array( $type, $allowed_types, true ) ) {
			$classes[] = "notice-${type}";
		}

		if ( true === $is_dismissible ) {
			$classes[] = 'is-dismissible';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns the url to a file within the ACF Content Blocks plugin directory.
	 *
	 * @param  string $path Path to the file.
	 * @return string
	 */
	public static function get_dir( $path ) {
		return str_replace( 'classes/', '', plugin_dir_url( __FILE__ ) . $path );
	}

	/**
	 * Checks if query parameter is set.
	 *
	 * @param  string $key Query parameter key.
	 * @return boolean
	 */

	public static function query_param_is_set( $key ) {
		return ( isset( $_GET[ $key ] )  && ! empty( $_GET[ $key ] ) );
	}

	/**
	 * Gets content block title by its name.
	 *
	 * @param  string $key Query parameter key.
	 * @return string
	 */
	public static function get_content_block_title( $name ) {
		$title = strtoupper( str_replace( '_', ' ', $name ) );
		$title = apply_filters( 'acb_content_block_title', $title );
		
		return $title;
	}

}
