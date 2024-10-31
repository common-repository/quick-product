<?php
/**
 * @package Quick_Product
 * @version 1.0
 */
/*
Plugin Name: Quick Product
Plugin URI: http://wordpress.org/plugins/quick-product/
Description: This Plugin enables the user to create a brand new product while adding product line items in the order. While searching for products to add line items and if the searched product is not available in the catalog, you can easily add a new product and it'll be created in the catalog and added to the order line.
Author: Crystal Paladin
Version: 1.0
*/

function add_quick_product_admin_scripts() {
	wp_register_style( 'quick_product_style', WP_PLUGIN_URL . '/quick-product/styles/customstyles.css', false, '20151127' );
	wp_register_script( 'quick_product_script', WP_PLUGIN_URL . '/quick-product/scripts/customscripts.js', array( 'jquery' ), '20151127' );
	
	wp_register_script( 'quick_product_script_metabox', WP_PLUGIN_URL . '/quick-product/scripts/quick-product-wc-admin-meta-boxes.js', array( 'jquery', 'wc-admin-meta-boxes'), '20151127');
	wp_localize_script( 'quick_product_script_metabox', 'quickProductMetaboxServerScriptData', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	
	wp_enqueue_style( 'quick_product_style' );
	wp_enqueue_script( 'quick_product_script' );
	wp_enqueue_script( 'quick_product_script_metabox' );
}

add_action( 'admin_enqueue_scripts', 'add_quick_product_admin_scripts' );

function unauthorized_service_access_notification() {
	$response = array("status"=>-1, "description"=>"Unauthorized access denied");
	wp_send_json($response);
}

function quickcreate_product() {
	require_once WP_PLUGIN_DIR . '/quick-product/common_features/modified_woocommerce_functions.php';

	$response = array("status"=>0, "description"=>"success");
	//TODO: Check user roles and validate the data
	$pdtName = sanitize_title($_POST["productname"], "deliveryproduct", "save");
	$pdtPrice = sanitize_text_field($_POST["productprice"]);
	$data = array('product' => array(
			'type'=>'simple'
		, 'title'=>$pdtName
		, 'regular_price'=>$pdtPrice
	//, 'sale_price'=>$pdtPrice
	));
	$pdt = quick_product_create_product($data);
	$response['productObject'] = $pdt;
	wp_send_json($response);
}

add_action("wp_ajax_quickcreate_product", "quickcreate_product");
add_action("wp_ajax_nopriv_quickcreate_product", "unauthorized_service_access_notification");

?>
