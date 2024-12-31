<style>
    <?php include("styles/style.css")?>
</style>
<?php

if (file_exists("/.settings.php")){
    require_once("/.settings.php");
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
function svdRecommendations($ratingsMatrix, $k = 2, $steps = 5000, $alpha = 0.002, $beta = 0.02) {
    $numUsers = count($ratingsMatrix);
    $numItems = count($ratingsMatrix[0]);

    // Инициализируем матрицы P и Q
    $P = initializeMatrix($numUsers, $k);
    $Q = initializeMatrix($numItems, $k);

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

        // Выходим, если ошибка стала достаточно малой
        if ($totalError < 0.001) {
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

    return $resultMatrix;
}

// Функция для вычисления скалярного произведения двух векторов
function dotProduct($vector1, $vector2) {
    $dotProduct = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $dotProduct += $vector1[$i] * $vector2[$i];
    }
    return $dotProduct;
}

function printMatrix($matrix, $header = "") {
    echo "<table>";
    if ($header){
            echo "<tr style='width: 100px;'><th>".$header."</th></tr>";
    }

    echo "<tr>";
    echo "<th style='width: 100px;'> u/p</th>";
    for ($i = 0; $i < count($matrix[0]); $i++) {
        echo "<th  style='width: 100px;'> ".($i+1)." </th>";
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

// Пример использования
$ratingsMatrix = [
    [5, 3, 0, 1],
    [4, 0, 0, 1],
    [1, 1, 0, 5],
    [0, 0, 5, 4],
    [0, 3, 4, 0]
];
printMatrix($ratingsMatrix, "ratingsMatrix");

$predictedMatrix = svdRecommendations($ratingsMatrix);

echo "<pre>";

printMatrix($predictedMatrix, "predictedMatrix");
echo "</pre>";
?>
