PUPPET_VERSION = "5.3.3"
MODULES = [
  { name: "Slashbunny-phpfpm", version: "0.0.13" },
  { name: "puppet-nginx", git: "https://github.com/voxpupuli/puppet-nginx.git" },
  { name: "CraigWatson1987-bitcoind" },
  { name: "puppetlabs-concat", version: "4.0.1" }
]

Vagrant.configure("2") do |c|

  # Check for Puppet plugin
  unless Vagrant.has_plugin?("vagrant-puppet-install")
    raise 'vagrant-puppet-install is not installed!'
  end

  # Static config
  ip            = '172.16.100.24'
  c.vm.hostname = 'bitcoind-status.test.local'
  c.vm.box      = 'ubuntu/xenial64'
  c.puppet_install.puppet_version = PUPPET_VERSION

  # Synchronised folder
  if Vagrant::Util::Platform.darwin?
    c.vm.synced_folder ".", "/vagrant", type: "nfs"
    c.nfs.map_uid = Process.uid
    c.nfs.map_gid = Process.gid
  else
    c.vm.synced_folder ".", "/vagrant"
  end

  # IP & VM custommisation
  c.vm.network :private_network, ip: ip
  c.vm.provider "virtualbox" do |vb|
    vb.customize ["modifyvm", :id, "--memory", 1024]
    vb.gui = false
  end

  # Fix TTY messages
  c.vm.provision :shell, :inline => "(grep -q -E '^mesg n$' /root/.profile && sed -i 's/^mesg n$/tty -s \\&\\& mesg n/g' /root/.profile && echo 'Ignore the previous error about stdin not being a tty. Fixing it now...') || exit 0;"

  # Handle Puppet 3 and 4/5 paths
  if PUPPET_VERSION.start_with?('3')
    puppet_bin_path = '/usr/bin/puppet'
    module_path = '/etc/puppet/modules'
  else
    puppet_bin_path = '/opt/puppetlabs/bin/puppet'
    module_path = '/etc/puppetlabs/code/environments/production/modules'
  end

  # Install git ... with Puppet!
  c.vm.provision :shell, :inline => "#{puppet_bin_path} resource package git ensure=present"

  # Install modules
  MODULES.each do |mod|
    if mod[:git].nil?
      if mod[:version].nil?
        mod_version = ''
      else
        mod_version = " --version #{mod[:version]}"
      end
      c.vm.provision :shell, :inline => "#{puppet_bin_path} module install #{mod[:name]}#{mod_version}"
    else
      mod_name = mod[:name].split('-').last
      c.vm.provision :shell, :inline => "if [ ! -d #{module_path}/#{mod_name} ]; then git clone #{mod[:git]} #{module_path}/#{mod_name}; fi"
    end
  end

  # Provision with Puppet
  c.vm.provision :shell, :inline => "STDLIB_LOG_DEPRECATIONS=false #{puppet_bin_path} apply --verbose --show_diff /vagrant/manifests/default.pp"

  # Move config file
  c.vm.provision :shell, :inline => "/bin/bash -c 'if [ ! -f /vagrant/php/config.php ]; then cp /vagrant/php/config.vagrant.php /vagrant/php/config.php; fi'"

  # Finally, output VM's hostname to terminal
  c.vm.provision :shell, :inline => "echo \"Status Page URL: http://$(hostname --fqdn)\" or http://#{ip}"

end
