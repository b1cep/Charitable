<?php
/** 
 * Class used to provide information about the current request.
 *  
 * @version		1.0.0
 * @package		Charitable/Classes/Charitable_Request
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; 

if ( ! class_exists( 'Charitable_Request' ) ) :

/** 
 * Charitable_Request. 
 *  
 * @since		1.0.0
 * @final
 */
final class Charitable_Request extends Charitable_Start_Object {

	/**
	 * @var 	Charitable_Campaign
	 * @access 	private
	 */
	private $campaign;

	/**
	 * @var 	Charitable_Donor
	 * @access 	private
	 */
	private $donor;

	/**
	 * @var 	Charitable_Donation
	 * @access 	private
	 */
	private $donation; 

	/**
	 * Set up the class. 
	 * 
	 * Note that the only way to instantiate an object is with the on_start method, 
	 * which can only be called during the start phase. In other words, don't try 
	 * to instantiate this object. 
	 *
	 * @param 	Charitable 		$charitable
	 * @access 	protected
	 * @since 	1.0.0
	 */
	protected function __construct() {	
		add_action( 'the_post', array( $this, 'set_current_campaign' ) );
	}

	/**
	 * When the_post is set, sets the current campaign to the current post if it is a campaign.
	 *
	 * @param 	array 		$args
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function set_current_campaign( $post ) {
		if ( 'campaign' == $post->post_type ) {

			$this->campaign = new Charitable_Campaign( $post );

		}
		else {

			unset( $this->campaign, $this->campaign_id );

		}
	}

	/** 
	 * Returns the current campaign. If there is no current campaign, return false. 
	 *
	 * @return 	Charitable_Campaign if we're viewing a campaign within a loop. False otherwise. 
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function get_current_campaign() {

		if ( ! isset( $this->campaign ) ) {

			if ( $this->get_current_campaign_id() > 0 ) {

				$this->campaign = new Charitable_Campaign( $this->get_current_campaign_id() );

			}
			else {

				$this->campaign = false;

			}
		}

		return $this->campaign;
	}

	/**
	 * Returns the current campaign ID. If there is no current campaign, return 0. 
	 *
	 * @return 	int
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_current_campaign_id() {

		if ( isset( $this->campaign ) ) {

			$this->campaign_id = $this->campaign->ID;

		}
		else {

			$this->campaign_id = 0;

			if ( get_post_type() == 'campaign' ) {

				$this->campaign_id = $this->post->ID;

			}
			elseif ( get_query_var( 'donate', false ) ) {

				$session_donation = charitable_get_session()->get( 'donation' );

				if ( false !== $session_donation ) {

					$this->campaign_id = $session_donation->get( 'campaign_id' );

				}
			}
		}

		return $this->campaign_id;
	}
}

endif; // End class_exists check 