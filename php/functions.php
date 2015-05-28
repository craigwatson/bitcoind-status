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
 * Connects to Bitcoin daemon and retrieves information, then writes to cache
 *
 * @return array
 */
function getData()
{
    global $config;

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
        writeToCache($return_data);
        return $return_data;
    }

    // Get free disk space
    if ($config['display_free_disk_space'] === true) {
        $data['free_disk_space'] = getFreeDiskSpace();
    }

    if (isset($config['display_ip']) && $config['display_ip'] === true) {
        // Use bitcoind IP
        if ($config['use_bitcoind_ip'] === true) {
            $net_info = $bitcoin->getnetworkinfo();
            $data['node_ip'] = $net_info['localaddresses'][0]['address'];
        } else {
            $data['node_ip'] = $_SERVER['SERVER_ADDR'];
        }
    }

    // Add peer info
    if ($config['display_peer_info'] === true) {
        $data['peers'] = parsePeers($bitcoin->getpeerinfo());
    }

    // Node geolocation
    if ($config['display_ip_location'] === true) {
        $data['ip_location'] = getGeolocation($data['node_ip'], 'all');
    }

    // Bitcoin Daemon uptime
    if (($config['display_bitcoind_uptime'] === true) || (strcmp(PHP_OS, "Linux") == 0)) {
        $data['bitcoind_uptime'] = getProcessUptime($config['bitcoind_process_name']);
    }

    // Get max height from bitnodes.io
    if ($config['display_max_height'] === true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Bitcoin Node Status Page');
        curl_setopt($ch, CURLOPT_URL, "https://getaddr.bitnodes.io/api/v1/snapshots/");
        $exec_result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $data['max_height'] = $exec_result['results'][0]['latest_height'];
        $data['node_height_percent'] = round(($data['blocks']/$data['max_height'])*100, 1);
    }

    writeToCache($data);
    return $data;

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

        // Do geolocation
        if ($config['geolocate_peer_ip'] === true) {
            $peer['country'] = getGeolocation($peer_ip, 'geoplugin_countryCode');
        }

        // Override peer addr with IP
        if ($config['display_peer_port'] === false) {
            $peer['addr'] = $peer_ip;
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
        $raw_data = serialize($data);
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
    $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
    $base = 1024;
    $return_class = min((int)log($bytes, $base), count($si_prefix) - 1);
    return sprintf('%1.2f', $bytes / pow($base, $return_class)) . ' ' . $si_prefix[$return_class];
}

/**
 * Gets location of an IP via Geolocation
 *
 * @param String $ip_address   The IP to Geolocate
 * @param String $response_key The key of the response array to return
 *                             'all' will return all keys
 *
 * @return mixed Either an array if 'all' is passed to $response_key or string
 */
function getGeolocation($ip_address, $response_key)
{
    global $config;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.geoplugin.net/php.gp?ip=$ip_address");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Bitcoin Node Status Page');
    $exec_result = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response_code === 200) {
        $response_array = unserialize($exec_result);
        if (strcmp($response_key, "all") == 0) {
            return $response_array;
        } else {
            return $response_array[$response_key];
        }
    } else {
        return 'Unavailable';
    }
}
