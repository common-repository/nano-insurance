<?php
/**
 * @package NanoInsurance
 */
/*
Plugin Name: Nano Insurance
Plugin URI: https://www.nanoinsurancelimited.com/
Description: If you are frustrated about poor customer reviews or abandoned carts, you’re in luck. This application integrates into checkout offering to protect your customer’s shipping risk.
Version: 1.0.0
Author: GoBuddies
Author URI: http://www.gobuddies.tech
License: GPLv2 or later
Text Domain: nanoinsurance
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$root_url = plugins_url('', __FILE__);

function nano_insurance_get_app_url() {
  return 'https://woocommerce.nanoinsurancelimited.com';
}
require_once 'includes/order_paid_hook.php';
require_once 'includes/options_page.php';

function nano_insurance_enqueue_scripts() {
  global $root_url;
  $css_url = "{$root_url}/public/app.css";
  $js_url = "{$root_url}/public/app.js";
  wp_enqueue_style('ni-css', $css_url);
  wp_enqueue_script('ni-js', $js_url, array('jquery'), null, true);
  $page = 'other';
  if (is_cart()) {
    $page = 'cart';
  } else if (is_checkout()) {
    $page = 'checkout';
  }
  wp_localize_script('ni-js', 'NanoInsuranceSettings', array('ajaxurl' => admin_url('admin-ajax.php'), 'page' => $page));
}
add_action('wp_enqueue_scripts', 'nano_insurance_enqueue_scripts');

/*** AJAX HOOKS ***/
add_action('wp_ajax_nopriv_nano_insurance_policy', 'nano_insurance_policy');
add_action('wp_ajax_nano_insurance_policy', 'nano_insurance_policy');
function nano_insurance_policy() {
  $base_url = nano_insurance_get_app_url();
  $cart_total = WC()->cart->cart_contents_total;
  $cart_total_session = WC()->session->get('ni_cart_total');
  if ($cart_total_session && floatval($cart_total) === floatval($cart_total_session)) {
    wp_die(0);
  }
  WC()->session->set('ni_cart_total', $cart_total);
  foreach (WC()->cart->get_cart() as $cart_item) {
    $product = wc_get_product($cart_item['product_id']);
    $body['products'][] = array(
      'id' => $cart_item['product_id'],
      'name' => $product->get_name(),
      'price' => $product->get_price(),
      'categories' => strip_tags($product->get_categories()),
      'quantity' => $cart_item['quantity']
    );
  }
  if (empty($body)) {
    wp_die(-1);
  }
  $response = wp_remote_post("{$base_url}/insurance",
    array(
      'method' => 'POST',
      'timeout' => 45,
      'blocking' => true,
      'headers' => array(
        'Content-type' => 'application/json',
        'Accept' => 'application/json'
      ),
      'body' => json_encode($body),
    )
  );
  if ($response['response']['code'] === 200) {
    $body = json_decode($response['body'], true);
    $price = floatval($body['price']);
    WC()->session->set('nano_insurance_cart_fees', $price);
    wp_die(json_encode(array('price' => $price, 'cartTotal' => $cart_total)));
  }
  WC()->session->set('nano_insurance_cart_fees', 0);
  wp_die(-1);
}

add_action('wp_ajax_nano_insurance_subscribe', 'nano_insurance_subscribe');
function nano_insurance_subscribe() {
  $subscribed = isset($_POST['subscribed']) && $_POST['subscribed'] == '1' ? '1' : '0';
  update_option('nano_insurance_subscribed', $subscribed);
}

add_action('wp_ajax_nopriv_nano_insurance_is_active', 'nano_insurance_is_store_active');
add_action('wp_ajax_nano_insurance_is_active', 'nano_insurance_is_store_active');
function nano_insurance_is_store_active() {
  $base_url = nano_insurance_get_app_url();
  $url = get_bloginfo('url');
  $response = wp_remote_get("{$base_url}/store?url={$url}");
  if (is_array($response) && $response['response']['code'] === 200) {
    wp_die(true);
  } else {
    wp_die(false);
  }
}

add_action('wp_ajax_nopriv_nano_insurance_add_policy', 'nano_insurance_add_policy');
add_action('wp_ajax_nano_insurance_add_policy', 'nano_insurance_add_policy');
function nano_insurance_add_policy() {
  WC()->session->set('nano_insurance', 1);
}

