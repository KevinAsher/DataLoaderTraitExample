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

function formatGraphqlFields($arr, $defaultResolver = null) {
    return array_map(function ($el) {
        if (is_array($el)) {
            return $el;
        }

        $newEl = ['type' => $el];

        if (isset($defaultResolver)) {
            $newEl['resolve'] = $defaultResolver;
        }

        return $newEl;
    }, $arr);

}