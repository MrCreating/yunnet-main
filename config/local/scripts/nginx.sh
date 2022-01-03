#!/usr/bin/env bash
echo "Starting nginx service complilation."
export dollar='$'
export https="https"
export host="host"
export uri="uri"
if [[ "$UNT_PRODUCTION" == "1" ]]; then
export PRODUCTION_NGINX_HTTPS_CODE="
if ($dollar$https != 'on') {
	return 301 https://$dollar$host$dollar$uri;
} 

listen 443 ssl;
ssl_certificate /home/unt/config/local/config/nginx/fullchain.pem;
ssl_certificate_key /home/unt/config/local/config/nginx/privkey.pem;
ssl_trusted_certificate /home/unt/config/local/config/nginx/cert.pem;"
fi
if [[ "$UNT_PRODUCTION" == "1" ]]; then
	echo "Production environmet creating..."
else
	echo "Not the Production environmet creating..."
fi
touch /etc/nginx/yunnet.conf
envsubst '${PRODUCTION_NGINX_HTTPS_CODE}' < /home/unt/config/local/config/nginx/yunnet.conf > /etc/nginx/yunnet.conf
echo "Config complied. Starting..."
nginx -g "daemon off;" > "/dev/null"
