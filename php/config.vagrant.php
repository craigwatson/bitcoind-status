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
  // RPC
  'rpc_user'                  => 'status',
  'rpc_pass'                  => 'statustest',
  'rpc_host'                  => 'localhost',
  'rpc_port'                  => '18332',
  'rpc_ssl'                   => false,
  'rpc_ssl_ca'                => null,

  // Donations
  'display_donation_text'     => true,
  'donation_address'          => 'not_set',
  'donation_amount'           => '0.001',

  // Peers
  'display_peer_info'         => true,
  'display_peer_port'         => false,
  'hide_dark_peers'           => true,
  'ignore_unknown_ping'       => true,
  'peers_to_ignore'           => array(),

  // Cache
  'cache_geo_data'            => true,
  'geo_cache_file'            => '/var/tmp/bitcoind-geolocation.cache',
  'geo_cache_time'            => 604800,
  'use_cache'                 => false,
  'cache_file'                => '/var/tmp/bitcoind-status.cache',
  'max_cache_time'            => 300,
  'nocache_whitelist'         => array('127.0.0.1'),

  // Geolocation
  'geolocate_peer_ip'         => false,
  'display_ip_location'       => false,

  // UI
  'display_ip'                => true,
  'display_free_disk_space'   => true,
  'display_testnet'           => true,
  'display_version'           => true,
  'display_github_ribbon'     => false,
  'display_max_height'        => true,
  'use_bitcoind_ip'           => false,
  'intro_text'                => 'not_set',
  'title_text'                => 'Bitcoin Node Status',
  'display_bitnodes_info'     => false,
  'display_chart'             => false,
  'display_peer_chart'        => false,
  'node_links'                => array(),

  // Stats
  'stats_whitelist'           => array('127.0.0.1'),
  'stats_file'                => '/var/tmp/bitcoind-status.data',
  'stats_max_age'             => '604800',
  'stats_min_data_points'     => 5,

  // Node Count
  'peercount_whitelist'       => array('127.0.0.1'),
  'peercount_file'            => '/var/tmp/bitcoind-peers.data',
  'peercount_max_age'         => '2592000',
  'peercount_min_data_points' => 5,
  'peercount_extra_nodes'     => array(),

  // Uptime
  'display_bitcoind_uptime'   => false,
  'bitcoind_process_name'     => 'bitcoind',

  // System
  'date_format'               => 'H:i:s T, j F Y ',
  'disk_space_mount_point'    => '.',
  'timezone'                  => null,
  'stylesheet'                => 'v2-light.css',
  'debug'                     => false,
  'admin_email'               => 'me@example.com',
);
