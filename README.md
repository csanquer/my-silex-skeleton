my-silex-skeleton
=================

a silex application skeleton


Nginx vhost example
-------------------

```
server {
    listen 80 ;

    root /var/www/my-silex-skeleton/web;
    index index.php index.html index.htm;

    server_name silex.local;

    #auth_basic           "RESTRICTED ACCESS";
    #auth_basic_user_file /var/www/my-silex-skeleton/.htpasswd;

    access_log /var/log/nginx/my-silex-skeleton.access.log;
    error_log /var/log/nginx/my-silex-skeleton.error.log;

    location / { 
        # try to serve file directly, fallback to rewrite
        try_files $uri @rewriteapp;
    }   

    location @rewriteapp {
        rewrite ^(.*)$ /index.php/$1 last;
    }   

    location ~ ^/(index|index_dev|config|info)\.php(/|$) {
        fastcgi_pass unix:/var/run/php5-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        #fastcgi_param HTTPS off;
        #fastcgi_buffer_size 256k;
        #fastcgi_buffers 16 512k;
        #fastcgi_busy_buffers_size 512k;
    }   

    # deny access to .htaccess files, if Apache's document root
    # concurs with nginx's one 
    #   
    location ~ /\.ht {
        deny all;
    }   
}

```