<?php
$content = file_get_contents('tests/phpunit/beastfeedbacks-public/extract-post-params-test.php');
// We need to change the test to expect '' for nested_array
$search = <<<'SEARCH'
		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
SEARCH;

$replace = <<<'REPLACE'
		$this->assertSame( '', $result['nested_array'] );
REPLACE;

$new_content = str_replace($search, $replace, $content);
file_put_contents('tests/phpunit/beastfeedbacks-public/extract-post-params-test.php', $new_content);
