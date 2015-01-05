<?php

$config = array(
  'rpc_user'               => 'rpcuser',
  'rpc_pass'               => 'rpcpass',
  'rpc_host'               => 'localhost',
  'rpc_port'               => '8332',
  'rpc_ssl'                => FALSE,
  'rpc_ssl_ca'             => NULL,
  'cache_file'             => '/tmp/bitcoind-status.cache',
  'max_cache_time'         => 300,
  'nocache_whitelist'      => array('127.0.0.1'),
  'admin_email'            => 'admin@vikingserv.net',
  'display_donation_text'  => FALSE,
  'donation_address'       => 'not_set',
  'donation_amount'        => '0.001',
  'into_text'              => FALSE
);

?>
