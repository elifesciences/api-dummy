Vagrant.configure(2) do |config|

    config.vm.box = "ubuntu/trusty64"

    config.vm.network "forwarded_port", guest: 8080, host: 8080

    config.vm.network "private_network", type: "dhcp"
    config.vm.synced_folder ".", "/vagrant", type: "nfs"

    config.vm.provision :salt do |salt|

        salt.minion_config = "salt/etc/minion"
        salt.run_highstate = true
        salt.verbose = true

    end

    config.vm.provider "virtualbox" do |virtualbox|
        virtualbox.customize ["modifyvm", :id, "--memory", "4096"]
        virtualbox.customize ["modifyvm", :id, "--cpus", "2"]
        virtualbox.customize ["modifyvm", :id, "--cpuexecutioncap", "50"]
    end

end
