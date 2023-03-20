<?php
/**
 * Plugin Name: WooCommerce Stax Payment Gateway
 * Plugin URI: https://wpstriker.com/plugins
 * Description: Stax Payment Gateway for WooCommerce
 * Version: 1.0.0
 * Author: wpstriker
 * Author URI: https://wpstriker.com
 * Text Domain: wps-woocommerce-stax
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 5.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'WPS_WOOCOMMERCE_STAX_VERSION', '1.0.0' );
define( 'WPS_WOOCOMMERCE_STAX_FILE', __FILE__ );
define( 'WPS_WOOCOMMERCE_STAX_SLUG', 'wps-woocommerce-stax' );
define( 'WPS_WOOCOMMERCE_STAX_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPS_WOOCOMMERCE_STAX_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WPS_WOOCOMMERCE_STAX_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require_once WPS_WOOCOMMERCE_STAX_DIR . '/functions.php';

class WooCommerce_Stax_Payment_Gateway {

	protected static $instance;

	public function __construct() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_stax_gateway_add_to_gateways' ), 500, 1 );
		add_action( 'plugins_loaded', array( $this, 'woocommerce_stax_gateway_init' ), 15 );
	}

	public function woocommerce_stax_gateway_add_to_gateways( $methods ) {
		$methods[] = 'WooCommerce_Stax_Gateway';
		return $methods;
	}

	public function woocommerce_stax_gateway_init() {
		require_once WPS_WOOCOMMERCE_STAX_DIR . '/class-woocommerce-stax-gateway.php';
	}

	public function get_log_dir( string $handle ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/' . $handle . '-logs';
		wp_mkdir_p( $log_dir );
		return $log_dir;
	}

	public function get_log_file_name( string $handle ) {
		if ( function_exists( 'wp_hash' ) ) {
			$date_suffix = date( 'Y-m-d', time() );
			$hash_suffix = wp_hash( $handle );
			return $this->get_log_dir( $handle ) . '/' . sanitize_file_name( implode( '-', array( $handle, $date_suffix, $hash_suffix ) ) . '.log' );
		}

		return $this->get_log_dir( $handle ) . '/' . $handle . '-' . date( 'Y-m-d', time() ) . '.log';
	}

	public function log( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->debug( print_r( $message, true ), array( 'source' => WPS_WOOCOMMERCE_STAX_SLUG ) );
		} else {
			error_log( date( '[Y-m-d H:i:s e] ' ) . print_r( $message, true ) . PHP_EOL, 3, $this->get_log_file_name( WPS_WOOCOMMERCE_STAX_SLUG ) );
		}
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

function WooCommerce_Stax_Payment_Gateway() {
	return WooCommerce_Stax_Payment_Gateway::get_instance();
}

$GLOBALS[ WPS_WOOCOMMERCE_STAX_SLUG ] = WooCommerce_Stax_Payment_Gateway();
