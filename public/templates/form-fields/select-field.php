<?php
/**
 * The template used to display select form fields.
 *
 * @author 	Studio 164a
 * @since 	1.0.0
 * @version 1.0.0
 */

if ( ! isset( $view_args[ 'form' ] ) || ! isset( $view_args[ 'field' ] ) ) {
	return;
}

$form 			= $view_args[ 'form' ];
$field 			= $view_args[ 'field' ];
$is_required 	= isset( $field[ 'required' ] ) 	? $field[ 'required' ] 		: false;
$options		= isset( $field[ 'options' ] ) 		? $field[ 'options' ] 		: array();
$value			= isset( $field[ 'value' ] ) 		? $field[ 'value' ] 		: '';

if ( count( $options ) ) : 

?>
<div id="charitable_field_<?php echo $field['key'] ?>" class="charitable-form-field <?php if ( $is_required ) echo 'required-field' ?>">
	<?php if ( isset( $field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo $field['key'] ?>">
			<?php echo $field['label'] ?>
			<?php if ( $is_required ) : ?>
				<abbr class="required" title="required">*</abbr>
			<?php endif ?>
		</label>
	<?php endif ?>
	<select name="<?php echo $field['key'] ?>">
		<?php 

		foreach ( $options as $val => $label ) :
			if ( is_array( $label ) ) : ?>
				
				<optgroup>
				
				<?php foreach( $label as $val => $label ) : ?>

					<option value="<?php echo $val ?>" <?php selected( $val, $value ) ?>><?php echo $label ?></option>

				<?php endforeach; ?>
				
				</optgroup>
			
			<?php else : ?>

				<option value="<?php echo $val ?>" <?php selected( $val, $value ) ?>><?php echo $label ?></option> 
				
			<?php 

			endif;
		endforeach;

		?>
	</select>
</div>
<?php 

endif;