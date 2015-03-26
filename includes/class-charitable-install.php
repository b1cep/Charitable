<?php
/**
 * Charitable Install class.
 * 
 * The responsibility of this class is to manage the events that need to happen 
 * when the plugin is activated.
 *
 * @package		Charitable
 * @subpackage	Charitable/Charitable Install
 * @copyright 	Copyright (c) 2014, Eric Daams	
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 		1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Install' ) ) : 

/**
 * Charitable_Install
 *
 * @since 		1.0.0
 */
class Charitable_Install {

	/**
	 * Install the plugin. 
	 *
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function __construct() {	
		$this->setup_roles();
		$this->create_tables();	
		$this->add_endpoints();
		$this->add_rewrite_rules();

		flush_rewrite_rules();

		do_action( 'charitable_install' );	
	}

	/**
	 * Create wp roles and assign capabilities
	 *
	 * @return 	void
	 * @static
	 * @access 	public
	 * @since 	1.0.0
	 */
	private function setup_roles(){
		$roles = new Charitable_Roles();
		$roles->add_roles();
		$roles->add_caps();
	}

	/**
	 * Create database tables. 
	 *
	 * @return 	void
	 * @access 	private
	 * @since 	1.0.0
	 */
	private function create_tables() {
		@charitable()->get_db_table( 'campaign_donations' )->create_table();
	}

	/**
	 * Add custom endpoints. 
	 *
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function add_endpoints() {
		add_rewrite_endpoint( 'donate', EP_ALL );
		add_rewrite_endpoint( 'widget', EP_ALL );
	}

	/**
	 * Add rewrite rules. 
	 *
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^campaigns/([^&]+)/donate/?', 'index.php?campaign=$matches[1]&donate=1', 'top' );
	}
}

endif;