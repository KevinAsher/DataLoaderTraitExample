<?php

function nullIfArrayEmptyValues($arr) {
    $testArr = array_filter($arr, function($item) { return !empty($item); });
    return empty($testArr) ? null : $arr; 
}