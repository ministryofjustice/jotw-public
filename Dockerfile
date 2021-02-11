FROM ministryofjustice/wp-multisite-base:latest
ADD . /bedrock
WORKDIR /bedrock
ARG COMPOSER_USER
ARG COMPOSER_PASS
# Add custom nginx config and init script
RUN sed -i 's/fastcgi_intercept_errors off;/fastcgi_intercept_errors on;/' /etc/nginx/php-fpm.conf
# Set execute bit permissions before running build scripts
RUN composer install