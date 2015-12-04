all:
	php FacebookTotalsErrors.php
install:
	curl -sS "https://getcomposer.org/installer" | php
	php composer.phar install --no-dev
