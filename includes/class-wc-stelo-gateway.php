<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stelo Payment Credit Card Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Stelo_Gateway
 * @extends WC_Payment_Gateway
 * @version 1.0.0
 * @author  Stelo
 */

ini_set('display_errors', true);
error_reporting(E_ALL);

class WC_Stelo_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		global $woocommerce;

		$this->id                   = 'wc_stelo_gateway';
		$this->icon                 = apply_filters( 'woocommerce_wc_stelo_gateway_icon', '' );
		$this->method_title         = __( 'Stelo', 'stelo-woocommerce' );
		$this->method_description   = __( 'Accept payments using Stelo.', 'stelo-woocommerce' );
		$this->has_fields           = true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Optins.
		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->client_id       	= $this->get_option( 'client_id' );
		$this->client_secret    = $this->get_option( 'client_secret' );
		$this->installments     = $this->get_option( 'installments' );
		$this->sandbox          = $this->get_option( 'sandbox', 'no' );
		$this->debug            = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}
		
		
		$this->api = new WC_Stelo_API($this);
		

		// Actions.
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'notification_handler' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$api = ! empty( $this->client_id ) && ! empty( $this->client_secret );

		$available = 'yes' == $this->get_option( 'enabled' ) && $api && $this->api->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'stelo-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar Stelo', 'stelo-woocommerce' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'stelo-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'stelo-woocommerce' ),
				'desc_tip'    => true,
				'default'     => __( 'Stelo Gateway', 'stelo-woocommerce' )
			),
			'description' => array(
				'title'       => __( 'Description', 'stelo-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'stelo-woocommerce' ),
				'default'     => __( 'Pay with Stelo', 'stelo-woocommerce' )
			),	
			'integration' => array(
				'title'       => __( 'Integration Settings', 'stelo-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'client_id' => array(
				'title'             => __( 'Client ID', 'stelo-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Por favor, digite o ID da sua conta. Isto é necessário para receber pagamentos. É possível encontrar o seu ID de conta nas %s.', 'stelo-woocommerce' ), '<a href="http://www.stelo.com.br/cadastre-se/" target="_blank">' . __( 'Stelo carteira digital', 'stelo-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'client_secret' => array(
				'title'            => __( 'Client Secret', 'stelo-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Por favor, digite o seu Client Secret. Isto é necessário para receber pagamentos. É possível gerar um novo Client Secret em %s.', 'stelo-woocommerce' ), '<a href="http://www.stelo.com.br/cadastre-se/" target="_blank">' . __( 'Stelo carteira digital', 'stelo-woocommerce' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'payment' => array(
				'title'       => __( 'Payment Options', 'stelo-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'installments' => array(
				'title'             => __( 'Number of credit card Installments', 'stelo-woocommerce' ),
				'type'              => 'number',
				'description'       => __( 'The maximum number of installments allowed for credit cards. Put a number bigger than 1 to enable the field. This cannot be greater than the number allowed in your Stelo account.', 'stelo-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '1',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
					'max'  => '12'
				)
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'stelo-woocommerce' ),
				'type'        => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title'       => __( 'Stelo Sandbox', 'stelo-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Habilitar Stelo Sandbox', 'stelo-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Stelo pode ser usado em modo sandbox', 'stelo-woocommerce' ))
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'stelo-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'stelo-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log de eventos do Stelo tais como requests da API, poderam ser verificados em %s.', 'stelo-woocommerce' ), WC_Stelo::get_log_view( $this->id ) )
			)
		);
	}

	/**
	 * Output for the order received page.
	 *
	 * @param  object $order Order data.
	 *
	 * @return void
	 */
	public function receipt_page( $order ) {
		echo $this->generate_stelo_checkout( $order );
	}
	
	/**
	 * Generate the form.
	 *
	 * @param int     $order_id Order ID.
	 *
	 * @return string           Payment form.
	 */
	protected function generate_stelo_checkout( $order_id ) {
	
		$order = new WC_Order( $order_id );
	
		if ( 'yes' == $this->debug ) {
			$this->log->add( 'stelo-woocommerce', 'Stelo checkout for order ' . $order->get_order_number() );
		}
	
		ob_start();
		include plugin_dir_path( dirname( __FILE__ ) ) . 'templates/checkout-page.php';
		$html = ob_get_clean();
	
		return $html;
	}
	
	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array         Redirect.
	 */
	public function process_payment( $order_id ) {
		
		if ( empty($_POST) ) {
			$order = new WC_Order( $order_id );
		
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Error doing the charge for order ' . $order->get_order_number() . ': Missing the "POST".' );
			}
		
			$this->api->add_error( '<strong>' . esc_attr( $this->title ) . '</strong>: ' . __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'stelo-woocommerce' ) );
		
			return array(
					'result'   => 'fail',
					'redirect' => ''
			);
		}
		
		return $this->api->process_payment($order_id);
			
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page( $order_id ) {
		$order        = new WC_Order( $order_id );
		$order_status = $order->get_status();
	}
	
	/**
	 * Notification handler.
	 */
	public function notification_handler() {
		
		ini_set('always_populate_raw_post_data', 'On');
		$postdata = file_get_contents("php://input");
		
		$myfile = fopen(__DIR__."/notificacao_0.txt", "a+") or die("Unable to open file!");
		fwrite($myfile, '<pre>--´post'.print_r($_POST,true).'</pre>');
		fclose($myfile);
		$myfile = fopen(__DIR__."/notificacao_0.txt", "a+") or die("Unable to open file!");
		fwrite($myfile, '<pre>--request'.print_r($_REQUEST,true).'</pre>');
		fclose($myfile);
		$myfile = fopen(__DIR__."/notificacao_0.txt", "a+") or die("Unable to open file!");
		fwrite($myfile, '<pre>--post raw'.print_r($postdata,true).'</pre>');
		fclose($myfile);
		
		 try {
			$this->api->notification_handler();
			
		} catch (Exception $e) {
			
			$myfile = fopen(__DIR__."/notificacao_error.txt", "a+") or die("Unable to open file!");
			fwrite($myfile, '<pre>--post'.print_r($e->getMessage(),true).'</pre>');
			fclose($myfile);
		}
	}

}
