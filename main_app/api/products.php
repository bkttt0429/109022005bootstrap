<?php
// Static product catalog used by PHP endpoints (sync with js/shop-data.js)
// Add stock to enable simple inventory validation and reporting.

function shop_products()
{
    return [
        1 => ['id'=>1, 'title'=>'黑色經典 T-Shirt', 'price'=>19.99, 'stock'=>30],
        2 => ['id'=>2, 'title'=>'運動鞋 (白)', 'price'=>69.99, 'stock'=>25],
        3 => ['id'=>3, 'title'=>'水壺 750ml', 'price'=>12.50, 'stock'=>80],
        4 => ['id'=>4, 'title'=>'牛仔外套', 'price'=>89.00, 'stock'=>18],
        5 => ['id'=>5, 'title'=>'登山背包 20L', 'price'=>59.99, 'stock'=>22],
        6 => ['id'=>6, 'title'=>'耳機（藍牙）', 'price'=>49.00, 'stock'=>40],
        7 => ['id'=>7, 'title'=>'智慧手錶', 'price'=>129.00, 'stock'=>15],
        8 => ['id'=>8, 'title'=>'偏光太陽眼鏡', 'price'=>39.99, 'stock'=>50],
        9 => ['id'=>9, 'title'=>'鋁合金筆電架', 'price'=>42.00, 'stock'=>35],
        10 => ['id'=>10, 'title'=>'雙層保溫杯', 'price'=>18.00, 'stock'=>60],
    ];
}

function find_product($id)
{
    $products = shop_products();
    return $products[$id] ?? null;
}
