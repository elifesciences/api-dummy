api-composer:
    composer.installed:
        - user: vagrant
        - name: /vagrant
        - no_dev: True
        - prefer_dist: True
        - optimize: True
        - composer_home: /home/vagrant/.composer
        - require:
            - file: composer
