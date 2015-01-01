<?php

require_once('./config.php');
require_once('./functions.php');

if( (isset($_GET['nocache'])) & (in_array($_SERVER['REMOTE_ADDR'],$config['nocache_whitelist'])) ){
  $data = get_raw_data();
} elseif (is_file($config['cache_file'])) {
  
  $raw_cache = file_get_contents($config['cache_file']);
  $cache = unserialize($raw_cache);
  
  if (time() > ($cache['cache_time']+$config['max_cache_time'])) {
  	$data = get_raw_data();
  } else {
    $data = $cache;
  }
} else {
  $data = get_raw_data();
}

$data['server_ip'] = $_SERVER['SERVER_ADDR'];

require_once('./template.html');

?>

