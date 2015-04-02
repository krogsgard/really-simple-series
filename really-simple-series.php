<?php
/* 
Plugin Name: Really Simple Series
Plugin URI: http://krogsgard.com/2012/simple-wordpress-series-plugin
Description: This plugin changes the default order of selected categories used to ascending (oldest first). It also lists all posts in the selected categories at the bottom of each post in the series.
Version: 0.2
Author: Brian Krogsgard & Pippin Williamson
Author URI: http://krogsgard.com
Contributors: krogsgard, mordauk
Thanks: Tom McFarlin, and Ryan Imel for your help, even though you don't know it : )
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


class RSSeries {

	// static property to hold singleton instance

	static $instance = false;
	
	// this is the constructor, which is private to force the use of
	// getInstance() to make this a singleton
	
	
	private function __construct() {
	
		load_plugin_textdomain( 'really-simple-series', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		
		// action to filter posts with pre_get_posts if it's the main query and if it matches our category. 
		// These are our categories for the series 
		
		add_action( 'pre_get_posts', array( $this, 'rsseries_reverse_category_order' ) );
		
		// add shortcode to list the posts in our category 
		
		add_shortcode( 'rsseries', array( $this, 'rsseries_create_series_shortcode' ) );
		
		// filter to add the list of posts in the series on the singular view only when that post has our category defined 
		
		add_filter( 'the_content', array( $this, 'rsseries_filter_content_on_single_with_cat' ) );
		
		// the next four actions are for adding the checkbox to enable a series
		
		add_action( 'edit_category_form_fields', array( $this, 'rsseries_category_edit_form_field' ) );
		
		add_action( 'category_add_form_fields', array( $this, 'rsseries_category_edit_form_field' ) );

		add_action( 'edited_category', array( $this, 'rsseries_save_category_edited_field' ) );
		
		add_action( 'created_category', array( $this, 'rsseries_save_category_edited_field' ) );
	
	}

	
	 // if an instance exists, this returns it. If not, it creates one and returns it.
	 
	 public static function getInstance() {
	 	
	 	if ( !self::$instance )
	 		
	 		self::$instance = new self;
	 	
	 	return self::$instance;
	 
	 }

	
	// function to filter posts with pre_get_posts if it's the main query and if it matches our category. 
	// These are our categories for the series 
	
	public function rsseries_reverse_category_order( $query ) {
	    
		if ( $query->is_category() && $query->is_main_query() && ! is_admin() ) {
		
			$category = $query->get_queried_object_id(); 

			// $category = get_query_var('cat'); doesn't work, so i used used get_term_by and get_query_var( 'category_name' ) instead to reverse retrieve the ID
			// it must be a bug with pre_get_posts as I ran into the same thing recently too https://gist.github.com/bb26c6074c358389a039
			
			$rsoption = get_option( 'rsseries_cat_' . $category );
			
			if ( 'on' ==  $rsoption['rsseries_check'] ) {
				
				$query->set( 'order', 'ASC' );
			
			}
		
		}
	
	}
	
	
	// let's create a shortcode to list the posts in our category 
	
	public function rsseries_create_series_shortcode( $atts ) {
	     
		extract(shortcode_atts(array(
			'post_type'		=>	'post',
			'num_posts' 	=> 	'-1',
			'order'		=>	'ASC',
			'cat' 		=> 	NULL,
		), $atts));

		if ( NULL == $cat ) {	
			
			$categories = get_the_category( get_the_ID() );
			
			$cat_series = NULL;
			
			if( $categories ){
				
				foreach( $categories as $category ) {					
					
					$rsoption = get_option( 'rsseries_cat_' . $category->term_id );
					
					if ( 'on' ==  $rsoption['rsseries_check'] ) {
						
						$cat_series[] = $category->term_id;
					
					}
				
				}
			
			}
			
			$cat = $cat_series;
		
		} else {
			
			$cat = array( $cat );
			
		}
		
		$args = array (
			'post_type' 		=> 	$post_type,
			'posts_per_page' 		=> 	$num_posts,
			'order'			=>	$order,
			'category__in' 		=> 	$cat
		);
		
		$rsseriesquery = new WP_Query( $args );
				
		$listseries = '<div class="really-simple-series">' . apply_filters( 'rsseries_title', '<h5>' . __( 'View all posts in this series', 'really-simple-series' ) . '</h5>' );
	
			$listseries .= apply_filters( 'rsseries_before_series', '' );
			
			$listseries .= '<ul>';
			
			while( $rsseriesquery->have_posts() ) : $rsseriesquery->the_post();
			
				$listseries .= '<li  class="really-simple" id="post-' . get_the_ID() . '">';
					
					$listseries .= apply_filters( 'rsseries_content', '<a href="' . get_permalink() . '">' . get_the_title()  . '</a>' . the_date( '', '<span>' . __( ' - ', 'really-simple-series' ), '</span>', false) );
				
				$listseries .= '</li>';		
			
			endwhile;
			
			$listseries .= '</ul>';
			
			$listseries .= apply_filters( 'rsseries_after_series', '' );
		
		$listseries .= '</div>'; // .really-simple-series
		
		wp_reset_postdata(); // reset the query

		return $listseries;
	
	}
	
	// function to add the list of posts in the series on the singular view only when that post has our category defined 
	// thanks Pippin Williamson @ pippinsplugins.com for making sure I filter the content properly
	
	public function rsseries_filter_content_on_single_with_cat( $content ) {
	
		if( is_singular() && is_main_query() ) {
		
			$categories = get_the_category();
			
			$cat_series = NULL;
			
			if( $categories ) {
				
				foreach( $categories as $category ) {					
					
					$rsoption = get_option( 'rsseries_cat_' . $category->term_id );
					
					if ( 'on' ==  $rsoption['rsseries_check'] ) {
						
						$cat_series[] = $category->term_id;
					
					}
				
				}
				
				if ( NULL != $cat_series && has_category( $cat_series ) ) {
						
					$new_content = do_shortcode( '[rsseries]' );
					
					$content .= $new_content;	
					
				}
			
			}
		
		}
		
		return $content;
	
	}
	
	// create extra category fields
	
	//add extra fields to category edit form callback function
	
	// thanks Ohad Raz for putting me in the right direction http://en.bainternet.info/2011/wordpress-category-extra-fields
	
	public function rsseries_category_edit_form_field( $cat ) {
		
		//check for existing taxonomy meta for term ID
		
		$t_id = $cat->term_id;
		$category_meta = get_option( 'rsseries_cat_' . $t_id);
		?>
		
		<table class="form-table">
			<h2><?php _e( 'Really Simple Series' ); ?></h2>
			<tbody>
				<tr class="form-field">
				<th scope="row" valign="top"><label for="rsseries_check"><?php _e( 'Enable Really Simple Series', 'really-simple-series' ); ?></label></th>
					<td>
						<label for="rsseries_check"><?php _e( 'Check the box to enable Really Simple Series', 'really-simple-series' ); ?></label>

						<input type="checkbox" id="category_meta[rsseries_check]" name="category_meta[rsseries_check]" <?php checked( true, isset( $category_meta['rsseries_check'] ) ? true : false ) ?> />
						
						<br />
					 </td>
				</tr>
			<tbody>
		</table>
		<?php 
	
	}
	 
	// save extra category fields callback function
	
	public function rsseries_save_category_edited_field( $term_id ) {
	    
		if ( isset( $_POST['category_meta'] ) ) {
			
			$t_id = $term_id;
		 
		  	$category_meta = get_option( "rsseries_cat_$t_id" );
		  
		  	$cat_keys = array_keys($_POST['category_meta']);
		 
		    foreach ($cat_keys as $key){
		    
		      	if (isset($_POST['category_meta'][$key])){
		          
		         	 $category_meta[$key] = $_POST['category_meta'][$key];
		      
		      	} else {
			   		
			   		unset($category_meta[$key]);
				
				}
		 
		  	}
		  
		  //save the option array
		 
		  update_option( 'rsseries_cat_' . $t_id, $category_meta );
		
		} else {
			
			delete_option( 'rsseries_cat_' . $term_id );
		
		}
	
	}

	

} // end class

// instantiate the class

$RSSeries = RSSeries::getInstance();


// Really Simple Series display function
// just a wrapper for what's in the class.
// thanks Ryan Imel @ryanimel.com for the wrapper and class Instance setup

function rsseries_really_simple_series_wrapper() {
	
	global $RSSeries;
	
	if ( $RSSeries ) {
		
		return $RSSeries();
	
	}

}