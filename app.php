<?php

require_once 'DataBase.php';

use \MDB\DataBase;

function recursive ($arr, $variation, $level, $result, $sizeArr) {
    $level++;		
    if ($level < $sizeArr) {
        foreach ($arr[$level] as $value) {
            $variation[$level] = $value;
            $result = recursive($arr, $variation, $level, $result, $sizeArr);
        }
    }else {
        $result[] = $variation;
    }		
    return $result;	
}

function tuply($arr) {
    $variation = [];
    $result = [];
    $sizeArr = count($arr);
    return recursive($arr, $variation, -1, $result, $sizeArr);
}

function getCombinations($arr, $n) {
    $results = [];
    if ($n == 1) {
        return $arr;
    } 
    
    if ($n == count($arr)) {
        $results[] = implode(',', $arr);
        return $results;
    }
 
    $tmpArr = $arr;
    $lastElement = array_pop($tmpArr);
 
    $subSet = getCombinations($tmpArr, ($n-1));
 
    foreach ($subSet as $combination) {
        $results[] = $combination . ',' . $lastElement;
    }
 
    $subSet = getCombinations($tmpArr, $n);
 
    foreach ($subSet as $combination) {
        $results[] = $combination;
    }
    return $results;
}

function main($argv) {
    if (empty($argv[1])) {
        return "Bad request";
    } else {
        $input = $argv[1];
    }
    
    if (!preg_match('/^[a-z]+$/', $input)) {
        return "Bad request";
    }
    
    $input = str_split($input);
    $count_ingredient_type = array_count_values($input);
    
    $db = new DataBase();
    
    $distinct_type = array_unique($input);
    
    $search_parameters = '';
    foreach ($distinct_type as $item) {
        $search_parameters .= "ingredient_type.code = '$item' or ";
    }
    $search_parameters = substr($search_parameters, 0, -4);
    
    $ingredients = $db->query("SELECT ingredient.id AS type_id, ingredient_type.title AS type_title, ingredient_type.code AS type_code, ingredient.title AS ingredient_title, ingredient.price AS ingredient_price FROM `ingredient_type` INNER JOIN `ingredient` ON ingredient_type.id = ingredient.type_id WHERE $search_parameters;");
    
    $ingredientsMenu = [];
    $ingredientsById = [];
    
    foreach ($ingredients as $ingredient) {
        $ingredientsMenu[$ingredient->type_code]['items'][$ingredient->type_id] = [
            'type' => $ingredient->type_title,
            'value' => $ingredient->ingredient_title,
            'price' => $ingredient->ingredient_price
        ];
        $ingredientsById[$ingredient->type_id] = [
            'type' => $ingredient->type_title,
            'value' => $ingredient->ingredient_title,
            'price' => $ingredient->ingredient_price
        ];
    }
    
    $combinations = [];
    
    foreach ($count_ingredient_type as $type => $count) {
        if (!array_key_exists($type, $ingredientsMenu)) {
            return 'Bad request';
        } else {
            $arr = [];
            foreach ($ingredientsMenu[$type]['items'] as $key => $item) {
                $arr[] = $key;
            }
            if ($count > count($arr)) {
                return 'Bad request';
            }
            $combinations[] = getCombinations($arr, $count);
        }
    }
    
    $result = [];
    $tuplys = tuply($combinations);
    foreach ($tuplys as $tuply) {
        $resultItem = [
            'products' => []
        ];
        $price = 0;
        foreach ($tuply as $line) {
            foreach (explode(',', strval($line)) as $id) {
                $resultItem['products'][] = [
                    'type' => $ingredientsById[$id]['type'],
                    'value' => $ingredientsById[$id]['value']
                ];
                $price += $ingredientsById[$id]['price'];  
            }
        }
        $resultItem['price'] = $price;
        $result[] = $resultItem;
    }
    
    return json_encode($result, JSON_UNESCAPED_UNICODE);
}

echo main($argv);

