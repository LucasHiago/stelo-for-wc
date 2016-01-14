<?php
ini_set ( 'display_errors', true );
error_reporting ( E_ALL );

/**
 * WC Stelo API Class.
 */
use Gpupo\SteloSdk\Factory;

class WC_Stelo_API {
	
	/**
	 * Setup data for gateway request
	 *
	 * @var array
	 */
	private $setup;
	
	
	
	/**
	 * Gateway class.
	 *
	 * @var WC_Stelo_Gateway
	 */
	protected $gateway;
	
	/**
	 * Constructor.
	 *
	 * @param WC_Stelo_Gateway $gateway        	
	 */
	public function __construct($gateway = null) {
		$this->gateway = $gateway;
		
		if (! empty ( $gateway )) {
			$this->setup = array (
					'client_id' => $gateway->client_id,
					'client_secret' => $gateway->client_secret,
					'version' => ($gateway->sandbox == 'yes') ? 'sandbox' : 'api',
					'login_version' => ($gateway->sandbox == 'yes') ? 'login.hml' : 'login',
					//'redirect_url' => $this->get_wc_request_url (),
					//'redirect_url' => 'http://www.braising.com.br',
					//'registerPath' => __DIR__
			);
			
			try {
				$myfile = fopen(__DIR__."/config.txt", "a+") or die("Unable to open file!");
				fwrite($myfile, '<pre>'.print_r($this->setup,true).'</pre>');
				fclose($myfile);
			} catch (Exception $e) {
			}
			
		} else {
			throw new Exception ( __ ( 'Gateway cant be empty', 'stelo-woocommerce' ) );
		}
	}
	
