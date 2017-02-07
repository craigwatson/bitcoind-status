# bitcoind-status

[![Travis CI Status](https://travis-ci.org/craigwatson/bitcoind-status.svg?branch=master)](https://travis-ci.org/craigwatson/bitcoind-status)

This is a small PHP application designed to display status and information from the Bitcoin node daemon.

#### Table of Contents

1. [Requirements](#requirements)
1. [Getting Started](#getting-started)
1. [Contributing](#contributing)
1. [Advanced Configuration Options](#advanced-configuration-options)
1. [Licensing](#licensing)

## Requirements

To run the application, you will need:

  * A Bitcoin node with RPC enabled.
  * A web-server with PHP installed.
  * The PHP `curl` module - this is used to make RPC calls to the Bitcoin daemon.

### PHP Support

This application has been tested with PHP 5.4, 5.5 and 5.6, as well as HHVM and Nightly PHP builds, via TravisCI.

## Getting Started

To get started, all you need to do is copy/rename `php/config.sample.php` to `php/config.php` and configure your node's RPC credentials. The application will connect to your node via RPC and report statistics.

To use Google Analytics, simply create a file called `google_analytics.inc` inside the `php` directory and paste your GA code into it.

## Collecting Connection Statistics

The script can also periodically collect the current number of connections as well as peer protocol versions and store it for display via Google Charts on your status page.

To do this, just schedule the `/stats.php` script as well as the `/peercount.php` script to be called at whatever interval you like, then `config.php` to enable the chart display. You can optionally tweak the settings under the "Stats" and "Node Count" sections if you want to keep more or less data.

* `/stats.php` will create a graph showing the number of connections over time.
* `/peercount.php` will create a graph showing the most common protocol versions over time.

Below are two example `crontab` entries to call the scripts every five minutes via `curl`. It is **highly recommended** to only allow `127.0.0.1` to call the script, as allowing other IP addresses could lead to your node becoming vulnerable to DDoS attacks.

```
*/5 *  *   *   *  curl -Ssk http://127.0.0.1/stats.php > /dev/null
*/5 *  *   *   *  curl -Ssk http://127.0.0.1/peercount.php > /dev/null
```

## Node Profile Icons

To configure profile icons for your node, just set them up using the `node_links` variable in `config.php`. The format is a multi-dimensional array, as
below. Icon images for Bitnodes.21.co and Blockchain.info are included in the `img` directory.

```
    'node_links' => array (
        array (
            'name' => 'Bitnodes.21.co',
            'image'=> 'img/bitnodes.21.co.png',
            'link' => 'https://bitnodes.21.co/nodes/[IP]-[PORT]/'
        ),
        array (
            ...
        )
    ),
```

## Peer Count Nodes

The node count script automatically counts Core, Classic, Unlimited and BitcoinJ clients. To add more node types to the chart, simply add an entry into the `peercount_extra_nodes` array in `config.php`.

The key of the entry is an internal-only identifier, and the value is the lower-case text that should be matched in order to increment the counter.

## Contributing

[![Buy me a beer!](https://cdn.changetip.com/img/graphics/Beer_Graphic.png)](https://www.changetip.com/tipme/craigwatson1987)

Contributions and testing reports are extremely welcome. Please submit a pull request or issue on [GitHub](https://github.com/craigwatson/bitcoind-status), and make sure
that your code conforms to the PEAR PHP coding standards (Travis CI will test your pull request when it's sent).

I accept tips via [ChangeTip](https://www.changetip.com/tipme/craigwatson1987) in any currency - if you would like to buy me a beer, please do!

## Advanced Options

The `config.php` file also contains lots of options to control how the application behaves, and is separated out into loose sections:

### RPC

| Value        | Type    | Default     | Explanation                        |
|--------------|---------|-------------|------------------------------------|
| `rpc_user`   | String  | `rpcuser`   | Username for RPC calls             |
| `rpc_pass`   | String  | `rpcpass`   | Password for RPC calls             |
| `rpc_host`   | String  | `localhost` | Which RPC host to connect to       |
| `rpc_port`   | String  | `8332`      | Port to use for the RPC connection |
| `rpc_ssl`    | Boolean | `false`     | Enables SSL for the RPC connection |
| `rpc_ssl_ca` | String  | `null`      | The SSL CA chain file              |

### Donations

| Value                   | Type    | Default   | Explanation                                 |
|-------------------------|---------|-----------|---------------------------------------------|
| `display_donation_text` | Boolean | `true`    | Display text to encourage donations         |
| `donation_address`      | String  | `not_set` | Bitcoin address to advertise for donations  |
| `donation_amount`       | String  | `0.001`   | Donation amount - not currently implemented |

### Peers

| Value                 | Type    | Default   | Explanation                                     |
|-----------------------|---------|-----------|-------------------------------------------------|
| `display_peer_info`   | Boolean | `false`   | Display connected peers                         |
| `display_peer_port`   | Boolean | `false`   | Display remote peer's port                      |
| `hide_dark_peers`     | Boolean | `true`    | Hides peers connected from "Dark" networks      |
| `ignore_unknown_ping` | Boolean | `false`   | Hides peers that do not report pingtime         |
| `peers_to_ignore`     | Array   | `array()` | List of peers *not* to display in the peer list |

### Cache

| Value               | Type    | Default                       | Explanation                                                |
|---------------------|---------|-------------------------------|------------------------------------------------------------|
| `use_cache`         | Boolean |  `true`                       | Enable cache                                               |
| `cache_file`        | String  |  `/tmp/bitcoind-status.cache` | File location to write to for cache                        |
| `max_cache_time`    | Int     |  `300`                        | Expiry time for cache                                      |
| `nocache_whitelist` | Array   |  `array('127.0.0.1')`         | The IP addresses that are allowed to bypass or clear cache |

### Geolocation

| Value                 | Type    | Default | Explanation                   |
|-----------------------|---------|---------|-------------------------------|
| `geolocate_peer_ip`   | Boolean | `false` | Geolocate peers' IP addresses |
| `display_ip_location` | Boolean | `false` | Geolocate node IP address     |

### UI

| Value   | Type    | Default | Explanation                                                                                                                          |
|----------------------------|---------|-----------|-----------------------------------------------------------------------------------------------------------------|
| `display_ip`               | Boolean | `false`   | Display the server IP address                                                                                   |
| `display_free_disk_space`  | Boolean | `false`   | Displayfree disk space                                                                                          |
| `display_testnet`          | Boolean | `false`   | Display testnet status                                                                                          |
| `display_version`          | Boolean | `true`    | Display node `bitcoind` version                                                                                 |
| `display_github_ribbon`    | Boolean | `true`    | Displays the 'Fork me on GitHub' ribbon                                                                         |
| `display_max_height`       | Boolean | `false`   | Displays the node height as a percentage of network height                                                      |
| `use_bitcoind_ip`          | Boolean | `true`    | Use the Bitcoin daemon to get the public IP, instead of `$_SERVER`                                              |
| `intro_text`               | String  | `not_set` | Introductory text to display above the node statistics.                                                         |
| `display_bitnodes_info`    | Boolean | `false`   | Displays various information via the bitnodes.21.co API                                                         |
| `display_chart`            | Boolean | `false`   | Displays a chart showing the stats collected by the stats.php script                                            |
| `display_peer_chart`       | Boolean | `false`   | Displays a chart showing the mix of node versions connected to your node                                        |
| `node_links`               | Array   | `array()` | Displays links to various other profiles for your node. Takes the form of a multidimensional array, see example |

### Stats

| Value                   | Type   | Default                     | Explanation                                            |
|-------------------------|--------|-----------------------------|--------------------------------------------------------|
| `stats_whitelist`       | Array  | `array('127.0.0.1')`        | Hosts that can run the stats script                    |
| `stats_file`            | String | `/tmp/bitcoind-status.data` | File to store stats                                    |
| `stats_max_age`         | String | `604800`                    | Maximum age for stats                                  |
| `stats_min_data_points` | Int    | `5`                         | Minimum data points to collect before displaying chart |

### Peer Count Stats

| Value                       | Type    | Default                     | Explanation                                                  |
|-----------------------------|---------|-----------------------------|--------------------------------------------------------------|
| `peercount_whitelist`       | Array   | `array('127.0.0.1')`        | Hosts that can run the host-count script                     |
| `peercount_file`            | String  | `/tmp/bitcoind-peers.data`  | File to store host-count                                     |
| `peercount_max_age`         | String  | `604800`                    | Maximum age for host-count                                   |
| `peercount_min_data_points` | Int     | `5`                         | Minimum data points to collect before displaying chart       |
| `peercount_extra_nodes`     | Array   | `array()`                   | Key-Value array of extra node types to count (value = regex) |

### Uptime

| Value                     | Type    | Default    | Explanation                                                 |
|---------------------------|---------|------------|-------------------------------------------------------------|
| `display_bitcoind_uptime` | Boolean | `true`     | Displays the uptime of the Bitcoin daemon                   |
| `bitcoind_process_name`   | String  | `bitcoind` | Name to use when getting the bitcoin daemon process' uptime |

### System

| Value         | Type    | Default             | Explanation                                                  |
|---------------|---------|---------------------|--------------------------------------------------------------|
| `date_format` | String  | `H:i:s T, j F Y`    | PHP date fuction format to use when outputting dates         |
| `timezone`    | String  | `null`              | Timezone to use for dates. Set to null to use system default |
| `stylesheet`  | String  | `v2-light.css`      | CSS Stylesheet to use                                        |
| `debug`       | Boolean | `false`             | If enabled, the contents of $data is echoed in HTML comments |
| `admin_email` | String  | `admin@example.com` | Email address to display on error                            |

#### Important Note

  *  **Do not** disable cache unless you either have an alternative mechanism or your node is protected from potential DDoS attacks.

## Licensing

* Copyright (C) 2015 [Craig Watson](http://www.cwatson.org)
* Distributed under the terms of the [Apache License v2.0](http://www.apache.org/licenses/LICENSE-2.0) - see [LICENSE file](https://github.com/craigwatson/bitcoind-status/blob/master/LICENSE) for details.
* [EasyBitcoin-PHP library](https://github.com/aceat64/EasyBitcoin-PHP) is reproduced under the terms of the [MIT licence](http://opensource.org/licenses/MIT) and is used from commit [670414e](https://github.com/aceat64/EasyBitcoin-PHP/tree/670414e1b733e11bb7bdf4fcb17169853301716b).
