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

    // Use bitcoind IP
    if ($config['use_bitcoind_ip'] === true) {
        $net_info = $bitcoin->getnetworkinfo();
        $data['node_ip'] = $net_info['localaddresses'][0]['address'];
    } else {
        $data['node_ip'] = $_SERVER['SERVER_ADDR'];
    }

    // Add peer info
    if ($config['display_peer_info'] === true) {
        if ($config['display_peer_port'] === true) {
            $data['peers'] = $bitcoin->getpeerinfo();
            if ($config['geolocate_peer_ip'] === true) {
                for ($num = 0; $num < count($data['peers']); ++$num) {
                    $peer_addr_array = explode(':', $data['peers'][$num]['addr']);
                    $data['peers'][$num]['country'] = getGeolocation($peer_addr_array[0], 'geoplugin_countryCode');
                }
            }
        } else {
            foreach ($bitcoin->getpeerinfo() as $peer) {
                $peer_addr_array = explode(':', $peer['addr']);
                $peer['addr'] = $peer_addr_array[0];
                if ($config['geolocate_peer_ip'] === true) {
                    $peer['country'] = getGeolocation($peer['addr'], 'geoplugin_countryCode');
                }
                $data['peers'][] = $peer;
            }
        }
    }

    // Node geolocation
    if ($config['display_ip_location'] === true) {
          $data['ip_location'] = getGeolocation($data['node_ip'], 'all');
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