add_action('wp_ajax_nopriv_nano_insurance_remove_policy', 'nano_insurance_remove_policy');
add_action('wp_ajax_nano_insurance_remove_policy', 'nano_insurance_remove_policy');
function nano_insurance_remove_policy() {
  WC()->session->set('nano_insurance', 0);
}

add_action('woocommerce_cart_calculate_fees', 'nano_insurance_woocommerce_custom_surcharge');
function nano_insurance_woocommerce_custom_surcharge() {
  $nano_insurance_cart_fees_session = floatval(WC()->session->get('nano_insurance_cart_fees'));
  $nano_insurance_session = intval(WC()->session->get('nano_insurance'));
  if ($nano_insurance_session === 1 && $nano_insurance_cart_fees_session !== 0) {
    WC()->cart->add_fee('Nano Insurance', $nano_insurance_cart_fees_session);
  }
}

/*** HTML RENDERING ***/
$options = get_option('nano_insurance_options', false);
if ($options && get_option('nano_insurance_verified', '0') !== '0' && get_option('nano_insurance_subscribed', '0') !== '0') {
  if ($options['style'] === 'modern') {
    add_action( 'wp_footer', 'nano_insurance_insure_shipment_modern', 1);
  } else if ($options['style'] === 'classic') {
    add_action( 'woocommerce_before_cart', 'nano_insurance_insure_shipment_classic' );
    add_action( 'woocommerce_before_checkout_form', 'nano_insurance_insure_shipment_classic' );
  }
}
function nano_insurance_insure_shipment_classic($checkout) {
  global $root_url;
  $options = get_option('nano_insurance_options', false);
  if ($options) {
    ?>
    <style>
      .ni-header{background-color:#<?php echo $options['mdl-head-bg-color']?>}
      .ni-header{color:#<?php echo $options['mdl-head-text-color']?>}
      .ni-nano-modal{color:#<?php echo $options['mdl-text-color']?>}
      .ni-content{border-color:#<?php echo $options['box-border-color']?>}
      .ni-btn{background-color:#<?php echo $options['buy-btn-bg-color']?>}
      .ni-btn{color:#<?php echo $options['buy-btn-text-color']?>}
      .ni-bought .ni-btn{background-color:#<?php echo $options['rm-btn-bg-color']?>}
      .ni-bought .ni-btn{color:#<?php echo $options['rm-btn-text-color']?>}
    </style>
    <?php
  }
  ?>
  <div class="ni-nano-modal" id="ni-nanoinsurance">
    <div class="ni-container">
      <div class="ni-header">
        <div class="ni-header-title">
          <span class="ni-uninsured">INSURE YOUR SHIPMENT</span>
          <span class="ni-insured">TOTAL AMOUNT</span>
        </div>
        <div class="ni-header-subtitle">
          <span class="ni-uninsured">This insurance covers you for all loss or damage to your purchased item during shipping.</span>
          <span class="ni-insured">Your shipment has been insured. Total amount includes insurance cost.</span>
        </div>
      </div>
    </div>
    <div class="ni-container ni-content-container">
      <div class="ni-content">
        <div class="ni-price-content">
          <span id="ni-currency">$</span><span id="ni-price">0.00</span>
        </div>
        <div class="ni-buy-policy">
          <a href="//www.nanoinsurancelimited.com" target="_blank" class="ni-logo" >
            <img src="<?php echo $root_url; ?>/public/logo.png" alt="nanoinsurance Limited - logo">
          </a>
          <div class="ni-checkbox">
            <input type="checkbox" id="ni-terms-conditions">
            <label for="ni-terms-conditions"><span>I have read and agree to the <a href="//www.nanoinsurancelimited.com/public/terms.pdf" target="_blank">Terms &amp; Conditions</a>.</span></label>
          </div>
          <div class="ni-btn" id="ni-buy-policy">
            <span class='ni-uninsured'>Insure shipment</span>
            <span class='ni-insured'>Remove insurance</span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
}

function nano_insurance_insure_shipment_modern() {
  global $root_url;
  $options = get_option('nano_insurance_options', false);
  if ($options) {
    ?>
    <style>
      .ni-header{background-color:#<?php echo $options['mdrn-head-bg-color']?>}
      .ni-header{color:#<?php echo $options['mdrn-head-text-color']?>}
      .ni-nano-modal{color:#<?php echo $options['mdrn-mdl-text-color']?>}
      #ni-nanoinsurance.ni-nano-modal{background-color:#<?php echo $options['mdrn-mdl-bg-color']?>}
      .ni-btn{background-color:#<?php echo $options['mdrn-buy-btn-bg-color']?>}
      .ni-btn{color:#<?php echo $options['mdnr-buy-btn-text-color']?>}
      .ni-bought .ni-btn{background-color:#<?php echo $options['mdrn-rm-btn-bg-color']?>}
      .ni-bought .ni-btn{color:#<?php echo $options['mdrn-rm-btn-text-color']?>}
    </style>
    <div class="ni-nano-modal" id="ni-nanoinsurance" data-type="modern" data-state="closed" data-position="<?php echo $options['modal-position']; ?>">
      <?php if ($options['modal-position'] != 'top') { ?>
        <div id="nano-insurance-opener">
          <div id="nano-insurance-opener-inner">
            <span id="nano-insurance-opener-label">
              <span class="ni-uninsured">Insure your shipment</span>
              <span class="ni-insured">Shipment insured</span>
            </span>
            <img src="<?php echo $root_url; ?>/public/logo.png?>" alt="NanoInsuranceLtd" id="nano-insurance-opener-logo">
          </div>
        </div>
      <?php } ?>
      <div class="ni-header">
        <div class="ni-header-title">
          <span class="ni-uninsured">INSURE YOUR SHIPMENT</span>
          <span class="ni-insured">TOTAL AMOUNT</span>
        </div>
        <div class="ni-header-subtitle">
          <span class="ni-uninsured">This insurance covers you for all loss or damage to your purchased item during shipping.</span>
          <span class="ni-insured">Your shipment has been insured. Total amount includes insurance cost.</span>
        </div>
      </div>
      <div class="ni-container ni-content-container">
        <div class="ni-content">
          <div class="ni-price-content">
            <span id="ni-currency">$</span><span id="ni-price">0.00</span>
          </div>
          <div class="ni-buy-policy">
            <a href="//www.nanoinsurancelimited.com" target="_blank" class="ni-logo" >
              <img src="<?php echo $root_url; ?>/public/logo.png" alt="nanoinsurance Limited - logo">
            </a>
            <div class="ni-checkbox">
              <input type="checkbox" id="ni-terms-conditions">
              <label for="ni-terms-conditions"><span>I have read and agree to the <a href="//woocommerce.nanoinsurancelimited.com/public/terms.pdf" target="_blank">Terms &amp; Conditions</a>.</span></label>
            </div>
            <div class="ni-btn" id="ni-buy-policy">
              <span class='ni-uninsured'>Insure shipment</span>
              <span class='ni-insured'>Remove insurance</span>
            </div>
          </div>
        </div>
      </div>
      <?php if ($options['modal-position'] == 'top') { ?>
        <div id="nano-insurance-opener">
          <div id="nano-insurance-opener-inner">
            <span id="nano-insurance-opener-label">
              <span class="ni-uninsured">Insure your shipment</span>
              <span class="ni-insured">Shipment insured</span>
            </span>
            <img src="<?php echo $root_url; ?>/public/logo.png?>" alt="NanoInsuranceLtd" id="nano-insurance-opener-logo">
          </div>
        </div>
      <?php } ?>
    </div>
  <?php
  }
}

add_action('wp_ajax_nano_insurance_verify', 'nano_insurance_verify');
function nano_insurance_verify() {
  $base_url = nano_insurance_get_app_url();
  $path = wp_upload_dir();
  $code = $_POST['verificationCode'];
  $key = $_POST['apiKey'];
  if (empty($code) || empty($key)) {
    wp_die(2);
  }
  $return = file_put_contents($path['path'].'/nano-verification.txt', $code);
  $body = array(
    'api_key' => $key,
    'code_url' => $path['url'].'/nano-verification.txt'
  );
  $response = wp_remote_post("{$base_url}/verify",
    array(
      'method' => 'POST',
      'timeout' => 45,
      'blocking' => true,
      'headers' => array(
        'Content-type' => 'application/json',
        'Accept' => 'application/json'
      ),
      'body' => json_encode($body),
    )
  );
  if ($response['response']['code'] === 200) {
    add_option('nano_insurance_verified', '1');
    wp_die(1);
  } else if ($response['response']['code'] === 401) {
    delete_option('nano_insurance_verified');
  }
  wp_die(0);
}