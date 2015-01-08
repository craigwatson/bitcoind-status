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
    $return_data['error'] = $bitcoin->error;
    $return_data['status'] = $bitcoin->status;
    write_to_cache($return_data);
    return $return_data;
  }

  // Use bitcoind IP
  if ($config['use_bitcoind_ip'] === TRUE) {
    $net_info = $bitcoin->getnetworkinfo();
    $data['node_ip'] = $net_info['localaddresses'][0]['address'];
  } else {
    $data['node_ip'] = $_SERVER['SERVER_ADDR'];
  }

  // Add peer info
  if ($config['display_peer_info'] === TRUE) {
    $data['peers'] = $bitcoin->getpeerinfo();
  }

  write_to_cache($data);
  return $data;

}

/** Simple function to serialize an array and write to file **/
function write_to_cache($array_data) {
  global $config;
  $array_data['cache_time'] = time();
  $raw_data = serialize($array_data);
  file_put_contents($config['cache_file'],$raw_data);
}

/** Generates a QR Code image for donations **/
function generate_donation_image() {
  global $config;
  $alt_text = 'Donate ' . $config['donation_amount'] . ' BTC to ' . $config['donation_address'];
  return "\n" . '<img src="https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=' . $config['donation_address'] . '" alt="' . $alt_text . '" />' . "\n";
}

?>
