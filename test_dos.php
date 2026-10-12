<?php
$array = [];
$ref = &$array;
for ($i = 0; $i < 5; $i++) {
    $ref['child'] = [];
    $ref = &$ref['child'];
}

print_r($array);
