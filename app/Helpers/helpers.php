<?php

function nullIfArrayEmptyValues($arr) {
    $testArr = array_filter($arr, function($item) { return !empty($item); });
    return empty($testArr) ? null : $arr; 
}

function firstOrNull($arr) {
    if (empty($arr)) {
        return null;
    }
    
    return reset($arr);
}