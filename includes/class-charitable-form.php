<?php
/**
 * A base class to be extended by specific form classes.
 *
 * @package		Charitable/Classes/Charitable_Form
 * @version 	1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2014, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License  
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Charitable_Form' ) ) : 

/**
 * Charitable_Form
 *
 * @abstract
 * @since 		1.0.0
 */
abstract class Charitable_Form {

	/**
	 * Temporary, unique ID of this form. 
	 *
	 * @var 	string
	 * @access  protected
	 */
	protected $id;

	/**
	 * @var 	string
	 * @access 	protected
	 */
	protected $nonce_action = 'charitable_form';

	/**
	 * @var 	string
	 * @access 	protected
	 */
	protected $nonce_name = '_charitable_form_nonce';

	/**
	 * Form action.  
	 *
	 * @var 	string
	 * @access  protected
	 */
	protected $form_action;

	/**
	 * Errors with the form submission.
	 *
	 * @var 	array
	 * @access  protected
	 */
	protected $errors = array();

	/**
	 * Set up callbacks for actions and filters. 
	 *
	 * @return 	void
	 * @access  protected
	 * @since 	1.0.0
	 */
	protected function attach_hooks_and_filters() {
		add_action( 'charitable_form_before_fields',	array( $this, 'add_hidden_fields' ) ); 
		add_action( 'charitable_form_field', 			array( $this, 'render_field' ), 10, 4 );
		add_filter( 'charitable_form_field_increment', 	array( $this, 'increment_index' ), 10, 4 );
	}

	/**
	 * Compares the ID of the form passed by the action and the current form object to ensure they're the same. 
	 *
	 * @param 	string 		$id
	 * @return 	boolean
	 * @access  public
	 * @since 	1.0.0
	 */
	public function is_current_form( $id ) {
		return $id === $this->id;
	}

	/**
	 * Whether the given field type can use the default field template. 
	 *
	 * @param 	string 		$field_type
	 * @return 	boolean
	 * @access 	protected
	 * @since 	1.0.0
	 */
	protected function use_default_field_template( $field_type ) {
		$default_field_types = apply_filters( 'charitable_default_template_field_types', array( 
			'text', 
			'url', 
			'email', 
			'password', 
			'date'
		) );
		return in_array( $field_type, $default_field_types );
	}

	/**
	 * Adds hidden fields to the start of the donation form.	
	 *
	 * @param 	Charitable_Form 	$form
	 * @return 	void
	 * @access  public
	 * @since 	1.0.0
	 */
	public function add_hidden_fields( $form ) {
		if ( ! $form->is_current_form( $this->id ) ) {
			return false;
		}

		$this->nonce_field();	

		?>
		<input type="hidden" name="charitable_action" value="<?php echo $this->form_action ?>" />	
		<?php
	}

	/**
	 * Set how much the index should be incremented by. 
	 *
	 * @param 	int 		$index
	 * @param 	array 		$field
	 * @param 	string 		$key
	 * @param 	Charitable_Form 	$form	 
	 * @return  int
	 * @access  public
	 * @since   1.0.0
	 */
	public function increment_index( $increment, $field, $key, $form ) {
		if ( 'hidden' == $field[ 'type' ] || ( isset( $field[ 'fullwidth'] ) && $field[ 'fullwidth' ] ) ) {
			$increment = 0;
		}

		return $increment;
	}

	/**
	 * Render a form field. 
	 *
	 * @param 	array 		$field
	 * @param 	string 		$key
	 * @param 	Charitable_Form 	$form
	 * @param 	int 		$index
	 * @return 	boolean 	False if the field was not rendered. True otherwise.
	 * @access  public
	 * @since 	1.0.0
	 */
	public function render_field( $field, $key, $form, $index = 0 ) {		
		if ( ! $form->is_current_form( $this->id ) ) {
			return false;
		}

		if ( ! isset( $field[ 'type' ] ) ) {
			return false;
		}				

		$field[ 'key' ] = $key;

		/* Display template, passing the form and field objects as parameters to the view */
		$template = charitable_template( $this->get_template_name( $field ), false );

		if ( ! $template->template_file_exists() ) {
			return false;
		}

		$template->set_view_args( array(
			'form' 		=> $this, 
			'field' 	=> $field, 
			'classes'	=> $this->get_field_classes( $field, $index )
		) );
		$template->render();

		return true;
	}

