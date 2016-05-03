{% set composer_home = '/home/vagrant/.composer' %}

composer-home:
    environ.setenv:
        - name: COMPOSER_HOME
        - value: {{ composer_home }}
    cmd.run:
        - name: echo 'export COMPOSER_HOME={{ composer_home }}' > /etc/profile.d/composer-home.sh
        - require:
            - environ: composer-home

composer-install:
    cmd.run:
        - cwd: /usr/local/bin/
        - name: |
            wget -O - https://getcomposer.org/installer | php
            mv composer.phar composer
        - require:
            - cmd: composer-update
        - unless:
            - which composer

composer-global-paths:
    cmd.run:
        - name: echo 'export PATH={{ composer_home }}/vendor/bin:$PATH' > /etc/profile.d/composer-global-paths.sh
        - require:
            - cmd: composer-install

composer-update:
    cmd.run:
        - name: composer self-update
        - require:
            - environ: composer-home
            - pkg: php-cli
            - pkg: php-zip
        - onlyif:
            - which composer

composer:
    file.directory:
        - name: {{ composer_home }}
        - user: vagrant
        - group: vagrant
        - recurse:
            - user
            - group
        - require:
            - cmd: composer-install
