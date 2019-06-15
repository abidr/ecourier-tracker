<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/abidr
 * @since             1.0.0
 * @package           Ecourier_Tracker
 *
 * @wordpress-plugin
 * Plugin Name:       eCourier Parcel Tracker for WooCommerce
 * Plugin URI:        https://github.com/abidr/ecourier-tracker
 * Description:       eCourier Parcel Tracker allows you to add a tracking service for your customer to track their parcel sent with eCourier.
 * Version:           1.0.0
 * Author:            Abidur Rahman Abid
 * Author URI:        https://github.com/abidr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecourier-tracker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ECOURIER_TRACKER_VERSION', '1.0.0' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ecourier-tracker.php';
require plugin_dir_path( __FILE__ ) . 'admin/ecourier-tracker-admin.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ecourier_tracker() {

	$plugin = new Ecourier_Tracker();
	$plugin->run();

}
run_ecourier_tracker();


add_action( 'woocommerce_view_order', 'ecourier_tracker_view_order', 10 );
 
function ecourier_tracker_view_order( $order_id ){  

$ecourier_tracker_order = wc_get_order( $order_id );
$ecourier_tracker_order_id = $ecourier_tracker_order->get_id(); 
$ecourier_tracker_order_method = $ecourier_tracker_order->get_shipping_method(); 
$ecourier_tracker_order_status = $ecourier_tracker_order->get_status();
$ecourier_tracker_options = get_option( 'ecourier_tracker_option_name' );
$ecourier_api_key = $ecourier_tracker_options['api_key_0'];
$ecourier_secret_key = $ecourier_tracker_options['secret_key_1'];
$ecourier_user_id = $ecourier_tracker_options['user_id_2'];
$ecourier_delivery_confirmation = get_post_meta($ecourier_tracker_order_id, 'ecourier_tracking_delivered_through_ecourier', true); 


$ecourier_response = wp_remote_get( 
	'https://ecourier.com.bd/apiv2/?parcel=track&product_id=' . $ecourier_tracker_order_id, 
	array( 
		'timeout' => 10, 
		'headers' => array( 
			'API_KEY' => $ecourier_api_key, 
			'API_SECRET' => $ecourier_secret_key,
			'USER_ID' => $ecourier_user_id,
		),
	));

$ecourier_body = wp_remote_retrieve_body( $ecourier_response );

$ecourier_data = json_decode( $ecourier_body, true );

$ecourier_statuses = $ecourier_data['query_data'][0]['status'];


?>
<?php if($ecourier_delivery_confirmation == true){ ?>
<br>
    <h2><?php echo __('eCourier Tracking', 'ecourier-tracker'); ?></h2>
    <?php
    if(empty($ecourier_api_key) || empty($ecourier_secret_key) || empty($ecourier_user_id)) {
		echo '<p style="color: red;"><strong>Please set up your API information first from Settings > eCourier Tracker.</strong></p>';
	} else {
	?>
    <table class="woocommerce-table shop_table gift_info">
    	<thead>
			<th>Date</th>
			<th>Status</th>
		</thead>
        <tbody>
			<?php foreach ($ecourier_statuses as $ecourier_status): ?>
				<tr>
					<td><?php echo $ecourier_status[2]; ?></td>
					<td><?php echo $ecourier_status[0]; ?></td>
				</tr>
			<?php endforeach; ?>
        </tbody>
</table>

<?php } } ?>
    
<?php }

/* Add a metabox to add CN Number */

function ecourier_tracking_get_meta( $value ) {
	global $post;

	$field = get_post_meta( $post->ID, $value, true );
	if ( ! empty( $field ) ) {
		return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
	} else {
		return false;
	}
}

function ecourier_tracking_add_meta_box() {
	add_meta_box(
		'ecourier_tracking-ecourier-tracking',
		__( 'eCourier Tracking', 'ecourier_tracking' ),
		'ecourier_tracking_html',
		'shop_order',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ecourier_tracking_add_meta_box' );

function ecourier_tracking_html( $post) {
	wp_nonce_field( '_ecourier_tracking_nonce', 'ecourier_tracking_nonce' ); ?>

	<p>Tick this box if you delivered this order through eCourier.</p>

	<p>

		<input type="checkbox" name="ecourier_tracking_delivered_through_ecourier" id="ecourier_tracking_delivered_through_ecourier" value="delivered-through-ecourier" <?php echo ( ecourier_tracking_get_meta( 'ecourier_tracking_delivered_through_ecourier' ) === 'delivered-through-ecourier' ) ? 'checked' : ''; ?>>
		<label for="ecourier_tracking_delivered_through_ecourier"><?php _e( 'Delivered through eCourier', 'ecourier_tracking' ); ?></label>	</p><?php
}

function ecourier_tracking_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['ecourier_tracking_nonce'] ) || ! wp_verify_nonce( $_POST['ecourier_tracking_nonce'], '_ecourier_tracking_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['ecourier_tracking_delivered_through_ecourier'] ) )
		update_post_meta( $post_id, 'ecourier_tracking_delivered_through_ecourier', esc_attr( $_POST['ecourier_tracking_delivered_through_ecourier'] ) );
	else
		update_post_meta( $post_id, 'ecourier_tracking_delivered_through_ecourier', null );
}
add_action( 'save_post', 'ecourier_tracking_save' );