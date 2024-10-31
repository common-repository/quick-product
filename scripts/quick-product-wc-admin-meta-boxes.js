jQuery(document).ready(function(){
	setup_wc_add_products_modal_extension();
});

function setup_wc_add_products_modal_extension() {
	//if the modal is not available, then skip this step
	if(0 == jQuery("script#wc-modal-add-products").length) {
		window.console&&console.info('script#wc-modal-add-products is not available in this page so skipping setup_wc_add_products_modal_extension()');
		return;
	}
	var control_new_product_in_line = '<strong>or create new &nbsp; &nbsp;</strong><input type="text" class="newproductname" placeholder="Product Name Here"/><input type="text" class="newproductprice" placeholder="Product Price"/><a class="button button-primary create_product_insert_line_button" href="#">&nbsp;<i class="woo-product-icon"></i>&nbsp;</a>';
	//Since the backbone modal dialog is compiled on demand and not initialized on page ready / page load, we need to modify the HTML template inside the script tag
	var contents = jQuery("script#wc-modal-add-products").html();
	var updatedjQObj = jQuery('<div>'+contents+'</div>');
	updatedjQObj.find("input.wc-product-search").closest("article").append('<div class="control_new_product_in_line_group">' + control_new_product_in_line + '</div>');
	jQuery("#wc-modal-add-products").html(updatedjQObj.html());
	
	//This line will not work since the elements are not part of DOM and they are still inside the <script> tag as template string
	//jQuery("input.wc-product-search").closest("article").append("booga booga");
	
	//Register a Add new product listener to the DOM element
	jQuery("body").on("click","a.create_product_insert_line_button", function(event){
		var prod_name = jQuery(".control_new_product_in_line_group .newproductname").val();
		var prod_price = jQuery(".control_new_product_in_line_group .newproductprice").val();
		var data = { action:"quickcreate_product", productname:prod_name, productprice:prod_price };
		jQuery.post(quickProductMetaboxServerScriptData.ajaxurl, data, function(response){
			//alert(response);
			if(0 == response.status) {
				var pdtObj = response.productObject;
				add_new_product_to_selection_box(pdtObj.post.post_title, pdtObj.id);
			}
		}, "json");
		event.preventDefault();
	});
}

function add_new_product_to_selection_box(productname, productid) {
	var product_tag_html = '<li class="select2-search-choice"><div><div class="selected-option" data-id="' + productid + '">' + productname + '</div></div><a href="#" class="select2-search-choice-close" tabindex="-1"></a></li>';
	jQuery(".wc-backbone-modal-main form ul.select2-choices").prepend(product_tag_html);
	var pdtids_csv = jQuery('.wc-backbone-modal-main form input[type="hidden"]#add_item_id').val();
	var pdtArray = pdtids_csv.split(",");
	pdtArray.push(productid);
	var pdtids = pdtArray.join(",");
	jQuery('.wc-backbone-modal-main form input[type="hidden"]#add_item_id').val(pdtids);
}

