<?php

class sjcb_acf_field_block_preset extends acf_field {

	function __construct() {
		$this->name = 'sjcb_block_preset';
		$this->label = __("Block Preset",'sjcb');
		$this->category = 'relational';
		$this->defaults = array(
			'post_type'          => array( 'sjcb_block_preset' ),
			'allow_null'         => 1,
			'multiple'           => 0,
			'ui'                 => 1,
			'content_block_type' => null,
		);

		add_action( 'wp_ajax_acf/fields/sjcb_block_preset/query', array( $this, 'ajax_query' ) );
		add_action( 'wp_ajax_nopriv_acf/fields/sjcb_block_preset/query', array( $this, 'ajax_query' ) );

		parent::__construct();
	}

	function ajax_query() {

		if( ! acf_verify_ajax() ) die();

		$response = $this->get_ajax_query( $_POST );

		acf_send_ajax_results($response);

	}

	function get_ajax_query( $options = array() ) {

		$options = acf_parse_args( $options, array(
			'post_id'   => 0,
			's'         => '',
			'field_key' => '',
			'paged'     => 1
		));

		$field = acf_get_field( $options[ 'field_key' ] );

		if( ! $field ) return false;

		$results = array();
		$args = array();
		$s = false;
		$is_search = false;

		$args[ 'posts_per_page' ] = 20;
		$args[ 'post_type' ] = 'sjcb_block_preset';
		$args[ 'paged' ] = $options[ 'paged' ];
		$args[ 'meta_query' ] = array(
			array(
				'key'     => 'sjcb_block_preset_type',
				'value'   => $field[ 'content_block_type' ],
				'compare' => '=',
			)
		);

		if( $options[ 's' ] !== '' ) {

			$s = wp_unslash( strval( $options[ 's' ] ) );
			$args[ 's' ] = $s;
			$is_search = true;

		}

		$args = apply_filters( 'acf/fields/sjcb_block_preset/query', $args, $field, $options[ 'post_id' ] );
		$args = apply_filters( 'acf/fields/sjcb_block_preset/query/name=' . $field[ 'name' ], $args, $field, $options[ 'post_id' ] );
		$args = apply_filters( 'acf/fields/sjcb_block_preset/query/key=' . $field[ 'key' ], $args, $field, $options[ 'post_id' ] );

		$groups = acf_get_grouped_posts( $args );

		if( empty( $groups ) ) return false;

		foreach( array_keys( $groups ) as $group_title ) {

			$posts = acf_extract_var( $groups, $group_title );

			$data = array(
				'text'     => $group_title,
				'children' => array()
			);

			foreach( array_keys( $posts ) as $post_id ) {

				$posts[ $post_id ] = $this->get_post_title( $posts[ $post_id ], $field, $options[ 'post_id' ], $is_search );

			}

			if( $is_search && empty( $args[ 'orderby' ] ) ) {

				$posts = acf_order_by_search( $posts, $args[ 's' ] );

			}

			foreach( array_keys( $posts ) as $post_id ) {

				$data[ 'children' ][] = $this->get_post_result( $post_id, $posts[ $post_id ]);

			}

			$results[] = $data;

		}

		if( count( $args[ 'post_type' ] ) == 1 ) {

			$results = $results[ 0 ][ 'children' ];

		}

		$response = array(
			'results' => $results,
			'limit'   => $args[ 'posts_per_page' ]
		);

		return $response;

	}


	function get_post_result( $id, $text ) {

		$result = array(
			'id'   => $id,
			'text' => $text
		);

		$search = '| ' . __( 'Parent', 'sjcb' ) . ':';
		$pos = strpos( $text, $search );

		if( $pos !== false ) {

			$result[ 'description' ] = substr( $text, $pos+2 );
			$result[ 'text' ] = substr( $text, 0, $pos );

		}

		return $result;

	}

	function get_post_title( $post, $field, $post_id = 0, $is_search = 0 ) {

		if( ! $post_id ) $post_id = acf_get_form_data( 'post_id' );

		$title = acf_get_post_title( $post, $is_search );

		$title = apply_filters( 'acf/fields/sjcb_block_preset/result', $title, $post, $field, $post_id );
		$title = apply_filters( 'acf/fields/sjcb_block_preset/result/name=' . $field[ '_name' ], $title, $post, $field, $post_id );
		$title = apply_filters( 'acf/fields/sjcb_block_preset/result/key=' . $field[ 'key' ], $title, $post, $field, $post_id );

		return $title;
	}

	function render_field( $field ) {

		$field[ 'type' ] = 'select';
		$field[ 'ui' ] = 1;
		$field[ 'ajax' ] = 1;
		$field[ 'choices' ] = array();

		$posts = $this->get_posts( $field[ 'value' ], $field );

		if( $posts ) {

			foreach( array_keys( $posts ) as $i ) {

				$post = acf_extract_var( $posts, $i );

				$field[ 'choices' ][ $post->ID ] = $this->get_post_title( $post, $field );

			}

		}

		acf_render_field( $field );

	}

	function render_field_settings( $field ) {

		acf_render_field_setting( $field, array(
			'label'        => __('Content Block Type','sjcb'),
			'instructions' => __(''),
			'type'         => 'text',
			'name'         => 'content_block_type',
		));

	}

	function load_value( $value, $post_id, $field ) {

		if( $value === 'null' ) return false;

		return $value;

	}

	function format_value( $value, $post_id, $field ) {

		$value = acf_get_numeric( $value) ;

		if( empty( $value ) ) return false;

		$value = $this->get_posts( $value, $field );

		if( acf_is_array( $value ) ) {

			$value = current( $value );

		}

		return $value;

	}

	function update_value( $value, $post_id, $field ) {

		if( empty( $value ) ) {

			return $value;

		}


		if( is_array( $value ) ) {

			foreach( $value as $k => $v ){

				if( is_object( $v ) && isset( $v->ID ) ) {

					$value[ $k ] = $v->ID;

				}

			}

			$value = array_map( 'strval', $value );

		} elseif( is_object( $value ) && isset( $value->ID ) ) {

			$value = $value->ID;

		}

		return $value;

	}

	function get_posts( $value, $field ) {

		$value = acf_get_numeric($value);

		if( empty( $value ) ) return false;

		$posts = acf_get_posts(array(
			'post__in'  => $value,
			'post_type' => $field[ 'post_type' ]
		));

		return $posts;

	}

}

acf_register_field_type( new sjcb_acf_field_block_preset() );
