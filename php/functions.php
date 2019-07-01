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

$curl_requests = 0;
$default_app_title = 'Bitcoin Node Status';

/**
 * Connects to Bitcoin daemon and retrieves information, then writes to cache
 *
 * @param string $from_cache Whether to get the data from cache or not
 *
 * @return array
 */
function getData($from_cache = false)
{
    global $config;
    global $cache_message;
    global $geo_cache;
    global $curl_requests;

    // If we're getting data from cache, do it
    if (($from_cache === true) && (is_file($config['cache_file']))) {
        $cache = json_decode(file_get_contents($config['cache_file']), true);

        // Only proceed if the cache is an array - invalid otherwise
        if (is_array($cache)) {
            if ($cache['config_hash'] == md5(json_encode($config))) {
                if (time() < $cache['cache_expiry']) {
                    return $cache;
                }
            } else {
                $cache_message = 'Configuration has changed, cache has been refreshed.';
            }
        }
    }

    // Include EasyBitcoin library and set up connection
    include_once './php/easybitcoin.php';
    $bitcoin = new Bitcoin($config['rpc_user'], $config['rpc_pass'], $config['rpc_host'], $config['rpc_port']);

    // Setup SSL if configured
    if ($config['rpc_ssl'] === true) {
        $bitcoin->setSSL($config['rpc_ssl_ca']);
    }

    // Get blockchain and network info
    $data = $bitcoin->getblockchaininfo();
    $net_info = $bitcoin->getnetworkinfo();
    $data['connections'] = $net_info['connections'];
    $data['subversion'] = $net_info['subversion'];

    // Handle errors if they happened
    if (!$data) {
        $return_data['error'] = $bitcoin->error;
        $return_data['status'] = $bitcoin->status;
        $return_data['display_connection_chart'] = false;
        $return_data['display_peer_chart'] = false;
        writeToCache($return_data);
        return $return_data;
    }

    // Get geolocation cache data
    if ($config['cache_geo_data'] === true) {
        $geo_cache = getGeoCache($config['geo_cache_file']);
        $data['geo'] = $geo_cache;
    }

    // Get free disk space
    if ($config['display_free_disk_space'] === true) {
        $data['free_disk_space'] = getFreeDiskSpace($config['disk_space_mount_point']);
    }

    if ($config['display_ip'] === true) {
        // Use bitcoind IP
        if ($config['use_bitcoind_ip'] === true) {
            $data['node_ip'] = $net_info['localaddresses'][0]['address'];
        } else {
            $data['node_ip'] = $_SERVER['SERVER_ADDR'];
        }
    }

    // Create geo handle
    if (($config['display_ip_location'] === true) || ($config['geolocate_peer_ip'] === true)) {
        $geo_curl = curl_init();
    } else {
        $geo_curl = false;
    }

    // Add peer info
    if ($config['display_peer_info'] === true) {
        $data['peers'] = parsePeers($bitcoin->getpeerinfo(), $geo_curl);
    }

    // Node geolocation
    if (($config['display_ip_location'] === true) && ($config['display_ip'] === true)) {
        $data['ip_location'] = getGeolocation($data['node_ip'], $geo_curl);
    }

    // Bitcoin Daemon uptime
    if (($config['display_bitcoind_uptime'] === true) && (strcmp(PHP_OS, "Linux") == 0)) {
        $data['bitcoind_uptime'] = getProcessUptime($config['bitcoind_process_name']);
    }

    // Create handle
    if ($config['display_max_height'] || $config['display_bitnodes_info']) {
        $bitnodes_curl = curl_init();
    }

    // Get max height from bitnodes.earn.com
    if ($config['display_max_height'] === true) {
        if ($config['display_testnet'] === true) {
            $exec_result = json_decode(curlRequest("https://chain.so/api/v2/get_info/BTCTEST", $bitnodes_curl), true);
        } else {
            $exec_result = json_decode(curlRequest("https://chain.so/api/v2/get_info/BTC", $bitnodes_curl), true);
        }
        $data['max_height'] = $exec_result['data']['blocks'];
        $data['node_height_percent'] = round(($data['blocks']/$data['max_height'])*100, 1);
    }

    // Get node info from bitnodes.earn.com
    if ($config['display_bitnodes_info'] === true) {
        $data['bitnodes_info'] = json_decode(curlRequest("https://bitnodes.earn.com/api/v1/nodes/" . $data['node_ip'] . "-8333/", $bitnodes_curl), true);
        $latency = json_decode(curlRequest("https://bitnodes.earn.com/api/v1/nodes/" . $data['node_ip'] . "-8333/latency/", $bitnodes_curl), true);
        $data['bitnodes_info']['latest_latency'] = $latency['daily_latency'][0]['v'];
    }

    // Close handles
    if ($config['display_max_height'] || $config['display_bitnodes_info']) {
        curl_close($bitnodes_curl);
    }

    if (($config['display_ip_location'] === true) || ($config['geolocate_peer_ip'] === true)) {
        curl_close($geo_curl);
    }

    // Work out if we should display charts or not
    $data['display_connection_chart'] = displayChart($config['display_chart'], $config['stats_file'], $config['stats_min_data_points']);
    $data['display_peer_chart'] = displayChart($config['display_peer_chart'], $config['peercount_file'], $config['peercount_min_data_points']);

    // Write geolocation cache
    if ($config['cache_geo_data'] === true) {
        file_put_contents($config['geo_cache_file'], json_encode($geo_cache), LOCK_EX);
    }

    writeToCache($data);
    return $data;

}

