php-fpm:
    pkg.installed:
        - name: php7.0-fpm
        - require:
            - pkg: php-cli
    service.running:
        - name: php7.0-fpm
        - require:
            - pkg: php-fpm
    file.managed:
        - name: /etc/php/7.0/fpm/pool.d/www.conf
        - source: salt://php/config/etc-php-7.0-fpm-pool.d-www.conf
        - listen_in:
            - service: php-fpm
