<?php
/**
 * Donation model
 *
 * @version		1.0.0
 * @package		Charitable/Classes/Charitable_Donation
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Donation' ) ) : 

/**
 * Donation Model
 *
 * @since		1.0.0
 */

class Charitable_Donation {
	
	/**
	 * The donation ID. 
	 *
	 * @var 	int 
	 * @access 	private
	 */
	private $donation_id;

	/**
	 * The database record for this donation from the Posts table.
	 * 
	 * @var 	Object 
	 * @access  private 
	 */
	private $donation_data;

	/**
	 * The Campaign Donations table.
	 *
	 * @var 	Charitable_Campaign_Donations_DB
	 * @access  private
	 */
	private $campaign_donations_db;	

	/**
	 * The payment gateway used to process the donation.
	 *
	 * @var 	Charitable_Gateway_Interface
	 * @access 	private
	 */
	private $gateway;

	/**
	 * The campaign donations made as part of this donation. 
	 *
	 * @var 	Object
	 * @access 	private
	 */
	private $campaign_donations;

	/**
	 * The campaign that was donated to.
	 * 
	 * @var 	Charitable_Campaign 
	 * @access 	private
	 */
	// private $campaign;

	/**
	 * The WP_User object of the person who donated. 
	 * 
	 * @var 	WP_User 
	 * @access 	private
	 */
	private $donor;

	/**
	 * Instantiate a new donation object based off the ID.
	 * 
	 * @param 	mixed 		$donation 		The donation ID or WP_Post object.
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function __construct( $donation ) {
		if ( is_a( $donation, 'WP_Post' ) ) {
			$this->donation_id 			= $donation->ID;
			$this->donation_data 		= $donation;	
		}
		else {
			$this->donation_id 			= $donation;
			$this->donation_data 		= get_post( $donation );		
		}		
	}

	/**
	 * Magic getter.
	 *
	 * @param 	string 		$key
	 * @return 	mixed
	 * @access  public
	 * @since 	1.0.0
	 */
	public function __get( $key ) {
		if ( method_exists( $this, 'get_' . $key ) ) {
			$method = 'get_' . $key;
			return $this->$method;
		}

		return $this->donation_data->$key;
	}

	/**
	 * Get the donation data.
	 *
	 * @return 	Charitable_Campaign_Donations_DB
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_campaign_donations_db() {
		if ( ! isset( $this->campaign_donations_db ) ) {
			$this->campaign_donations_db = new Charitable_Campaign_Donations_DB();
		}

		return $this->campaign_donations_db;
	}

	/**
	 * The amount donated on this donation.
	 *
	 * @return 	float
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_total_donation_amount() {
		return $this->get_campaign_donations_db()->get_donation_total_amount( $this->donation_id );
	}

	/**
	 * Return the campaigns donated to in this donation. 
	 *
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_campaign_donations() {
		if ( ! isset( $this->campaign_donations ) ) {
			$this->campaign_donations = $this->get_campaign_donations_db()->get_donation_records( $this->donation_id );
		}

		return $this->campaign_donations;
	}

	/**
	 * Returns an array of the campaigns that were donated to.
	 *
	 * @return 	string[]
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_campaigns() {
		return array_map( array( $this, 'get_campaign_name' ), $this->get_campaign_donations() );
	}

	/**
	 * Returns the campaign name from a campaign donation record.
	 *
	 * @return 	string
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_campaign_name( $campaign_donation ) {
		return $campaign_donation->campaign_name;
	}

	/**
	 * Return a comma separated list of the campaigns that were donated to.	
	 *
	 * @param 	boolean 	$linked 		Whether to return the campaigns with links to the campaign pages.
	 * @return 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_campaigns_donated_to( $linked = false ) {
		$campaigns = $linked ? $this->get_campaigns_links() : $this->get_campaigns();

		return implode( ', ', $campaigns );
	}

	/**
	 * Return a comma separated list of the campaigns that were donated to, with links to the campaigns. 
	 *
	 * @return 	string[]
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_campaigns_links() {
		$links = array();

		foreach ( $this->get_campaign_donations() as $campaign ) {

			if ( ! isset( $links[ $campaign->campaign_id ] ) ) {

				$links[ $campaign->campaign_id ] = sprintf( '<a href="%s" title="%s">%s</a>', 
					get_permalink( $campaign->campaign_id ), 
					sprintf( '%s %s', _x( 'Go to', 'go to campaign', 'charitable' ), get_the_title( $campaign->campaign_id ) ), 
					get_the_title( $campaign->campaign_id )
				);

			}
		}

		return $links;
	}

	/**
	 * Return the date of the donation.
	 *
	 * @param 	string 		$format
	 * @return 	string
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_date( $format = '' ) {
		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}

		return date_i18n( $format, strtotime( $this->donation_data->post_date ) );
	}

	/**
	 * The name of the gateway used to process the donation.
	 *
	 * @return 	string 				The name of the gateway.
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_gateway() {
		return get_post_meta( $this->donation_id, 'donation_gateway', true );
	}

	/**
	 * The status of this donation.
	 *
	 * @param 	boolean 	$label 	Whether to return the label. If not, returns the key.
	 * @return 	string
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_status( $label = false ) {
		$status = $this->donation_data->post_status;

		if ( ! $label ) {
			return $status;
		}

		$statuses = self::get_valid_donation_statuses();
		return $statuses[ $status ];
	} 

	/**
	 * Returns the donation ID. 
	 * 
	 * @return 	int
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_donation_id() {
		return $this->donation_id;
	}

	/**
	 * Returns the customer note attached to the donation.
	 *
	 * @return 	string
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_notes() {
		return $this->donation_data->post_content;
	}

	/**
	 * Returns the donor who made this donation.
	 *
	 * @return 	WP_User
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_donor() {
		if ( ! isset( $this->donor ) ) {
			$this->donor = new WP_User( $this->donation_data->post_author );
		}

		return $this->donor;
	}
	
	/**
	 * Return array of valid donations statuses. 
	 *
	 * @return 	array
	 * @access  public
	 * @static
	 * @since 	1.0.0
	 */
	public static function get_valid_donation_statuses() {
		return apply_filters( 'charitable_donation_statuses', array( 
			'charitable-pending' 	=> __( 'Pending', 'charitable' ),
			'charitable-completed' 	=> __( 'Completed', 'charitable' ),
			'charitable-failed' 	=> __( 'Failed', 'charitable' ),
			'charitable-cancelled' 	=> __( 'Cancelled', 'charitable' ),
			'charitable-refunded' 	=> __( 'Refunded', 'charitable' ),
		) );
	}

