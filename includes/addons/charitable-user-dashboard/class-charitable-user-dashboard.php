<?php
/**
 * Main class for setting up the Charitable User Dashboard Addon, which is programatically activated by child themes.
 *
 * @package     Charitable/Classes/Charitable_User_Dashboard
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Charitable_User_Dashboard' ) ) : 

/**
 * Charitable_User_Dashboard
 *
 * @since       1.0.0
 */
class Charitable_User_Dashboard implements Charitable_Addon_Interface {

    /**
     * Responsible for creating class instances. 
     *
     * @return  void
     * @access  public
     * @static
     * @since   1.0.0
     */
    public static function load() {
        charitable()->register_object( new Charitable_User_Dashboard() );
    }

    /**
     * Create class instance. 
     *
     * @access  private
     * @since   1.0.0
     */
    private function __construct() {        
        $this->load_dependencies();
        $this->attach_hooks_and_filters();      
    }

    /**
     * Include required files. 
     *
     * @return  void
     * @access  private
     * @since   1.0.0
     */
    private function load_dependencies() {
        require_once( 'charitable-user-dashboard-functions.php' );
        require_once( 'charitable-user-dashboard-template-functions.php' );
        require_once( 'class-charitable-profile-form.php' );
        require_once( 'class-charitable-login-form.php' );
        require_once( 'class-charitable-registration-form.php' );
        require_once( 'class-charitable-user-dashboard-shortcodes.php' );
    }

    /**
     * Set up hooks and filter. 
     *
     * @return  void
     * @access  private
     * @since   1.0.0
     */
    private function attach_hooks_and_filters() {        
        add_action( 'charitable_user_dashboard_start',  array( 'Charitable_User_Dashboard_Shortcodes', 'start' ), 5 );
        add_action( 'charitable_update_profile',        array( 'Charitable_Profile_Form', 'update_profile' ) );     
        add_action( 'charitable_save_registration',     array( 'Charitable_Registration_Form', 'save_registration' ) );
        add_action( 'after_setup_theme',                array( $this, 'register_menu' ) );
        add_action( 'template_include',                 array( $this, 'load_user_dashboard_template' ) );
        add_action( 'wp_update_nav_menu',               array( $this, 'flush_menu_object_cache' ) );
        add_action( 'wp_update_nav_menu_item',          array( $this, 'flush_menu_object_cache' ) );
        
        add_filter( 'body_class',                       array( $this, 'add_body_class' ) );
        add_filter( 'charitable_settings_fields_general', array( $this, 'add_page_settings' ) );     

        do_action( 'charitable_user_dashboard_start', $this );   
    }

    /**
     * Register navigation menu for frontend dashboard. 
     *
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function register_menu() {
        register_nav_menu( 'charitable-dashboard', __( 'User Dashboard', 'charitable' ) );
    }

    /**
     * Returns the user dashboard navigation menu. 
     *
     * @uses    wp_nav_menu
     * @param   array       $args
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function nav( $args ) {
        $defaults = array(
            'theme_location'    => 'charitable-dashboard', 
            'fallback_cb'       => false
        );

        $args = wp_parse_args( $args, $defaults );

        return wp_nav_menu( $args );
    }

    /**
     * Return the menu ID based on the theme location. 
     *
     * @return  int         0 if no menu found. Menu ID otherwise.
     * @access  public
     * @since   1.0.0
     */
    public function get_nav_id() {
        $locations = get_nav_menu_locations();

        if ( ! isset( $locations[ 'charitable-dashboard' ] ) ) {
            return 0;
        }
        
        return wp_get_nav_menu_object( $locations[ 'charitable-dashboard' ] );
    }

    /**
     * Returns all objects in the user dashboard navigation.
     *
     * @uses    wp_get_nav_menu_items
     * @return  WP_Post[]
     * @access  public
     * @since   1.0.0
     */
    public function nav_objects() {     
        $objects    = get_transient( 'charitable_user_dashboard_objects' ); 
        
        if ( false === $objects ) {         

            $objects        = array();
            $nav_menu_items = wp_get_nav_menu_items( $this->get_nav_id() ); 

            if ( is_array( $nav_menu_items ) ) {

                foreach ( $nav_menu_items as $nav_menu_item ) {

                    switch ( $nav_menu_item->type ) {

                        case 'custom' : 

                            $identifier = trailingslashit( $nav_menu_item->url );

                            break;

                        default : 

                            $identifier = apply_filters( 'charitable_nav_menu_object_identifier', $nav_menu_item->object_id, $nav_menu_item );
                    }

                    $objects[] = $identifier;

                }           
            
            }

            set_transient( 'charitable_user_dashboard_objects', $objects );
        }

        return $objects;
    }