/**
 * Serializes an array and write to file
 *
 * @param array $data Data to write
 *
 * @return void
 */
function writeToCache($data)
{
    global $config;
    if ($config['use_cache'] === true) {
        $data['cache_time'] = time();
        $data['cache_expiry'] = $data['cache_time']+$config['max_cache_time'];
        $data['config_hash'] = md5(json_encode($config));
        $raw_data = json_encode($data);
        file_put_contents($config['cache_file'], $raw_data, LOCK_EX);
    }
}

/**
 * Parses an array of peers and applies our filtering
 *
 * @param array  $peers       The array of peers to parse
 * @param handle $curl_handle A reusable handle to pass to getGeolocation()
 *
 * @return array
 */
function parsePeers($peers, $curl_handle)
{
    global $config;
    $to_return = array();

    foreach ($peers as $peer) {

        // Extract IP address for later
        $peer_addr_array = preg_split('/\:(?=[^:]*$)/', $peer['addr']);
        $peer_ip = $peer_addr_array[0];

        // Continue if peer is 'dark'
        if ($config['hide_dark_peers'] === true) {
            if ((strcmp($peer_ip, '127.0.0.1') == 0) || (strpos($peer_ip, '.onion') !== false)) {
                continue;
            }
        }

        // Continue if peer is to be ignored
        if (is_array($config['peers_to_ignore']) & in_array($peer_ip, $config['peers_to_ignore'])) {
            continue;
        }

        // Continue if peer reports no pingtime
        if ($config['ignore_unknown_ping'] === true) {
            if (!array_key_exists('pingtime', $peer)) {
                continue;
            }
        }

        // Do geolocation
        if ($config['geolocate_peer_ip'] === true) {
            $peer['geo_data'] = getGeolocation($peer_ip, $curl_handle);
        }

        // Override peer addr with IP
        if ($config['display_peer_port'] === false) {
            array_pop($peer_addr_array);
            $peer['addr'] = str_replace(array('[',']'), '', implode(':', $peer_addr_array));
        }

        $to_return[] = $peer;

    }

    return $to_return;

}


/**
 * Reads IP geolocation data from file
 *
 * @param string $file_path The file to read cache from
 *
 * @return Array
 */
function getGeoCache($file_path)
{

    global $config;
    $to_return = array();

    if ($config['cache_geo_data'] === true) {
        if (is_file($file_path)) {
            $raw = json_decode(file_get_contents($file_path), true);
            if (is_array($raw)) {
                $cache = $raw;
            } else {
                $cache = array();
            }
        } else {
            $cache = array();
        }
    } else {
        $cache = array();
    }

    foreach ($cache as $ip => $data) {
        if (!array_key_exists('expiry', $data)) {
            continue;
        } elseif (time() > $data['expiry']) {
            continue;
        } else {
            $to_return[$ip] = $data;
        }
    }

    return $cache;
}

/**
 * Gets location of an IP via Geolocation
 *
 * @param string        $ip_address  The IP to Geolocate
 * @param curl_resource $curl_handle The CURL handle to pass to the curlRequest call
 *
 * @return array An array of shortened country code and full country name
 */
function getGeolocation($ip_address, $curl_handle)
{
    global $config;
    global $geo_cache;

    // Default flag + array
    $run_curl = false;
    $to_return = array(
        'country_code' => 'blank',
        'country_name' => 'Unavailable',
    );

    // Check for IP in cache + return if found
    if ($config['cache_geo_data'] === true) {
        if (array_key_exists($ip_address, $geo_cache)) {
            return $geo_cache[$ip_address];
        }
    }

    // If not found in cache, ping API
    $exec_result = curlRequest("http://www.geoplugin.net/php.gp?ip=$ip_address", $curl_handle, true);
    if ($exec_result !== false) {
        $array = unserialize($exec_result);

        // If match found, store it
        $to_return['country_code'] = $array['geoplugin_countryCode'];
        $to_return['country_name'] = $array['geoplugin_countryName'];

        // If we're configured to cache, add expiry + cache it
        if ($config['cache_geo_data'] === true) {
            $geo_cache[$ip_address]['expiry'] = time() + $config['geo_cache_time'];
            $geo_cache[$ip_address]['country_code'] = $array['geoplugin_countryCode'];
            $geo_cache[$ip_address]['country_name'] = $array['geoplugin_countryName'];
        }
    }

    return $to_return;
}

