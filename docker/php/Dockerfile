FROM php:7.4

# Container + PHP Setup
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zlib1g-dev \
    git-core \
    unzip \
    gnupg2 \
    mariadb-client \
    subversion

RUN pecl install xdebug-2.9.0
RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable xdebug

# Install Composer
RUN curl -s https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

RUN mkdir /var/cache/composer
VOLUME /var/cache/composer
RUN chown -R www-data:www-data /var/cache/composer
ENV COMPOSER_HOME=/var/cache/composer

# WP CLI Setup
ENV WORDPRESS_CLI_GPG_KEY 63AF7AA15067C05616FDDD88A3A2E8F226F0BC06
ENV WORDPRESS_CLI_VERSION 2.5.0
ENV WORDPRESS_CLI_SHA512 08dd9035fda1d529807380d5b757839e2809e289eb1a698fe33e7e21a1431d3f77c551c2b2db5adc55083d5075ea4137407994111890f765e790a97e6d9ca7af

RUN set -ex; \
	curl -o /usr/local/bin/wp.gpg -fSL "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
	\
	export GNUPGHOME="$(mktemp -d)"; \
	gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$WORDPRESS_CLI_GPG_KEY"; \
	gpg --batch --decrypt --output /usr/local/bin/wp /usr/local/bin/wp.gpg; \
	command -v gpgconf && gpgconf --kill all || :; \
	rm -rf "$GNUPGHOME" /usr/local/bin/wp.gpg; \
	\
	echo "$WORDPRESS_CLI_SHA512 */usr/local/bin/wp" | sha512sum -c -; \
	chmod +x /usr/local/bin/wp; \
	\
	wp --allow-root --version

ENV WP_TESTS_DIR /usr/src/vendor/wordpress
ENV WP_TESTS_CONFIG_FILE_PATH /usr/src/tests/functional

VOLUME /usr/src
WORKDIR /usr/src