    /**
     * Flushes the menu object cache after updating a menu or menu item. 
     *
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function flush_menu_object_cache( $menu_id ) {
        $nav_menu   = wp_get_nav_menu_object( $this->get_nav_id() );

        if ( $nav_menu && $menu_id == $nav_menu->term_id ) {

            delete_transient( 'charitable_user_dashboard_objects' );

        }
    }

    /**
     * Checks whether the current requested page is in the user dashboard nav. 
     *
     * @param   Object      $object         Optional. If not set, will base it on the current queried object.
     * @return  boolean
     * @access  public
     * @since   1.0.0
     */
    public function in_nav() {
        global $wp;

        $ret = wp_cache_get( 'charitable_in_user_dashboard', '', false, $found );       

        if ( false === $found ) {           
            $current_url    = trailingslashit( charitable_get_current_url() );
            $ret            = in_array( get_queried_object_id(), $this->nav_objects() ) || in_array( $current_url, $this->nav_objects() );
            $ret            = apply_filters( 'charitable_is_current_in_nav', $ret, $this->nav_objects() );

            wp_cache_set( 'charitable_in_user_dashboard', $ret );
        }

        return $ret;
    }

    /**
     * Loads the user dashboard template. 
     *
     * @param   string      $template
     * @return  string
     * @access  public
     * @since   1.0.0
     */
    public function load_user_dashboard_template( $template ) {     

        /**
         * The user dashboard template is not loaded by default; this has to be enabled. 
         */
        if ( false === apply_filters( 'charitable_force_user_dashboard_template', false ) ) {
            return $template;
        }

        /**
         * The current object isn't in the nav, so return the template.
         */
        if ( ! $this->in_nav() ) {
            return $template;
        }

        do_action( 'charitable_is_user_dashboard' );
            
        $new_template   = apply_filters( 'charitable_user_dashboard_template', 'user-dashboard.php' );
        $path           = charitable_template( $new_template, false )->locate_template();

        if ( file_exists( $path ) ) {

            $template = $path;

        }

        return $template;
    }

    /**
     * Add the user-dashboard class to the body if we're looking at it. 
     *
     * @param   array       $classes
     * @return  array
     * @access  public
     * @since   1.0.0
     */
    public function add_body_class( $classes ) {
        if ( $this->in_nav() ) {

            $classes[] = 'user-dashboard'; 

        }

        return $classes;
    }

    /**
     * Add page settings to the General settings tab in Charitable.
     *
     * @param   array[]
     * @return  array[]
     * @access  public
     * @since   1.0.0
     */
    public function add_page_settings( $fields ) {
        $new_fields = apply_filters( 'charitable_user_dashboard_settings', array(
            'profile_page'  => array(
                'title'     => __( 'Profile Page', 'charitable' ), 
                'type'      => 'select', 
                'priority'  => 24, 
                'options'   => charitable_get_helper( 'admin_settings' )->get_pages(), 
                'help'      => __( 'The static page should contain the <code>[charitable_profile]</code> shortcode.', 'charitable' )
            ), 
            'login_page'    => array(
                'title'     => __( 'Login Page', 'charitable' ), 
                'type'      => 'select', 
                'priority'  => 24, 
                'default'   => 'wp',
                'options'   => array(
                    'wp'            => __( 'Use WordPress Login', 'charitable' ), 
                    'pages'         => array( 
                        'options'   => charitable_get_helper( 'admin_settings' )->get_pages(), 
                        'label'     => __( 'Choose a Static Page', 'charitable' )
                    )
                ), 
                'help'      => __( 'Allow users to login via the normal WordPress login page or via a static page. The static page should contain the <code>[charitable_login]</code> shortcode.', 'charitable' )

            ), 
            'registration_page' => array(
                'title'     => __( 'Registration Page', 'charitable' ), 
                'type'      => 'select', 
                'priority'  => 26, 
                'default'   => 'wp',
                'options'   => array(
                    'wp'    => __( 'Use WordPress Registration Page', 'charitable' ),
                    'pages' => array(
                        'options'   => charitable_get_helper( 'admin_settings' )->get_pages(),
                        'label'     => __( 'Choose a Static Page', 'charitable' )
                    )
                ),
                'help'      => __( 'Allow users to register via the default WordPress login or via a static page. The static page should contain the <code>[charitable_register]</code> shortcode.', 'charitable' )
            )
        ) );

        $fields = array_merge( $fields, $new_fields );

        return $fields;
    }

    /**
     * Activate the addon. 
     *
     * @return  void
     * @access  public
     * @static
     * @since   1.0.0
     */
    public static function activate() {         
        /* This method should only be called on the charitable_activate_addon hook */
        if ( 'charitable_activate_addon' !== current_filter() ) {
            return false;
        }

    }   
}

endif; // End class_exists check