<?php

require_once './php/config.php';
require_once './php/functions.php';

// If we're good to clear cache, remove cache file
if(!empty($_GET['clearcache']) & in_array($_SERVER['REMOTE_ADDR'],$config['nocache_whitelist']) & is_file($config['cache_file'])){
  unlink($config['cache_file']);
}

// If we're good to bypass cache, get raw data from RPC
if((isset($_GET['nocache']) & in_array($_SERVER['REMOTE_ADDR'],$config['nocache_whitelist'])) || ($config['use_cache'] === FALSE)){
  $data = get_raw_data();
} elseif (is_file($config['cache_file'])) {

  // Get cache data
  $raw_cache = file_get_contents($config['cache_file']);
  $cache = unserialize($raw_cache);

  // If the data is still valid, use it. If not, get from RPC
  if (time() > ($cache['cache_time']+$config['max_cache_time'])) {
  	$data = get_raw_data();
  } else {
    $data = $cache;
  }
} else {
  $data = get_raw_data();
}

// Add the IP of the server, and include template
require_once './html/template.html';
