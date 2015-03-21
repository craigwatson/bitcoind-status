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

require_once './php/config.php';
require_once './php/functions.php';

// If we're good to clear cache, remove cache file
if (!empty($_GET['clearcache']) & in_array($_SERVER['REMOTE_ADDR'], $config['nocache_whitelist']) & is_file($config['cache_file'])) {
    unlink($config['cache_file']);
}

// If we're good to bypass cache, get raw data from RPC
if ((isset($_GET['nocache']) & in_array($_SERVER['REMOTE_ADDR'], $config['nocache_whitelist'])) || ($config['use_cache'] === false)) {
    $data = getData();
} elseif (is_file($config['cache_file'])) {

    // Get cache data
    $raw_cache = file_get_contents($config['cache_file']);
    $cache = unserialize($raw_cache);

    // If the data is still valid, use it. If not, get from RPC
    if (time() > ($cache['cache_time']+$config['max_cache_time'])) {
        $data = getData();
    } else {
        $data = $cache;
    }
} else {
    $data = getData();
}

// Add the IP of the server, and include template
require_once './html/template.html';
