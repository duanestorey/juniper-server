#!/bin/bash

composer update
composer install
cp config/site-mirror.yaml ./site.yaml
php build.php
