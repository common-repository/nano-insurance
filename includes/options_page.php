<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action('admin_enqueue_scripts', 'nano_insurance_admin_enqueue_scripts');
function nano_insurance_admin_enqueue_scripts($hook) {
  if ('settings_page_nano-insurance' != $hook) {
    return;
  }
  global $root_url;
  wp_enqueue_script('ni-stripe', 'https://js.stripe.com/v3/', array(), null, true);
  wp_enqueue_script('ni-js-color', $root_url.'/public/jscolor.js', array(), null, true);
  wp_enqueue_script('ni-admin-js', $root_url.'/public/admin.js', array('jquery'), null, true);
  wp_enqueue_style('ni-bulma-css', $root_url.'/public/bulma.min.css');
  wp_enqueue_style('ni-admin-css', $root_url.'/public/admin.css');
}

add_action('admin_menu', 'nano_insurance_admin_add_page');
function nano_insurance_admin_add_page() {
  add_options_page('Nano Insurance', 'Nano Insurance', 'manage_options', 'nano-insurance', 'nano_insurance_options_page');
}

add_action('admin_init', 'nano_insurance_admin_init');
function nano_insurance_admin_init() {
  register_setting( 'nano_insurance_options', 'nano_insurance_options');
  add_settings_section('nano_insurance_section', '', 'nano_insurance_section_cb', 'nano_insurance');
}

function nano_insurance_section_cb() {
  echo '';
}

