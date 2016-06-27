api-vhost:
    file.managed:
        - name: /etc/nginx/sites-enabled/api.conf
        - source: salt://api/config/etc-nginx-sites-enabled-api.conf
        - require:
            - pkg: nginx
        - listen_in:
            - service: nginx
