<?php
/**
 * Bitcoin Status Page
 *
 * @category File
 * @package  BitcoinStatus
 * @author   Craig Watson <craig@cwatson.org>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link     https://github.com/craigwatson/bitcoind-status
 */

$config = array(
  'debug'                   => false,
  'rpc_user'                => 'rpcuser',
  'rpc_pass'                => 'rpcpass',
  'rpc_host'                => 'localhost',
  'rpc_port'                => '8332',
  'rpc_ssl'                 => false,
  'rpc_ssl_ca'              => null,
  'cache_file'              => '/tmp/bitcoind-status.cache',
  'max_cache_time'          => 300,
  'nocache_whitelist'       => array('127.0.0.1'),
  'admin_email'             => 'admin@domain.net',
  'display_donation_text'   => true,
  'donation_address'        => 'not_set',
  'donation_amount'         => '0.001',
  'use_bitcoind_ip'         => false,
  'intro_text'              => 'not_set',
  'display_peer_info'       => false,
  'display_peer_port'       => false,
  'display_free_disk_space' => false,
  'display_ip_location'     => false,
  'display_testnet'         => true,
  'display_version'         => false,
  'display_github_ribbon'   => true,
  'use_cache'               => true,
  'date_format'             => 'H:i:s e j F Y ',
  'stylesheet'              => 'v1.css'
);
