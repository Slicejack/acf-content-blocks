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

		$screen = get_current_screen();

		return ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type );
	}

	/**
	 * Returns the array of registered content blocks.
	 *
	 * @return array
	 */
	public static function get_acf_content_blocks() {
		$content_blocks = array();

		$field_groups = new \WP_Query( array(
			'post_type'      => 'acf-field-group',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order title',
			'order'          => 'asc',
			'meta_query'     => array( // WPCS: slow query ok.
				array(
					'key'   => 'content_block',
					'value' => 1,
					'type'  => 'NUMERIC',
				),
			),
			'post_status'    => 'any',
		) );

		if ( $field_groups->have_posts() ) {
			while ( $field_groups->have_posts() ) {
				$field_groups->the_post();

				$field_group_id = get_post_field( 'post_name' );
				$field_group_hash = str_replace( 'group_', '', $field_group_id );

				$content_blocks[] = (object) array(
					'id'    => $field_group_id,
					'hash'  => $field_group_hash,
					'title' => apply_filters( 'acb_content_block_title', get_the_title() ),
					'name'  => strtolower( str_replace( ' ', '_', get_the_title() ) ),
				);
			}
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
		$classes = array( 'notice' );
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

}
