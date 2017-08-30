<?php
/**
 * Plugin Name: ACF Content Blocks
 * Plugin URI: https://github.com/Slicejack/acf-content-blocks
 * Description: ACF Content Blocks
 * Version: alpha-0.1
 * Author: Slicejack
 * Author URI: https://slicejack.com/
 * License: GNU General Public License v3.0
 * License URI: https://github.com/Slicejack/acf-content-blocks/blob/master/LICENSE
 * Text Domain: acf-content-blocks
 * Domain Path: /languages
 *
 * @package ACF_Content_Blocks
 */

/**
 * Plugin class
 */
class ACF_Content_Blocks {
	/**
	 * Field groups
	 *
	 * @var array
	 */
	private $field_groups;

	/**
	 * ACF Content Blocks constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_custom_post_type' ), 0 );

		add_action( 'acf/init', array( $this, 'initialize' ) );

		add_filter( 'acf/validate_field_group', array( $this, 'add_field_group_block_option' ) );
		add_action( 'acf/render_field_group_settings', array( $this, 'render_field_group_block_option' ) );
		add_action( 'acf/update_field_group', array( $this, 'update_field_group_block_option' ) );

		add_filter( 'acf/prepare_field/key=field_acb_content_blocks', array( $this, 'prepare_content_blocks_field' ) );
		add_filter( 'acf/prepare_field/name=acb_use_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/name=acb_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/name=acb_content_block', array( $this, 'remove_content_block_conditional_logic' ) );

		add_filter( 'acf/fields/post_object/query/name=acb_preset', array( $this, 'filter_preset_field_presets' ), 10, 2 );
	}

	/**
	 * Filter preset field presets
	 *
	 * @param array $args  Query arguments.
	 * @param array $field ACF field.
	 * @return array
	 */
	public function filter_preset_field_presets( $args, $field ) {
		$parent_field = get_field_object( $field['parent'] );
		$layout = $parent_field['layouts'][ $field['parent_layout'] ];

		$args['meta_query'] = array(
			array(
				'key' => 'content_blocks',
				'value' => serialize( array( $layout['name'] ) ),
			),
		);

		return $args;
	}

	/**
	 * Hide preset fields in preset custom post type
	 *
	 * @param array $field ACF field.
	 * @return array|false
	 */
	public function hide_preset_fields( $field ) {
		$screen = get_current_screen();

		if ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type ) {
			return false;
		}

