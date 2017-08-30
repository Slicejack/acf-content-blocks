<?php
/*
Plugin Name: ACF Content Blocks
Plugin URI: https://github.com/Slicejack/acf-content-blocks
Description: ACF Content Blocks
Version: alpha-0.1
Author: Slicejack
Author URI: https://slicejack.com/
License: GNU General Public License v3.0
License URI: https://github.com/Slicejack/acf-content-blocks/blob/master/LICENSE
Text Domain: acf-content-blocks
Domain Path: /languages
*/

class slicejack_acf_content_blocks {

	private $field_groups;

	public function __construct() {

		add_action( 'init', array( $this, 'register_block_presets_cpt' ), 0 );

		add_action( 'acf/init', array( $this, 'initialize' ) );

		add_action( 'acf/input/admin_footer', array( $this, 'update_acf_admin_footer' ) );

	}

	public static function has_content_blocks( $location, $post_id = null ) {

		if ( is_numeric( $post_id ) ) {

			$post_id = ( $post_id === null )? get_the_ID() : $post_id ;

		}

		$container = get_field( $location, $post_id );

		if ( isset( $container[ 'content_blocks' ] ) && ! empty ( $container[ 'content_blocks' ] ) ) {

			return true;

		}

		return false;

	}

	public static function render_content_blocks( $location, $post_id = null ) {

		if ( is_numeric( $post_id ) ) {

			$post_id = ( $post_id === null )? get_the_ID() : $post_id ;

		}

		$container = get_field( $location, $post_id );

		if ( isset( $container[ 'content_blocks' ] ) && ! empty ( $container[ 'content_blocks' ] ) ) {

			foreach ( $container[ 'content_blocks' ] as $block_index => $block ) {

				$block_layout = str_replace( '_', '-', sanitize_title( $block[ 'acf_fc_layout' ] ) );

				$block_data = self::get_block_data( $block );

				if ( file_exists( locate_template( 'blocks/' . $block_layout . '.php' ) ) ) {

					include locate_template( 'blocks/' . $block_layout . '.php' );

				}

			}

		}

	}

	public static function get_block_data( $block ) {

		$data = array();

		$block_layout = $block[ 'acf_fc_layout' ];

		if ( isset( $block[ 'use_preset' ] ) && $block[ 'use_preset' ] == true ) {

			return self::get_block_preset_data( $block );

		}

		return ( isset( $block[ $block_layout ] ) )? $block[ $block_layout ] : $block ;

	}

	public static function get_block_preset_data( $block ) {

		$preset = $block[ 'preset' ];

		$block_layout = $block[ 'acf_fc_layout' ];

		if ( $preset instanceof WP_Post ) {

			$block = get_field( 'sjcb_' . $block_layout . '_block_preset_settings', $preset );

			if ( is_array( $block ) ) {

				unset( $block[ 'acf_fc_layout' ] );

				return $block;

			}

		}

		return array();

	}

	public function initialize() {

		$this->field_groups = $this->get_acf_content_blocks();

		$this->register_block_presets_component_field_group();

		$this->register_content_blocks_component_field_group();

		$this->load_custom_acf_fields();

	}

	public function update_acf_admin_footer() { ?>
		<script type="text/javascript">
			(function($) {
				acf.fields.sjcb_block_preset = acf.fields.select.extend({
					type: 'sjcb_block_preset'
				});
			})(jQuery);
		</script>
	<?php }

