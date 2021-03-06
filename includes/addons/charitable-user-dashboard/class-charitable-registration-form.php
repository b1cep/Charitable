<?php
/**
 * Class that manages the display and processing of the registration form.
 *
 * @package     Charitable/Classes/Charitable_Registration_Form
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Charitable_Registration_Form' ) ) : 

/**
 * Charitable_Registration_Form
 *
 * @since       1.0.0
 */
class Charitable_Registration_Form extends Charitable_Form {

    /**
     * Shortcode parameters. 
     *
     * @var     array
     * @access  protected
     */
    protected $shortcode_args;

    /**
     * @var     string
     */
    protected $nonce_action = 'charitable_user_registration';

    /**
     * @var     string
     */
    protected $nonce_name = '_charitable_user_registration_nonce';

    /**
     * Action to be executed upon form submission. 
     *
     * @var     string
     * @access  protected
     */
    protected $form_action = 'save_registration';

    /**
     * The current donor. 
     *
     * @var     Charitable_Donor
     * @access  protected
     */
    protected $donor;

    /**
     * Create class object.
     * 
     * @param   array       $args       User-defined shortcode attributes.
     * @access  public
     * @since   1.0.0
     */
    public function __construct( $args = array() ) {    
        $this->id = uniqid();   
        $this->shortcode_args = $args;      
        $this->attach_hooks_and_filters();  
    }

    /**
     * Profile fields to be displayed.      
     *
     * @return  array
     * @access  public
     * @since   1.0.0
     */
    public function get_fields() {
            
        $fields = apply_filters( 'charitable_user_registration_fields', array(            
            'user_email' => array(
                'label'     => __( 'Email', 'charitable' ), 
                'type'      => 'email',
                'required'  => true, 
                'priority'  => 4
            ),
            'user_login' => array( 
                'label'     => __( 'Username', 'charitable' ),                 
                'type'      => 'text', 
                'priority'  => 6, 
                'required'  => true
            ),
            'user_pass' => array(
                'label'     => __( 'Password', 'charitable' ),              
                'type'      => 'password', 
                'priority'  => 8, 
                'required'  => false
            )
        ) );        

        uasort( $fields, 'charitable_priority_sort' );

        return $fields;
    }

    /**
     * Update registration after form submission. 
     *
     * @return  void
     * @access  public
     * @static
     * @since   1.0.0
     */
    public static function save_registration() {
        
        $form = new Charitable_Registration_Form();

        if ( ! $form->validate_nonce() ) {
            return;
        }

        $fields = $form->get_fields();

        $valid = $form->check_required_fields( $fields );    

        if ( $valid ) {
                        
            $submitted = $_POST;
        
            unset( $submitted[ 'coppa' ], $submitted[ 'user_confirmation' ] );

            $user = new Charitable_User();
            $user->save( $submitted, array_keys( $fields ) );

        }   
        else {

            /**
             * @todo Send error to say that some required fields are missing. 
             */
            
        }   
    }
}

endif; // End class_exists check