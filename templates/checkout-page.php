<?php
	echo $this->api->createLightbox($_REQUEST['lightboxurl']);
	
	$order_id = null;
	$order = null;
	
	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
		$order_id = absint( get_query_var( 'order-pay' ) );
	} else {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	}
	
	if (!empty($order_id)) {
		$order = new WC_Order($order_id);
	}
//9542-9322

	try {
		$myfile = fopen(__DIR__."/checkout_page.txt", "a+") or die("Unable to open file!");
		fwrite($myfile, '<pre>'.print_r($_REQUEST,true).'</pre>');
		fclose($myfile);
	} catch (Exception $e) {
	}
?>

<a href="<?php echo $this->get_return_url( $order ).'&steloid='.$_REQUEST['steloid'];?>" class="button button-primary">Após o pagamento clique aqui >></a>
