<?php
/**
 * Paypal Payment Gateway class
 *
 * @version		1.0.0
 * @package		Charitable/Classes/Charitable_Gateway_Paypal
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Gateway_Paypal' ) ) : 

/**
 * Paypal Payment Gateway 
 *
 * @since		1.0.0
 */
class Charitable_Gateway_Paypal extends Abstract_Charitable_Gateway {
	
	/**
	 * @var 	string 		The gateway name.
	 */
	const GATEWAY_NAME = 'Paypal';

	/**
	 * @var 	string 		The gateway ID.
	 */
	const GATEWAY_ID = 'paypal';

	/**
	 * Send the donation/donor off to the gateway.  
	 *
	 * @param 	Charitable_Campaign 	$campaign
	 * @param 	int 					$donation_id
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function send_donation_to_gateway( $campaign, $donation_id ) {

	}
}

endif; // End class_exists check