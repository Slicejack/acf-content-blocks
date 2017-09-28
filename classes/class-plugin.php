<?php
/**
 * Main plugin class
 *
 * @package ACF_Content_Blocks
 */

namespace ACF_Content_Blocks;

/**
 * Plugin class
 */
class Plugin {

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
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version = '0.2.1';

	/**
	 * Field groups.
	 *
	 * @var array
	 */
	private $field_groups;

	/**
	 * Admin notices.
	 *
	 * @var array
	 */
	private $notices;

	/**
	 * Returns instance of the ACF content blocks class.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Plugin();
		}

		return $instance;
	}

	/**
	 * ACF Content Blocks constructor.
	 */
	private function __construct() {
		add_action( 'init', function () {
			$this->do_prerequisites_check();
			$this->register_custom_post_type();
		}, 0 );

		add_action( 'admin_enqueue_scripts', function() {
			$this->enqueue_admin_assets();
		} );

		add_action( 'admin_notices', function() {
			$this->render_admin_notices();
		} );

		add_action( 'acf/init', function () {
			$this->initialize();
		} );

		add_filter( 'acf/validate_field_group', function ( $field_group ) {
			return $this->add_field_group_block_option( $field_group );
		} );
		add_action( 'acf/render_field_group_settings', function ( $field_group ) {
			$this->render_field_group_block_option( $field_group );
		} );
		add_action( 'acf/update_field_group', function ( $field_group ) {
			$this->update_field_group_block_option( $field_group );
		} );
		add_action( 'acf/get_field_group', function ( $field_group ) {
			return $this->get_field_group_acb_content_blocks( $field_group );
		} );

		add_filter( 'acf/prepare_field/key=field_acb_content_blocks', function ( $field ) {
			return $this->prepare_content_blocks_field( $field );
		} );
		add_filter( 'acf/prepare_field/name=acb_use_preset', function ( $field ) {
			return $this->hide_preset_fields( $field );
		} );
		add_filter( 'acf/prepare_field/name=acb_preset', function ( $field ) {
			return $this->hide_preset_fields( $field );
		} );
		add_filter( 'acf/prepare_field/name=acb_content_block', function ( $field ) {
			return $this->remove_content_block_conditional_logic( $field );
		} );

		add_filter( 'acf/fields/post_object/query/name=acb_preset', function ( $args, $field ) {
			return $this->filter_preset_field_presets( $args, $field );
		}, 10, 2 );
		add_filter( 'acf/fields/flexible_content/no_value_message', function ( $default, $field ) {
			return $this->get_no_value_message( $default, $field );
		}, 10, 2 );

		add_filter( 'manage_edit-acf-field-group_columns', function ( $columns ) {
			return $this->filter_field_group_columns( $columns );
		}, 11, 1 );
		add_action( 'manage_acf-field-group_posts_custom_column', function ( $column, $post_id ) {
			$this->render_field_group_columns( $column, $post_id );
		}, 11, 2 );
	}

	/**
	 * Registers the "Content Blocks" field group.
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
	 * Generates and returns the content blocks layouts array.
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
	 * Does a plugin prerequisites check. Stores errors and/or warnings for later use.
	 *
	 * @return void
	 */
	private function do_prerequisites_check() {
		$notices = array();

		if ( ! function_exists( 'get_field' ) ) {
			$notices[] = apply_filters(
				'acb_prerequisites_check_acf_pro_missing_message',
				__(
					'Advanced Custom Fields PRO plugin must be installed and activated in order for the ACF Content Blocks plugin to work.',
					'acf-content-blocks'
				)
			);
		} elseif ( version_compare( acf_get_full_version( acf_get_setting( 'version' ) ), '5.4.0', '<' ) ) {
			$notices[] = apply_filters(
				'acb_prerequisites_check_acf_pro_update_required_message',
				__(
					'Your current Advanced Custom Fields PRO version is not supported by the ACF Content Blocks plugin. Please update the Advanced Custom Fields PRO plugin to version 5.4.0 or newer.',
					'acf-content-blocks'
				)
			);
		}

		$this->notices = $notices;
	}

