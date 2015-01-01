<?php

function get_raw_data() {

  global $config;
  require_once('./easybitcoin.php');

  $bitcoin = new Bitcoin($config['rpc_user'],$config['rpc_pass'],$config['rpc_host'],$config['rpc_port']);

  if($config['rpc_ssl'] === TRUE) {
    $bitcoin->setSSL($config['rpc_ssl_ca']);
  }
  
  $data = $bitcoin->getinfo();
  write_to_cache($data);
  return $data;

}

function write_to_cache($data) {
  global $config;
  
  $raw_data = serialize($data);
  file_put_contents($config['cache_file']);
}

?>