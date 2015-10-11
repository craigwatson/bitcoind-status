node default {

  include apt

  class { 'apache':
    docroot    => '/vagrant',
    mpm_module => 'prefork',
  }

  include php::params
  include php::apache
  include php::extension::curl
  include apache::mod::php

  class { 'bitcoind':
    rpcallowip  => ['127.0.0.1'],
    rpcpassword => 'statustest',
    rpcuser     => 'status',
    testnet     => true,
  }

  Php::Extension <| |> -> Php::Config <| |> ~> Service['httpd']

}
