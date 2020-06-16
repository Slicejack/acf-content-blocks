<?php
/**
 * Block Details List Class
 *
 * @package ACF_Content_Blocks
 */

/**
 * Block Details List Class
 */
class Block_Details_List extends WP_List_Table {
	/**
	 * Total number of items returned with WP_Query.
	 *
	 * @var string
	 */
	private $total_items;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	public $block_name;

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$columns      = $this->get_columns();
		$data         = $this->get_block_details( $per_page, $current_page );
		$total_items  = $this->total_items;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $columns );
		$this->items           = $data;
	}

	/**
	 * Get a list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'post_title'  => __( 'Title', 'acf-content-blocks' ),
			'post_type'   => __( 'Post Type', 'acf-content-blocks' ),
			'post_status' => __( 'Status', 'acf-content-blocks' ),
		);

		return $columns;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array  $item          Data.
	 * @param  string $column_name  Current column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'post_title':
			case 'post_type':
				return $item[ $column_name ];
			case 'post_status':
				return ucfirst( $item[ $column_name ] );
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Define data and actions to show on post_type column.
	 *
	 * @param array $item Data.
	 *
	 * @return string
	 */
	public function column_post_type( $item ) {
		$post_type_obj      = get_post_type_object( $item['post_type'] );
		$post_singular_name = $post_type_obj->labels->singular_name;

		return $post_singular_name;
	}

	/**
	 * Define data and actions to show on post_title column.
	 *
	 * @param array $item Data.
	 *
	 * @return string
	 */
	public function column_post_title( $item ) {
		$edit_link = get_edit_post_link( $item['ID'] );
		$permalink = get_the_permalink( $item['ID'] );

		$actions = array(
			'edit' => '<a href="' . $edit_link . '">Edit</a>',
			'view' => '<a href="' . $permalink . '">View</a>',
		);

		return '<a class="row-title" href="' . $edit_link . '"><strong>' . $item['post_title'] . '</strong></a>' . $this->row_actions( $actions );
	}


	/**
	 * Get the table data
	 *
	 * @param  array  $per_page  Number of posts to be retrieved.
	 * @param  string $paged     Current page number.
	 *
	 * @return array
	 */
	private function get_block_details( $per_page = 10, $paged = 1 ) {
		$data = array();

		if ( isset( $_GET['block'] ) ) { // phpcs:ignore
			$this->block_name           = sanitize_text_field( wp_unslash( $_GET['block'] ) ); // phpcs:ignore
			$block_name_srtlen = strlen( $this->block_name );
		}

		if ( $this->block_name ) {
			$args = array(
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'post_type'      => 'any',
				'meta_query'     => array(
					'relation'   => 'AND',
					array(
						'compare_key' => 'LIKE',
						'key'         => 'acb_content_blocks',
					),
					array(
						'value'   => 's:' . $block_name_srtlen . ':"' . $this->block_name . '"',
						'compare' => 'LIKE',
					),
				),
				'orderby'        => 'post_title',
				'order'          => 'ASC',
			);

			$query = new WP_Query( $args ); // phpcs:ignore
			$this->total_items = $query->found_posts;

			$data = array_map(
				function( $post ) {
					return (array) $post; // Cast object to array because prepare_items uses it as an array.
				},
				$query->posts
			);
		}

		return $data;
	}
}
