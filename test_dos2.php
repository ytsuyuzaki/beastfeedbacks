<?php
class Test {
    public function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
        if ( $current_depth > $max_depth ) {
            return '';
        }

        if ( is_array( $value ) ) {
            if ( $current_depth === $max_depth ) {
                return '';
            }

            $sanitized_array = array();
            $max_items       = 50;
            $item_count      = 0;

            foreach ( $value as $key => $item ) {
                if ( $item_count >= $max_items ) {
                    break;
                }

                $sanitized_item_key = (string) $key;
                $sanitized_item_key = substr( $sanitized_item_key, 0, 100 );
                if ( '' === $sanitized_item_key ) {
                    continue;
                }

                $sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );
                ++$item_count;
            }

            return $sanitized_array;
        }

        if ( is_object( $value ) ) {
            return '';
        }

        return $value;
    }
}

$t = new Test();
$array = [];
$ref = &$array;
for ($i = 0; $i < 5; $i++) {
    $ref['child'] = [];
    $ref = &$ref['child'];
}

$start = microtime(true);
$res = $t->sanitize_param_value($array);
$end = microtime(true);

echo "Time: " . ($end - $start) . "\n";
print_r($res);
