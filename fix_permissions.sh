#!/bin/bash
sudo chown -R www-data:www-data /var/www/konvix_ecommerce/var/cache
sudo chmod -R 775 /var/www/konvix_ecommerce/var/cache
sudo php bin/console cache:clear --env=prod
