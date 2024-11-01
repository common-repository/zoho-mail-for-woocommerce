<?php

/**
*
* Plugin Name: Zoho Mail for WooCommerce
* Description: Configure your Zoho Mail account to send business emails for WooCommerce from your WordPress site.
* Version: 1.0.0
* Plugin URI: https://mail.zoho.com/
* Author: Zoho Mail
* Author URI: https://www.zoho.com/mail/
* Developer: Zoho Mail
* Developer URI: mail.zoho.com
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Requires Plugins:  woocommerce
*/

  /*
    Copyright (c) 2015, ZOHO CORPORATION
    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/



if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'ZOHOMAILWOO_PLUGIN_NAME', 'zoho-mail-for-woocommerce' );

define ('ZOHOMAILWOO_PLUGIN_VERSION', '1.0.0');

define( 'ZOHOMAILWOO_PLUGIN_NAME_BASE_DIR', plugin_dir_path( __FILE__ ) );

define( 'ZOHOMAILWOO_PLUGIN_NAME_BASE_NAME', plugin_basename( __FILE__ ) );

define( 'ZOHOMAILWOO_PLUGIN_REDIRECT_URI', admin_url('admin.php?page=zohomail-config-woo&grant_type=refresh_token') );

final class ZohoMailWooPlugin {
	public static $woocommerce_loaded = false;
	private function load_dependencies(){
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-zohomail-woocommerce.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-zohomail-woocommerce-helper.php';
	}
	public function initialize_hooks() {
		$this->load_dependencies();
		self::$woocommerce_loaded = did_action( 'woocommerce_loaded' ) > 0;

		if ( ! self::$woocommerce_loaded ) {
			return;
		}
		$instance = new ZohoMailWoo();
		$instance->initialize_hooks();
		
	}
	public function maybe_show_admin_notice() {
		if ( self::$woocommerce_loaded ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>Please enable WooCommerce plugin</p>
		</div>
		<?php
	}
	private function zohomailwoo_prepare_script(){
		wp_register_script( 'woocommerce_admin',  plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.min.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), '1.0' );

        $locale = localeconv();
        $decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';
        $params = array(
            /* translators: %s: decimal */
            'i18n_decimal_error' => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'zoho-mail-for-woocommerce' ), $decimal ),
            /* translators: %s: price decimal separator */
            'i18n_mon_decimal_error' => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'zoho-mail-for-woocommerce' ), 'a' ),
            'i18n_country_iso_error' => __( 'Please enter in country code with two capital letters.', 'zoho-mail-for-woocommerce' ),
            'i18_sale_less_than_regular_error' => __( 'Please enter in a value less than the regular price.', 'zoho-mail-for-woocommerce' ),
            'decimal_point' => $decimal,
            'mon_decimal_point' => ',',
            'strings' => array(
                'import_products' => __( 'Import', 'zoho-mail-for-woocommerce' ),
                'export_products' => __( 'Export', 'zoho-mail-for-woocommerce' ),
            ),
            'urls' => array(
                'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
                'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
            ),
        );

        wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
        wp_enqueue_script( 'woocommerce_admin' );
		wp_enqueue_script( 'zohomailwoo', plugin_dir_url( __FILE__ ) . 'admin/js/zohomail-woocommerce-admin.js', array( 'jquery' ), ZOHOMAILWOO_PLUGIN_VERSION, false );
	}
	private function zohomailwoo_prepare_styles(){
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
		wp_enqueue_style( 'zohomailwoo_style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', false, ZOHOMAILWOO_PLUGIN_VERSION );
	}
public function zohomailwoo_prepare_admin()
    {
		if ( !self::$woocommerce_loaded ) {
			return;
		}
		$this->zohomailwoo_prepare_styles();
		$this->zohomailwoo_prepare_script();
	}
}
// Add a submenu to the WooCommerce settings menu
function zohomailwoo_add_submenu() {
    add_submenu_page(
        'woocommerce',
        'Zoho Mail',
        'Zoho Mail',
        'manage_options',
        'zohomail-config-woo',
        'zohomailwoo_settings'
    );
	 
}
add_action('admin_menu', 'zohomailwoo_add_submenu');

	
	
function zohomailwoo_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

?>
	
<body>
	
<?php
require_once plugin_dir_path( __FILE__ ) . 'includes/class-zohomail-woocommerce-api.php';
if(zohomailwoo_get_instance()::$woocommerce_loaded){
	include plugin_dir_path( __FILE__ ). 'admin/zohomail-admin.php'; 
}

?>


</body>
<?php
     
}



function zohomailwoo_uninstall()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zohomail-woocommerce-helper.php';
	delete_option('zohomailwoo_client_id');
	delete_option('zohomailwoo_client_secret');
	delete_option('zohomailwoo_refresh_token');
	delete_option('zohomailwoo_access_token');
	delete_option('zohomailwoo_is_configured');
	delete_option('zohomailwoo_from_name');
	foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue){
		delete_option('zohomailwoo_from_'.$zohomailTriggerKey);
	}
	
}

register_uninstall_hook( __FILE__, 'zohomailwoo_uninstall' );



function zohomailwoo_get_instance() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new ZohoMailWooPlugin();
	}

	return $instance;
}

add_action(
	'woocommerce_loaded',
	function() {
		zohomailwoo_get_instance()->initialize_hooks();
	}
);

add_action(
	'admin_notices',
	function() {
		zohomailwoo_get_instance()->maybe_show_admin_notice();
	}
);
add_action('admin_enqueue_scripts', array(zohomailwoo_get_instance(),'zohomailwoo_prepare_admin'));
   

