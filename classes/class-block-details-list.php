<?php
/**
 * Block Details List Class
 *
 * @package ACF_Content_Blocks
 */

class Block_Details_List extends WP_List_Table {

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$data = $this->get_block_details();

		$perPage = 10;
		$currentPage = $this->get_pagenum();
		$totalItems = count( $data );

		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page'    => $perPage
		) );

		$data = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );

		$this->_column_headers = array( $columns );
		$this->items = $data;
	}

	/**
	 * Get a list of columns.
	 * 
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'post_title'  => 'Title',
			'post_type'   => 'Post Type',
			'post_status' => 'Status'
		);

		return $columns;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array $item          Data.
	 * @param  string $column_name  Current column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'post_title':
			case 'post_type':
				return $item[ $column_name ];
			case 'post_status':
				return ucfirst( $item[ $column_name ] );
			default:
				return print_r( $item, true ) ;
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
		$post_type_obj = get_post_type_object( $item['post_type'] );
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
		$permalink = get_the_permalink( $item['post_id'] );

		$actions = array(
			'edit' => '<a href="'. $edit_link . '">Edit</a>',
			'view' => '<a href="'. $permalink . '">View</a>'
		);

		return '<a class="row-title" href="' . $edit_link . '"><strong>' . $item['post_title'] . '</strong></a>' . $this->row_actions( $actions );
	}


	/**
	 * Get the table data
	 *
	 * @return mixed
	 */
	private function get_block_details() {
		global $wpdb;

		$block =  $_GET['block'];
		$block_name_srtlen = strlen( $block );
		
		if( $block ) {
			$query = "
				SELECT * FROM `wp_postmeta` LEFT JOIN `wp_posts` ON `wp_postmeta`.`post_id`=`wp_posts`.`ID` WHERE `wp_posts`.`ID` IS NOT NULL AND `wp_posts`.`post_status` IN ('publish', 'draft' ) AND `meta_key` LIKE '%acb_content_blocks' AND `meta_value` LIKE '%s:$block_name_srtlen:\"$block\"%' GROUP BY `wp_posts`.`ID`ORDER BY `wp_posts`.`post_title`
			";
			$result = $wpdb->get_results( $query, 'ARRAY_A' );
			
			return $result;
		}
		
		return false;
	}
}
