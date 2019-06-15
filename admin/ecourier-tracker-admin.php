<?php

/* eCourier API options panel in Settings */

class ECourierTracker {
	private $ecourier_tracker_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'ecourier_tracker_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'ecourier_tracker_page_init' ) );
	}

	public function ecourier_tracker_add_plugin_page() {
		add_options_page(
			'eCourier Tracker', // page_title
			'eCourier Tracker', // menu_title
			'manage_options', // capability
			'ecourier-tracker', // menu_slug
			array( $this, 'ecourier_tracker_create_admin_page' ) // function
		);
	}

	public function ecourier_tracker_create_admin_page() {
		$this->ecourier_tracker_options = get_option( 'ecourier_tracker_option_name' ); ?>

		<div class="wrap">
			<h2>eCourier Tracker</h2>
			<p>Fill out the information below for your tracker to work.</p>
			<p><b>You can get the API information from your eCourier merchant panel > Your Account > API > Parcel Tracking > Request Header</b></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'ecourier_tracker_option_group' );
					do_settings_sections( 'ecourier-tracker-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function ecourier_tracker_page_init() {
		register_setting(
			'ecourier_tracker_option_group', // option_group
			'ecourier_tracker_option_name', // option_name
			array( $this, 'ecourier_tracker_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'ecourier_tracker_setting_section', // id
			'API Information', // title
			array( $this, 'ecourier_tracker_section_info' ), // callback
			'ecourier-tracker-admin' // page
		);

		add_settings_field(
			'api_key_0', // id
			'API Key', // title
			array( $this, 'api_key_0_callback' ), // callback
			'ecourier-tracker-admin', // page
			'ecourier_tracker_setting_section' // section
		);

		add_settings_field(
			'secret_key_1', // id
			'Secret Key', // title
			array( $this, 'secret_key_1_callback' ), // callback
			'ecourier-tracker-admin', // page
			'ecourier_tracker_setting_section' // section
		);

		add_settings_field(
			'user_id_2', // id
			'User ID', // title
			array( $this, 'user_id_2_callback' ), // callback
			'ecourier-tracker-admin', // page
			'ecourier_tracker_setting_section' // section
		);
	}

	public function ecourier_tracker_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['api_key_0'] ) ) {
			$sanitary_values['api_key_0'] = sanitize_text_field( $input['api_key_0'] );
		}

		if ( isset( $input['secret_key_1'] ) ) {
			$sanitary_values['secret_key_1'] = sanitize_text_field( $input['secret_key_1'] );
		}

		if ( isset( $input['user_id_2'] ) ) {
			$sanitary_values['user_id_2'] = sanitize_text_field( $input['user_id_2'] );
		}

		return $sanitary_values;
	}

	public function ecourier_tracker_section_info() {
		
	}

	public function api_key_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="ecourier_tracker_option_name[api_key_0]" id="api_key_0" value="%s">',
			isset( $this->ecourier_tracker_options['api_key_0'] ) ? esc_attr( $this->ecourier_tracker_options['api_key_0']) : ''
		);
	}

	public function secret_key_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="ecourier_tracker_option_name[secret_key_1]" id="secret_key_1" value="%s">',
			isset( $this->ecourier_tracker_options['secret_key_1'] ) ? esc_attr( $this->ecourier_tracker_options['secret_key_1']) : ''
		);
	}

	public function user_id_2_callback() {
		printf(
			'<input class="regular-text" type="text" name="ecourier_tracker_option_name[user_id_2]" id="user_id_2" value="%s">',
			isset( $this->ecourier_tracker_options['user_id_2'] ) ? esc_attr( $this->ecourier_tracker_options['user_id_2']) : ''
		);
	}

}
if ( is_admin() )
	$ecourier_tracker = new ECourierTracker();

/* 
 * Retrieve this value with:
 * $ecourier_tracker_options = get_option( 'ecourier_tracker_option_name' ); // Array of All Options
 * $api_key_0 = $ecourier_tracker_options['api_key_0']; // API Key
 * $secret_key_1 = $ecourier_tracker_options['secret_key_1']; // Secret Key
 * $user_id_2 = $ecourier_tracker_options['user_id_2']; // User ID
 */
