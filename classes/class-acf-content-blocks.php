<?php
/**
 * ACF Content Blocks class
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
	 * Returns instance of this class
	 *
	 * @return ACF_Content_Blocks
	 */
	public static function get_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new ACF_Content_Blocks();
		}

		return $instance;
	}

	/**
	 * ACF Content Blocks constructor
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_custom_post_type' ), 0 );

		add_action( 'acf/init', array( $this, 'initialize' ) );

		add_filter( 'acf/validate_field_group', array( $this, 'add_field_group_block_option' ) );
		add_action( 'acf/render_field_group_settings', array( $this, 'render_field_group_block_option' ) );
		add_action( 'acf/update_field_group', array( $this, 'update_field_group_block_option' ) );

		add_filter( 'acf/prepare_field/key=field_acb_content_blocks', array( $this, 'prepare_content_blocks_field' ) );
		add_filter( 'acf/prepare_field/key=field_acb_use_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/key=field_acb_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/key=field_acb_content_block', array( $this, 'remove_content_block_conditional_logic' ) );

		add_filter( 'acf/fields/post_object/query/key=field_acb_preset', array( $this, 'filter_preset_field_presets' ), 10, 2 );

		add_filter( 'manage_edit-acf-field-group_columns', array( $this, 'filter_field_group_columns' ), 11, 1 );
		add_action( 'manage_acf-field-group_posts_custom_column', array( $this, 'render_field_group_columns' ), 11, 2 );
	}

	/**
	 * Filters ACF field group columns
	 *
	 * @param array $columns ACF columns.
	 * @return array
	 */
	public function filter_field_group_columns( $columns ) {
		$status_key_index = array_search( 'acf-fg-status', array_keys( $columns ), true );
		$column = array(
			'acb-is-content-block' => '<i class="dashicons-before dashicons-screenoptions acf-js-tooltip" title="' . esc_attr__( 'Is Content Block?', 'acf-content-blocks' ) . '"></i>',
		);

		if ( false === $status_key_index ) {
			return array_merge( $columns, $column );
		}

		return array_merge(
			array_slice( $columns, 0, $status_key_index + 1, true ),
			$column,
			array_slice( $columns, $status_key_index + 1, null, true )
		);
	}

	/**
	 * Render ACF field group columns
	 *
	 * @param string  $column  Column name.
	 * @param integer $post_id Post ID.
	 */
	public function render_field_group_columns( $column, $post_id ) {
		$field_group = acf_get_field_group( $post_id );

		if ( 'acb-is-content-block' === $column ) {
			if ( 1 === $field_group['content_block'] ) {
				echo '<i class="acf-icon -check dark small acf-js-tooltip" title="' . esc_attr__( 'Yes', 'acf-content-blocks' ) . '"></i> ';
			} else {
				echo '<i class="acf-icon -minus grey small acf-js-tooltip" title="' . esc_attr__( 'No', 'acf-content-blocks' ) . '"></i> ';
			}
		}
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
				'key' => 'acb_content_blocks',
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

	/**
	 * This function will instantiate a global variable containing the rows
	 * of a content blocks field, after which, it will determine if another
	 * row exists to loop through.
	 *
	 * @param WP_Post|integer|null $post The post of which the value is saved against.
	 * @return boolean
	 */
	public static function have_content_blocks( $post = null ) {
		if ( null === $post ) {
			global $post;
		} else {
			$post = get_post( $post ); // WPCS: override ok.
		}

		if ( empty( $post ) ) {
			return false;
		}

		return have_rows( 'acb_content_blocks', $post->ID );
	}

	/**
	 * Alias of ACF the_row function.
	 *
	 * @param boolean $format_values Whether or not to format values.
	 * @return array Current block data.
	 */
	public static function the_content_block( $format_values = false ) {
		return the_row( $format_values );
	}

	/**
	 * Returns content block field value
	 *
	 * @param string  $selector     The field name or key.
	 * @param boolean $format_value Whether or not to format the value.
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
	 * @param string  $selector     The field name or key.
	 * @param boolean $format_value Whether or not to format the value.
	 */
	public static function the_content_block_field( $selector, $format_value = true ) {
		$value = ACF_Content_Blocks::get_content_block_field( $selector, $format_value );

		if ( is_array( $value ) ) {
			$value = @join( ', ', $value );
		}

		echo $value; // WPCS: xss ok.
	}

	/**
	 * Alias for ACF get_row_layout function
	 *
	 * @param string $context Context.
	 * @return string
	 */
	public static function get_content_block_name( $context = 'template' ) {
		$name = get_row_layout();

		if ( 'template' === $context ) {
			$name = str_replace( '_', '-', $name );
		}

		return $name;
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
		$fields = array(
			array(
				'key'               => 'field_acb_content_blocks',
				'label'             => 'Content Blocks',
				'name'              => 'acb_content_blocks',
				'type'              => 'flexible_content',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'layouts'           => $this->get_content_blocks_component_layouts_array(),
				'button_label'      => 'Add Content Block',
				'min'               => '',
				'max'               => '',
			),
		);

		$location = array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'acb_block_preset',
				),
			),
		);

		acf_add_local_field_group( array(
			'key'                   => 'group_acb_content_blocks',
			'title'                 => '[COMPONENT] Content Blocks',
			'fields'                => $fields,
			'location'              => $location,
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => 1,
			'description'           => '',
			'content_block'         => 0,
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

			$sub_fields = array(
				array(
					'key'               => 'field_acb_use_preset',
					'label'             => 'Use Preset',
					'name'              => 'acb_use_preset',
					'type'              => 'true_false',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'message'           => '',
					'default_value'     => 0,
					'ui'                => 1,
					'ui_on_text'        => '',
					'ui_off_text'       => '',
				),
				array(
					'key'               => 'field_acb_preset',
					'label'             => 'Preset',
					'name'              => 'acb_preset',
					'type'              => 'post_object',
					'instructions'      => '',
					'required'          => 1,
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_acb_use_preset',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
					'wrapper'          => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'post_type'        => array( 'acb_block_preset' ),
					'taxonomy'         => array(),
					'allow_null'       => 1,
					'multiple'         => 0,
					'return_format'    => 'id',
					'ui'               => 1,
				),
				array(
					'key'               => 'field_acb_content_block',
					'label'             => '',
					'name'              => 'acb_content_block',
					'type'              => 'clone',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_acb_use_preset',
								'operator' => '!=',
								'value'    => '1',
							),
						),
					),
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'clone'             => array( $field_group->id ),
					'display'           => 'group',
					'layout'            => 'block',
					'prefix_label'      => 0,
					'prefix_name'       => 0,
				),
			);

			$layouts[ $field_group_hash ] = array(
				'key'        => $field_group_hash,
				'name'       => $field_group->name,
				'label'      => $field_group->title,
				'display'    => 'block',
				'sub_fields' => $sub_fields,
				'min'        => '',
				'max'        => '',
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
			'posts_per_page' => 100, // Probably we won't have more then 100 field groups.
			'orderby' => 'menu_order title',
			'order'   => 'asc',
			'meta_query' => array(
				array(
					'key' => 'content_block',
					'value' => 1,
					'type' => 'NUMERIC',
				),
			),
			'post_status' => 'any',
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

ACF_Content_Blocks::get_instance();
