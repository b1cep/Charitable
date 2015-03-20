<?php
/**
 * The class that defines how donations are managed on the admin side.
 * 
 * @package 	Charitable/Classes/Charitable_Donation_Post_Type
 * @version     1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; 

if ( ! class_exists( 'Charitable_Donation_Post_Type' ) ) : 

/**
 * Charitable_Donation_Post_Type class.
 *
 * @final
 * @since 	    1.0.0
 */
final class Charitable_Donation_Post_Type {

	/**
	 * @var 	Charitable 		$charitable
	 * @access 	private
	 */
	private $charitable;

	/**
	 * @var 	Charitable_Meta_Box_Helper $meta_box_helper
	 * @access 	private
	 */
	private $meta_box_helper;

	/**
	 * Create an object instance. This will only work during the charitable_start event.
	 * 
	 * @see 	charitable_start hook
	 *
	 * @param 	Charitable $charitable
	 * @return 	void
	 * @access 	private
	 * @since 	1.0.0
	 */
	public static function charitable_start( Charitable $charitable ) {
		if ( ! $charitable->is_start() ) {
			return;
		}

		new Charitable_Donation_Post_Type( $charitable );
	}

	/**
	 * Create object instance. 
	 *
	 * @param 	Charitable 		$charitable
	 * @return 	void
	 * @access 	private
	 * @since 	1.0.0
	 */
	private function __construct( Charitable $charitable ) {
		$this->charitable = $charitable;
		$this->charitable->register_object($this);

		// Add fields to the dashboard listing of donations.
		add_filter( 'manage_edit-donation_columns', 		array( $this, 'dashboard_columns' ), 11, 1 );
		add_filter( 'manage_donation_posts_custom_column', 	array( $this, 'dashboard_column_item' ), 11, 2 );
		add_filter( 'views_edit-donation', 					array( $this, 'view_options' ) );

		do_action( 'charitable_admin_donation_post_type_start', $this );
	}

	/**
	 * Customize donations columns.  
	 *
	 * @see 	get_column_headers
	 *
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function dashboard_columns( $column_names ) {
		$column_names = apply_filters( 'charitable_donation_dashboard_column_names', array(
			'cb'                => '<input type="checkbox"/>',
			'id'				=> __( 'ID', 'charitable' ),
			'donor'				=> __( 'Donor', 'charitable' ), 
			'details'			=> __( 'Details', 'charitable' ),
			'amount'			=> __( 'Amount Donated', 'charitable' ), 
			'campaigns'			=> __( 'Campaign(s)', 'charitable' ), 			
			'donation_date'		=> __( 'Date', 'charitable' ), 
			'status'			=> __( 'Status', 'charitable' )
		) );

		return $column_names;
	}

	/**
	 * Add information to the dashboard donations table listing.
	 *
	 * @see 	WP_Posts_List_Table::single_row()
	 * 
	 * @param 	string 	$column_name 	The name of the column to display.
	 * @param 	int 	$post_id     	The current post ID.
	 * @return 	void
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function dashboard_column_item( $column_name, $post_id ) {		

		$donation = $this->get_donation( $post_id );
		
		switch ( $column_name ) {
			case 'id' : 
				$display = $donation->ID;
				break;

			case 'donor' : 
				$display = $donation->get_donor()->display_name;
				break;

			case 'details' : 
				$display = sprintf( '<a href="%s">%s</a>', 
					add_query_arg( array( 'post' => $donation->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ), 
					__( 'View Donation Details', 'charitable' ) );
				break;

			case 'amount' : 
				$display = charitable()->get_currency_helper()->get_monetary_amount( $donation->get_total_donation_amount() );
				break;			

			case 'campaigns' : 
				$display = implode( ', ', $donation->get_campaigns() );
				break;

			case 'donation_date' : 				
				$display = $donation->get_date(); 
				break;

			case 'status' : 
				$display = $donation->get_status( true );
				break;

			default :
				$display = '';
				break;
		}

		echo apply_filters( 'charitable_donation_column_display', $display, $column_name, $post_id, $donation );
	}	

	/**
	 * Returns the donation object. Caches the object to avoid re-creating this for each column.
	 *
	 * @return 	Charitable_Donation
	 * @access  private
	 * @since 	1.0.0
	 */
	private function get_donation( $post_id ) {
		$key = 'charitable_donation_' . $post_id;
		$donation = wp_cache_get( $key );

		if ( false === $donation ) {

			$donation = new Charitable_Donation( $post_id );

			wp_cache_set( $key, $donation );

		}

		return $donation;
	}

	/**
	 * Returns the array of view options for this campaign. 
	 *
	 * @param 	array 		$views
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function view_options( $views ) {

		$current 		= isset( $_GET['post-status'] ) ? $_GET['post-status'] : '';
		$statuses 		= Charitable_Donation::get_valid_donation_statuses();
		$donations 		= new Charitable_Donations();
		$status_count 	= $donations->count_by_status();

		$views 			= array();
		$views['all'] 	= sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>', 
			remove_query_arg( array( 'post_status', 'paged' ) ), 
			$current === 'all' || $current == '' ? ' class="current"' : '', 
			__('All', 'charitable'), 
			$donations->count_all()
		);

		foreach ( $statuses as $status => $label ) {
			$views[ $status ] = sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>', 
				add_query_arg( array( 'post_status' => $status, 'paged' => false ) ), 
				$current === $status ? ' class="current"' : '', 
				$label, 
				isset( $status_count[ $status ] ) ? $status_count[ $status ]->num_donations : 0
			);
		} 

		return $views;
	}	
}

endif; // End class_exists check