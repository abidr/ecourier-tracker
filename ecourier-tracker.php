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
$ecourier_tracker_options = get_option( 'ecourier_tracker_option_name' );
$ecourier_delivery_confirmation = get_post_meta($ecourier_tracker_order_id, 'ecourier_tracking_delivered_through_ecourier', true); 
$ecourier_api_key = $ecourier_tracker_options['api_key_0'];
$ecourier_secret_key = $ecourier_tracker_options['secret_key_1'];
$ecourier_user_id = $ecourier_tracker_options['user_id_2'];

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
<?php if($ecourier_data['query_data'] == 'No Data Found'){} else { ?>
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

	<p>Click Parcel Insert/View to insert parcel info or track delivery status. </p>

	<?php add_thickbox(); ?>

	<a href="#TB_inline?width=800&height=650&inlineId=ecourier_parcel_insert" class="thickbox">Parcel Insert/View</a>

	<?php
	add_filter('admin_footer','ecourier_parcel_insertion_form');

	function ecourier_parcel_insertion_form(){
	    global $pagenow,$typenow;   
	    if (!in_array( $pagenow, array( 'post.php', 'post-new.php' )))
	        return;
	    global $post;
	    $ecourier_order_id = $post->ID;
	    $ecourier_order = wc_get_order($ecourier_order_id);

		$ecourier_rec_name = $ecourier_order->get_shipping_first_name() . ' ' . $ecourier_order->get_shipping_last_name();
		$ecourier_rec_mobile = $ecourier_order->get_billing_phone();
		$ecourier_rec_state = 'Dhaka';
		$ecourier_rec_city = $ecourier_order->get_shipping_city();
		$ecourier_rec_address = $ecourier_order->get_shipping_address_1() . ' ' . $ecourier_order->get_shipping_address_2();
		$ecourier_price_total = $ecourier_order->get_total();
		$ecourier_order_id = $ecourier_order->get_id();
		$ecourier_payment_method = $ecourier_order->get_payment_method();
		$ecourier_order_status = $ecourier_order->get_status();

		$ecourier_tracker_options = get_option( 'ecourier_tracker_option_name' );
		$ecourier_api_key = $ecourier_tracker_options['api_key_0'];
		$ecourier_secret_key = $ecourier_tracker_options['secret_key_1'];
		$ecourier_user_id = $ecourier_tracker_options['user_id_2'];
	?>
	<div id="ecourier_parcel_insert" style="display:none;">

	    <form method="POST" action="<?php echo plugin_dir_url( __FILE__ ); ?>ecourier-submit.php" id="ecourier-parcel-insert-submit">
	    <?php 

		$ecourier_admin_response = wp_remote_get( 
			'https://ecourier.com.bd/apiv2/?parcel=track&product_id=' . $ecourier_order_id,
			array( 
				'timeout' => 10, 
				'headers' => array( 
					'API_KEY' => $ecourier_api_key, 
					'API_SECRET' => $ecourier_secret_key,
					'USER_ID' => $ecourier_user_id,
				),
			));

		$ecourier_admin_body = wp_remote_retrieve_body( $ecourier_admin_response );

		$ecourier_admin_data = json_decode( $ecourier_admin_body, true );

		$ecourier_admin_statuses = $ecourier_admin_data['query_data'][0]['status'];

		?>

		<?php 
		if($ecourier_admin_data['query_data'] == 'No Data Found'){ ?>

		
			<p>
			<label for="recipient_name">Recipient Name</label><br>
	    	<input style="width: 100%;" type="text" name="recipient_name" id="recipient_name" value="<?php echo $ecourier_rec_name; ?>"><br>
	    	</p>

			<p>
	    	<label for="recipient_mobile">Recipient Mobile</label><br>
	    	<input style="width: 100%;" type="text" name="recipient_mobile" id="recipient_mobile" value="<?php echo $ecourier_rec_mobile; ?>"><br>
	    	</p>

			<p>
	    	<label for="recipient_city">Recipient City</label><br>
	    	<input style="width: 100%;" type="text" name="recipient_city" id="recipient_city" value="<?php echo $ecourier_rec_state; ?>"><br>
	    	</p>
	    	
			<p>
	    	<label for="recipient_area">Recipient Area</label><br>
	    	<input style="width: 100%;" type="text" name="recipient_area" id="recipient_area" value="<?php echo $ecourier_rec_city; ?>"><br>
	    	</p>
	    	
			<p>
	    	<label for="recipient_address">Recipient Address</label><br>
	    	<input style="width: 100%;" type="text" name="recipient_address" id="recipient_address" value="<?php echo $ecourier_rec_address; ?>"><br>
	    	</p>
	    	
			<p>
	    	<label for="product_price">Product Price</label><br>
	    	<input style="width: 100%;" type="text" name="product_price" id="product_price" value="<?php echo $ecourier_price_total; ?>"><br>
	    	</p>
			
			<?php 
			if($ecourier_payment_method == 'cod'){
			?>			
	    	<input style="width: 100%;" type="hidden" name="payment_method" id="payment_method" value="COD">			
			<?php
			} else {
			?>
	    	<input style="width: 100%;" type="hidden" name="payment_method" id="payment_method" value="MPAY">			
			<?php }	?>

	    	<input style="width: 100%;" type="hidden" name="product_id" id="product_id" value="<?php echo $ecourier_order_id; ?>">
	    	<input style="width: 100%;" type="hidden" name="ecourier_api_key" id="ecourier_api_key" value="<?php echo $ecourier_api_key; ?>">
	    	<input style="width: 100%;" type="hidden" name="ecourier_secret_key" id="ecourier_secret_key" value="<?php echo $ecourier_secret_key; ?>">
	    	<input style="width: 100%;" type="hidden" name="ecourier_user_id" id="ecourier_user_id" value="<?php echo $ecourier_user_id; ?>">

	    	<input class="button button-primary ecourier-submit-btn" type="submit">

	    	<div id="ecourier-message"></div>

	    <?php 
		} else { ?>
			<h2>Parcel Already Inserted</h2>
			<table class="woocommerce-table shop_table gift_info">
		    	<thead>
					<th>Date</th>
					<th>Status</th>
					<th>Notes</th>
				</thead>
		        <tbody>
					<?php foreach ($ecourier_admin_statuses as $ecourier_admin_status): ?>
						<tr>
							<td><?php echo $ecourier_admin_status[2]; ?></td>
							<td><?php echo $ecourier_admin_status[0]; ?></td>
							<td><?php echo $ecourier_admin_status[1]; ?></td>
						</tr>
					<?php 
					if($ecourier_order_status == 'completed') {
						echo '';
					} else {
						if (strpos($ecourier_admin_status[0], 'Delivered') !== false) {
							$ecourier_order->update_status( 'completed' );
						} else {
							echo '';
						}
					}
					?>
					<?php endforeach; ?>
		        </tbody>
			</table>
		<?php }
		?>
	    </form>
	</div>
	<?php
	}
}
add_action('admin_enqueue_scripts', 'ecourier_tracker_js' );

function ecourier_tracker_js(){ 
	wp_enqueue_script('ecourier-tracker', plugin_dir_url( __FILE__ ) . 'assets/ecourier-tracker.js', array('jquery'), '1.0', false);
};


