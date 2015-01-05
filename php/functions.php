<?php

/** Connects to Bitcoin daemon and retrieves information, then writes to cache **/
function get_raw_data() {
  global $config;

  // Include EasyBitcoin library and set up connection
  require_once('./php/easybitcoin.php');
  $bitcoin = new Bitcoin($config['rpc_user'],$config['rpc_pass'],$config['rpc_host'],$config['rpc_port']);

  // Setup SSL if configured
  if($config['rpc_ssl'] === TRUE) {
    $bitcoin->setSSL($config['rpc_ssl_ca']);
  }

  // Get info
  $data = $bitcoin->getinfo();

  // Handle errors if they happened
  if (!$data) {
    $data['error'] = $bitcoin->error;
    $data['status'] = $bitcoin->status;
  } else {
    // Set cache time and write cache
    $data['cache_time'] = time();
    write_to_cache($data);
  }

  return $data;

}

/** Simple function to serialize an array and write to file **/
function write_to_cache($data) {
  global $config;
  $raw_data = serialize($data);
  file_put_contents($config['cache_file'],$raw_data);
}

/** Generates a QR Code image for donations **/
function generate_donation_image() {
  global $config;
  $alt_text = 'Donate ' . $config['donation_amount'] . ' BTC to ' . $config['donation_address'];
  return "\n" . '<img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $config['donation_address'] . '" alt="' . $alt_text . '" />' . "\n";
}

?>
