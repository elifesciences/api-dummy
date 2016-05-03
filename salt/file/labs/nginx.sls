labs-vhost:
    file.managed:
        - name: /etc/nginx/sites-enabled/labs.conf
        - source: salt://labs/config/etc-nginx-sites-enabled-labs.conf
        - require:
            - pkg: nginx
        - listen_in:
            - service: nginx
