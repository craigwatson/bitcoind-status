<?php

$config = array(
  'debug'                   => FALSE,
  'rpc_user'                => 'rpcuser',
  'rpc_pass'                => 'rpcpass',
  'rpc_host'                => 'localhost',
  'rpc_port'                => '8332',
  'rpc_ssl'                 => FALSE,
  'rpc_ssl_ca'              => NULL,
  'cache_file'              => '/tmp/bitcoind-status.cache',
  'max_cache_time'          => 300,
  'nocache_whitelist'       => array('127.0.0.1'),
  'admin_email'             => 'admin@domain.net',
  'display_donation_text'   => TRUE,
  'donation_address'        => 'not_set',
  'donation_amount'         => '0.001',
  'use_bitcoind_ip'         => FALSE,
  'intro_text'              => 'not_set',
  'use_cache' => TRUE,
  'display_peer_info'       => FALSE,
  'display_free_disk_space' => FALSE,
  'display_ip_location'     => FALSE,
  'display_testnet' => TRUE,
  'display_version' => FALSE
);

?>
