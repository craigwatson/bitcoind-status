node default {

  include ::apt

  # == PHP-FPM

  class { '::phpfpm':
    poold_purge => true,
  }

  ::phpfpm::pool { 'main': }

  package { 'php-curl':
    ensure => present,
    notify => Service['php7.0-fpm'],
  }

  # == Nginx

  class { '::nginx':
    server_purge => true,
    confd_purge  => true,
  }

  ::nginx::resource::server { 'default':
    index_files          => ['index.php'],
    use_default_location => false,
    www_root             => '/vagrant',
  }

  ::nginx::resource::location { 'webroot':
    location => '~ \.php$',
    server   => 'default',
    fastcgi  => '127.0.0.1:9000',
  }

  # == Bitcoin Daemon

  class { '::bitcoind':
    rpcallowip                => ['127.0.0.1'],
    rpcpassword               => 'statustest',
    rpcuser                   => 'status',
    testnet                   => true,
    disablewallet             => true,
    download_bitcoind_version => '0.18.0'
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
