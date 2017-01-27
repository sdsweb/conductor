<?php
/**
 * Conductor Category Dropdown Walker (used to add the data-content-type and data-permalink attributes)
 *
 * @class Walker_ConductorCategoryDropdown
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Walker_ConductorCategoryDropdown' ) ) {
	class Walker_ConductorCategoryDropdown extends Walker {
		/**
		* @see Walker::$tree_type
		* @since 1.0.0
		* @var string
		*/
		var $tree_type = 'category';

		/**
		* @see Walker::$db_fields
		* @since 1.0.0
		* @var array
		*/
		var $db_fields = array (
			'parent' => 'parent',
			'id' => 'term_id'
		);

		/**
		* Start the element output.
		*
		* @see Walker::start_el()
		* @since 1.0.0
		*
		* @param string $output   Passed by reference. Used to append additional content.
		* @param object $category Category data object.
		* @param int    $depth    Depth of category. Used for padding.
		* @param array  $args     Uses 'selected' and 'show_count' keys, if they exist. @see wp_dropdown_categories()
		*/
		public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
			$pad = str_repeat( '&nbsp;', $depth * 3 );

			/** This filter is documented in wp-includes/category-template.php */
			$cat_name = apply_filters( 'list_cats', $category->name, $category );

			$output .= "\t" . '<option class="level-' . $depth . '" value="' . $category->term_id . '" data-content-type="category" data-permalink="' . esc_attr( trailingslashit( home_url() ) . '?cat=' . $category->term_id ) . '"';
			if ( $category->term_id == $args['selected'] )
				$output .= ' selected="selected"';
			$output .= '>';
			$output .= $pad.$cat_name;
			if ( $args['show_count'] )
				$output .= '&nbsp;&nbsp;('. number_format_i18n( $category->count ) .')';
			$output .= '</option>' . "\n";
		}
	}
}