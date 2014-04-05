<?php
if(!class_exists('WC_ShipWorks_XML_Export')){
	class WC_ShipWorks_XML_Export{

		/**
		 * Constructor.
		 */
		function __construct(){
			add_action('admin_init', array(&$this, 'load_hooks'));
			add_action('admin_menu', array(&$this, 'shipworks_export_menu_page'));
		}

		/**
		 * Load the admin hooks
		 */
		function load_hooks(){
			if(wc_shipworks_plugin_version > '1.0.0'){
				add_action('woocommerce_order_status_completed', array(&$this, 'export_single_order'));
			}
		}

		/* 
		 * Adds a submenu page to WooCommerce menu.
		 */
		function shipworks_export_menu_page(){
			$parent_slug = 'woocommerce';
			$page_title = __('WooCommerce Export XML for ShipWorks', 'wc_shipworks');
			$menu_title = __('ShipWorks Export', 'wc_shipworks');
			$capability = 'manage_woocommerce';
			$menu_slug = 'woocommerce_shipworks_export';
			$function = 'shipworks_export_page';
			add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, array(&$this, $function));
		}

		/* 
		 * This is the page that the user comes to 
		 * export the completed orders.
		 */
		function shipworks_export_page(){
			if(!current_user_can('manage_woocommerce')){
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			if(isset($_GET['start'])){ $start = $_GET['start']; }else{ $start = ''; }
			if(isset($_GET['end'])){ $end = $_GET['end']; }else{ $end = ''; }
			echo '<div class="wrap">'.
			'<div class="icon32 icon32-woocommerce_shipworks_export"><br /></div>'.
			'<h2>'.__('WooCommerce Export XML for ShipWorks', 'wc_shipworks').'</h2>'.
			'<form method="get" action="admin.php?page=woocommerce_shipworks_export">'.
			'<p>If you don\'t select any dates, all orders will be exported.</p>'.
			'<p><label for="start_date">'.__('Start Date', 'wc_shipworks').'&nbsp;<input id="date_start" class="date-picker" type="text" name="start_date" value="'.$start.'" placeholder="'.__('From', 'wc_shipworks').'&hellip;" maxlength="10" />&nbsp;<code>YYYY-MM-DD</code></label></p>'.
			'<p><label for="end_date">'.__('End Date', 'wc_shipworks').'&nbsp;<input id="date_end" class="date-picker" type="text" name="end_date" value="'.$end.'" placeholder="'.__('To', 'wc_shipworks').'&hellip;" maxlength="10" />&nbsp;<code>YYYY-MM-DD</code></label></p>'.
			'<p>Select the order status of your orders you wish to be exporting.</p>'.
			'<p><label for="export_status"><strong>'.__('Export Status', 'wc_shipworks').':</strong>&nbsp;<input type="radio" name="export_status" value="processing"';
			if(isset($_GET['status']) && $_GET['status'] == 'processing'){ echo ' checked="checked"'; }
			echo ' />&nbsp;'.__('Processing', 'wc_shipworks').'&nbsp;&nbsp;<input type="radio" name="export_status" value="completed"';
			if(isset($_GET['status'])){ if($_GET['status'] == 'completed'){ echo ' checked="checked"'; } }else{ echo ' checked="checked"'; }
			echo ' />&nbsp;'.__('Completed', 'wc_shipworks').'</label></p>'.
			'<p class="submit">'.
			'<input class="button-primary" type="submit" name="create" value="'.__('Export Orders', 'wc_shipworks').'" />'.
			'</p>'.
			'<input type="hidden" name="export" value="1" />'.
			'</form>'.
			'</div>';
		}

		/*
		 * Export order after WooCommerce sets an order as completed.
		 */
		function export_single_order($order_id){
			global $woocommerce;

			$output = ''; // Clear output.
			$order = new WC_Order($order_id);
			// Export XML header.
			$output .= export_order_start();
			// Gets the Order ID number of the order.
			$orderID = $order->id;
			// Checks the order status.
			$orderStatus = $order->status;
			// Export order details.
			$output .= export_order_details($orderID, $orderStatus, $exportStatus);
			// Export XML footer.
			$output .= export_order_end();

			return $output;
		}

	}
	// Instantiate plugin class and add it to the set of globals.
	$GLOBALS['wc_shipworks_export'] = new WC_ShipWorks_XML_Export();
}

/*
 * If "Export Orders" has been pressed then 
 * completed orders will export an XML file.
 */
