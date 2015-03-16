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

  // Get free disk space
  if ($config['display_free_disk_space'] === TRUE) {
    $data['free_disk_space'] = get_free_disk_space();
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
    if($config['display_peer_port'] === TRUE) {
      $data['peers'] = $bitcoin->getpeerinfo();
    } else {
      foreach ($bitcoin->getpeerinfo() as $peer) {
        $peer_addr_array = explode(':',$peer['addr']);
        $peer['addr'] = $peer_addr_array[0];
        $data['peers'][] = $peer;
      }
    }
  }

  // Use geolocation
  if($config['display_ip_location'] === TRUE) {
    $data['ip_location'] = geolocate_ip($data['node_ip']);
  }

  write_to_cache($data);
  return $data;

}

/** Simple function to serialize an array and write to file **/
function write_to_cache($array_data) {
  global $config;
  if ($config['use_cache'] === TRUE) {
    $array_data['cache_time'] = time();
    $raw_data = serialize($array_data);
    file_put_contents($config['cache_file'],$raw_data);
  }
}

/** Generates a QR Code image for donations **/
function generate_donation_image() {
  global $config;
  $alt_text = 'Donate ' . $config['donation_amount'] . ' BTC to ' . $config['donation_address'];
  return "\n" . '<img src="https://chart.googleapis.com/chart?chld=H|2&chs=225x225&cht=qr&chl=' . $config['donation_address'] . '" alt="' . $alt_text . '" />' . "\n";
}

/** Gets free disk space **/
function get_free_disk_space() {
  $bytes = disk_free_space(".");
  $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
  $base = 1024;
  $return_class = min((int)log($bytes , $base) , count($si_prefix) - 1);
  return sprintf('%1.2f' , $bytes / pow($base,$return_class)) . ' ' . $si_prefix[$return_class] . '<br />';
}

/** Gets location of an IP via Geolocation **/
function geolocate_ip($ip_address) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "http://www.geoplugin.net/php.gp?ip=$ip_address");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BitCoin Node Status Page');
  $curl_response = curl_exec($ch);
  curl_close ($ch);
  return unserialize($curl_response);
}

?>
