<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

dump(AlvarezERP::construirdatospedido(Tools::getValue('id'), ''));