		return $field;
	}

	/**
	 * Remove section field conditional logic in preset custom post type
	 *
	 * @param array $field ACF field.
	 * @return array
	 */
	public function remove_content_block_conditional_logic( $field ) {
		$screen = get_current_screen();

		if ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type ) {
			$field['conditional_logic'] = 0;
		}

		return $field;
	}

	/**
	 * Allow only one layout in preset
	 *
	 * @param array $field ACF field.
	 * @return array
	 */
	public function prepare_content_blocks_field( $field ) {
		$screen = get_current_screen();

		if ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type ) {
			$field['required'] = 1;
			$field['min'] = '1';
			$field['max'] = '1';
		}

		return $field;
	}

	/**
	 * Adds block option to field group array
	 *
	 * @param array $field_group ACF field group.
	 * @return array
	 */
	public static function add_field_group_block_option( $field_group ) {
		if ( empty( $field_group['content_block'] ) ) {
			$field_group['content_block'] = 0;
		} else {
			$field_group['content_block'] = 1;
		}

		return $field_group;
	}

	/**
	 * Renders block option in Options metabox
	 */
	public static function render_field_group_block_option() {
		global $field_group;

		acf_render_field_wrap( array(
			'label'        => __( 'Content Block', 'acf' ),
			'instructions' => '',
			'type'         => 'true_false',
			'name'         => 'content_block',
			'prefix'       => 'acf_field_group',
			'value'        => $field_group['content_block'],
			'ui'           => 1,
		) );
	}

	/**
	 * Update field group's content_block post meta.
	 *
	 * @param array $field_group ACF field group.
	 */
	public static function update_field_group_block_option( $field_group ) {
		update_post_meta( $field_group['ID'], 'content_block', $field_group['content_block'] );
	}

	public static function has_content_blocks( $location, $post_id = null ) {
		if ( is_numeric( $post_id ) ) {
			$post_id = ( null === $post_id ) ? get_the_ID() : $post_id ;
		}

		$container = get_field( $location, $post_id );

		if ( isset( $container['content_blocks'] ) && ! empty( $container['content_blocks'] ) ) {
			return true;
		}

		return false;
	}

	public static function render_content_blocks( $location, $post_id = null ) {
		if ( is_numeric( $post_id ) ) {
			$post_id = ( null === $post_id ) ? get_the_ID() : $post_id ;
		}

		$container = get_field( $location, $post_id );

		if ( isset( $container['content_blocks'] ) && ! empty( $container['content_blocks'] ) ) {
			foreach ( $container['content_blocks'] as $block_index => $block ) {

				$block_layout = str_replace( '_', '-', sanitize_title( $block['acf_fc_layout'] ) );

				$block_data = self::get_block_data( $block );

				if ( file_exists( locate_template( 'blocks/' . $block_layout . '.php' ) ) ) {
					include locate_template( 'blocks/' . $block_layout . '.php' );
				}
			}
		}
	}

	public static function get_block_data( $block ) {
		$data = array();

		$block_layout = $block['acf_fc_layout'];

		if ( ! empty( $block['acb_use_preset'] ) ) {

			return self::get_block_preset_data( $block );

		}

		return ( isset( $block[ $block_layout ] ) ) ? $block[ $block_layout ] : $block ;
	}

	public static function get_block_preset_data( $block ) {
		$preset = $block['acb_preset'];

		$block_layout = $block['acf_fc_layout'];

		if ( $preset instanceof WP_Post ) {
			$block = get_field( 'sjcb_' . $block_layout . '_block_preset_settings', $preset );

			if ( is_array( $block ) ) {
				unset( $block['acf_fc_layout'] );

				return $block;
			}
		}

		return array();
	}

	/**
	 * Initialize
	 */
	public function initialize() {
		$this->field_groups = $this->get_acf_content_blocks();

		$this->register_content_blocks_component_field_group();
	}

	/**
	 * Register Block Preset custom post type
	 */
	public function register_custom_post_type() {
		$labels = array(
			'name'                  => _x( 'Block Presets', 'Post Type General Name', 'acf-content-blocks' ),
			'singular_name'         => _x( 'Block Preset', 'Post Type Singular Name', 'acf-content-blocks' ),
			'menu_name'             => __( 'Block Presets', 'acf-content-blocks' ),
			'name_admin_bar'        => __( 'Block Preset', 'acf-content-blocks' ),
			'archives'              => __( 'Block Presets', 'acf-content-blocks' ),
			'attributes'            => __( 'Block Preset Attributes', 'acf-content-blocks' ),
			'parent_item_colon'     => __( 'Parent Block Preset:', 'acf-content-blocks' ),
			'all_items'             => __( 'All Block Presets', 'acf-content-blocks' ),
			'add_new_item'          => __( 'Add New Block Preset', 'acf-content-blocks' ),
			'add_new'               => __( 'Add New', 'acf-content-blocks' ),
			'new_item'              => __( 'New Block Preset', 'acf-content-blocks' ),
			'edit_item'             => __( 'Edit Block Preset', 'acf-content-blocks' ),
			'update_item'           => __( 'Update Block Preset', 'acf-content-blocks' ),
			'view_item'             => __( 'View Block Preset', 'acf-content-blocks' ),
			'view_items'            => __( 'View Block Presets', 'acf-content-blocks' ),
			'search_items'          => __( 'Search Block Preset', 'acf-content-blocks' ),
			'not_found'             => __( 'Not found', 'acf-content-blocks' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'acf-content-blocks' ),
			'featured_image'        => __( 'Featured Image', 'acf-content-blocks' ),
			'set_featured_image'    => __( 'Set featured image', 'acf-content-blocks' ),
			'remove_featured_image' => __( 'Remove featured image', 'acf-content-blocks' ),
			'use_featured_image'    => __( 'Use as featured image', 'acf-content-blocks' ),
			'insert_into_item'      => __( 'Insert into item', 'acf-content-blocks' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'acf-content-blocks' ),
			'items_list'            => __( 'Items list', 'acf-content-blocks' ),
			'items_list_navigation' => __( 'Items list navigation', 'acf-content-blocks' ),
			'filter_items_list'     => __( 'Filter items list', 'acf-content-blocks' ),
		);

		$args = array(
			'label'                 => __( 'Block Preset', 'acf-content-blocks' ),
			'description'           => __( 'ACF Content Blocks Block Preset', 'acf-content-blocks' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'author', 'revisions' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-screenoptions',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'capability_type'       => 'page',
			'show_in_rest'          => false,
		);

		register_post_type( 'acb_block_preset', $args );
	}

	/**
	 * Register content blocks component field group
	 */
	private function register_content_blocks_component_field_group() {
		acf_add_local_field_group( array(
			'key' => 'group_sjcb_content_blocks',
			'title' => '[COMPONENT] Content Blocks',
			'fields' => array(
				array(
					'key' => 'field_acb_content_blocks',
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
						'value' => 'acb_block_preset',
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
		) );
	}

	/**
	 * Content blocks flexible content layouts
	 *
	 * @return array
	 */
	private function get_content_blocks_component_layouts_array() {
		$layouts = array();

		foreach ( $this->field_groups as $field_group ) {

			$field_group_hash = str_replace( 'group_', '', $field_group->id );

			$layouts[ $field_group_hash ] = array(
				'key' => $field_group_hash,
				'name' => $field_group->name,
				'label' => $field_group->title,
				'display' => 'block',
				'sub_fields' => array(
					array(
						'key' => 'field_sjcb_' . $field_group_hash . '_use_preset',
						'label' => 'Use Preset',
						'name' => 'acb_use_preset',
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
						'name' => 'acb_preset',
						'type' => 'post_object',
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
						'post_type' => array(
							0 => 'acb_block_preset',
						),
						'allow_null' => 1,
						'multiple' => 0,
						'ui' => 1,
					),
					array(
						'key' => 'field_sjcb_' . $field_group_hash . '_display_settings',
						'label' => $field_group->title . ' Block Display Settings',
						'name' => 'acb_content_block',
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
							0 => $field_group->id,
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

	/**
	 * Get all content blocks
	 *
	 * @return array
	 */
	private function get_acf_content_blocks() {
		$content_blocks = array();

		$field_groups = new WP_Query( array(
			'post_type' => 'acf-field-group',
			'posts_per_page' => -1,
			'orderby' => 'menu_order title',
			'order'   => 'asc',
			'meta_query' => array(
				array(
					'key' => 'content_block',
					'value' => 1,
					'type' => 'NUMERIC',
				),
			),
		) );

		if ( $field_groups->have_posts() ) {
			while ( $field_groups->have_posts() ) {
				$field_groups->the_post();

				$field_group_id = get_post_field( 'post_name' );
				$field_group_hash = str_replace( 'group_', '', $field_group_id );
				$field_group_title = get_the_title();
				$field_group_name = strtolower( str_replace( ' ', '_', $field_group_title ) );

				$content_blocks[] = (object) array(
					'id'    => $field_group_id,
					'hash'  => $field_group_hash,
					'title' => $field_group_title,
					'name'  => $field_group_name,
				);
			}
		}

		return $content_blocks;
	}
}

/**
 * Creates new instance of ACF_Content_Blocks
 *
 * @return ACF_Content_Blocks
 */
function acf_content_blocks() {
	global $sjcb;

	if ( ! isset( $sjcb ) ) {

		$sjcb = new ACF_Content_Blocks();

	}

	return $sjcb;
}

acf_content_blocks();