	/**
	 * Add a message to the donation log. 
	 *
	 * @param 	string 		$message
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function update_donation_log( $message ) {
		$log = $this->get_donation_log();

		$log[] = array( 
			'time'		=> time(), 
			'message'	=> $message
		);

		update_post_meta( $this->donation_id, '_donation_log', $log );
	}

	/**
	 * Get a donation's log.  
	 *
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_donation_log() {
		$log = get_post_meta( $this->donation_id, '_donation_log', true );;

		return is_array( $log ) ? $log : array();
	}

	/**
	 * Checks whether the user data passed when inserting the donation is valid. 
	 *
	 * @return 	boolean
	 * @access  private
	 * @static
	 * @since 	1.0.0
	 */
	private static function is_valid_user_data( $args ) {		
		return $args[ 'user_id' ] || false == apply_filters( 'charitable_require_user_account', true );		
	}

	/**
	 * Inserts a new donation. 
	 *
	 * @param 	array 	$args
	 * @return 	int 	$donation_id 		Returns 0 in case of failure. Positive donation ID otherwise.
	 * @access 	public
	 * @static
	 * @since 	1.0.0
	 */
	public static function insert( array $args ) {
		$args = apply_filters( 'charitable_donation_args', $args );

		/* Ensure that an array of campaigns has been passed */
		if ( ! isset( $args['campaigns'] ) || ! is_array( $args['campaigns'] ) ) {
			_doing_it_wrong( 'Charitable_Donation::insert()', 'A donation cannot be inserted without an array of campaigns being donated to.', '1.0.0' );
			return 0;
		}

		/* Validate the user data we have received */
		if ( ! self::is_valid_user_data( $args ) ) {
			_doing_it_wrong( 'Charitable_Donation::insert()', 'A donation cannot be inserted without a valid user id.', '1.0.0' );
			return 0;
		}
		
		do_action( 'charitable_before_add_donation', $args );

		/* Save core donation object in Posts table */
		$donation_args = array(
			'post_type'		=> 'donation', 
			'post_author'	=> $args[ 'user_id' ], 
			'post_status'	=> 'charitable-pending'
		);		
		$donation_args['post_content'] 	= isset( $args['note'] ) 			? $args['note'] 			: '';		
		$donation_args['post_parent'] 	= isset( $args['donation_plan'] )	? $args['donation_plan']	: 0;
		$donation_args['post_date']		= isset( $args['date'] ) 			? $args['date'] 			: date('Y-m-d h:i:s');
		$donation_args['post_title'] 	= sprintf( '%s &ndash; %s', __( 'Donation', 'charitable' ), date( 'j F Y H:i a', strtotime( $donation_args['post_date'] ) ) );

		if ( isset( $args['status'] ) && array_key_exists( $args['status'], self::get_valid_donation_statuses() ) ) {
			$donation_args['post_status'] = $args['status'];
		}

		$donation_id = wp_insert_post( $donation_args );

		if ( ! $donation_id || is_wp_error( $donation_id ) ) {
			return 0;
		}

		/* Insert campaign donations */
		self::insert_campaign_donations( $donation_id, $args[ 'campaigns' ] );

		/* Save donation meta */
		self::add_donation_meta( $donation_id, $args );		

		$donation = new Charitable_Donation( $donation_id );
		$donation->update_donation_log( __( 'Donation created.', 'charitable' ) );

		do_action( 'charitable_after_add_donation', $donation_id, $args );

		/* Finally, return the donation ID. */
		return $donation_id;
	}