	/**
	 * Registers the "Block Preset" custom post type.
	 *
	 * @return void
	 */
	private function register_custom_post_type() {
		if ( ! empty( $this->notices ) ) {
			return;
		}

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
	 * Enqueue admin assets (CSS & JS).
	 *
	 * @return void
	 */
	private function enqueue_admin_assets() {
		wp_enqueue_style( 'acf-content-blocks', Utils::get_dir( 'assets/css/acf-content-blocks.css' ), array(), $this->version );
	}

	/**
	 * Renders the admin notices.
	 *
	 * @return void
	 */
	private function render_admin_notices() {
		if ( ! empty( $this->notices ) ) {
			foreach ( $this->notices as $notice ) {
				Utils::render_admin_notice( $notice, 'warning' );
			}
		}
	}

	/**
	 * Initializes the ACF content blocks plugin core components.
	 *
	 * @return void
	 */
	private function initialize() {
		$this->field_groups = Utils::get_acf_content_blocks();
		$this->register_content_blocks_component_field_group();
	}

	/**
	 * Updates the option within the field group array.
	 *
	 * @param  array $field_group  ACF field group.
	 * @return array
	 */
	private function add_field_group_block_option( $field_group ) {
		if ( empty( $field_group['content_block'] ) ) {
			$field_group['content_block'] = 0;
		} else {
			$field_group['content_block'] = 1;
		}

		return $field_group;
	}

	/**
	 * Renders the "Content block" toggle option within the field group
	 * settings meta box.
	 *
	 * @param  array $field_group  ACF field group.
	 * @return void
	 */
	private function render_field_group_block_option( $field_group ) {
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
	 * Updates the field group content_block post meta.
	 *
	 * @param  array $field_group  ACF field group.
	 * @return void
	 */
	private function update_field_group_block_option( $field_group ) {
		update_post_meta( $field_group['ID'], 'content_block', $field_group['content_block'] );
	}

	/**
	 * Updates the style property of the content blocks field group.
	 *
	 * @param  array $field_group  ACF field group.
	 * @return array
	 */
	private function get_field_group_acb_content_blocks( $field_group ) {
		if ( Utils::is_block_preset_screen() && self::GROUP_KEY === $field_group['key'] ) {
			$field_group['style'] = 'seamless';
		}

		return $field_group;
	}

	/**
	 * Modifies the ACF content blocks field meta box settings if the
	 * current screen is set o to block presets admin screen.
	 *
	 * @param  array $field  ACF field.
	 * @return array
	 */
	private function prepare_content_blocks_field( $field ) {
		if ( Utils::is_block_preset_screen() ) {
			$field['label'] = __( 'Block Preset', 'acf-content-blocks' );
			$field['required'] = 1;
			$field['min'] = '1';
			$field['max'] = '1';
		}

		return $field;
	}

	/**
	 * Removes the preset fields if the current screen is set to
	 * block preset admin screen.
	 *
	 * @param  array $field  ACF field.
	 * @return array|false
	 */
	private function hide_preset_fields( $field ) {
		if ( Utils::is_block_preset_screen() ) {
			return false;
		}

		return $field;
	}

	/**
	 * Removes the content block field conditional logic if the current screen
	 * is set to block preset admin screen.
	 *
	 * @param  array $field  ACF field.
	 * @return array
	 */
	private function remove_content_block_conditional_logic( $field ) {
		if ( Utils::is_block_preset_screen() ) {
			$field['conditional_logic'] = 0;
		}

		return $field;
	}

	/**
	 * Filters the preset field options by the parent content block type.
	 *
	 * @param  array $args   Query arguments.
	 * @param  array $field  ACF field.
	 * @return array
	 */
	private function filter_preset_field_presets( $args, $field ) {
		$parent_field = get_field_object( $field['parent'] );
		$layout = $parent_field['layouts'][ $field['parent_layout'] ];

		$args['meta_query'] = array( // WPCS: slow query ok.
			array(
				'key'   => 'acb_content_blocks',
				'value' => serialize( array( $layout['name'] ) ),
			),
		);

		return $args;
	}

	/**
	 * Customizes the block preset flexible content no_value_message value.
	 *
	 * @param  string $default  Default no_value_message value.
	 * @param  array  $field    ACF field.
	 * @return string
	 */
	private function get_no_value_message( $default, $field ) {
		if ( Utils::is_block_preset_screen() && self::FIELD_KEY === $field['key'] ) {
			// translators: %s represents the button label.
			return __( 'Click the "%s" button below to start creating your block preset', 'acf-content-blocks' );
		}

		return $default;
	}

	/**
	 * Filters the ACF field group columns. Adds a block preset table
	 * column within the ACF fields groups screen.
	 *
	 * @param  array $columns  ACF columns.
	 * @return array
	 */
	private function filter_field_group_columns( $columns ) {
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
	 * @param  string  $column   Column name.
	 * @param  integer $post_id  Post ID.
	 * @return void
	 */
	private function render_field_group_columns( $column, $post_id ) {
		$field_group = acf_get_field_group( $post_id );

		if ( 'acb-is-content-block' === $column ) {
			if ( 1 === $field_group['content_block'] ) {
				echo '<i class="acf-icon -check dark small acf-js-tooltip" title="' . esc_attr__( 'Yes', 'acf-content-blocks' ) . '"></i> ';
			} else {
				echo '<i class="acf-icon -minus grey small acf-js-tooltip" title="' . esc_attr__( 'No', 'acf-content-blocks' ) . '"></i> ';
			}
		}
	}

}
