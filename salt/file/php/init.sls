php-ppa:
    pkgrepo.managed:
        - humanname: Ondřej Surý PHP PPA
        - ppa: ondrej/php

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
