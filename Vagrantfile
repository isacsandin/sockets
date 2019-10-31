# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.network "private_network", ip: "192.168.77.15"
  config.vm.hostname = "app"

  # Virtual machine name.
  config.vm.define :app

  config.vm.provider :virtualbox do |v|
    # Provider name. Will be visible on the list of VMs through the GUI.
    v.name = "app"
    v.memory = 512
    v.cpus = 1
  end

   config.vm.provision "shell", inline: <<-SHELL
     apt-get update
     apt-get install -y php5-cli
   SHELL
end
