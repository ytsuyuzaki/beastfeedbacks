#!/bin/bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
if [ ! -d $WP_TESTS_DIR ]; then
  svn co --quiet https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ $WP_TESTS_DIR
  wget -q https://raw.githubusercontent.com/markjaquith/WordPress-Tests/master/wp-tests-config-sample.php -O $WP_TESTS_DIR/wp-tests-config.php
  sed -i "s/youremptytestdbnamehere/wordpress_test/" $WP_TESTS_DIR/wp-tests-config.php
  sed -i "s/yourusernamehere/root/" $WP_TESTS_DIR/wp-tests-config.php
  sed -i "s/yourpasswordhere//" $WP_TESTS_DIR/wp-tests-config.php
fi
