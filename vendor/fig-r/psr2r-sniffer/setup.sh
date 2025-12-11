# Make sure this file is executable
# chmod +x setup.sh

# Download composer phar if not available yet
[ ! -f composer.phar ] && curl -sS https://getcomposer.org/installer | php

# Download phpunit phar if not available yet
[ ! -f phpunit.phar ] && wget https://phar.phpunit.de/phpunit.phar && chmod +x phpunit.phar



