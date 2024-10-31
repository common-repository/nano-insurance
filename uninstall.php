<?php 

//this check makes sure that this file is called manually.
if (defined("WP_UNINSTALL_PLUGIN")) {
  delete_option('nano_insurance_options');
  delete_option('nano_insurance_subscribed');
  delete_option('nano_insurance_verified');
  delete_option('nano_insurance_order_count');
  delete_option('nano_insurance_order_total');
}