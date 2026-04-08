#!/bin/bash

# Exit on error
set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}1. Installing Composer dependencies...${NC}"
composer install

echo -e "${GREEN}2. Installing NPM dependencies...${NC}"
npm install

echo -e "${GREEN}3. Setting up WordPress test environment...${NC}"
yes y | bash scripts/install-wp-tests.sh \
    wp_test_db \
    wp_test_user \
    wp_test_password \
    127.0.0.1:3306 \
    latest

echo -e "${GREEN}4. Running PHPUnit tests...${NC}"
./vendor/bin/phpunit

echo -e "${GREEN}5. Running JavaScript tests...${NC}"
npm test