	/**
	 * Inserts the campaign donations into the campaign_donations table. 
	 *
	 * @param 	int 		$donation_id
	 * @param 	array 		$campaigns
	 * @return 	int 		The number of donations inserted. 
	 * @access  public
	 * @static
	 * @since 	1.0.0
	 */
	public static function insert_campaign_donations( $donation_id, $campaigns ) {
		
		$inserted = 0;

		foreach ( $campaigns as $campaign ) {

			$campaign[ 'donation_id' ] = $donation_id;
			$campaign_donation_id = charitable()->get_db_table( 'campaign_donations' )->insert( $campaign );

			if ( 0 == $campaign_donation_id ) {
				return 0;
			}

			$inserted++;

		}	

		return $inserted;
	}

	/**
	 * Save the meta for the donation.	
	 *
	 * @param 	int 		$donation_id
	 * @param 	array 		$args
	 * @return 	void
	 * @access  public
	 * @static
	 * @since 	1.0.0
	 */
	public static function add_donation_meta( $donation_id, $args ) {		

		/* Save other donation meta */
		$meta_keys = array_intersect_key( self::get_mapped_meta_keys(), $args );

		foreach ( $meta_keys as $key => $meta_key ) {

			$value = apply_filters( 'charitable_sanitize_donation_meta', $args[ $key ], $meta_key );
			update_post_meta( $donation_id, $meta_key, $value );

		}

	}

	/**
	 * Return an array of meta keys with alternative keys. 
	 *
	 * In frontend forms, the preference is for using the mapped meta keys, which 
	 * are the keys of the array this method returns. In the backend, the actual
	 * meta keys are used instead.
	 *
	 * @return 	string[]
	 * @access  public
	 * @static
	 * @since 	1.0.0
	 */
	public static function get_mapped_meta_keys() {
		return apply_filters( 'charitable_donation_mapped_meta_keys', array(
			'gateway' => 'donation_gateway'
		) );
	}

	/**
	 * Sanitize meta values before they are persisted to the database. 
	 *
	 * @param  	mixed 		$value
	 * @param 	string 		$key
	 * @return 	mixed
	 * @access 	public
	 * @static
	 * @since 	1.0.0
	 */
	public static function sanitize_meta( $value, $key ) {

		if ( 'donation_gateway' == $key ) {			
			if ( empty( $value ) || ! $value ) {
				$value = 'manual';
			}			
		}

		return apply_filters( 'charitable_sanitize_donation_meta-' . $key, $value );
	}

	/**
	 * Update the status of the donation. 
	 *	
	 * @uses 	wp_update_post()
	 * @uses 	wp_transition_post_status()		Use this for hooks that tap into status transitions.
	 * @param 	string 		$new_status
	 * @return 	int|WP_Error 					The value 0 or WP_Error on failure. The donation ID on success.
	 * @access  public
	 * @since 	1.0.0
	 */
	public function update_status( $new_status ) {
		/**
		 * Validate new status.
		 */
		$valid_statuses = self::get_valid_donation_statuses();

		if ( ! array_key_exists( $new_status, $valid_statuses ) ) {
			$status = array_search( $new_status, $valid_statuses );

			if ( false === $status ) {
				_doing_it_wrong( __METHOD__, sprintf( '%s is not a valid donation status.', $new_status ), '1.0.0' );
				return 0;
			}

			$new_status = $status;
		}

		$old_status = $this->get_status();		

		if ( $old_status == $new_status ) {
			return 0;
		}		

		/**
		 * This actually updates the post status.
		 */
		$this->donation_data->post_status = $new_status;
		$donation_id = wp_update_post( $this->donation_data );		

		/**
		 * Log the status transition.
		 */
		$log_message = sprintf( __( 'Donation status updated from %s to %s', 'charitable' ), $valid_statuses[$old_status], $valid_statuses[$new_status] );
		$this->update_donation_log( $log_message );

		/**
		 * Fires off action hooks that you can use to tap into this event.
		 */
		wp_transition_post_status( $new_status, $old_status, $this->donation_data );

		return $donation_id;
	}
}

endif; // End class_exists check