	/**
	 * Get WooCommerce return URL.
	 *
	 * @return string
	 */
	protected function get_wc_request_url() {
		global $woocommerce;
		
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			return WC ()->api_request_url ( get_class ( $this->gateway ) );
		} else {
			return $woocommerce->api_request_url ( get_class ( $this->gateway ) );
		}
	}
	
	/**
	 * Get the settings URL.
	 *
	 * @return string
	 */
	public function get_settings_url() {
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			return admin_url ( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower ( get_class ( $this->gateway ) ) );
		}
		
		return admin_url ( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . get_class ( $this->gateway ) );
	}
	
	/**
	 * Get order total.
	 *
	 * @return float
	 */
	public function get_order_total() {
		global $woocommerce;
		
		$order_total = 0;
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			$order_id = absint ( get_query_var ( 'order-pay' ) );
		} else {
			$order_id = isset ( $_GET ['order_id'] ) ? absint ( $_GET ['order_id'] ) : 0;
		}
		
		// Gets order total from "pay for order" page.
		if (0 < $order_id) {
			$order = new WC_Order ( $order_id );
			$order_total = ( float ) $order->get_total ();
			
			// Gets order total from cart/checkout.
		} elseif (0 < $woocommerce->cart->total) {
			$order_total = ( float ) $woocommerce->cart->total;
		}
		
		return $order_total;
	}
	
	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' == get_woocommerce_currency ();
	}
	
	/**
	 * Only numbers.
	 *
	 * @param string|int $string        	
	 *
	 * @return string|int
	 */
	protected function only_numbers($string) {
		return preg_replace ( '([^0-9])', '', $string );
	}
	
	/**
	 * Add error message in checkout.
	 *
	 * @param string $message
	 *        	Error message.
	 *        	
	 * @return string Displays the error message.
	 */
	public function add_error($message) {
		global $woocommerce;
		
		if (function_exists ( 'wc_add_notice' )) {
			wc_add_notice ( $message, 'error' );
		} else {
			$woocommerce->add_error ( $message );
		}
	}
	
	/**
	 * Send email notification.
	 *
	 * @param string $subject
	 *        	Email subject.
	 * @param string $title
	 *        	Email title.
	 * @param string $message
	 *        	Email message.
	 */
	public function send_email($subject, $title, $message) {
		global $woocommerce;
		
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			$mailer = WC ()->mailer ();
		} else {
			$mailer = $woocommerce->mailer ();
		}
		
		$mailer->send ( get_option ( 'admin_email' ), $subject, $mailer->wrap_message ( $title, $message ) );
	}
	
	/**
	 * Empty card.
	 */
	public function empty_card() {
		global $woocommerce;
		
		// Empty cart.
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			WC ()->cart->empty_cart ();
		} else {
			$woocommerce->cart->empty_cart ();
		}
	}
	
	/**
	 * Value in cents.
	 *
	 * @param float $value        	
	 * @return int
	 */
	protected function get_cents($value) {
		return number_format ( $value, 2, '', '' );
	}
	
	/**
	 * Get phone number
	 *
	 * @param WC_Order $order        	
	 *
	 * @return string
	 */
	protected function get_phone_number($order) {
		$phone_number = $this->only_numbers ( $order->billing_phone );
		
		return array (
				'area_code' => substr ( $phone_number, 0, 2 ),
				'number' => substr ( $phone_number, 2 ) 
		);
	}
	
	/**
	 * Get CPF or CNPJ.
	 *
	 * @param WC_Order $order        	
	 *
	 * @return string
	 */
	protected function get_cpf_cnpj($order) {
		$wcbcf_settings = get_option ( 'wcbcf_settings' );
		
		if (0 != $wcbcf_settings ['person_type']) {
			if ((1 == $wcbcf_settings ['person_type'] && 1 == $order->billing_persontype) || 2 == $wcbcf_settings ['person_type']) {
				return $this->only_numbers ( $order->billing_cpf );
			}
			
			if ((1 == $wcbcf_settings ['person_type'] && 2 == $order->billing_persontype) || 3 == $wcbcf_settings ['person_type']) {
				return $this->only_numbers ( $order->billing_cnpj );
			}
		}
		
		return '';
	}
	
	/**
	 * Get a US date Format
	 * 
	 * @param string $date
	 */
	protected function dateFormat($date){
		$date = date_create_from_format('d/m/Y', $date);
		return date_format($date, 'Y-m-d');
	}
	
	/**
	 * Get the invoice data.
	 *
	 * @param WC_Order $order        	
	 *
	 * @return array
	 */
	public function get_invoice_data($order, $posted = array()) {
		
		$shipping_cost = 0;
		
		$payment = array (
				'amount' => $this->get_order_total(),
				'freight' => $shipping_cost,
				'currency' => get_woocommerce_currency(),
				'maxInstallment' => $this->gateway->installments
		);
		
		// Shipping Cost.
		if ( method_exists( $order, 'get_total_shipping' ) ) {
			$shipping_cost = $order->get_total_shipping();
		} else {
			$shipping_cost = $order->get_shipping();
		}
		
		if ( 0 < $shipping_cost) {
			$payment['freight'] = $shipping_cost;
		}
		
		
		//cart
		$cart = array();
		
		foreach ( $order->get_items() as $order_item ) {
			if ( $order_item['qty'] ) {
				//$item_total = $this->get_cents( $order->get_item_total( $order_item, false ) );
				$item_total = $order->get_item_total( $order_item, false );
		
				if ( 0 > $item_total ) {
					continue;
				}
		
				$item_name = $order_item['name'];
				$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );
		
				if ( $meta = $item_meta->display( true, true ) ) {
					$item_name .= ' - ' . $meta;
				}
				
				$cart[] = array (
					'productName' => $item_name,
					'productQuantity' => $order_item['qty'],
					'productPrice' => $item_total,
					'productSku' => '',
				);
			}
		}
		
		// Taxes.
		if ( 0 < sizeof( $order->get_taxes() ) ) {
			foreach ( $order->get_taxes() as $tax ) {
				$tax_total = $this->get_cents( $tax['tax_amount'] + $tax['shipping_tax_amount'] );
		
				if ( 0 > $tax_total ) {
					continue;
				}
				
				$cart[] = array (
						'productName' =>  $tax['label'],
						'productQuantity' => 1,
						'productPrice' => $tax_total,
				);
			}
		}
		
		// Fees.
		if ( 0 < sizeof( $order->get_fees() ) ) {
			foreach ( $order->get_fees() as $fee ) {
				$fee_total = $this->get_cents( $fee['line_total'] );
		
				if ( 0 > $fee_total ) {
					continue;
				}
				
				$cart[] = array (
						'productName' =>  $fee['name'],
						'productQuantity' => 1,
						'productPrice' => $fee_total,
				);
			}
		}
		
		// Discount.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.3', '<' ) ) {
			if ( 0 < $order->get_order_discount() ) {
				$payment['amount'] -= $order->get_order_discount();
			}
		}
		
		$phones[] = array(
				'phoneType' => 'LANDLINE',
				'number' => $this->only_numbers ( $order->billing_phone )
		);
		
		$customer = array (
				'customerIdentity' => $this->get_cpf_cnpj($order),
				'customerName' => $order->billing_first_name . ' ' . $order->billing_last_name,
				'customerEmail' => $order->billing_email,
				'birthDate' => $this->dateFormat($order->billing_birthdate),
				'gender' => substr($order->billing_sex, 0,1),
				'phone' => $phones,
				'billingAddress' => Array
				(
						'street' => $order->billing_address_1,
						'number' => $order->billing_number,
						'complement' => $order->billing_address_2,
						'neighborhood' => $order->billing_neighborhood,
						'zipCode' => $this->only_numbers( $order->billing_postcode ),
						'city' => $order->billing_city,
						'state' => $order->billing_state,
						'country' => isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country
				),
				
				'shippingAddress' => Array
				(
						'street' => $order->billing_address_1,
						'number' => $order->billing_number,
						'complement' => $order->billing_address_2,
						'neighborhood' => $order->billing_neighborhood,
						'zipCode' => $this->only_numbers( $order->billing_postcode ),
						'city' => $order->billing_city,
						'state' => $order->billing_state,
						'country' => isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country
				)
		);
		
		$data = array (
				'id' => $order->id,
				'transactionType'	=>	'w',
				'shippingBehavior'	=>	'default',
				'changeShipment'	=>	'',
				'country'			=>	isset( WC()->countries->countries[ $order->billing_country ] ) ? WC()->countries->countries[ $order->billing_country ] : $order->billing_country,
				'payment'			=>	$payment,
				'cart'				=>	$cart,
				'customer'			=>	$customer,
		);
		
		
		$data = apply_filters ( 'stelo_woocommerce_invoice_data', $data );
		
		return $data;
	}
	
	/**
	 * Get customer ID.
	 *
	 * @param WC_Order $order
	 *        	Order data.
	 *        	
	 * @return string Customer ID.
	 */
	public function get_customer_id($order) {
		$user_id = $order->get_user_id ();
		
		// Try get a saved customer ID.
		if (0 < $user_id) {
			$customer_id = get_user_meta ( $user_id, '_stelo_customer_id', true );
			
			if ($customer_id) {
				return $customer_id;
			}
		}
		
		// Create customer in Stelo.
		$customer_id = $this->create_customer ( $order );
		
		// Save the customer ID.
		if (0 < $user_id) {
			update_user_meta ( $user_id, '_stelo_customer_id', $customer_id );
		}
		
		return $customer_id;
	}
	
	/**
	 * Process the payment.
	 *
	 * @param int $order_id        	
	 *
	 * @return array
	 */
	public function process_payment($order_id) {
		
		
		$order = new WC_Order( $order_id );
			
		$data = $this->get_invoice_data($order,$_POST);
			
		$stelo_order = $this->createOrder($data);
			
		$manager = $this->factoryManager('transaction');
			
		$transaction = $manager->createFromOrder($stelo_order);
			
		$checkoutUrl = $transaction->getCheckoutUrl();
		
		update_post_meta( $order->id, $this->gateway->id.'_transaction_data', $data );
		update_post_meta( $order->id, '_stelo_id', sanitize_text_field( $transaction->getId() ) );
		
		// Save only in old versions.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {
			update_post_meta( $order->id, __( 'Stelo Transaction details', 'stelo-woocommerce' ), sanitize_text_field( $transaction->getId() ) );
		}
		
		
		$this->empty_card();
		
		if (empty($checkoutUrl) || empty($transaction->getId())) {
			return array(
					'result'   => 'fail',
					'redirect' => ''
			);
		}
			
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url( true ).'&lightboxurl='.$checkoutUrl.'&steloid='.$transaction->getId()
			);
		} else {
			return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) ).'&lightboxurl='.$checkoutUrl.'&steloid='.$transaction->getId()
			);
		}
		
	}
	
	/**
	 * Update order status.
	 *
	 * @param int $order_id        	
	 * @param string $stelo_status        	
	 *
	 * @return bool
	 */
	protected function update_order_status($order_id, $stelo_status) {
		$order = new WC_Order ( $order_id );
		$stelo_status = strtolower ( $stelo_status );
		$order_status = $order->get_status ();
		$order_updated = false;
		
		if ('yes' == $this->gateway->debug) {
			$this->gateway->log->add ( $this->gateway->id, 'Stelo payment status for order ' . $order->get_order_number () . ' is now: ' . $stelo_status );
		}
		
		switch ($stelo_status) {
			case 'e' :
				if (! in_array ( $order_status, array (
						'on-hold',
						'processing',
						'completed' 
				) )) {
					
					$order->update_status ( 'on-hold', __ ( 'Stelo: Invoice paid by credit card, waiting for operator confirmation.', 'stelo-woocommerce' ) );
					
					$order_updated = true;
				}
				
				break;
			case 'a' :
				if (! in_array ( $order_status, array (
						'processing',
						'completed' 
				) )) {
					$order->add_order_note ( __ ( 'Stelo: Invoice paid successfully.', 'stelo-woocommerce' ) );
					
					// Changing the order for processing and reduces the stock.
					$order->payment_complete ();
					$order_updated = true;
				}
				
				break;
			case 'n' :
				$order->update_status ( 'cancelled', __ ( 'Stelo: Invoice canceled.', 'stelo-woocommerce' ) );
				$order_updated = true;
				
				break;
			case 'ne' :
				$order->update_status ( 'cancelled', __ ( 'Stelo: Invoice canceled.', 'stelo-woocommerce' ) );
				$order_updated = true;
				
				break;
			case 's' :
				$order->update_status ( 'refunded', __ ( 'Stelo: Invoice refunded.', 'stelo-woocommerce' ) );
				$this->send_email ( sprintf ( __ ( 'Invoice for order %s was refunded', 'stelo-woocommerce' ), $order->get_order_number () ), __ ( 'Invoice refunded', 'stelo-woocommerce' ), sprintf ( __ ( 'Order %s has been marked as refunded by Stelo.', 'stelo-woocommerce' ), $order->get_order_number () ) );
				$order_updated = true;
				
				break;
			case 'sp' :
				$order->update_status ( 'refunded', __ ( 'Stelo: Invoice refunded.', 'stelo-woocommerce' ) );
				$this->send_email ( sprintf ( __ ( 'Invoice for order %s was refunded', 'stelo-woocommerce' ), $order->get_order_number () ), __ ( 'Invoice refunded', 'stelo-woocommerce' ), sprintf ( __ ( 'Order %s has been marked as refunded by Stelo.', 'stelo-woocommerce' ), $order->get_order_number () ) );
				$order_updated = true;
				
				break;
			
			default :
				
				// No action xD.
				break;
		}
		
		// Allow custom actions when update the order status.
		do_action ( 'stelo_woocommerce_update_order_status', $order, $stelo_status, $order_updated );
		
		return $order_updated;
	}
	
	/**
	 * Payment notification handler.
	 */
	public function notification_handler() {
		
		ini_set('always_populate_raw_post_data', 'On');
		$postdata = file_get_contents("php://input");
		
		$myfile = fopen(__DIR__."/notificacao_post.txt", "a+") or die("Unable to open file!");
		fwrite($myfile, '<pre>--post'.print_r($postdata,true).'</pre>');
		fclose($myfile);
		
		
		@ob_clean ();
		
		if (!empty($postdata)) {
			global $wpdb;
			
			header ( 'HTTP/1.1 200 OK' );
			
			$postdata = json_decode($postdata);
			
			$stelo_id = sanitize_text_field ( $postdata->steloId );
			$order_id = $wpdb->get_var ( $wpdb->prepare ( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_stelo_id' AND meta_value = '%s'", $stelo_id ) );
			$order_id = intval ( $order_id );
			
			if ($order_id) {
				$stelo_status = $this->getTransactionStatus($stelo_id);
				$stelo_status = $stelo_status['StatusCode'];
				
				if ($stelo_status) {
					$this->update_order_status ( $order_id, $stelo_status );
					exit ();
				}
			}
		}
		
		wp_die ( __ ( 'The request failed!', 'stelo-woocommerce' ) );
	}
	
	/**
	 * 
	 * @param unknown $data
	 */
	public function createOrder($data){
		
		$steloSdk = Factory::getInstance()->setup($this->setup);
		return $steloSdk->createOrder($data);
		
	}
	
	/**
	 * 
	 * @param unknown $transaction
	 */
	public function factoryManager($transaction){
		
		$steloSdk = Factory::getInstance()->setup($this->setup);
		return $steloSdk->factoryManager($transaction);
		
	}
	
	/**
	 * 
	 * @param unknown $checkoutUrl
	 */
	public function createLightbox($checkoutUrl){
		$steloSdk = Factory::getInstance()->setup($this->setup);
		return $steloSdk->createLightbox($checkoutUrl);
	}
	
	public function getTransactionStatus($stelo_id){
		$steloSdk = Factory::getInstance()->setup($this->setup);
		$transaction = $steloSdk->factoryManager('transaction')
		->findById($stelo_id);
		
		return array(
			'StatusCode' => $transaction->getStatusCode(),
			'StatusMessage' => $transaction->getStatusMessage(),
			'Amount' => $transaction->getAmount()
		);
	}
}
