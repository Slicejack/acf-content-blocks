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
	 * Content blocks group key (ID).
	 *
	 * @var string
	 */
	const GROUP_KEY = 'group_acb_content_blocks';

	/**
	 * Content blocks field key (ID).
	 *
	 * @var string
	 */
	const FIELD_KEY = 'field_acb_content_blocks';

	/**
	 * Returns instance of the ACF content blocks class.
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
	 * ACF Content Blocks constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_custom_post_type' ), 0 );

		add_action( 'acf/init', array( $this, 'initialize' ) );

		add_filter( 'acf/validate_field_group', array( $this, 'add_field_group_block_option' ) );
		add_action( 'acf/render_field_group_settings', array( $this, 'render_field_group_block_option' ) );
		add_action( 'acf/update_field_group', array( $this, 'update_field_group_block_option' ) );
		add_action( 'acf/get_field_group', array( $this, 'get_field_group_acb_content_blocks' ) );

		add_filter( 'acf/prepare_field/key=field_acb_content_blocks', array( $this, 'prepare_content_blocks_field' ) );
		add_filter( 'acf/prepare_field/name=acb_use_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/name=acb_preset', array( $this, 'hide_preset_fields' ) );
		add_filter( 'acf/prepare_field/name=acb_content_block', array( $this, 'remove_content_block_conditional_logic' ) );

		add_filter( 'acf/fields/post_object/query/name=acb_preset', array( $this, 'filter_preset_field_presets' ), 10, 2 );
		add_filter( 'acf/fields/flexible_content/no_value_message', array( $this, 'get_no_value_message' ), 10, 2 );

		add_filter( 'manage_edit-acf-field-group_columns', array( $this, 'filter_field_group_columns' ), 11, 1 );
		add_action( 'manage_acf-field-group_posts_custom_column', array( $this, 'render_field_group_columns' ), 11, 2 );
	}

	/**
	 * Filters the ACF field group columns. Adds a block preset table
	 * column within the ACF fields groups screen.
	 *
	 * @param  array $columns ACF columns.
	 * @return array
	 */
	public static function filter_field_group_columns( $columns ) {
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
	 * Renders the ACF field group columns. Populates the custom block preset
	 * table column.
	 *
	 * @param  string  $column  Column name.
	 * @param  integer $post_id Post ID.
	 * @return void
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
	 * Filter preset field presets. Only allows selecting presets that are
	 * of the same type as the parent content block.
	 *
	 * @param  array $args  Query arguments.
	 * @param  array $field ACF field.
	 * @return array
	 */
	public function filter_preset_field_presets( $args, $field ) {
		$parent_field = get_field_object( $field['parent'] );
		$layout = $parent_field['layouts'][ $field['parent_layout'] ];

		$args['meta_query'] = array(
			array(
				'key'   => 'acb_content_blocks',
				'value' => serialize( array( $layout['name'] ) ),
			),
		);

		return $args;
	}

	/**
	 * Customizes block preset flexible content no_value_message.
	 *
	 * @param  string $default Default no_value_message.
	 * @param  array  $field   ACF field.
	 * @return string
	 */
	public function get_no_value_message( $default, $field ) {
		if ( self::is_acb_block_preset_screen() && $field['key'] === self::FIELD_KEY ) {
			return __( 'Click the "%s" button below to start creating your block preset', 'acf-content-blocks' );
		}

		return $default;
	}

	/**
	 * Hides preset fields in block preset custom post type.
	 *
	 * @param  array $field ACF field.
	 * @return array|false
	 */
	public function hide_preset_fields( $field ) {
		if ( self::is_acb_block_preset_screen() ) {
			return false;
		}

		return $field;
	}

	/**
	 * Remove section field conditional logic in preset custom post type.
	 *
	 * @param  array $field ACF field.
	 * @return array
	 */
	public function remove_content_block_conditional_logic( $field ) {
		if ( self::is_acb_block_preset_screen() ) {
			$field['conditional_logic'] = 0;
		}

		return $field;
	}

	/**
	 * Updates the settings of the ACF content blocks field if located within
	 * the block preset admin screen.
	 *
	 * @param  array $field ACF field.
	 * @return array
	 */
	public function prepare_content_blocks_field( $field ) {
		if ( self::is_acb_block_preset_screen() ) {
			$field['required'] = 1;
			$field['min'] = '1';
			$field['max'] = '1';
			$field['label'] = __( 'Block Preset', 'acf-content-blocks' );
		}

		return $field;
	}

	/**
	 * Adds block option to field group array.
	 *
	 * @param  array $field_group ACF field group.
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
	 * Renders the content block option within the field group options metabox.
	 *
	 * @return void
	 */
	public static function render_field_group_block_option() {
		global $field_group; // Y tho :/.

		acf_render_field_wrap( array(
			'label'        => __( 'Content block', 'acf-content-blocks' ),
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
	 * @param  array $field_group ACF field group.
	 * @return void
	 */
	public static function update_field_group_block_option( $field_group ) {
		update_post_meta( $field_group['ID'], 'content_block', $field_group['content_block'] );
	}

	/**
	 * Updates the style property of the group_acb_content_blocks field group.
	 *
	 * @param  array $field_group ACF field group.
	 * @return array
	 */
	public static function get_field_group_acb_content_blocks( $field_group ) {
		if ( self::is_acb_block_preset_screen() && $field_group['key'] === self::GROUP_KEY ) {
			$field_group['style'] = 'seamless';
		}

		return $field_group;
	}

	/**
	 * This function will instantiate a global variable containing the rows
	 * of a content blocks field, after which, it will determine if another
	 * row exists to loop through.
	 *
	 * @param  string               $prefix ACF field group prefix.
	 * @param  WP_Post|integer|null $post   The post of which the value is saved against.
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
	 * @param  boolean $format_values Whether or not to format values.
	 * @return array Current block data.
	 */
	public static function the_content_block( $format_values = false ) {
		return the_row( $format_values );
	}

	/**
	 * Returns content block field value
	 *
	 * @param  string  $selector     The field name or key.
	 * @param  boolean $format_value Whether or not to format the value.
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
	 * @param  string  $selector     The field name or key.
	 * @param  boolean $format_value Whether or not to format the value.
	 * @return void
	 */
	public static function the_content_block_field( $selector, $format_value = true ) {
		$value = ACF_Content_Blocks::get_content_block_field( $selector, $format_value );

		if ( is_array( $value ) ) {
			$value = @implode( ', ', $value );
		}

		echo $value; // WPCS: xss ok.
	}

	/**
	 * Alias for ACF get_row_layout function
	 *
	 * @param  string $context Context.
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
	 * Initializes the ACF content blocks plugin.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->field_groups = $this->get_acf_content_blocks();

		$this->register_content_blocks_component_field_group();
	}

	/**
	 * Registers the "Block Preset" custom post type.
	 *
	 * @return void
	 */
	public static function register_custom_post_type() {
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
	 * Registers the content blocks component field group.
	 *
	 * @return void
	 */
	private function register_content_blocks_component_field_group() {
		$fields = array(
			array(
				'key'               => self::FIELD_KEY,
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
			'key'                   => self::GROUP_KEY,
			'title'                 => 'ACF Content Blocks',
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
	 * Returns the content blocks flexible content layouts array.
	 *
	 * @return array
	 */
	private function get_content_blocks_component_layouts_array() {
		$layouts = array();

		foreach ( $this->field_groups as $field_group ) {

			$field_group_hash = str_replace( 'group_', '', $field_group->id );

			$sub_fields = array(
				array(
					'key'               => "field_${field_group_hash}_use_preset",
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
					'key'               => "field_${field_group_hash}_preset",
					'label'             => 'Preset',
					'name'              => 'acb_preset',
					'type'              => 'post_object',
					'instructions'      => '',
					'required'          => 1,
					'conditional_logic' => array(
						array(
							array(
								'field'    => "field_${field_group_hash}_use_preset",
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
					'key'               => "field_${field_group_hash}_content_block",
					'label'             => '',
					'name'              => 'acb_content_block',
					'type'              => 'clone',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => array(
						array(
							array(
								'field'    => "field_${field_group_hash}_use_preset",
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
	 * Returns the content blocks array.
	 *
	 * @return array
	 */
	private function get_acf_content_blocks() {
		$content_blocks = array();

		$field_groups = new WP_Query( array(
			'post_type'      => 'acf-field-group',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order title',
			'order'          => 'asc',
			'meta_query'     => array(
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

	/**
	 * Checks if the post type of the current admin screen is "acb_block_preset".
	 *
	 * @return boolean
	 */
	public static function is_acb_block_preset_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		return ( ! empty( $screen ) && 'acb_block_preset' === $screen->post_type );
	}

}

ACF_Content_Blocks::get_instance();
