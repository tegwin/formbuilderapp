<?php
// includes/functions.php

function generate_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug . '-' . substr(md5(uniqid(rand(), true)), 0, 5);
}
?>
