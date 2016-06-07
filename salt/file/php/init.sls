php-ppa:
    cmd.run:
        - name: apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C
    pkgrepo.managed:
        - humanname: Ondřej Surý PHP PPA
        - ppa: ondrej/php
        - require:
            - cmd: php-ppa

php-cli:
    pkg.installed:
        - name: php7.0-cli
        - require:
            - pkgrepo: php-ppa

php-curl:
    pkg:
        - installed
        - require:
            - pkg: php-cli

php-gd:
    pkg:
        - installed
        - require:
            - pkg: php-cli

php-mbstring:
    pkg:
        - installed
        - require:
            - pkg: php-cli

php-zip:
    pkg:
        - installed
        - require:
            - pkg: php-cli

php-xml:
    pkg:
        - installed
        - require:
            - pkg: php-cli
