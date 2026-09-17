<?php
/**
 * Renders the free-shipping progress notice on the WooCommerce checkout page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ShipThresh_Notice {

	public function __construct() {
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render_notice' ), 5 );
		add_action( 'wp_head', array( $this, 'render_styles' ) );
	}

	public function render_notice() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$settings  = ShipThresh_Settings::get_settings();
		$threshold = (float) $settings['threshold'];

		if ( $threshold <= 0 ) {
			return;
		}

		$current = (float) WC()->cart->get_subtotal();

		if ( $current >= $threshold ) {
			$message = $settings['success_message'];
			$percent = 100;
		} else {
			$remaining = $threshold - $current;
			$message   = str_replace(
				array( '{remaining}', '{threshold}' ),
				array( wc_price( $remaining ), wc_price( $threshold ) ),
				$settings['progress_message']
			);
			$percent = max( 0, min( 100, ( $current / $threshold ) * 100 ) );
		}
		?>
		<div class="shipthresh-notice">
			<p><?php echo wp_kses_post( $message ); ?></p>
			<div class="shipthresh-bar">
				<div class="shipthresh-bar-fill" style="width:<?php echo esc_attr( $percent ); ?>%;"></div>
			</div>
		</div>
		<?php
	}

	public function render_styles() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		?>
		<style>
			.shipthresh-notice {
				margin: 0 0 20px;
				padding: 12px 16px;
				background: #f7f7f7;
				border-radius: 6px;
				font-size: 14px;
			}
			.shipthresh-notice p {
				margin: 0 0 8px;
				font-weight: 600;
			}
			.shipthresh-bar {
				height: 8px;
				background: #e0e0e0;
				border-radius: 4px;
				overflow: hidden;
			}
			.shipthresh-bar-fill {
				height: 100%;
				background: #4caf50;
				transition: width 0.3s ease;
			}
		</style>
		<?php
	}
}