/**
 * Wrapper function for CURL calls
 *
 * @param string        $url           The URL to CURL
 * @param curl_resource $curl_handle   An initialised CURL Handle to use
 * @param boolean       $fail_on_error Whether to fail if return code is >= 400
 *
 * @return string
 */
function curlRequest($url, $curl_handle, $fail_on_error = false)
{
    global $curl_requests;

    if ($curl_handle === false) {
        return false;
    }

    if ($fail_on_error === true) {
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
    }

    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Bitcoin Node Status Page');
    curl_setopt($curl_handle, CURLOPT_URL, $url);

    $curl_requests++;
    return curl_exec($curl_handle);
}

/**
 * Generates a QR Code image for donations
 *
 * @return String
 */
function generateDonationImage()
{
    global $config;
    $alt_text = 'Donate ' . $config['donation_amount'] . ' BTC to ' . $config['donation_address'];
    return "\n" . '<img src="https://chart.googleapis.com/chart?chld=H|2&chs=225x225&cht=qr&chl=' . $config['donation_address'] . '" alt="' . $alt_text . '" />' . "\n";
}

/**
 * Small function to split out chart-display logic
 *
 * @param boolean $config_var      Master variable from $config
 * @param string  $data_file       The filename holding the stats to display
 * @param int     $min_data_points The minimum number of data points to display
 *
 * @return boolean Whether to display the chart or not
 */
function displayChart($config_var, $data_file, $min_data_points)
{
    if (($config_var === true) & (is_file($data_file))) {
        if (count(json_decode(file_get_contents($data_file), true)) > $min_data_points) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Gets free disk space
 *
 * @param String $mount_point The mount point to check
 *
 * @return String
 */
function getFreeDiskSpace($mount_point)
{
    return convertToSI(disk_free_space($mount_point)) . '<br />';
}

/**
 * Formats a number into SI storage units
 *
 * @param Int $bytes The number of bytes to convert
 *
 * @return String Expression of $bytes in SI units
 */
function convertToSI($bytes)
{
    if ($bytes === 0) {
        return '0B';
    } else {
        $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
        $base = 1024;
        $return_class = min((int)log($bytes, $base), count($si_prefix) - 1);
        return sprintf('%1.2f', $bytes / pow($base, $return_class)) . ' ' . $si_prefix[$return_class];
    }
}

/**
 * Generate "time ago" text from timestamp.
 *
 * @param Int $ptime UNIX timestamp
 *
 * @return String Time ago text.
 */
function elapsedTime($ptime)
{
    $etime = time() - $ptime;

    if ($etime < 1) {
        return '0 seconds';
    }

    $a = array( 365 * 24 * 60 * 60  =>  'year',
                 30 * 24 * 60 * 60  =>  'month',
                      24 * 60 * 60  =>  'day',
                           60 * 60  =>  'hour',
                                60  =>  'minute',
                                 1  =>  'second'
                );
    $a_plural = array( 'year'   => 'years',
                       'month'  => 'months',
                       'day'    => 'days',
                       'hour'   => 'hours',
                       'minute' => 'minutes',
                       'second' => 'seconds'
                );

    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str);
        }
    }
}

/**
 * Gets uptime of a process - reference: http://unix.stackexchange.com/q/27276/39264
 *
 * @param string $process The name of the pricess to find
 *
 * @return string A textual representation of the lifetime of the process
 */
function getProcessUptime($process)
{
    $process_pid = exec("pidof $process");
    $system_uptime = exec('cut -d "." -f1 /proc/uptime');
    $pid_uptime = round((exec("cut -d \" \" -f22 /proc/$process_pid//stat")/100), 0);
    $seconds = $system_uptime-$pid_uptime;
    $days = floor($seconds / 86400);
    $hours = str_pad(floor(($seconds - ($days*86400)) / 3600), 2, "0", STR_PAD_LEFT);
    $mins = str_pad(floor(($seconds - ($days*86400) - ($hours*3600)) / 60), 2, "0", STR_PAD_LEFT);
    $secs = str_pad(floor($seconds % 60), 2, "0", STR_PAD_LEFT);
    return "$days days, $hours:$mins:$secs";
}