if(isset($_GET['export']) && $_GET['export'] == 1){
	export_query_order($_GET['start_date'], $_GET['end_date'], $_GET['export_status']);
}

/*
 * Export the header of the XML file.
 */
function export_order_start(){
	$output = '<?xml version="1.0" encoding="utf-8"?>'."\n";
	$output .= '<ShipWorks xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n";
	// Start of Orders.
	$output .= '<Orders>'."\n";

	return $output;
}

/*
 * Export the footer of the XML file.
 */
function export_order_end(){
	$output = '</Orders>'."\n"; // end of orders.
	$output .= '</ShipWorks>'."\n"; // end of xml export.

	return $output;
}

/**
 * All ordered items are listed for the order.
 **/
function ordered_items($orderID){
	$output = '';

	$order = new WC_Order($orderID);

	if(sizeof($order->get_items()) > 0){
		$items = $order->get_items();

		foreach($items as $item){
			if(isset($item['variation_id']) && $item['variation_id'] != '0'){
				$item_sku = get_post_meta($item['variation_id'], '_sku', true); // SKU ID Number.
				$item_weight = get_post_meta($item['variation_id'], '_weight', true); // Weight of Product.
				$code = $item['id'].'-'.$item['variation_id']; // Item Code.
				$product_id = $item['variation_id']; // Product ID.
				$variable_height = get_post_meta($item['variation_id'], '_height', true); // Product Height.
				$variable_width = get_post_meta($item['variation_id'], '_width', true); // Product Width.
				$variable_length = get_post_meta($item['variation_id'], '_length', true); // Product Length.
				$variable_size = get_post_meta($item['variation_id'], 'attribute_pa_size', true); // Product Size Name.
				$variable_colour = get_post_meta($item['variation_id'], 'attribute_pa_color', true); // Product Colour.
			}
			else{
				$item_sku = get_post_meta($item['id'], '_sku', true); // SKU ID Number.
				$code = $item['id']; // Item Code.
				$product_id = $item['id']; // Product ID.
			}
			// If variation of product is the same as a single product, return default values.
			if(empty($variable_height)){ $variable_height = get_post_meta($item['id'], '_height', true); /* Product Height. */ }
			if(empty($variable_width)){ $variable_width = get_post_meta($item['id'], '_width', true); /* Product Width. */ }
			if(empty($variable_length)){ $variable_length = get_post_meta($item['id'], '_length', true); /* Product Length. */ }
			if(empty($item_weight)){ $item_weight = get_post_meta($item['id'], '_weight', true); /* Weight of Product. */ }

			$unit_price = $item['line_total']; // Total Price of Product x Quantity.
			if($item['qty'] > 1){
				$price = get_post_meta($item['id'], '_price', true); // Price of one item.
				if(empty($price)){ $price = get_post_meta($item['id'], '_regular_price', true); } // Price of one item.
			}
			else{
				$price = $unit_price; // Price
			}

			if(empty($variable_height)){ $variable_height = ''; } // If no height was entered then set as empty.
			if(empty($variable_width)){ $variable_width = ''; } // If no width was entered then set as empty.
			if(empty($variable_length)){ $variable_length = ''; } // If no length was entered then set as empty.
			if(empty($item_weight)){ $item_weight = ''; } // If no weight was entered then set it as zero.
			if(empty($variable_size)){ $variable_size = ''; } // If no size was entered then set as empty.
			if(empty($variable_colour)){ $variable_colour = ''; } // If no colour was entered then set as empty.

			$output .= '<Item>'."\n";
			$output .= '<ItemID>O'.$orderID.'-I'.$item['id'].'</ItemID>'."\n";
			$output .= '<Code>'.$code.'</Code>'."\n";
			$output .= '<ProductID>'.$product_id.'</ProductID>'."\n";
			if(!empty($item_sku)){ $output .= '<SKU>'.$item_sku.'</SKU>'."\n"; }else{ $output .= '<SKU></SKU>'; }
			$output .= '<Name>'.$item['name'].'</Name>'."\n";
			$output .= '<Quantity>'.$item['qty'].'</Quantity>'."\n";
			$output .= '<Attributes>'."\n";
			// Each attribute on the order is listed, if any.
			if(!empty($variable_height)){
				$output .= '<Attribute>'."\n";
				$output .= '<Name>Height</Name>'."\n";
				$output .= '<Value>'.$variable_height.'</Value>'."\n";
				$output .= '</Attribute>'."\n";
			}
			if(!empty($variable_width)){
				$output .= '<Attribute>'."\n";
				$output .= '<Name>Width</Name>'."\n";
				$output .= '<Value>'.$variable_width.'</Value>'."\n";
				$output .= '</Attribute>'."\n";
			}
			if(!empty($variable_length)){
				$output .= '<Attribute>'."\n";
				$output .= '<Name>Length</Name>'."\n";
				$output .= '<Value>'.$variable_length.'</Value>'."\n";
				$output .= '</Attribute>'."\n";
			}
			if(isset($item['variation_id']) && $item['variation_id'] != '0'){
				if(!empty($variable_size)){
					$output .= '<Attribute>'."\n";
					$output .= '<Name>Size</Name>'."\n";
					$output .= '<Value>'.ucwords($variable_size).'</Value>'."\n";
					$output .= '</Attribute>'."\n";
				}
				if(!empty($variable_colour)){
					$output .= '<Attribute>'."\n";
					$output .= '<Name>Size</Name>'."\n";
					$output .= '<Value>'.ucwords($variable_colour).'</Value>'."\n";
					$output .= '</Attribute>'."\n";
				}
			}
			$output .= '</Attributes>'."\n";
			$output .= '<UnitPrice>'.$unit_price.'</UnitPrice>'."\n";
			$output .= '<UnitCost>'.$price.'</UnitCost>'."\n"; // Price of Product when purchased.
			$output .= '<Weight>'.$item_weight.'</Weight>'."\n";
			$output .= '</Item>'."\n";
		} // end for each item.
	}
	else{
		$output .= '';
	}
	return $output;
}

