<?php
/**
 * Really Simple Series
 *
 * @package   Really_Simple_Series
 * @author    Brian Krogsgard
 * @license   GPL-2.0+
 * @link      https://github.com/krogsgard/really-simple-series
 * @copyright 2012 Brian Krogsgard
 *
 * @wordpress-plugin
 * Plugin Name:       Really Simple Series
 * Plugin URI:        http://krogsgard.com/2012/simple-wordpress-series-plugin
 * Description:       This plugin changes the default order of selected categories used to ascending (oldest first). It also lists all posts in the selected categories at the bottom of each post in the series.
 * Version:           0.2.0-dev
 * Author:            Brian Krogsgard & Pippin Williamson
 * Author URI:        http://krogsgard.com
 * Text Domain:       really-simple-series
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/krogsgard/really-simple-series
 * GitHub Branch:     master
 * Requires WP:       3.4
 * Requires PHP:      5.2
 */

/**
 * Really Simple Series.
 */
class RSSeries {
	/**
	 * Holds singleton instance of this class.
	 *
	 * @var boolean
	 */
	static $instance = false;

	/**
	 * Private constructor to force use of getInstance().
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		load_plugin_textdomain( 'really-simple-series', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		// Action to filter posts with pre_get_posts if it's the main query and if it matches our category.
		// These are our categories for the series.
		add_action( 'pre_get_posts', array( $this, 'rsseries_reverse_category_order' ) );

		// Add shortcode to list the posts in our category.
		add_shortcode( 'rsseries', array( $this, 'rsseries_create_series_shortcode' ) );

		// Filter to add the list of posts in the series on the singular view only when that post has our category defined.
		add_filter( 'the_content', array( $this, 'rsseries_filter_content_on_single_with_cat' ) );

		// The next four actions are for adding the checkbox to enable a series.
		add_action( 'edit_category_form_fields', array( $this, 'rsseries_category_edit_form_field' ) );
		add_action( 'category_add_form_fields', array( $this, 'rsseries_category_edit_form_field' ) );
		add_action( 'edited_category', array( $this, 'rsseries_save_category_edited_field' ) );
		add_action( 'created_category', array( $this, 'rsseries_save_category_edited_field' ) );
	}

	/**
	 * Get instance of this object.
	 *
	 * @since 0.1.0
	 *
	 * @return RSSeries Self object.
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Filter posts with pre_get_posts.
	 *
	 * Only applies if it is the main query and it matches out category.
	 * These are our categories for the series.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Query $query Query object.
	 */
	public function rsseries_reverse_category_order( $query ) {
		if ( $query->is_category() && $query->is_main_query() && ! is_admin() ) {
			$category = $query->get_queried_object_id();

			/*
			 * $category = get_query_var('cat'); doesn't work, so I used used get_term_by and get_query_var( 'category_name' ) instead to reverse retrieve the ID.
			 * It must be a bug with pre_get_posts as I ran into the same thing recently too.
			 */

			$rsoption = get_option( 'rsseries_cat_' . $category );

			if ( 'on' === $rsoption['rsseries_check'] ) {
				$query->set( 'order', 'ASC' );
			}
		}
	}

