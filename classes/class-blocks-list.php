<?php
/**
 * Blocks List Class
 *
 * @package ACF_Content_Blocks
 */

use ACF_Content_Blocks\Utils;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Blocks_List extends WP_List_Table {

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$data = $this->get_blocks();

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
			'title' => __( 'Title', 'acf-content-blocks' ),
		);

		return $columns;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array $item         Data.
	 * @param  string $column_name Current column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'title':
				return '<a href="admin.php?page=content-blocks-locator&block=' . $item['name'] . '"><strong>' . $item[ $column_name ] . '</strong></a>';

			default:
				return print_r( $item, true ) ;
		}
	}

	/**
	 * Get the table data
	 *
	 * @return array
	 */
	private function get_blocks() {
		$blocks = Utils::get_acf_content_blocks();
		$data = array();

		if( is_array( $blocks ) && !empty( $blocks ) ) {
			$data = array_map(
				function( $block ) {
					return (array) $block;  // Cast object to array because prepare_items uses it as an array.
				},
				$blocks
			);
		}
			
		return $data;
	}
}
