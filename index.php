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
if (isset($_GET['clearcache']) & in_array($_SERVER['REMOTE_ADDR'], $config['nocache_whitelist']) & is_file($config['cache_file'])) {
    unlink($config['cache_file']);
    $cache_message = 'Cache has been cleared!';
}

// Check if we need to use the cache
if ($config['use_cache'] === false) {
    $use_cache = false;
} elseif (isset($_GET['nocache']) & in_array($_SERVER['REMOTE_ADDR'], $config['nocache_whitelist'])) {
    $use_cache = false;
    $cache_message = 'Cache has been bypassed!';
} else {
    $use_cache = true;
}

// Get the data
$data = getData($use_cache);

// Add the IP of the server, and include template
require_once './html/template.html';