	/**
	 * Shortcode callback.
	 *
	 * @since 0.1.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode output.
	 */
	public function rsseries_create_series_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_type' => 'post',
				'num_posts' => '-1',
				'order'     => 'ASC',
				'cat'       => null,
			),
			$atts
		);

		if ( is_null( $atts['cat'] ) ) {
			$categories = get_the_category( get_the_ID() );
			$cat_series = array();

			if ( $categories ) {
				foreach ( $categories as $category ) {
					$rsoption = get_option( 'rsseries_cat_' . $category->term_id );

					if ( 'on' === $rsoption['rsseries_check'] ) {
						$cat_series[] = $category->term_id;
					}
				}
			}

			$cat = $cat_series;
		} else {
			$cat = array( $atts['cat'] );
		}

		$args = array(
			'post_type'      => $atts['post_type'],
			'posts_per_page' => $atts['num_posts'],
			'order'          => $atts['order'],
			'category__in'   => $cat,
		);

		$rsseriesquery = new WP_Query( $args );

		$listseries = '<div class="really-simple-series">' . apply_filters( 'rsseries_title', '<h5>' . esc_html__( 'View all posts in this series', 'really-simple-series' ) . '</h5>' );

		$listseries .= apply_filters( 'rsseries_before_series', '' );

		$listseries .= '<ul>';

		while ( $rsseriesquery->have_posts() ) :
			$rsseriesquery->the_post();

			$listseries .= '<li class="really-simple" id="' esc_attr( 'post-' . get_the_ID() ) . '">';
			$listseries .= apply_filters( 'rsseries_content', '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() )  . '</a>' . esc_html( the_date( '', '<span>' . esc_html__( ' - ', 'really-simple-series' ), '</span>', false ) ) );
			$listseries .= '</li>';
		endwhile;

		$listseries .= '</ul>';
		$listseries .= apply_filters( 'rsseries_after_series', '' );

		$listseries .= '</div>';

		wp_reset_postdata();

		return $listseries;
	}

	/**
	 * Add the list of posts in the series on the singular view.
	 *
	 * Thanks Pippin Williamson @ pippinsplugins.com for making sure I filter the content properly.
	 *
	 * @since 0.1.0
	 *
	 * @param string $content Existing post content.
	 *
	 * @return string Possibly amended post content.
	 */
	public function rsseries_filter_content_on_single_with_cat( $content ) {

		if ( ! is_singular() || ! is_main_query() ) {
			return $content;
		}

		$categories = get_the_category();

		if ( empty( $categories ) || ! is_array( $categories ) ) {
			return $content;
		}

		$cat_series = array();

		foreach ( $categories as $category ) {
			$rsoption = get_option( 'rsseries_cat_' . $category->term_id );

			if ( 'on' === $rsoption['rsseries_check'] ) {
				$cat_series[] = $category->term_id;
			}
		}

		if ( ! empty( $cat_series ) && has_category( $cat_series ) ) {
			$new_content = do_shortcode( '[rsseries]' );

			$content .= $new_content;
		}

		return $content;
	}

	/**
	 * Create extra category field.
	 *
	 * Add extra field to category edit form callback function.
	 *
	 * Thanks Ohad Raz for putting me in the right direction http://en.bainternet.info/2011/wordpress-category-extra-fields.
	 *
	 * @since 0.1.0
	 *
	 * @param object $cat Category object.
	 */
	public function rsseries_category_edit_form_field( $cat ) {
		// Check for existing taxonomy meta for term ID.
		$t_id          = $cat->term_id;
		$category_meta = get_option( 'rsseries_cat_' . $t_id );
		?>

		<table class="form-table">
			<h2><?php esc_html_e( 'Really Simple Series' ); ?></h2>
			<tbody>
				<tr class="form-field">
				<th scope="row" valign="top"><label for="rsseries_check"><?php esc_html_e( 'Enable Really Simple Series', 'really-simple-series' ); ?></label></th>
					<td>
						<label for="rsseries_check"><?php esc_html_e( 'Check the box to enable Really Simple Series', 'really-simple-series' ); ?></label>
						<input type="checkbox" id="category_meta[rsseries_check]" name="category_meta[rsseries_check]"<?php checked( isset( $category_meta['rsseries_check'] ) ) ?> />
						<br />
					 </td>
				</tr>
			<tbody>
		</table>
		<?php
	}

	/**
	 * Save extra category fields callback function.
	 *
	 * @since 0.1.0
	 *
	 * @param int $term_id Term ID.
	 */
	public function rsseries_save_category_edited_field( $term_id ) {

		if ( isset( $_POST['category_meta'] ) ) {

			$t_id = $term_id;

			$category_meta = get_option( "rsseries_cat_$t_id" );

			$cat_keys = array_keys( $_POST['category_meta'] );

			foreach ( $cat_keys as $key ) {
				if ( isset( $_POST['category_meta'][ $key ] ) ) {
					 $category_meta[ $key ] = sanitize_key( $_POST['category_meta'][ $key ] );
				} else {
					unset( $category_meta[ $key ] );
				}
			}

			// Save the option array.
			update_option( 'rsseries_cat_' . $t_id, $category_meta );
		} else {
			delete_option( 'rsseries_cat_' . $term_id );
		}
	}
}

// Instantiate the class.
$RSSeries = RSSeries::getInstance();

/**
 * Really Simple Series display function.
 *
 * Just a wrapper for what's in the class.
 * Thanks Ryan Imel @ryanimel.com for the wrapper and class Instance setup.
 *
 * @since 0.1.0
 *
 * @return RSSSeries Instance of main plugin object.
 */
function rsseries_really_simple_series_wrapper() {

	global $RSSeries;

	if ( $RSSeries ) {

		return $RSSeries();

	}

}
