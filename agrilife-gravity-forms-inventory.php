<?php
/**
 * AgriLife Gravity Forms Inventory
 *
 * @package      AgriLife Gravity Forms Inventory
 * @author       Zachary Watkins
 * @copyright    2019 Texas A&M AgriLife Communications
 * @license      GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:  AgriLife Gravity Forms Inventory
 * Plugin URI:   https://github.com/AgriLife/agrilife-gravity-forms-inventory
 * Description:  Gravity Forms extension to limit submissions based on the maximum value
 *               of Single Product and Number fields.
 * Version:      0.6.4
 * Author:       Zachary Watkins
 * Author URI:   https://github.com/ZachWatkins
 * Author Email: zachary.watkins@ag.tamu.edu
 * Text Domain:  af4-agrilife-org
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Define some useful constants.
define( 'AGFI_DIRNAME', 'agrilife-core' );
define( 'AGFI_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGFI_DIR_FILE', __FILE__ );

// Load class dependencies.
require_once AGFI_DIR_PATH . 'src/class-gw-notification-event.php';
require_once AGFI_DIR_PATH . 'src/class-gw-inventory.php';

if ( is_admin() ) {
	add_action( 'gform_field_standard_settings', 'agfi_custom_settings', 10, 1 );
	add_action( 'gform_editor_js', 'agfi_editor_script' );
	add_filter( 'gform_tooltips', 'agfi_add_tooltips' );
}

// Initialize functionality on certain Gravity Form field types.
foreach ( GFAPI::get_forms() as $form ) {
	foreach ( $form['fields'] as $field ) {
		if (
			$field instanceof GF_Field_SingleProduct
			|| $field instanceof GF_Field_Number
		) {
			if (
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName
				isset( $field->limitSubmissionsField )
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName
				&& true === $field->limitSubmissionsField
			) {
				if (
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName
					isset( $field->limitSubmissionsAmtField )
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName
					&& intval( $field->limitSubmissionsAmtField ) > 0
				) {
					new GW_Inventory(
						array(
							'form_id'                  => $form['id'],
							'field_id'                 => $field instanceof GF_Field_SingleProduct ? $field->id + '.3' : $field->id,
							// phpcs:ignore WordPress.NamingConventions.ValidVariableName
							'stock_qty'                => intval( $field->limitSubmissionsAmtField ),
							// phpcs:ignore WordPress.NamingConventions.ValidVariableName
							'out_of_stock_message'     => isset( $field->outOfStockMessageField ) ? $field->outOfStockMessageField : '',
							// phpcs:ignore WordPress.NamingConventions.ValidVariableName
							'not_enough_stock_message' => isset( $field->notEnoughStockMessageField ) ? $field->notEnoughStockMessageField : '',
							'approved_payments_only'   => false,
							'hide_form'                => false,
							// phpcs:ignore WordPress.NamingConventions.ValidVariableName
							'enable_notifications'     => isset( $field->enableStockNotificationField ) ? $field->enableStockNotificationField : false,
						)
					);
				}
			}
		}
	}
}

/**
 * Add custom form field options
 *
 * @since 1.0.0
 * @param int $position Position of the current form field setting.
 * @return void
 */
function agfi_custom_settings( $position ) {

	// Create settings on position 25 (right after Field Label).
	if ( 25 === $position ) {
		?>
		<li class="limit_submissions_setting field_setting">
			<label class="section_label" for="field_admin_label">
				<?php esc_html_e( 'Limit Submissions', 'gravityforms' ); ?>
				<?php gform_tooltip( 'form_field_limit_submissions_value' ); ?>
			</label>
			<ul class="limit_container">
				<li><input id="field_limit_submissions_value" type="checkbox" onclick="SetFieldProperty('limitSubmissionsField', this.checked);" onkeypress="SetFieldProperty('limitSubmissionsField', this.checked);" /> <label class="inline" for="field_limit_submissions_value">Limit Submissions</label></li>
				<li><label for="field_limit_submissions_amt_value">Limit</label><input id="field_limit_submissions_amt_value" type="text" onchange="SetFieldProperty('limitSubmissionsAmtField', this.value);" /></li>
				<li><label for="field_out_of_stock_message_value">Out Of Stock Message <?php gform_tooltip( 'form_field_out_of_stock_message_value' ); ?></label><textarea id="field_out_of_stock_message_value" class="fieldwidth-3 fieldheight-3" onblur="SetFieldProperty('outOfStockMessageField', this.value);"></textarea></li>
				<li><label for="field_not_enough_stock_message_value">Not Enough Stock Message <?php gform_tooltip( 'form_field_not_enough_stock_message_value' ); ?></label><textarea id="field_not_enough_stock_message_value" class="fieldwidth-3 fieldheight-3" onblur="SetFieldProperty('notEnoughStockMessageField', this.value);"></textarea></li>
				<li><input id="field_enable_stock_notification_value" type="checkbox" onclick="SetFieldProperty('enableStockNotificationField', this.checked);" onkeypress="SetFieldProperty('enableStockNotificationField', this.checked);" /> <label class="inline" for="field_enable_stock_notification_value">Enable Notifications <?php gform_tooltip( 'form_field_enable_stock_notification_value' ); ?></label></li>
				</ul>
		</li>
		<?php
	}
}

/**
 * Add JavaScript to initialize custom form field options.
 *
 * @since 0.1.0
 * @return void
 */
function agfi_editor_script() {
	?>
	<script type='text/javascript'>
		// To display custom field under each type of Gravity Forms field
		jQuery.each(fieldSettings, function(index, value) {
			if(value.indexOf('base_price_setting') >= 0 || value.indexOf('number_format_setting') >= 0){
				fieldSettings[index] += ", .limit_submissions_setting";
			}
		});

		//binding to the load field settings event to initialize the checkbox
		jQuery(document).on('gform_load_field_settings', function(event, field, form){
			jQuery('#field_limit_submissions_value').attr('checked', field.limitSubmissionsField == true);
			jQuery('#field_limit_submissions_amt_value').val(field.limitSubmissionsAmtField);
			jQuery('#field_out_of_stock_message_value').val(field.outOfStockMessageField);
			jQuery('#field_not_enough_stock_message_value').val(field.notEnoughStockMessageField);
			jQuery('#field_enable_stock_notification_value').attr('checked', field.enableStockNotificationField);
		});
	</script>
	<?php
}

/**
 * Add tooltips to new options which provide context.
 *
 * @since 1.0.0
 * @param array $tooltips Current tooltips in form.
 * @return array
 */
function agfi_add_tooltips( $tooltips ) {
	$tooltips['form_field_limit_submissions_value']         = '<h6>Limit Submissions</h6>Limit form submissions to a maximum value for this field.';
	$tooltips['form_field_out_of_stock_message_value']      = '<h6>Out Of Stock Message</h6>Example: Sorry, there are no more plants!';
	$tooltips['form_field_not_enough_stock_message_value']  = '<h6>Not Enough Stock Message</h6>Example: You ordered %1$s plants. There are only %2$s plants left.';
	$tooltips['form_field_enable_stock_notification_value'] = '<h6>Enable Notifications</h6>Send notifications when the this field has reached the limit among all form entries.';
	return $tooltips;
}
