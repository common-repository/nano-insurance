<?php
defined('ABSPATH') or die( 'No script kiddies please!');

add_action('woocommerce_thankyou', 'nano_insurance_order_paid', 10, 1);
function nano_insurance_order_paid($order_id) {
  $meta = get_post_meta($order_id, 'nano_insurance_order_paid', true);
  if (empty($meta)) {
    update_post_meta($order_id, 'nano_insurance_order_paid', 'paid');
  } else {
    return;
  }
  $base_url = nano_insurance_get_app_url();
  $order = wc_get_order($order_id);
  $policy_price = null;
  foreach($order->get_items('fee') as $item_id => $item_fee) {
    if ($item_fee->get_name() === 'Nano Insurance') {
      $policy_price = $item_fee->get_total();
    }
  }
  $options = get_option('nano_insurance_options'); 
  if (!empty($policy_price)) {
    $body = array(
      'products' => array(),
      'billing_address' => array(),
      'shipping_address' => array(),
      'browser_ip' => null,
      'policy_price' => $policy_price,
      'total_price' =>  null,
      'currency' => null,
      'customer_email' => null,
      'id' => $order_id,
      'shop_url' => null
    );
    $count = get_option('nano_insurance_order_count', 0);
    $count = intval($count) + 1;
    update_option('nano_insurance_order_count', $count);
    $total = get_option('nano_insurance_order_total', 0);
    $total = floatval($total) + floatval($policy_price) * 0.1;
    update_option('nano_insurance_order_total', $total);
    foreach ($order->get_items() as $item_id => $item_data) {
      $product = $item_data->get_product();
      $body['products'][] = array(
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'title' => $product->get_name(),
        'sku' => $product->get_sku(),
        'price' => "{$product->get_price()} {$order->get_currency()}",
        'quantity' => $item_data->get_quantity()
      );
    }
    $body['browser_ip'] = $order->get_customer_ip_address();
    $body['total_price'] = $order->get_total();
    $body['policy_price'] = $body['policy_price'];
    $body['currency'] = $order->get_currency();
    $body['shop_url'] = get_home_url();
    $body['customer_email'] = $order->get_billing_email();
    $body['billing_address']['first_name'] = $order->get_billing_first_name();
    $body['billing_address']['last_name']  = $order->get_billing_last_name();
    $body['billing_address']['address1'] = $order->get_billing_address_1();
    $body['billing_address']['address2'] = $order->get_billing_address_2();
    $body['billing_address']['city'] = $order->get_billing_city();
    $body['billing_address']['province_code'] = $order->get_billing_state();
    $body['billing_address']['zip'] = $order->get_billing_postcode();
    $body['billing_address']['company'] = $order->get_billing_company();
    $body['billing_address']['country_code'] = $order->get_billing_country();
    $body['billing_address']['phone'] = $order->get_billing_phone();
    $body['shipping_address']['first_name'] = $order->get_shipping_first_name();
    $body['shipping_address']['last_name']  = $order->get_shipping_last_name();
    $body['shipping_address']['address1'] = $order->get_shipping_address_1();
    $body['shipping_address']['address2'] = $order->get_shipping_address_2();
    $body['shipping_address']['city'] = $order->get_shipping_city();
    $body['shipping_address']['province_code'] = $order->get_shipping_state();
    $body['shipping_address']['zip'] = $order->get_shipping_postcode();
    $body['shipping_address']['country_code'] = $order->get_shipping_country();
    $body['shipping_address']['company'] = $order->get_shipping_company();
    $response = wp_remote_post("{$base_url}/order/created",
      array(
        'method' => 'POST',
        'timeout' => 45,
        'blocking' => true,
        'headers' => array(
          'Content-type' => 'application/json',
          'Accept' => 'application/json',
          'X-API-KEY' => $options['api-key']
        ),
        'body' => json_encode($body),
      )
    );
  }
}