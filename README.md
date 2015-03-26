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

This application has been tested with PHP 5.4, 5.5 and 5.6, as well as HHVM and Nightly PHP builds.

## Getting Started

To get started, all you need to do is copy/rename `php/config.sample.php` to `php/config.php` and configure your node's RPC credentials. The application will connect to your node via RPC and report statistics.

To use Google Analytics, simply create a file called `google_analytics.inc` inside the `php` directory and paste your GA code within.

## Contributing

[![Buy me a beer!](https://cdn.changetip.com/img/graphics/Beer_Graphic.png)](https://www.changetip.com/tipme/craigwatson1987)

Contributions and testing reports are extremely welcome. Please submit a pull request or issue on [GitHub](https://github.com/craigwatson/bitcoind-status), and make sure 
that your code conforms to the PEAR PHP coding standards (Travis CI will test your pull request when it's sent).

I accept tips via [ChangeTip](https://www.changetip.com/tipme/craigwatson1987) in any currency - if you would like to buy me a beer, please do!

## Advanced Options

The `config.php` file also contains lots of options to control how the application behaves:

  * `debug` (Boolean, default: `false`) - Enables debug mode. This prints the full `$data` array into the page's HTML output inside a comment.
  * `rpc_user`(String, default: `rpcuser`) - Username for RPC calls.
  * `rpc_pass` (String, default: `rpcpass`) - Password for RPC calls.
  * `rpc_host` (String, default: `localhost`) - Which RPC host to connect to.
  * `rpc_port` (String, default: `8332`) - Port to use for the RPC connection.
  * `rpc_ssl` (Boolean, default: `false`) - Should we use SSL for the RPC connection?
  * `rpc_ssl_ca` (String, default: `null`) - The SSL CA chain file.
  * `cache_file` (String, default: `/tmp/bitcoind-status.cache`) - File location to write to for cache.
  * `max_cache_time` (Int, default: `300`) - Expiry time for cache.
  * `nocache_whitelist` (Array, default: `array('127.0.0.1')`) - The IP addresses that are allowed to bypass or clear cache.
  * `admin_email` (String, default: `admin@domain.net`) - Email address to display on error
  * `display_donation_text` (Boolean, default: `true`) - Display text to encourage donations.
  * `donation_address` (String, default: `not_set`) - Bitcoin address to use for donations to support the node.
  * `donation_amount` (String, default: `0.001`) - Donation amount - not currently implemented.
  * `use_bitcoind_ip` (Boolean, default: `false`) - Use the Bitcoin daemon to get the public IP, instead of `$_SERVER`
  * `intro_text` (String, default: `not_set`) - Introductory text to display above the node statistics.
  * `display_peer_info` (Boolean, default: `false`) - Display connected peers.
  * `display_peer_port` (Boolean, default: `false`) - Display remote peer's port.
  * `geolocate_peer_ip` (Boolean: default: `true`) - Attempt to Geolocate peer's IP addresses
  * `hide_dark_peers` (Boolean: default: `true`) - Hides peers connected from "Dark" networks.
  * `display_free_disk_space` (Boolean, default: `false`) - Displayfree disk space.
  * `display_ip_location` (Boolean, default: `false`) - Attempt geolocation of node IP.
  * `display_testnet` (Boolean, default: `true`) - Display testnet status.
  * `display_version` (Boolean, default: `false`) - Display node `bitcoind` version.
  * `display_github_ribbon` (Boolean, default: `true`) - Displays the 'Fork me on GitHub vanity ribbon.
  * `display_bitcoind_uptime` (Boolean, default: `true`) - Displays the uptime of the Bitcoin daemon.
  * `use_cache` (Boolean, default: `true`) - Enable cache.
  * `date_format` (String, default: 'H:i:s e, j F Y') - PHP date fuction format to use when outputting dates.
  * `stylesheet` (String, default: `v2-light.css`) - CSS Stylesheet to use.

#### Important Note

  *  **Do not** disable cache unless you either have an alternative mechanism or your node is protected from potential DDoS attacks.

## Licensing

* Copyright (C) 2015 [Craig Watson](http://www.cwatson.org)
* Distributed under the terms of the [Apache License v2.0](http://www.apache.org/licenses/LICENSE-2.0) - see [LICENSE file](https://github.com/craigwatson/bitcoind-status/blob/master/LICENSE) for details.
* [EasyBitcoin-PHP library](https://github.com/aceat64/EasyBitcoin-PHP) is reproduced under the terms of the [MIT licence](http://opensource.org/licenses/MIT) and is used from commit [670414e](https://github.com/aceat64/EasyBitcoin-PHP/tree/670414e1b733e11bb7bdf4fcb17169853301716b).
