<style>
    <?php include("styles/style.css")?>
</style>
<?php

if (file_exists(".settings.php")){
    require_once(".settings.php");
}

if (file_exists("DB/DataContext.php")){
    require_once("DB/DataContext.php");
}
if (file_exists("DB/PgQuery.php")){
    require_once("DB/PgQuery.php");
}


// Функция для инициализации матриц
function initializeMatrix($rows, $cols, $value = null) {
    $matrix = [];
    for ($i = 0; $i < $rows; $i++) {
        $matrix[$i] = array_fill(0, $cols, $value ?? rand() / getrandmax());
    }
    return $matrix;
}

// Функция для транспонирования матрицы
function transposeMatrix($matrix) {
    $transposed = [];
    foreach ($matrix as $row) {
        foreach ($row as $colIndex => $value) {
            $transposed[$colIndex][] = $value;
        }
    }
    return $transposed;
}

// Основной алгоритм SVD
function svdRecommendations($ratingsMatrix, $k = 5, $steps = 1000, $alpha = 0.001, $beta = 0.05, $eps = 0.02) {
    $numUsers = count($ratingsMatrix);
    $numItems = count($ratingsMatrix[0]);

    // Инициализируем матрицы P и Q
    $P = initializeMatrix($numUsers, $k);
    $Q = initializeMatrix($k, $numItems);

    // Транспонируем Q для удобства вычислений
    $Q = transposeMatrix($Q);
    print_r($Q[7]);
    for ($step = 0; $step < $steps; $step++) {
        for ($i = 0; $i < $numUsers; $i++) {
            for ($j = 0; $j < $numItems; $j++) {
                if ($ratingsMatrix[$i][$j] > 0) { // Обрабатываем только существующие оценки
                    $error = $ratingsMatrix[$i][$j] - dotProduct($P[$i], $Q[$j]);
                    if ($j == 10 && $i == 0){
                        print_r(" ".$error." ");
                    }
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

        echo $step." ".$totalError."\n";
        // Выходим, если ошибка стала достаточно малой

        unset($i);
        unset($y);
        //unset($step);

        if ($totalError <= $eps) {

            break;
        }
    }
    //printMatrix($P, "P");
    //printMatrix($Q, "Q");
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

global $flag;
$flag = false;
// Функция для вычисления скалярного произведения двух векторов
function dotProduct($vector1, $vector2) {
    $dotProduct = 0;
    global $flag;
    if (!$flag)
    {
        //print_r($vector1);
        //print_r($vector2);
        $flag = true;
    }
    for ($i = 0; $i < count($vector1); $i++) {
        $dotProduct += $vector1[$i] * $vector2[$i];
    }
    return $dotProduct;
}

function printMatrix($matrix, $header = "") {
    echo "<table>";
    if ($header){
            echo "<caption>".$header."</caption>";
    }

    echo "<tr>";
    echo "<th> u/p</th>";
    for ($i = 0; $i < count($matrix[0]); $i++) {
        echo "<th> ".($i+1)." </th>";
    }
    echo "</tr>";

    for ($i = 0; $i < count($matrix); $i++) {
        echo "<tr><th> ".($i+1)." </th>>";

        for ($y = 0; $y < count($matrix[$i]); $y++) {
            echo "<td>".$matrix[$i][$y]."</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
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
function countSeven($matrix)
{
    $count = 0;
    foreach ($matrix as $i) {
        foreach ($i as $y) {
            if (round($y) >= 9) {
                $count++;
            }
        }
    }
    return $count;
}
// Пример использования
$ratingsMatrix = [
    [10, 7, 0, 3],
    [8, 0, 0, 1],
    [3, 1, 0, 10],
    [0, 0, 5, 4],
    [0, 9, 4, 0]
];

$matrix = json_decode(file_get_contents("cache/matrix.json"));
if (!$matrix) {
    $query = new PgQuery(PGCONNECT);

    $matrix = $query->getSVDDataTable();
    file_put_contents("cache/matrix.json", json_encode($matrix));
}

//print_r($matrix);
//printMatrix($ratingsMatrix, "Исходные данные");
print_r(countNotNull($matrix));

$predictedMatrix = json_decode(file_get_contents("cache/predicted.json"));
if (!$predictedMatrix) {
    $predictedMatrix = svdRecommendations($matrix);

    file_put_contents("cache/predicted.json", json_encode($predictedMatrix));
}

var_dump($matrix[0]);
var_dump($predictedMatrix[0]);

print_r(countSeven($predictedMatrix));

//printMatrix($predictedMatrix, "Прогнозируемые значения");

