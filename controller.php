<?php

declare(strict_types=1);

/**
 * Функция для инициализации матриц
 * @param int $rows
 * @param int $cols
 * @param null $value
 * @return array
 */
function initializeMatrix(int $rows, int $cols, $value = null): array
{
    $matrix = [];
    for ($i = 0; $i < $rows; $i++) {
        $matrix[$i] = array_fill(0, $cols, $value ?? rand() / getrandmax());
    }
    return $matrix;
}

/**
 * Функция для транспонирования матрицы
 * @param array $matrix
 * @return array
 */
function transposeMatrix(array $matrix): array
{
    $transposed = [];
    foreach ($matrix as $row) {
        foreach ($row as $colIndex => $value) {
            $transposed[$colIndex][] = $value;
        }
    }
    return $transposed;
}

/**
 * Основной алгоритм SVD
 * @param $ratingsMatrix
 * @param int $k
 * @param int $steps
 * @param float $alpha
 * @param float $beta
 * @param float $eps
 * @return array
 */
function svdRecommendations($ratingsMatrix,
                            int $k = 5,
                            int $steps = 1000,
                            float $alpha = 0.001,
                            float $beta = 0.05,
                            float $eps = 0.02): array
{
    $numUsers = count($ratingsMatrix);
    $numItems = count($ratingsMatrix[0]);

    // Инициализируем матрицы P и Q
    $P = initializeMatrix($numUsers, $k);
    $Q = initializeMatrix($k, $numItems);

    // Транспонируем Q для удобства вычислений
    $Q = transposeMatrix($Q);

    for ($step = 0; $step < $steps; $step++) {
        for ($i = 0; $i < $numUsers; $i++) {
            for ($j = 0; $j < $numItems; $j++) {
                if ($ratingsMatrix[$i][$j] > 0) { // Обрабатываем только существующие оценки
                    $error = $ratingsMatrix[$i][$j] - dotProduct($P[$i], $Q[$j]);

                    // Обновляем P и Q с учетом градиентного спуска
                    for ($kIndex = 0; $kIndex < $k; $kIndex++) {
                        $P[$i][$kIndex] += $alpha * ($error * $Q[$j][$kIndex] - $beta * $P[$i][$kIndex]);
                        $Q[$j][$kIndex] += $alpha * ($error * $P[$i][$kIndex] - $beta * $Q[$j][$kIndex]);
                    }
                }
            }
        }

        // Вычисляем текущую ошибку
        $totalError = 0;
        for ($i = 0; $i < $numUsers; $i++) {
            for ($j = 0; $j < $numItems; $j++) {
                if ($ratingsMatrix[$i][$j] > 0) {
                    $totalError += pow($ratingsMatrix[$i][$j] - dotProduct($P[$i], $Q[$j]), 2);
                    for ($kIndex = 0; $kIndex < $k; $kIndex++) {
                        $totalError += ($beta / 2) * (pow($P[$i][$kIndex], 2) + pow($Q[$j][$kIndex], 2));
                    }
                }
            }
        }

        echo $step." ".$totalError."<br>";
        unset($i);
        unset($y);

        // Выходим, если ошибка стала достаточно малой
        if ($totalError <= $eps) {
            break;
        }
    }

    // Результирующая матрица прогнозов
    $resultMatrix = [];
    for ($i = 0; $i < $numUsers; $i++) {
        for ($j = 0; $j < $numItems; $j++) {
            $resultMatrix[$i][$j] = dotProduct($P[$i], $Q[$j]);
        }
    }
    unset($P);
    unset($Q);
    return $resultMatrix;
}


/**
 * Функция для вычисления скалярного произведения двух векторов
 * @param array $vector1
 * @param array $vector2
 * @return float|int
 */
function dotProduct(array $vector1, array $vector2): float|int
{
    $dotProduct = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $dotProduct += $vector1[$i] * $vector2[$i];
    }
    return $dotProduct;
}


/**
 * Получение id значений товара, которым пользователь поставил оценку
 * @param array $matrix
 * @param int $userId
 * @return array
 */
function getUserKnowingRatings(array $matrix, int $userId): array
{
    $result = [];
    foreach ($matrix[$userId] as $key => $item) {
        if ($item != 0){
            $result[] = $key;
        }
    }
    return $result;
}

/**
 * Получение 3ёх товаров с наибольшим рейтингом
 * @param array $matrix
 * @param array $predictedMatrix
 * @param int $userId
 * @param int $maxRating
 * @return array
 */
function getRecommendation(array $matrix, array $predictedMatrix, int $userId, int $maxRating=10): array
{
    $knowingRatingIds = getUserKnowingRatings($matrix, $userId);
    $count = 0;
    $productIds = [];
    foreach ($predictedMatrix[$userId] as $key => $i) {
        if (round($i) >= $maxRating && !in_array($key, $knowingRatingIds)) {
            $count++;
            $productIds[] = $key;
            if ($count>=3){return $productIds;}
        }
    }
    return $productIds;
}

/**
 * @param string $name
 * @param array $products
 * @return array|null
 */
function findProductByName(string $name, array $products): array|null
{
    $products_copy = array_merge([], $products);

    $name = str_replace(' ','', $name);
    foreach ($products_copy as $key => $product) {
        $product["Product Name"] = str_replace(' ','', $product["Product Name"]);
        if($product["Product Name"]===$name){
            return $product;
        }
    }
    return null;
}

/**
 * @param int $id
 * @param array $products
 * @return array|null
 */
function findProductById(int $id, array $products): array|null
{
    foreach ($products as $key => $product) {
        if($key===$id){
            return $product["Product Name"];
        }
    }
    return null;
}

/**
 * @param string $productName
 * @param array $dataARL
 * @return array
 */
function findProductAssociation(string $productName, array $dataARL): array
{
    $productName = str_replace(' ','', $productName);
    $result = [];
    foreach ($dataARL as $key => $item) {
        foreach ($item as $keyItem => $product){
            $product = str_replace(' ','', $product);
            if($productName==$product){
                unset($item[$keyItem]);
                $result = array_merge($result, $item);
            }
        }
    }
    return $result;
}

/**
 *
 * @param array $products
 * @param int $id
 * @return array
 */
function getARLRecommendation(array $products, int $id) : array
{
    $json = file_get_contents("http://127.0.0.1:8000/api/ARLs");
    if (!$json){ return [];}
    $dataJSON = json_decode($json);
//285
    $foundProductName = findProductById($id, $products);
    //echo "<br>".$foundProductName."<br>";
    $association = findProductAssociation($foundProductName, $dataJSON);
    //echo '<br> ARL:'.$association;
    if (!$association){return [];}
    $result = [];
    foreach ($association as $item) {
        $result[] = findProductByName($item, $products);
    }
    return $result;
}

$arResult = [];
$query = new PgQuery(PGCONNECT);

$matrix = json_decode(file_get_contents("cache/matrix.json"));
if (!$matrix) {
    $matrix = $query->getSVDDataTable();
    file_put_contents("cache/matrix.json", json_encode($matrix));
}

$predictedMatrix = json_decode(file_get_contents("cache/predicted.json"));
if (!$predictedMatrix) {
    $predictedMatrix = svdRecommendations($matrix);

    file_put_contents("cache/predicted.json", json_encode($predictedMatrix));
}
