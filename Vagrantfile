Vagrant.configure("2") do |config|

  # Statis configuration
  ip                 = '172.16.100.24'
  config.vm.hostname = 'bitcoind-status.test.local'
  config.vm.box      = 'ubuntu/trusty64'

  # Synchronised folder
  if Vagrant::Util::Platform.darwin?
    config.vm.synced_folder ".", "/vagrant", type: "nfs"
    config.nfs.map_uid = Process.uid
    config.nfs.map_gid = Process.gid
  else
    config.vm.synced_folder ".", "/vagrant"
  end

  # IP & VM custommisation
  config.vm.network :private_network, ip: ip
  config.vm.provider "virtualbox" do |vb|
    vb.customize ["modifyvm", :id, "--memory", 1024]
    vb.gui = false
  end

  # Fix TTY messages
  config.vm.provision :shell, :inline => "(grep -q -E '^mesg n$' /root/.profile && sed -i 's/^mesg n$/tty -s \\&\\& mesg n/g' /root/.profile && echo 'Ignore the previous error about stdin not being a tty. Fixing it now...') || exit 0;"

  # Install Puppet repo
  config.vm.provision :shell, :inline => "
    if [ ! -f /etc/apt/sources.list.d/puppetlabs-pc1.list ]; then
      echo 'Adding Puppetlabs apt repository'
      wget -q -O /tmp/puppet.deb https://apt.puppetlabs.com/puppetlabs-release-pc1-trusty.deb
      sudo dpkg -i /tmp/puppet.deb > /dev/null 2>&1
      sudo apt-get -qq update > /dev/null 2>&1
      sudo apt-get -qq purge puppet > /dev/null 2>&1
      sudo apt-get -qq autoremove > /dev/null 2>&1
      rm /tmp/puppet.deb
    fi
  "

  # Install Puppet binary & Augeas lenses
  config.vm.provision :shell, :inline => "if [ ! -x /opt/puppetlabs/puppet/bin/puppet ]; then echo 'Installing Puppet binary' && sudo apt-get -qq install puppet-agent > /dev/null 2>&1; fi"
  config.vm.provision :shell, :inline => "if [ ! -d /usr/share/augeas/lenses ]; then echo 'Installing augeas-leneses' && sudo apt-get -qq install augeas-lenses > /dev/null 2>&1; fi"

  # Install Puppet modules
  config.vm.provision :shell, :inline => "
    for MODULE in nodes-php puppetlabs-apache CraigWatson1987-bitcoind; do
      if [ ! -d /etc/puppetlabs/code/environments/production/modules/${MODULE#*-} ]; then
        echo \"Installing Puppet module $MODULE\"
        sudo /opt/puppetlabs/puppet/bin/puppet module install $MODULE > /dev/null 2>&2
      fi
    done
  "

  # Provision with Puppet
  config.vm.provision :shell, :inline => "echo 'Running Puppet' && /opt/puppetlabs/puppet/bin/puppet apply --show_diff --verbose /vagrant/manifests/default.pp"

  # Finally, output VM's hostname to terminal
  config.vm.provision :shell, :inline => "echo \"Status Page URL: http://$(hostname --fqdn)\" or http://#{ip}"

end
