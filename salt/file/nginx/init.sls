nginx:
    pkg:
        - installed
    service:
        - running
        - require:
            - pkg: nginx
    file.managed:
        - name: /etc/nginx/nginx.conf
        - source: salt://nginx/config/etc-nginx-nginx.conf
        - listen_in:
            - service: nginx

nginx-default-vhost:
    file.absent:
        - name: /etc/nginx/sites-available/default
        - listen_in:
            - service: nginx
        - require:
            - pkg: nginx
