<?php
/**
 * Admin View: Notice - WooCommerce missing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_slug = 'woocommerce';

if ( current_user_can( 'install_plugins' ) ) {
	$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
} else {
	$url = 'http://wordpress.org/plugins/' . $plugin_slug;
}
?>

<div class="error">
	<p><strong><?php _e( 'Stelo Disabled', 'stelo-woocommerce' ); ?></strong>: <?php printf( __( 'Stelo For WooCommerce depends on the last version of %s to work!', 'stelo-woocommerce' ), '<a href="' . esc_url( $url ) . '">' . __( 'WooCommerce', 'stelo-woocommerce' ) . '</a>' ); ?></p>
</div>