/*
 * Now we export the order details.
 * Order ID, Customer ID, Shipping Method, 
 * Shipping Address, Billing Address and Items.
 */
function export_order_details($orderID, $orderStatus, $exportStatus){
	global $wpdb, $woocommerce;

	/**
	 * If order is ready to ship then we fetch that order.
	 * If order is not ready then we just ignore it and go to the next.
	 */
	if(!empty($orderStatus) && $orderStatus == $exportStatus){
		$order = new WC_Order($orderID);

		$orderDate = $order->order_date; // Order Placed
		$orderPaid = $order->modified_date; // Order Paid
		$second = substr($orderDate,17,2);
		$minute = substr($orderDate,14,2);
		$hour = substr($orderDate,11,2);
		$day = substr($orderDate,8,2);
		$month = substr($orderDate,5,2);
		$year = substr($orderDate,0,4);

		$notes = $order->customer_note; // Customer Note.
		$data = $order->order_custom_fields; // Fetch all data on the order.
		$customerID = $order->customer_user; // Customer ID.
		$bill_first_name = $order->billing_first_name; // Billing First Name.
		$bill_last_name = $order->billing_last_name; // Billing Last Name.
		$bill_company = $order->billing_company; // Billing Company.
		$bill_address_one = $order->billing_address_1; // Billing Address One.
		$bill_address_two = $order->billing_address_2; // Billing Address Two.
		$bill_city = $order->billing_city; // Billing City.
		$bill_postcode = $order->billing_postcode; // Billing Postcode.
		$bill_country = $order->billing_country; // Billing Country.
		$bill_state = $order->billing_state; // Billing State.
		$bill_phone = $order->billing_phone; // Billing Phone.
		$bill_email = $order->billing_email; // Billing Email.
		$ship_first_name = $order->shipping_first_name; // Shipping First Name.
		$ship_last_name = $order->shipping_last_name; // Shipping Last Name.
		$ship_company = $order->shipping_company; // Shipping Company.
		$ship_address_one = $order->shipping_address_1; // Shipping Address One.
		$ship_address_two = $order->shipping_address_2; // Shipping Address Two.
		$ship_city = $order->shipping_city; // Shipping City.
		$ship_postcode = $order->shipping_postcode; // Shipping Postcode.
		$ship_country = $order->shipping_country; // Shipping Country.
		$ship_state = $order->shipping_state; // Shipping State.
		$ship_cost = $order->order_shipping; // Shipping Cost.
		$ship_tax = $order->order_shipping_tax; // Shipping Tax.
		$shipping_method = $order->shipping_method; // Shipping Method.
		$shipping_method_title = $order->shipping_method_title; // Shipping Method Title.
		$payment_method = $order->payment_method; // Payment Method.
		$payment_method_title = $order->payment_method_title; // Payment Method Title
		$order_discount = $order->order_discount; // Order Discount.
		$order_tax = $order->order_tax; // Order Tax.
		$order_total = $order->order_total; // Order Total.

		// Date Order Completed
		$completed_date = $order->completed_date; // Full Completed Date.
		if($completed_date){
			$completed_second = substr($completed_date,17,2); // Second.
			$completed_minute = substr($completed_date,14,2); // Minute.
			$completed_hour = substr($completed_date,11,2); // Hour.
			$completed_day = substr($completed_date,8,2); // Day.
			$completed_month = substr($completed_date,5,2); // Month.
			$completed_year = substr($completed_date,0,4); // Year.
		}

		$output = '<Order>'."\n";
		$output .= '<OrderNumber>'.$orderID.'</OrderNumber>'."\n";
		// Order Dates.
		$output .= '<OrderDate>'.$year.'-'.$month.'-'.$day.'T'.$hour.':'.$minute.':'.$second.'.000</OrderDate>'."\n";
		if($completed_date){
			$output .= '<LastModified>'.$completed_year.'-'.$completed_month.'-'.$completed_day.'T'.$completed_hour.':'.$completed_minute.':'.$completed_second.'.000</LastModified>'."\n";
		}
		else{
			$output .= '<LastModified>'.$year.'-'.$month.'-'.$day.'T'.$hour.':'.$minute.':'.$second.'.000</LastModified>'."\n";
		}
		$output .= '<ShippingMethod>'.$shipping_method_title.'</ShippingMethod>'."\n";
		$output .= '<StatusCode>New Order</StatusCode>'."\n";
		$output .= '<CustomerID>'.$customerID.'</CustomerID>'."\n";
		// Notes.
		$output .= '<Notes>'."\n";
		$output .= '<Note>'.$notes.'</Note>'."\n";
		$output .= '</Notes>'."\n";
		// Shipping Address.
		$output .= '<ShippingAddress>'."\n";
		if(!empty($ship_first_name) && !empty($ship_last_name)){
			$output .= '<FullName>'.$ship_first_name.' '.$ship_last_name.'</FullName>'."\n";
		}
		else{
			if(!empty($ship_first_name) && empty($ship_last_name)){
				$output .= '<FullName>'.$ship_first_name.'</FullName>'."\n";
			}
			else if(empty($ship_first_name) && !empty($ship_last_name)){
				$output .= '<FullName>'.$ship_last_name.'</FullName>'."\n";
			}
			else if(empty($ship_first_name) && empty($ship_last_name)){
				$output .= '<FullName></FullName>'."\n";
			}
		}
		$output .= '<Company>'.$ship_company.'</Company>'."\n";
		$output .= '<Street1>'.$ship_address_one.'</Street1>'."\n";
		$output .= '<Street2>'.$ship_address_two.'</Street2>'."\n";
		$output .= '<City>'.$ship_city.'</City>'."\n";
		$output .= '<State>'.$ship_state.'</State>'."\n";
		$output .= '<PostalCode>'.$ship_postcode.'</PostalCode>'."\n";
		$output .= '<Country>'.$ship_country.'</Country>'."\n";
		$output .= '</ShippingAddress>'."\n"; // end of shipping address.
		// Billing Address.
		$output .= '<BillingAddress>'."\n";
		if(!empty($bill_first_name) && !empty($bill_last_name)){
			$output .= '<FullName>'.$bill_first_name.' '.$bill_last_name.'</FullName>'."\n";
		}
		else{
			if(!empty($bill_first_name) && empty($bill_last_name)){
				$output .= '<FullName>'.$bill_first_name.'</FullName>'."\n";
			}
			else if(empty($bill_first_name) && !empty($bill_last_name)){
				$output .= '<FullName>'.$bill_last_name.'</FullName>'."\n";
			}
			else if(empty($bill_first_name) && empty($bill_last_name)){
				$output .= '<FullName></FullName>'."\n";
			}
		}
		$output .= '<Company>'.$bill_company.'</Company>'."\n";
		$output .= '<Street1>'.$bill_address_one.'</Street1>'."\n";
		$output .= '<Street2>'.$bill_address_two.'</Street2>'."\n";
		$output .= '<City>'.$bill_city.'</City>'."\n";
		$output .= '<State>'.$bill_state.'</State>'."\n";
		$output .= '<PostalCode>'.$bill_postcode.'</PostalCode>'."\n";
		$output .= '<Country>'.$bill_country.'</Country>'."\n";
		$output .= '<Phone>'.$bill_phone.'</Phone>'."\n";
		$output .= '<Email>'.$bill_email.'</Email>'."\n";
		$output .= '</BillingAddress>'."\n"; // end of billing address.
		// Payment Method.
		$output .= '<Payment>'."\n";
		$output .= '<Method>'.$payment_method_title.'</Method>'."\n";
		$output .= '</Payment>'."\n";
		// Items
		$output .= '<Items>'."\n"; // Start of Items.
		$output .= ordered_items($orderID); // Display each product item selected for the order.
		$output .= '</Items>'."\n"; // End of items.
		// Shipping Cost Total
		$output .= '<Totals>'."\n";
		$output .= '<Total>'.$ship_cost.'</Total>'."\n";
		$output .= '</Totals>'."\n";
		$output .= '</Order>'."\n"; // end of order.
	} // end if any order.
	return $output;
}

