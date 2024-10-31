<?php
	/**
	 * Create a new product --- Copied from Woocommerce "woocommerce/includes/api" and modified a lil bit
	 *
	 * @since 2.2
	 * @param array $data posted data
	 * @return array
	 */
	function quick_product_create_product( $data ) {
		$id = 0;

		try {
			if ( ! isset( $data['product'] ) ) {
				throw new Exception("tag:woocommerce_api_missing_product_data | description: " . sprintf( __( 'No %1$s data specified to create %1$s', 'woocommerce' ), 'product' ) , 400);
			}

			$data = $data['product'];

			// Check permissions
			if ( ! current_user_can( 'publish_products' ) ) {
				throw new Exception("tag:woocommerce_api_user_cannot_create_product | description: " . __( 'You do not have permission to create products', 'woocommerce' ), 401);
			}

			// Check if product title is specified
			if ( ! isset( $data['title'] ) ) {
				throw new Exception("tag:woocommerce_api_missing_product_title | description: " . sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'title' ), 400);
			}

			// Check product type
			if ( ! isset( $data['type'] ) ) {
				$data['type'] = 'simple';
			}

			// Set visible visibility when not sent
			if ( ! isset( $data['catalog_visibility'] ) ) {
				$data['catalog_visibility'] = 'visible';
			}

			// Validate the product type
			if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ) ) ) {
				throw new Exception("tag:woocommerce_api_invalid_product_type | description: " . sprintf( __( 'Invalid product type - the product type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_product_types() ) ) ), 400);
			}

			// Enable description html tags.
			$post_content = isset( $data['description'] ) ? wc_clean( $data['description'] ) : '';
			if ( $post_content && isset( $data['enable_html_description'] ) && true === $data['enable_html_description'] ) {

				$post_content = $data['description'];
			}

			// Enable short description html tags.
			$post_excerpt = isset( $data['short_description'] ) ? wc_clean( $data['short_description'] ) : '';
			if ( $post_excerpt && isset( $data['enable_html_short_description'] ) && true === $data['enable_html_short_description'] ) {
				$post_excerpt = $data['short_description'];
			}

			$new_product = array(
				'post_title'   => wc_clean( $data['title'] ),
				'post_status'  => ( isset( $data['status'] ) ? wc_clean( $data['status'] ) : 'publish' ),
				'post_type'    => 'product',
				'post_excerpt' => ( isset( $data['short_description'] ) ? $post_excerpt : '' ),
				'post_content' => ( isset( $data['description'] ) ? $post_content : '' ),
				'post_author'  => get_current_user_id(),
			);

			// Attempts to create the new product
			$id = wp_insert_post( $new_product, true );

			// Checks for an error in the product creation
			if ( is_wp_error( $id ) ) {
				throw new Exception("tag:woocommerce_api_cannot_create_product | description: " . $id->get_error_message(), 400);
			}

			// Save product meta fields
			quick_product_save_product_meta( $id, $data );

			$product = wc_get_product( $id );
			
			return $product;
		} catch ( Exception $e ) {
			// Remove the product when fails
			// Delete product
			wp_delete_post( $product_id, true );

			return new WP_Error( $e->getCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}
	
	function quick_product_save_product_meta( $product_id, $data ) {
		global $wpdb;

		// Product Type
		$product_type = null;
		if ( isset( $data['type'] ) ) {
			$product_type = wc_clean( $data['type'] );
			wp_set_object_terms( $product_id, $product_type, 'product_type' );
		} else {
			$_product_type = get_the_terms( $product_id, 'product_type' );
			if ( is_array( $_product_type ) ) {
				$_product_type = current( $_product_type );
				$product_type  = $_product_type->slug;
			}
		}

		// Virtual
		if ( isset( $data['virtual'] ) ) {
			update_post_meta( $product_id, '_virtual', ( true === $data['virtual'] ) ? 'yes' : 'no' );
		}
		
		// Catalog Visibility
		if ( isset( $data['catalog_visibility'] ) ) {
			update_post_meta( $product_id, '_visibility', wc_clean( $data['catalog_visibility'] ) );
		}
		
		// Regular Price
			if ( isset( $data['regular_price'] ) ) {
				$regular_price = ( '' === $data['regular_price'] ) ? '' : wc_format_decimal( $data['regular_price'] );
				update_post_meta( $product_id, '_regular_price', $regular_price );
			} else {
				$regular_price = get_post_meta( $product_id, '_regular_price', true );
			}

			// Sale Price
			if ( isset( $data['sale_price'] ) ) {
				$sale_price = ( '' === $data['sale_price'] ) ? '' : wc_format_decimal( $data['sale_price'] );
				update_post_meta( $product_id, '_sale_price', $sale_price );
			} else {
				$sale_price = get_post_meta( $product_id, '_sale_price', true );
			}
			
			// Set Price values
			update_post_meta( $product_id, '_price', $regular_price );
			update_post_meta( $product_id, '_sale_price_dates_from', '' );
			update_post_meta( $product_id, '_sale_price_dates_to', '' );
			
			// Product categories
			if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
				$term_ids = array_unique( array_map( 'intval', $data['categories'] ) );
				wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
			}
			
			// Product tags
			if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
				$term_ids = array_unique( array_map( 'intval', $data['tags'] ) );
				wp_set_object_terms( $product_id, $term_ids, 'product_tag' );
			}

	}
	
?>