	/**
	 * Return the template name used for this field. 
	 *
	 * @param 	array 		$field
	 * @return 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_template_name( $field ) {
		if ( $this->use_default_field_template( $field[ 'type' ] ) ) {
			$template_name = 'form-fields/default.php';
		}
		else {
			$template_name = 'form-fields/' . $field[ 'type' ] . '.php';
		}

		return apply_filters( 'charitable_form_field_template_name', $template_name );
	}

	/**
	 * Return classes that will be applied to the field.	
	 *
	 * @param 	array 		$field
	 * @param 	int 		$index
	 * @return 	string
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_field_classes( $field, $index = 0 ) {
		if ( 'hidden' == $field[ 'type' ] ) {
			return;
		}

		$classes = $this->get_field_type_classes( $field[ 'type' ] );

		if ( isset( $field[ 'class' ] ) ) {
			$classes[] = $field[ 'class' ];
		}		

		if ( isset( $field[ 'required' ] ) && $field[ 'required' ] ) {
			$classes[] = 'required-field';
		}

		if ( isset( $field[ 'fullwidth' ] ) && $field[ 'fullwidth' ] ) {
			$classes[] = 'fullwidth';
		} 
		elseif ( $index > 0 ) {			
			$classes[] = $index % 2 ? 'odd' : 'even';
		}

		$classes = apply_filters( 'charitable_form_field_classes', $classes, $field, $index );

		return implode( ' ', $classes );
	}

	/**
	 * Return array of classes based on the field type.  
	 *
	 * @param 	string
	 * @return  string[]
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_field_type_classes( $type ) {
		$classes = array();

		switch ( $type ) {

			case 'paragraph' : 
				$classes[] = 'charitable-form-content';
				break;

			case 'fieldset' : 
				$classes[] = 'charitable-fieldset';
				break;

			default : 
				$classes[] = 'charitable-form-field';
				$classes[] = 'charitable-form-field-' . $type;
		}

		return $classes;
	}

	/**
	 * Output the nonce. 
	 *
	 * @return 	void
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function nonce_field() {
		wp_nonce_field( $this->nonce_action, $this->nonce_name );
	}

	/** 
	 * Validate nonce data passed by the submitted form. 
	 * 
	 * @return 	boolean
	 * @access 	public
	 * @since 	1.0.0
	 */
	public function validate_nonce() {
		return isset( $_POST[$this->nonce_name] ) && wp_verify_nonce( $_POST[$this->nonce_name], $this->nonce_action );
	}	

	/**
	 * Callback method used to filter out non-required fields. 
	 *
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function filter_required_fields( $field ) {
		return isset( $field[ 'required' ] ) && true == $field[ 'required' ];
	}

	/**
	 * Filters array returning just the required fields.  
	 *
	 * @return 	array
	 * @access  public
	 * @since 	1.0.0
	 */
	public function get_required_fields( $fields = array() ) {
		$required_fields = array_filter( $fields, array( $this, 'filter_required_fields' ) );

		return $required_fields;
	}

	/**
	 * Check the passed fields to ensure that all required fields have been submitted.
	 *
	 * @return 	boolean
	 * @access  public
	 * @since 	1.0.0
	 */
	public function check_required_fields( $fields ) {
		
		$ret = true;

		foreach ( $this->get_required_fields( $fields ) as $key => $field ) {

			if ( ! isset( $_POST[ $key ] ) || empty( $_POST[ $key ] ) ) {
				
				$label = isset( $field[ 'label' ] ) ? $field[ 'label' ] : $key;

				$this->errors[] = sprintf( '%s %s', $label, _x( 'is a required field', 'field is a required field', 'charitable' ) );

				$ret = false;
			}

		}

		return $ret;
	}
}

endif; // End class_exists check