function export_query_order($start = '', $end = '', $exportStatus = 'completed'){
	global $wpdb, $woocommerce;

	set_time_limit(0);
	$datetime = date('Y-m-d_H:i:s'); // Set date and time of export.

	$tax_query = array(array('taxonomy' => 'shop_order_status', 'field' => 'slug', 'terms' => array($exportStatus)));
	$clauses = get_tax_sql($tax_query, $wpdb->posts, 'ID');

	// Now we fetch our orders from the database.
	if(!empty($start) && !empty($end)){
		$queryOrders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts {$clauses['join']} WHERE post_type = 'shop_order' AND post_status = 'publish' AND post_date > '{$start}' AND post_date < '{$end}' {$clauses['where']} ORDER BY ID DESC");
	}
	else{
		if(!empty($start)){
			$queryOrders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts {$clauses['join']} WHERE post_type = 'shop_order' AND post_status = 'publish' AND post_date > '{$start}' {$clauses['where']} ORDER BY ID DESC");
		}
		else if(!empty($end)){
			$queryOrders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts {$clauses['join']} WHERE post_type = 'shop_order' AND post_status = 'publish' AND post_date < '{$end}' {$clauses['where']} ORDER BY ID DESC");
		}
		else{
			$queryOrders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts {$clauses['join']} WHERE post_type = 'shop_order' AND post_status = 'publish' {$clauses['where']} ORDER BY ID DESC");
		}
	}
	/**
	 * We count how many orders there are and if no orders 
	 * exists then we kill the process.
	 */
	if(count($queryOrders) == 0){
		wp_die(__('There are either no orders at all or none in the date range you have choosen. You need to have at least one order to be able to export an XML. Go back to', 'wc_shipworks')." <a href='".admin_url('admin.php?page=woocommerce_shipworks_export&amp;start='.$start.'&amp;end='.$end.'&amp;status='.$exportStatus)."'>".__('export page', 'wc_shipworks')."</a> ".__('to try again!', 'wc_shipworks'));
	}
	$output = ''; // Clear output.
	// Export XML header.
	$output .= export_order_start();
	// Display all Orders based on Export Query.
	foreach($queryOrders as $orders){
		// Gets the Order ID number for each order.
		$orderID = $orders->ID;
		$order = new WC_Order($orderID);
		// Checks the order status.
		$orderStatus = $order->status;
		// Export order details.
		$output .= export_order_details($orderID, $orderStatus, $exportStatus);
	} // end foreach order if any.
	// Export XML footer.
	$output .= export_order_end();
	// Sets the headers of document for download.
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
	header("Cache-Control: private",false);
	header("Content-Transfer-Encoding: binary");
	if( !empty($start) && !empty($end) ){
		header("Content-Disposition: attachment; filename=\"woocommerce-export-xml-for-shipworks-orders-".$exportStatus."-".$start."-".$end.".xml\"");
	}
	else{
		if( !empty($start) ){
			header("Content-Disposition: attachment; filename=\"woocommerce-export-xml-for-shipworks-orders-".$exportStatus."-".$start.".xml\"");
		}
		else{
			header("Content-Disposition: attachment; filename=\"woocommerce-export-xml-for-shipworks-orders-".$exportStatus."-".$datetime.".xml\"");
		}
	}
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header("Content-Type: text/xml");
	header("Content-Description: WooCommerce Orders Exporting XML File for ShipWorks");
	header("Content-Length: ".strlen($output).";\n");
	// export data into file and download.
	echo $output;
	// clear temp data and leave.
	flush();
	exit;
}
?>