	public function register_block_presets_cpt() {

		$labels = array(
			'name' => _x( 'Block Preset', 'Post Type General Name', 'slicejack-acf-content-blocks' ),
			'singular_name' => _x( 'Block Preset', 'Post Type Singular Name', 'slicejack-acf-content-blocks' ),
			'menu_name' => __( 'Block Presets', 'slicejack-acf-content-blocks' ),
			'name_admin_bar' => __( 'Block Preset', 'slicejack-acf-content-blocks' ),
			'archives' => __( 'Block Presets', 'slicejack-acf-content-blocks' ),
			'attributes' => __( 'Block Preset Attributes', 'slicejack-acf-content-blocks' ),
			'parent_item_colon' => __( 'Parent Preset:', 'slicejack-acf-content-blocks' ),
			'all_items' => __( 'All Block Presets', 'slicejack-acf-content-blocks' ),
			'add_new_item' => __( 'Add New Block Preset', 'slicejack-acf-content-blocks' ),
			'add_new' => __( 'Add New', 'slicejack-acf-content-blocks' ),
			'new_item' => __( 'New Block Preset', 'slicejack-acf-content-blocks' ),
			'edit_item' => __( 'Edit Block Preset', 'slicejack-acf-content-blocks' ),
			'update_item' => __( 'Update Block Preset', 'slicejack-acf-content-blocks' ),
			'view_item' => __( 'View Block Preset', 'slicejack-acf-content-blocks' ),
			'view_items' => __( 'View Block Presets', 'slicejack-acf-content-blocks' ),
			'search_items' => __( 'Search Block Presets', 'slicejack-acf-content-blocks' ),
			'not_found' => __( 'Block Preset not found', 'slicejack-acf-content-blocks' ),
			'not_found_in_trash' => __( 'Block Preset not found in Trash', 'slicejack-acf-content-blocks' ),
			'featured_image' => __( 'Featured Image', 'slicejack-acf-content-blocks' ),
			'set_featured_image' => __( 'Set featured image', 'slicejack-acf-content-blocks' ),
			'remove_featured_image' => __( 'Remove featured image', 'slicejack-acf-content-blocks' ),
			'use_featured_image' => __( 'Use as featured image', 'slicejack-acf-content-blocks' ),
			'insert_into_item' => __( 'Insert into item', 'slicejack-acf-content-blocks' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'slicejack-acf-content-blocks' ),
			'items_list' => __( 'Items list', 'slicejack-acf-content-blocks' ),
			'items_list_navigation' => __( 'Items list navigation', 'slicejack-acf-content-blocks' ),
			'filter_items_list' => __( 'Filter items list', 'slicejack-acf-content-blocks' ),
		);

		$args = array(
			'label' => __( 'Block Preset', 'slicejack-acf-content-blocks' ),
			'description' => __( 'Slicejack ACF Block Preset CPT', 'slicejack-acf-content-blocks' ),
			'labels' => $labels,
			'supports' => array( 'title', ),
			'hierarchical' => false,
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_icon' => 'dashicons-screenoptions',
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'rewrite' => false,
			'capability_type' => 'page',
			'show_in_rest' => false,
		);

		register_post_type( 'sjcb_block_preset', $args );
	}

	private function register_block_presets_component_field_group() {

		acf_add_local_field_group(array(
			'key' => 'group_sjcb_block_preset_settings',
			'title' => 'Block Preset Settings',
			'fields' => $this->get_block_presets_component_fields_array(),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'sjcb_block_preset',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => 1,
			'description' => '',
		));
	}

