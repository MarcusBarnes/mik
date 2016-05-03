# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure(2) do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.post_up_message = "Next Step:\nvagrant ssh\ncd /vagrant/mik\n./mik --config=extras/lsu/configuration_files/alias.ini --limit=1
  \nTo Pull a git update:\n"

  config.vm.provision "shell", inline: <<-SHELL
    sudo apt-get update && sudo apt-get upgrade -y
    sudo apt-get install -y php5-cli git default-jre
    if ! type composer;  then
      curl -OL https://getcomposer.org/installer
      php installer
      sudo mv composer.phar /usr/local/bin/composer
    fi
    cd /vagrant && composer install
  SHELL
end
