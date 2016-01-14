<?php
/**
 * Plugin Name: Stelo For WooCommerce
 * Plugin URI: https://github.com/braising/stelo-woocommerce
 * Description: Stelo payment gateway for WooCommerce.
 * Author: Stelo
 * Author URI: http://stelo.com.br/
 * Version: 1.0.6
 * License: GPLv2 or later
 * Text Domain: stelo-woocommerce
 * Domain Path: languages/
 */

ini_set('display_errors', true);
error_reporting(E_ALL);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Stelo' ) ) :

/**
 * WooCommerce Stelo main class.
 */
class WC_Stelo {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.6';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin actions.
	 */
	public function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce and WooCommerce Extra Checkout Fields for Brazil is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$this->includes();

			// Hook to add Stelo Gateway to WooCommerce.
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'dependencies_notices' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path() {
		return plugin_dir_path( __FILE__ ) . 'templates/';
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'stelo-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once 'includes/stelo-api/vendor/autoload.php';
		include_once 'includes/class-wc-stelo-api.php';
		include_once 'includes/class-wc-stelo-gateway.php';
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Stelo.
	 */
	public function add_gateway( $methods ) {
		
		$methods[] = 'WC_Stelo_Gateway';
		
		return $methods;
	}

	/**
	 * Dependencies notices.
	 */
	public function dependencies_notices() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			include_once 'templates/notices/html-notice-woocommerce-missing.php';
		}

		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			include_once 'templates/notices/html-notice-ecfb-missing.php';
		}
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	public static function get_log_view( $gateway_id ) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '>=' ) ) {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $gateway_id ) . '-' . sanitize_file_name( wp_hash( $gateway_id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'stelo-woocommerce' ) . '</a>';
		}

		return '<code>woocommerce/logs/' . esc_attr( $gateway_id ) . '-' . sanitize_file_name( wp_hash( $gateway_id ) ) . '.txt</code>';
	}

	/**
	 * Action links.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' );
		} else {
			$settings_url = admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=' );
		}

		$credit_card = 'wc_stelo_gateway';

		$plugin_links[] = '<a href="' . esc_url( $settings_url . $credit_card ) . '">' . __( 'Configurações', 'stelo-woocommerce' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}
}

add_action( 'plugins_loaded', array( 'WC_Stelo', 'get_instance' ) );

endif;
