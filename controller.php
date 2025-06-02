<?php

// Функция для инициализации матриц
function initializeMatrix($rows, $cols, $value = null): array
{
    $matrix = [];
    for ($i = 0; $i < $rows; $i++) {
        $matrix[$i] = array_fill(0, $cols, $value ?? rand() / getrandmax());
    }
    return $matrix;
}

// Функция для транспонирования матрицы
function transposeMatrix($matrix): array
{
    $transposed = [];
    foreach ($matrix as $row) {
        foreach ($row as $colIndex => $value) {
            $transposed[$colIndex][] = $value;
        }
    }
    return $transposed;
}

// Основной алгоритм SVD
function svdRecommendations($ratingsMatrix, $k = 5, $steps = 1000, $alpha = 0.001, $beta = 0.05, $eps = 0.02): array
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


// Функция для вычисления скалярного произведения двух векторов
function dotProduct($vector1, $vector2): float|int
{
    $dotProduct = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $dotProduct += $vector1[$i] * $vector2[$i];
    }
    return $dotProduct;
}


function countNotNull($matrix)
{
    $count = 0;
    foreach ($matrix as $i) {
        foreach ($i as $y) {
            if ($y > 0) {
                $count++;
            }
        }
    }
    return $count;
}

function getUserKnowingRatings($matrix, $userId): array
{
    $result = [];
    foreach ($matrix[$userId] as $key => $item) {
        if ($item != 0){
            $result[] = $key;
        }
    }
    return $result;
}

function getRecommendation($matrix, $predictedMatrix, $userId, $maxRating=10)
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
