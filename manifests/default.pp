node default {

  include apt

  Class['::apt::update'] -> Package <| title != 'software-properties-common' |>

  class { 'apache':
    mpm_module    => 'prefork',
    default_vhost => false,
  }

  apache::vhost { $::fqdn:
    port           => '80',
    docroot        => '/vagrant',
    manage_docroot => false,
  }

  include php::params
  include php::apache
  include php::extension::curl
  include apache::mod::php

  Php::Extension <| |> -> Php::Config <| |> ~> Service['httpd']

  class { 'bitcoind':
    rpcallowip    => ['127.0.0.1'],
    rpcpassword   => 'statustest',
    rpcuser       => 'status',
    testnet       => true,
    disablewallet => true,
  }

}
