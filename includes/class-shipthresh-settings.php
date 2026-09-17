<?php
/**
 * Admin settings screen: Settings → ShipThresh.
 *
 * Uses the WordPress Settings API, which handles the nonce and referrer
 * checks on submission (via settings_fields() + options.php) — reads and
 * writes are additionally gated on the manage_woocommerce capability, and
 * every stored value is sanitized before being written to wp_options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShipThresh_Settings {

	const OPTION_GROUP = 'shipthresh_options_group';
	const PAGE_SLUG     = 'shipthresh';
	const CAPABILITY    = 'manage_woocommerce';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Seed default options on activation, without clobbering existing settings.
	 */
	public static function set_defaults() {
		if ( false === get_option( SHIPTHRESH_OPTION_KEY ) ) {
			update_option( SHIPTHRESH_OPTION_KEY, self::defaults() );
		}
	}

	public static function defaults() {
		return array(
			'threshold'        => 60.00,
			'progress_message' => __( 'Spend {remaining} more to get free shipping!', 'shipthresh' ),
			'success_message'  => __( '🎉 You’ve unlocked free shipping!', 'shipthresh' ),
		);
	}

	public static function get_settings() {
		$settings = get_option( SHIPTHRESH_OPTION_KEY, array() );
		return wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
	}

	public function add_settings_page() {
		add_options_page(
			__( 'ShipThresh', 'shipthresh' ),
			__( 'ShipThresh', 'shipthresh' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			SHIPTHRESH_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'shipthresh_main',
			__( 'Free Shipping Notice', 'shipthresh' ),
			'__return_false',
			self::PAGE_SLUG
		);

		add_settings_field(
			'threshold',
			__( 'Free shipping threshold (€)', 'shipthresh' ),
			array( $this, 'render_threshold_field' ),
			self::PAGE_SLUG,
			'shipthresh_main'
		);

		add_settings_field(
			'progress_message',
			__( 'Progress message', 'shipthresh' ),
			array( $this, 'render_progress_message_field' ),
			self::PAGE_SLUG,
			'shipthresh_main'
		);

		add_settings_field(
			'success_message',
			__( 'Success message', 'shipthresh' ),
			array( $this, 'render_success_message_field' ),
			self::PAGE_SLUG,
			'shipthresh_main'
		);
	}

	/**
	 * Sanitize every field independently and fall back to the current stored
	 * value (never raw request input) for anything missing or invalid, so a
	 * malformed submission can't null out existing settings.
	 */
	public function sanitize_settings( $input ) {
		$current = self::get_settings();
		$output  = $current;

		if ( ! is_array( $input ) ) {
			return $current;
		}

		if ( isset( $input['threshold'] ) ) {
			$threshold            = floatval( str_replace( ',', '.', wp_unslash( $input['threshold'] ) ) );
			$output['threshold']  = max( 0, round( $threshold, 2 ) );
		}

		if ( isset( $input['progress_message'] ) ) {
			$progress_message = sanitize_text_field( wp_unslash( $input['progress_message'] ) );
			if ( '' !== trim( $progress_message ) ) {
				$output['progress_message'] = $progress_message;
			}
		}

		if ( isset( $input['success_message'] ) ) {
			$success_message = sanitize_text_field( wp_unslash( $input['success_message'] ) );
			if ( '' !== trim( $success_message ) ) {
				$output['success_message'] = $success_message;
			}
		}

		return $output;
	}

	public function render_threshold_field() {
		$settings = self::get_settings();
		?>
		<input
			type="number"
			step="0.01"
			min="0"
			name="<?php echo esc_attr( SHIPTHRESH_OPTION_KEY ); ?>[threshold]"
			value="<?php echo esc_attr( $settings['threshold'] ); ?>"
			class="regular-text"
		/>
		<p class="description"><?php esc_html_e( 'Order subtotal (excl. tax) a customer must reach for free shipping.', 'shipthresh' ); ?></p>
		<?php
	}

	public function render_progress_message_field() {
		$settings = self::get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr( SHIPTHRESH_OPTION_KEY ); ?>[progress_message]"
			value="<?php echo esc_attr( $settings['progress_message'] ); ?>"
			class="large-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Shown while the customer is below the threshold. Use {remaining} for the amount left to spend and {threshold} for the target amount.', 'shipthresh' ); ?>
		</p>
		<?php
	}

	public function render_success_message_field() {
		$settings = self::get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr( SHIPTHRESH_OPTION_KEY ); ?>[success_message]"
			value="<?php echo esc_attr( $settings['success_message'] ); ?>"
			class="large-text"
		/>
		<p class="description"><?php esc_html_e( 'Shown once the customer has reached the threshold.', 'shipthresh' ); ?></p>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'shipthresh' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ShipThresh Settings', 'shipthresh' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
