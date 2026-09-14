#!/bin/bash
# 6    => ES
# 15   => PT
# 8    => FR
# 1    => DE
# 10   => IT
# 2    => AU

php /home/alvarez/web/scripts/coding/precios.php 6
sleep 1

php /home/alvarez/web/scripts/coding/precios.php 15
sleep 1

php /home/alvarez/web/scripts/coding/precios.php 8
sleep 1

php /home/alvarez/web/scripts/coding/precios.php 1
sleep 1

php /home/alvarez/web/scripts/coding/precios.php 10
sleep 1

php /home/alvarez/web/scripts/coding/precios.php 2
sleep 1

php /home/alvarez/web/scripts/coding/precios_analizar_csv.php