	private function get_block_presets_component_fields_array() {

		$fields = array();

		$fields[] = array(
			'key' => 'field_sjcb_block_preset_type',
			'label' => 'Block Preset Type',
			'name' => 'sjcb_block_preset_type',
			'type' => 'select',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => $this->get_block_presets_component_choices_array(),
			'default_value' => array(
			),
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'ajax' => 0,
			'return_format' => 'value',
			'placeholder' => '',
		);

		foreach ( $this->field_groups as $field_group ) {
			$fields[] = array(
				'key' => 'field_sjcb_opt_' . $field_group->hash,
				'label' => $field_group->title . ' Block Preset Settings',
				'name' => 'sjcb_' . $field_group->name . '_block_preset_settings',
				'type' => 'clone',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_sjcb_block_preset_type',
							'operator' => '==',
							'value' => $field_group->name,
						),
					),
				),
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'clone' => array(
					0 => $field_group->id,
				),
				'display' => 'group',
				'layout' => 'block',
				'prefix_label' => 0,
				'prefix_name' => 1,
			);
		}

		return $fields;

	}

	private function get_block_presets_component_choices_array() {

		$choices = array();

		foreach ( $this->field_groups as $field_group ) {

			$choices[ $field_group->name ] = $field_group->title;

		}

		return $choices;

	}

	private function register_content_blocks_component_field_group() {

		acf_add_local_field_group( array(
			'key' => 'group_sjcb_content_blocks',
			'title' => '[COMPONENT] Content Blocks',
			'fields' => array(
				array(
					'key' => 'field_sjcb_content_blocks',
					'label' => 'Content Blocks',
					'name' => 'content_blocks',
					'type' => 'flexible_content',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'layouts' => $this->get_content_blocks_component_layouts_array(),
					'button_label' => 'Add Content Block',
					'min' => '',
					'max' => '',
				),
			),
			'location' => array(
				array(
					array(
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'post',
					),
				),
			),
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => 0,
			'description' => '',
		) );

	}

	private function get_content_blocks_component_layouts_array() {

		$layouts = array();

		foreach ( $this->field_groups as $field_group ) {

			$field_group_hash = str_replace( 'group_', '', $field_group->id );

			$layouts[ $field_group_hash ] = array(
				'key' => 'option_sjcb_' . $field_group_hash,
				'name' => $field_group->name,
				'label' => $field_group->title,
				'display' => 'block',
				'sub_fields' => array(
					array(
						'key' => 'field_sjcb_' . $field_group_hash . '_use_preset',
						'label' => 'Use Preset',
						'name' => 'use_preset',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui' => 1,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_sjcb_' . $field_group_hash . '_preset',
						'label' => 'Preset',
						'name' => 'preset',
						'type' => 'sjcb_block_preset',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_sjcb_' . $field_group_hash . '_use_preset',
									'operator' => '==',
									'value' => '1',
								),
							),
						),
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'content_block_type' => $field_group->name,
						'post_type' => array(
							0 => 'sjcb_block_preset'
						),
						'allow_null' => 1,
						'multiple' => 0,
						'ui' => 1,
					),
					array(
						'key' => 'field_sjcb_' . $field_group_hash . '_display_settings',
						'label' => $field_group->title . ' Block Display Settings',
						'name' => $field_group->name,
						'type' => 'clone',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => array(
							array(
								array(
									'field' => 'field_sjcb_' . $field_group_hash . '_use_preset',
									'operator' => '!=',
									'value' => '1',
								),
							),
						),
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'clone' => array(
							0 => $field_group->id
						),
						'display' => 'group',
						'layout' => 'block',
						'prefix_label' => 0,
						'prefix_name' => 1,
					),
				),
				'min' => '',
				'max' => '',
			);

		}

		return $layouts;

	}

	private function get_acf_content_blocks() {

		$content_blocks = array();

		$field_groups = get_posts( array(
			'post_type' => 'acf-field-group',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'asc',
			'suppress_filters' => false,
			'post_status' => array( 'acf-disabled' ),
			'update_post_meta_cache' => false,
		) );

		foreach ( $field_groups as $field_group ) {

			if ( substr( $field_group->post_title, 0, 7 ) == '[BLOCK]' ) {

				$field_group_id = $field_group->post_name;
				$field_group_hash = str_replace( 'group_', '', $field_group_id );
				$field_group_title = str_replace( '[BLOCK] ', '', $field_group->post_title );
				$field_group_name = strtolower( str_replace( ' ', '_', $field_group_title ) );

				$content_blocks[] = (object) array(
					'id' => $field_group_id,
					'hash' => $field_group_hash,
					'title' => $field_group_title,
					'name' => $field_group_name,
				);

			}

		}

		return $content_blocks;

	}

	private function load_custom_acf_fields() {

		include( plugin_dir_path( __FILE__ ) . 'fields/sjcb-block-preset.php');

	}

}

function slicejack_acf_content_blocks() {

	global $sjcb;

	if ( ! isset( $sjcb ) ) {

		$sjcb = new slicejack_acf_content_blocks();

	}

	return $sjcb;

}

slicejack_acf_content_blocks();
