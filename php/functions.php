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
    if ($curl_handle === false) {
        return false;
    }

    if ($fail_on_error) {
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, true);
    }

    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Bitcoin Node Status Page');
    curl_setopt($curl_handle, CURLOPT_URL, $url);

    return curl_exec($curl_handle);
}

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
    global $curl_handle;

    // If we're getting data from cache, do it
    if (($from_cache === true) && (is_file($config['cache_file']))) {
        $cache = json_decode(file_get_contents($config['cache_file']), true);

        // Only proceed if the array is a cache - invalid otherwise
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

    // Get info
    $data = $bitcoin->getinfo();

    // Handle errors if they happened
    if (!$data) {
        $return_data['error'] = $bitcoin->error;
        $return_data['status'] = $bitcoin->status;
        $return_data['display_connection_chart'] = false;
        $return_data['display_peer_chart'] = false;
        writeToCache($return_data);
        return $return_data;
    }

    // Get free disk space
    if ($config['display_free_disk_space'] === true) {
        $data['free_disk_space'] = getFreeDiskSpace();
    }

    // Store network info in data array
    $data['net_info'] = $bitcoin->getnetworkinfo();

    if ($config['display_ip'] === true) {
        // Use bitcoind IP
        if ($config['use_bitcoind_ip'] === true) {
            $data['node_ip'] = $data['net_info']['localaddresses'][0]['address'];
        } else {
            $data['node_ip'] = $_SERVER['SERVER_ADDR'];
        }
    }

    // Add peer info
    if ($config['display_peer_info'] === true) {
        $data['peers'] = parsePeers($bitcoin->getpeerinfo());
    }

    // Node geolocation
    if ($config['display_ip_location'] === true && $config['display_ip'] === true) {
        $node_curl = curl_init();
        $data['ip_location'] = getGeolocation($data['node_ip'], $node_curl);
        curl_close($node_curl);
    }

    // Bitcoin Daemon uptime
    if (($config['display_bitcoind_uptime'] === true) || (strcmp(PHP_OS, "Linux") == 0)) {
        $data['bitcoind_uptime'] = getProcessUptime($config['bitcoind_process_name']);
    }

    if ($config['display_max_height'] || $config['display_bitnodes_info']) {
        $bitnodes_curl = curl_init();
    }

    // Get max height from bitnodes.21.co
    if ($config['display_max_height'] === true) {
        $bitnodes_curl = curl_init();
        if ($config['display_testnet'] === true) {
            $exec_result = json_decode(curlRequest("https://testnet.blockexplorer.com/api/status?q=getBlockCount", $bitnodes_curl), true);
            $data['max_height'] = $exec_result['blockcount'];
        } else {
            $exec_result = json_decode(curlRequest("https://bitnodes.21.co/api/v1/snapshots/", $bitnodes_curl), true);
            $data['max_height'] = $exec_result['results'][0]['latest_height'];
        }
        $data['node_height_percent'] = round(($data['blocks']/$data['max_height'])*100, 1);
    }

    // Get node info from bitnodes.21.co
    if ($config['display_bitnodes_info'] === true) {
        $data['bitnodes_info'] = json_decode(curlRequest("https://bitnodes.21.co/api/v1/nodes/" . $data['node_ip'] . "-8333/", $bitnodes_curl), true);
        $latency = json_decode(curlRequest("https://bitnodes.21.co/api/v1/nodes/" . $data['node_ip'] . "-8333/latency/", $bitnodes_curl), true);
        $data['bitnodes_info']['latest_latency'] = $latency['daily_latency'][0]['v'];
    }

    // Work out if we should display charts or not
    $data['display_connection_chart'] = displayChart($config['display_chart'], $config['stats_file'], $config['stats_min_data_points']);
    $data['display_peer_chart'] = displayChart($config['display_peer_chart'], $config['peercount_file'], $config['peercount_min_data_points']);

    writeToCache($data);
    return $data;

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

/**
 * Parses an array of peers and applies our filtering
 *
 * @param array $peers The array of peers to parse
 *
 * @return array
 */
function parsePeers($peers)
{
    global $config;
    $to_return = array();

    foreach ($peers as $peer) {

        // Extract IP address for later
        $peer_addr_array = explode(':', $peer['addr']);
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
            $geo_curl = curl_init();
            $peer['geo_data'] = getGeolocation($peer_ip, $geo_curl);
            curl_close($geo_curl);
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
 * Gets free disk space
 *
 * @return String
 */
function getFreeDiskSpace()
{
    return convertToSI(disk_free_space(".")) . '<br />';
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
    global $country_codes;
    $to_return['country_code'] = 'blank';
    $to_return['country_name'] = 'Unavailable';
    $exec_result = curlRequest("http://www.geoplugin.net/php.gp?ip=$ip_address", $curl_handle, true);
    if ($exec_result !== false) {
        $array = unserialize($exec_result);
        $to_return['country_code'] = $array['geoplugin_countryCode'];
        $to_return['country_name'] = $array['geoplugin_countryName'];
    }
    return $to_return;
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
