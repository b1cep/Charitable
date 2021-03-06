<?php
/**
 * Charitable Settings Pages.
 * 
 * @package     Charitable/Classes/Charitable_Admin_Settings
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Charitable_Admin_Settings' ) ) : 

/**
 * Charitable_Admin_Settings
 *
 * @final
 * @since      1.0.0
 */
final class Charitable_Admin_Settings extends Charitable_Start_Object {

    /**
     * The page to use when registering sections and fields.
     *
     * @var     string 
     * @access  private
     */
    private $admin_menu_parent_page;

    /**
     * The capability required to view the admin menu. 
     *
     * @var     string
     * @access  private
     */
    private $admin_menu_capability;

    /**
     * Current field. Used to access field args from the views.      
     *
     * @var     array
     * @access  private
     */
    private $current_field; 

    /**
     * Create object instance. 
     *
     * @access  protected
     * @since   1.0.0
     */
    protected function __construct() {
        $this->admin_menu_capability    = apply_filters( 'charitable_admin_menu_capability', 'manage_options' );
        $this->admin_menu_parent_page   = 'charitable';

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );      

        add_filter( 'charitable_sanitize_value', array( $this, 'sanitize_checkbox_value' ), 10, 2 );

        do_action( 'charitable_admin_settings_start', $this );
    }

    /**
     * Add Settings menu item under the Campaign menu tab.
     * 
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function add_menu() {

        add_menu_page( 'Charitable', 'Charitable', $this->admin_menu_capability, $this->admin_menu_parent_page, array( $this, 'render_charitable_settings_page' ) );

        add_submenu_page( $this->admin_menu_parent_page, __( 'All Campaigns', 'charitable' ), __( 'Campaigns', 'charitable' ), $this->admin_menu_capability, 'edit.php?post_type=campaign' );
        add_submenu_page( $this->admin_menu_parent_page, __( 'Add Campaign', 'charitable' ), __( 'Add Campaign', 'charitable' ), $this->admin_menu_capability, 'post-new.php?post_type=campaign' );
        add_submenu_page( $this->admin_menu_parent_page, __( 'Donations', 'charitable' ), __( 'Donations', 'charitable' ), $this->admin_menu_capability, 'edit.php?post_type=donation' );
        add_submenu_page( $this->admin_menu_parent_page, __( 'Campaign Categories', 'charitable' ), __( 'Categories', 'charitable' ), $this->admin_menu_capability, 'edit-tags.php?taxonomy=campaign_category&post_type=campaign' );
        add_submenu_page( $this->admin_menu_parent_page, __( 'Campaign Tags', 'charitable' ), __( 'Tags', 'charitable' ), $this->admin_menu_capability, 'edit-tags.php?taxonomy=campaign_tag&post_type=campaign' );
        add_submenu_page( $this->admin_menu_parent_page, __( 'Settings', 'charitable' ), __( 'Settings', 'charitable' ), $this->admin_menu_capability, 'charitable-settings', array( $this, 'render_charitable_settings_page' ) );

        remove_submenu_page( $this->admin_menu_parent_page, $this->admin_menu_parent_page );
    }

    /**
     * Return the array of tabs used on the settings page.  
     *
     * @return  string[]
     * @access  public
     * @since   1.0.0
     */
    public function get_sections() {
        return apply_filters( 'charitable_settings_tabs', array( 
            'general'   => __( 'General', 'charitable' ), 
            'forms'     => __( 'Forms', 'charitable' ),
            'gateways'  => __( 'Payment Gateways', 'charitable' ), 
            'emails'    => __( 'Emails', 'charitable' )
        ) );
    }

    /**
     * Return all the general fields.  
     *
     * @return  array[]
     * @access  private
     * @since   1.0.0
     */
    private function get_general_fields() {
        return apply_filters( 'charitable_settings_fields_general', array(
            'section_locale'        => array(
                'title'             => __( 'Currency & Location', 'charitable' ), 
                'type'              => 'heading', 
                'priority'          => 2
            ),
            'country'               => array(
                'title'             => __( 'Base Country', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 4, 
                'default'           => 'AU', 
                'options'           => charitable()->get_location_helper()->get_countries()
            ), 
            'currency'              => array(
                'title'             => __( 'Currency', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 10, 
                'default'           => 'AUD',
                'options'           => charitable()->get_currency_helper()->get_all_currencies()                        
            ), 
            'currency_format'       => array(
                'title'             => __( 'Currency Format', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 12, 
                'default'           => 'left',
                'options'           => array(
                    'left'              => '$23.00', 
                    'right'             => '23.00$',
                    'left-with-space'   => '$ 23.00',
                    'right-with-space'  => '23.00 $'
                )
            ),
            'decimal_separator'     => array(
                'title'             => __( 'Decimal Separator', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 14, 
                'default'           => '.',
                'options'           => array(
                    '.' => 'Period (12.50)',
                    ',' => 'Comma (12,50)'                      
                )
            ), 
            'thousands_separator'   => array(
                'title'             => __( 'Thousands Separator', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 16, 
                'default'           => ',',
                'options'           => array(
                    ',' => __( 'Comma (10,000)', 'charitable' ), 
                    '.' => __( 'Period (10.000)', 'charitable' ), 
                    ''  => __( 'None', 'charitable' )
                )
            ),
            'decimal_count'         => array(
                'title'             => __( 'Number of Decimals', 'charitable' ), 
                'type'              => 'number', 
                'priority'          => 18, 
                'default'           => 2
            ),
            'section_pages'         => array(
                'title'             => __( 'Pages', 'charitable' ), 
                'type'              => 'heading', 
                'priority'          => 20
            ),
            'donation_page'         => array(
                'title'             => __( 'Donation Page', 'charitable' ), 
                'type'              => 'select', 
                'priority'          => 22, 
                'default'           => 'auto',
                'options'           => array(
                    'auto'          => __( 'Use Automatic Routing', 'charitable' ), 
                    'pages'         => array( 
                        'options'   => $this->get_pages(), 
                        'label'     => __( 'Choose a Static Page', 'charitable' )
                    )
                )
            ),
            'section_dangerous'     => array(
                'title'             => __( 'Dangerous Settings', 'charitable' ), 
                'type'              => 'heading', 
                'priority'          => 100
            ),
            'delete_data_on_uninstall'  => array(
                'label_for'         => __( 'Reset Data', 'charitable' ), 
                'type'              => 'checkbox', 
                'help'              => __( 'DELETE ALL DATA when uninstalling the plugin.', 'charitable' ), 
                'priority'          => 105
            )
        ) );
    }

    /**
     * Return all the settings fields related to forms (donation forms, profile forms, etc).
     *
     * @return  array[]
     * @access  private
     * @since   1.0.0
     */
    private function get_form_fields() {
        return apply_filters( 'charitable_settings_fields_forms', array(
            'section_donation_form' => array(
                'title'             => __( 'Donation Form', 'charitable' ),
                'type'              => 'heading',
                'priority'          => 2
            )
        ) ); 
    }

    /**
     * Returns all the payment gateway settings fields.  
     *
     * @return  array[]
     * @access  private
     * @since   1.0.0
     */
    private function get_gateway_fields() {
        return apply_filters( 'charitable_settings_fields_gateways', array(
            'gateways' => array(
                'label_for'         => __( 'Available Payment Gateways', 'charitable' ),
                'callback'          => array( $this, 'render_gateways_table' ), 
                'priority'          => 5
            )
        ) );
    }

    /**
     * Return an array with all the fields & sections to be displayed. 
     *
     * @uses    charitable_settings_fields
     * @see     Charitable_Admin_Settings::register_setting()
     * @return  array
     * @access  private
     * @since   1.0.0
     */
    private function get_fields() {
        /** 
         * Use the charitable_settings_tab_fields to include the fields for new tabs. 
         * DO NOT use it to add individual fields. That should be done with the
         * filters within each of the methods. 
         */
        return apply_filters( 'charitable_settings_tab_fields', array(
            'general'   => $this->get_general_fields(),
            'forms'     => $this->get_form_fields(),
            'gateways'  => $this->get_gateway_fields()          
        ) );
    }

    /**
     * Register setting.
     *
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function register_settings() {
        register_setting( 'charitable_settings', 'charitable_settings', array( $this, 'sanitize_settings' ) );

        $fields = $this->get_fields();

        if ( empty( $fields ) ) {
            return;
        }

        /* Register each section */
        foreach ( $this->get_sections() as $section_key => $section ) {
            $section_id = 'charitable_settings_' . $section_key;
            
            add_settings_section(
                $section_id,
                __return_null(), 
                '__return_false', 
                $section_id
            );          

            if ( ! isset( $fields[ $section_key ] ) || empty( $fields[ $section_key ] ) ) {
                continue;
            }

            /* Sort by priority */
            $section_fields = $fields[ $section_key ];
            uasort( $section_fields, 'charitable_priority_sort' );

            /* Add the individual fields within the section */
            foreach ( $section_fields as $key => $field ) {             

                $callback       = isset( $field[ 'callback' ] ) ? $field[ 'callback' ] : array( $this, 'render_field' );
                $field[ 'key' ] = $key;
                $field[ 'section' ] = $section_key;
                $label          = $this->get_field_label( $field, $key );                        

                add_settings_field( 
                    'charitable_settings['. $key .']', 
                    $label, 
                    $callback, 
                    $section_id, 
                    $section_id, 
                    $field 
                ); 
            }
        }
    }   

    /**
     * Return the label for the given field. 
     *
     * @return  string
     * @access  private
     * @since   1.0.0
     */
    private function get_field_label( $field, $key ) {
        if ( isset( $field[ 'label_for' ] ) ) {
            $label = $field[ 'label_for' ];
        }
        elseif ( isset( $field[ 'title' ] ) ) {
            $label = $field[ 'title' ];
        }
        else {
            $label = ucfirst( $key );
        }  

        return $label;
    }

    /**
     * Sanitize submitted settings before saving to the database. 
     *
     * @param   array   $values
     * @return  string
     * @access  public
     * @since   1.0.0
     */
    public function sanitize_settings( $values ) {

        $old_values = get_option( 'charitable_settings' );
        $new_values = array();
        $form_fields = $this->get_fields();

        foreach ( $values as $section => $submitted ) {

            if ( ! isset( $form_fields[ $section ] ) ) {
                continue;
            }

            $section_fields = $form_fields[ $section ];

            foreach ( $section_fields as $key => $field ) {

                $value = null;

                /* No need to save headings :) */
                if ( isset( $field[ 'type' ] ) && 'heading' == $field[ 'type' ] ) {
                    continue;
                }

                /* Checkbox fields need to be set to 0 when they're not in the submitted array */
                if ( isset( $field[ 'type' ] ) && 'checkbox' == $field[ 'type' ] ) {

                    $value = isset( $submitted[ $key ] );
                    $new_values[ $key ] = apply_filters( 'charitable_sanitize_value', $value, $field, $value, $key );

                }
                elseif ( isset( $submitted[ $key ] ) ) {

                    $value = $submitted[ $key ];
                    $new_values[ $key ] = apply_filters( 'charitable_sanitize_value', $value, $field, $values, $key );

                } 

            }

        }      

        $values = wp_parse_args( $new_values, $old_values );

        return apply_filters( 'charitable_save_settings', $values, $new_values, $old_values );
    }

    /**
     * Checkbox settings should always be either 1 or 0. 
     *
     * @param   mixed       $value     
     * @param   array       $field
     * @return  boolean
     * @access  public
     * @since   1.0.0
     */
    public function sanitize_checkbox_value( $value, $field ) {
        
        if ( isset( $field[ 'type' ] ) && 'checkbox' == $field[ 'type' ] ) {
            $value = intval( $value && 'on' == $value );            
        }

        return $value;
    }

    /**
     * Display the Charitable settings page. 
     *
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function render_charitable_settings_page() {
        charitable_admin_view( 'settings/settings' );
    }

    /**
     * Render field. This is the default callback used for all fields, unless an alternative callback has been specified. 
     *
     * @param   array       $args
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function render_field( $args ) {     
        $field_type = isset( $args[ 'type' ] ) ? $args[ 'type' ] : 'text';

        charitable_admin_view( 'settings/' . $field_type . '-field', $args );
    }

    /**
     * Display table with available payment gateways.  
     *
     * @return  void
     * @access  public
     * @since   1.0.0
     */
    public function render_gateways_table( $args ) {
        charitable_admin_view( 'settings/gateways-table', $args );
    }

    /**
     * Used by array_reduce to return an associative array with the page ID for the key and title for the value.
     *
     * @param   string[]        $result
     * @param   WP_Post         $page
     * @return  string[]        $result
     * @access  public
     * @since   1.0.0
     */
    public function filter_page( $result, $page ) {
        $result[ $page->ID ] = $page->post_title;
        return $result;
    }

    /**
     * Returns an array of all pages in the id=>title format. 
     *
     * @return  string[]
     * @access  public
     * @since   1.0.0
     */
    public function get_pages() {
        $pages = wp_cache_get( 'filtered_static_pages', 'charitable' );

        if ( false === $pages ) {
            $pages = array_reduce( get_pages(), array( $this, 'filter_page' ), array() );

            wp_cache_set( 'filtered_static_pages', $pages, 'charitable' );
        }

        return $pages;
    }   
}

endif; // End class_exists check