FROM ministryofjustice/wp-multisite-base@sha256:c5e00286c251f60ce7248019e180c9beade0ac417d753fd864e7f696f33b7a01

ADD . /bedrock

WORKDIR /bedrock

ARG COMPOSER_USER
ARG COMPOSER_PASS

# Add custom nginx config, monitoring tools and init script
RUN sed -i 's/fastcgi_intercept_errors off;/fastcgi_intercept_errors on;/' /etc/nginx/php-fpm.conf && \
    echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' > /etc/apt/sources.list.d/newrelic.list && \
    curl -fsSL https://download.newrelic.com/548C16BF.gpg | apt-key add - && \
    mv docker/init/configure-maintenance-mode.sh /etc/my_init.d/ && \
    mv docker/conf/php-fpm/newrelic.ini /etc/php/7.4/fpm/conf.d/ && \
    apt-get update && \
    apt-get install -y libffi-dev newrelic-php5 && \
    chmod +x /etc/my_init.d/configure-maintenance-mode.sh

# Set execute bit permissions before running build scripts
RUN chmod +x bin/* && sleep 1 && \
    make clean && \
    bin/composer-auth.sh && \
    make build && \
    rm -f auth.json