function nano_insurance_options_page() {
  global $root_url;
  $options = get_option('nano_insurance_options', false);
  $mdl_head_bg_color = $mdrn_head_bg_color = '2e2e36';
  $mdl_head_text_color = $mdrn_head_txt_color = 'fff';
  $mdl_txt_color = $mdrn_txt_clr = '2e2e36';
  $box_border_color = 'f1f2f4';
  $mdrn_mdl_bg_color = 'fff';
  $buy_btn_bg_color = $mdrn_buy_btn_bg_color = '0c586e';
  $buy_btn_txt_color = $mdrn_buy_btn_txt_color = 'fff';
  $rm_btn_bg_color = $mdrn_rm_btn_bg_color = '2e2e36';
  $rm_btn_txt_color = $mdrn_rm_btn_txt_color = 'fff';
  $mdl_position = 'bottom';
  $key = null;
  $code = null;
  $count = get_option('nano_insurance_order_count', 0);
  $total = get_option('nano_insurance_order_total', 0);
  $type = isset($options['style']) ? $options['style'] : 'classic';
  if ($options) {
    $mdl_head_bg_color = $options['mdl-head-bg-color'];
    $mdl_head_text_color = $options['mdl-head-text-color'];
    $mdl_txt_color = $options['mdl-text-color'];
    $box_border_color = $options['box-border-color'];
    $buy_btn_bg_color = $options['buy-btn-bg-color'];
    $buy_btn_txt_color = $options['buy-btn-text-color'];
    $rm_btn_bg_color = $options['rm-btn-bg-color'];
    $rm_btn_txt_color = $options['rm-btn-text-color'];
    $mdrn_mdl_bg_color = $options['mdrn-mdl-bg-color'];
    $mdrn_head_bg_color = $options['mdrn-head-bg-color'];
    $mdrn_txt_clr = $options['mdrn-mdl-text-color'];
    $mdrn_head_txt_color = $options['mdrn-head-txt-color'];
    $mdrn_buy_btn_bg_color = $options['mdrn-buy-btn-bg-color'];
    $mdrn_buy_btn_txt_color = $options['mdrn-buy-btn-text-color'];
    $mdrn_rm_btn_bg_color = $options['mdrn-rm-btn-bg-color'];
    $mdrn_rm_btn_txt_color = $options['mdrn-rm-btn-text-color'];
    $mdl_position = $options['modal-position'];
    $key = $options['api-key'];
    $code = $options['code'];
  }
  $app_url = nano_insurance_get_app_url();
  ?>
    <div id="ni-container" class="container no-tb-padding">
      <div class="section">
        <nav class="level">
          <div class="level-left">
            <div class="level-item mobile-centered">
              <div>
                <img src="<?php echo $root_url; ?>/public/logo.png" alt="logo" id="ni-logo" class="ni-heading"/>
              </div>
            </div>
          </div>
          <div class="level-right">
            <div class="level-item">
              <button class="button is-link" id="ni-save" disabled>Save</button>
            </div>
          </div>
        </nav>
      </div>
      <div class="tabs">
        <ul>
          <li class="is-active" data-tab="ni-settings-form"><a>Settings</a></li>
          <li data-tab="ni-payment-form"><a>Credit Card Details</a></li>
          <!-- <li data-tab="ni-api-form"><a>API & Verification Code</a></li> -->
        </ul>
      </div>
      <form action="options.php" method="post" id="ni-settings-form" class="ni-active ni-tab" data-style-type="<?php echo $type; ?>">
        <?php settings_fields('nano_insurance_options'); ?>
        <div class="no-tb-padding">  
          <div class="section has-bottom-border"> 
            <div class="columns no-lr-padding">
              <div class="column is-one-third"><p class="title is-6">Stats</p></div>
              <div class="column is-two-thirds box">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <p>
                      <b><?php if ($count == 0) { echo 'No'; } else { echo $count.' '; }; ?></b> <?php if ($count == 1) { echo 'shipment has'; } else { echo 'shipments have'; }; ?> been insured. 
                      <br/> <?php if ($count !== 0) { echo 'Value of your earnings is: <b>'.$total.' USD.</b></p>'; } ?>
                  </div>
                </div>           
              </div>
            </div>
          </div>
          <div class="section ni-styles has-bottom-border">
            <div class="columns">
              <div class="column is-one-third">
                <p class="title is-6">API Key & Verification Code</p>
                <p>Verification is required on installation and every time new API key is entered.</p>
              </div>
              <div class="column is-two-thirds box">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="api-key" class="is-block">API Key</label>
                    <input id="api-key" class="is-fullwidth" type="<?php if (empty($key)) { echo "text"; } else { echo "password"; }?>" value="<?php echo $key;?>" name="nano_insurance_options[api-key]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="code" class="is-block">Verification Code</label>
                    <input id="code" class="is-fullwidth" type="text" value="<?php echo $code; ?>" name="nano_insurance_options[code]"/>
                  </div>
                  <div class="tile is-child">
                    <button id="ni-verify" style="margin-bottom:10px" class="button is-primary">VERIFY</button>
                    <p style="font-size:11px">By clicking "VERIFY" button you agree to make a request to our server with the verification code.</p>
                    <p style="font-size:11px" id="verification-message-error"></p>     
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="section ni-styles">
            <div class="columns section">
              <div class="column is-one-third">
                <p class="title is-6">Style</p>
                <p>Select the way Nano Insurance integrates into your store.</p>
              </div>
              <div class="column is-two-thirds box">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <div class="control">
                      <label class="radio"><input type="radio" name="nano_insurance_options[style]" value="classic" <?php if ($type === 'classic') { echo 'checked'; } ?>><span>Classic</span></label><br/>
                      <label class="radio"><input type="radio" name="nano_insurance_options[style]" value="modern" <?php if ($type !== 'classic') { echo 'checked'; } ?>><span>Modern</span></label>
                    </div>
                    <p style="font-size:11px;margin-top:10px">With "classic" Nano Insurance integrates into store on cart & checkout pages while with "modern" it integrates on all pages.</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="columns ni-modern-suboptions section">
              <div class="column is-one-third">
                <p class="title is-6">Modal Position</p>
              </div>
              <div class="column is-two-thirds box">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <div class="control">
                      <label class="radio"><input type="radio" name="nano_insurance_options[modal-position]" value="bottom" <?php if ($mdl_position === 'bottom') { echo 'checked'; } ?>><span>Bottom</span></label><br/>
                      <label class="radio"><input type="radio" name="nano_insurance_options[modal-position]" value="top" <?php if ($mdl_position === 'top') { echo 'checked'; } ?>><span>Top</span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="columns section">
              <div class="column is-one-third">
                <p class="title is-6">Colors</p>
              </div>
              <div class="column is-two-thirds box ni-modern-suboptions">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor11">Modal Background Color</label>
                    <input id="jscolor11" class="jscolor" value="<?php echo $mdrn_mdl_bg_color ?>" name="nano_insurance_options[mdrn-mdl-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor12">Heading Background Color</label>
                    <input id="jscolor12" class="jscolor" value="<?php echo $mdrn_head_bg_color; ?>" name="nano_insurance_options[mdrn-head-bg-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor17">Modal Text Color</label>
                    <input id="jscolor17" class="jscolor" value="<?php echo $mdrn_txt_clr; ?>" name="nano_insurance_options[mdrn-mdl-text-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor18">Heading Text Color</label>
                    <input id="jscolor18" class="jscolor" value="<?php echo $mdrn_head_txt_color ?>" name="nano_insurance_options[mdrn-head-txt-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor13">Buy Button Background Color</label>
                    <input id="jscolor13" class="jscolor" value="<?php echo $mdrn_buy_btn_bg_color; ?>" name="nano_insurance_options[mdrn-buy-btn-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor14">Buy Button Text Color</label>
                    <input id="jscolor14" class="jscolor" value="<?php echo $mdrn_buy_btn_txt_color; ?>" name="nano_insurance_options[mdrn-buy-btn-text-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor15">Remove Button Background Color</label>
                    <input id="jscolor15" class="jscolor" value="<?php echo $mdrn_rm_btn_bg_color;?>" name="nano_insurance_options[mdrn-rm-btn-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor16">Remove Button Text Color</label>
                    <input id="jscolor16" class="jscolor" value="<?php echo $mdrn_rm_btn_txt_color;?>" name="nano_insurance_options[mdrn-rm-btn-text-color]"/>
                  </div>
                </div>
              </div>
              <div class="column is-two-thirds box ni-classic-suboptions">
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor1">Heading Background Color</label>
                    <input id="jscolor1" class="jscolor" value="<?php echo $mdl_head_bg_color;?>" name="nano_insurance_options[mdl-head-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor2">Heading Text Color</label>
                    <input id="jscolor2" class="jscolor" value="<?php echo $mdl_head_text_color;?>" name="nano_insurance_options[mdl-head-text-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor7">Price & Currency Text Color</label>
                    <input id="jscolor7" class="jscolor" value="<?php echo $mdl_txt_color;?>" name="nano_insurance_options[mdl-text-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor8">Box Border Color</label>
                    <input id="jscolor8" class="jscolor" value="<?php echo $box_border_color;?>" name="nano_insurance_options[box-border-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor3">Buy Button Background Color</label>
                    <input id="jscolor3" class="jscolor" value="<?php echo $buy_btn_bg_color;?>" name="nano_insurance_options[buy-btn-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor4">Buy Button Text Color</label>
                    <input id="jscolor4" class="jscolor" value="<?php echo $buy_btn_txt_color;?>" name="nano_insurance_options[buy-btn-text-color]"/>
                  </div>
                </div>
                <div class="tile is-parent">
                  <div class="tile is-child">
                    <label for="jscolor5">Remove Button Background Color</label>
                    <input id="jscolor5" class="jscolor" value="<?php echo $rm_btn_bg_color;?>" name="nano_insurance_options[rm-btn-bg-color]"/>
                  </div>
                  <div class="tile is-child">
                    <label for="jscolor6">Remove Button Text Color</label>
                    <input id="jscolor6" class="jscolor" value="<?php echo $rm_btn_txt_color;?>" name="nano_insurance_options[rm-btn-text-color]"/>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
      <form action="<?php echo $app_url; ?>/subscribe" method="post" id="ni-payment-form" class="ni-tab">
        <div class="section" style="overflow: auto">
          <div class="form-row">
            <label for="ni-card-element">
              Please enter credit card details
            </label>
            <div class="ni-note">
              You will be charged only for insured shipments. <br/>
              Card details are not stored on our servers.
            </div>
            <div id="ni-card-element">
            </div>
            <div id="ni-card-errors" role="alert"></div>
          </div>
          <div class="form-row" style="text-align: right">
            <button class="button is-link">Submit</button>
          </div>
        </div>
        <input type="hidden" id="nano-insurance-api-key" value="<?php echo $key;?>"/>
      </form>
      <div class="section">
        <div class="columns">
          <p id="support" class="column">
            <a href="mailto:support@nanoinsurancelimited.com">Support</a> |
            <a target="_blank" href="<?php echo nano_insurance_get_app_url(); ?>/public/terms.pdf">Terms & Conditions</a>
          </p>
        </div>
      </div>
    </div>
  <?php
}

function nano_insurance_admin_notice() {
  $verified = get_option('nano_insurance_verified', '0');
  $subscribed = get_option('nano_insurance_subscribed', '0');
  $options = get_option('nano_insurance_options', false);
  global $root_url;
  if ($verified == '0') {
    ?>
      <div class="notice notice-warning">
        <p>
          Visit <a href="https://woocommerce.nanoinsurancelimited.com">woocommerce.nanoinsurancelimited.com</a> to obtain <strong>API key</strong> and domain <strong>verification code</strong> first. Once you get those navigate to <strong>Settings -> Nano Insurance</strong>, enter obtained API key and verification code and press "VERFIY" button. 
        </p>
      </div>
    <?php
  } else {
    if ($options && empty($options['api-key'])) {
      ?>
        <div class="notice notice-error">
          <p>
            API key is missing. Please enter the key obtained with verification code.
          </p>
      </div>
      <?php
    }
  }
  if ($subscribed == '0') {
    ?>
      <div class="notice notice-warning">
        <p>
          Once you verify the domain and obtain API key, please enter your credit card details as the premiums generated will be collected on regular basis.
        </p>
      </div>
    <?php
  }
}
add_action( 'admin_notices', 'nano_insurance_admin_notice' );