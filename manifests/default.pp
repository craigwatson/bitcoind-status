node default {

  include ::apt

  Class['::apt::update'] -> Package <| title != 'software-properties-common' |>

  class { '::apache':
    mpm_module    => 'prefork',
    default_vhost => false,
  }

  ::apache::vhost { $::fqdn:
    port           => '80',
    docroot        => '/vagrant',
    manage_docroot => false,
  }

  include ::php::params
  include ::php::apache
  include ::php::extension::curl
  include ::apache::mod::php

  Php::Extension <| |> -> Php::Config <| |> ~> Service['httpd']

  class { '::bitcoind':
    rpcallowip          => ['127.0.0.1'],
    rpcpassword         => 'statustest',
    rpcuser             => 'status',
    testnet             => true,
    disablewallet       => true,
    use_bitcoin_classic => false,
  }

  cron { 'bitcoind_stats':
    command => '/usr/bin/curl -Ssk http://127.0.0.1/stats.php > /dev/null',
    user    => 'root',
    minute  => '*/5',
  }

  cron { 'bitcoind_peer_stats':
    command => '/usr/bin/curl -Ssk http://127.0.0.1/peercount.php > /dev/null',
    user    => 'root',
    minute  => '*/5',
  }

}
