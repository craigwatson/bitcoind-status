Vagrant.configure("2") do |config|

  ip                 = '172.17.100.24'
  config.vm.hostname = 'test.vikingserv.net'
  config.vm.box      = 'ubuntu/trusty64'

  config.vm.synced_folder "./", "/vagrant", type: "nfs"
  config.vm.network :private_network, ip: ip
  config.vm.provider "virtualbox" do |vb|
    vb.customize ["modifyvm", :id, "--memory", 2048]
    vb.gui = false
  end

  # Fix TTY messages
  config.vm.provision :shell, :inline => "(grep -q -E '^mesg n$' /root/.profile && sed -i 's/^mesg n$/tty -s \\&\\& mesg n/g' /root/.profile && echo 'Ignore the previous error about stdin not being a tty. Fixing it now...') || exit 0;"

  # Install Puppet
  config.vm.provision :shell, :inline => "
    puppet --version
    if [ ! -f /etc/apt/sources.list.d/puppetlabs.list ]; then
      wget -q -O /tmp/puppet.deb https://apt.puppetlabs.com/puppetlabs-release-trusty.deb
      sudo dpkg -i /tmp/puppet.deb > /dev/null 2>&1
      sudo apt-get -qq update > /dev/null 2>&1
      sudo apt-get -qq install git puppet > /dev/null 2>&1
      rm /tmp/puppet.deb
      puppet --version
    fi

    if [ $(cat /etc/puppet/puppet.conf | grep 'templatedir' | wc -l) -gt 0 ]; then
      sed -i '/templatedir/d' /etc/puppet/puppet.conf
    fi
  "

  # Install Puppet modules
  config.vm.provision :shell, :inline => "
    for MODULE in nodes-php puppetlabs-apache CraigWatson1987-bitcoind; do
      if [ ! -d /etc/puppet/modules/${MODULE#*-} ]; then sudo puppet module install $MODULE; fi
    done
  "

  # Provision with Puppet
  config.vm.provision "puppet"

end
