<style>
    <?php include("styles/style.css")?>
</style>

<?php
global $query;
global $predictedMatrix;
global $matrix;


if (file_exists(".settings.php")){
    require_once(".settings.php");
}
if (file_exists("DB/DataContext.php")){
    require_once("DB/DataContext.php");
}
if (file_exists("DB/PgQuery.php")){
    require_once("DB/PgQuery.php");
}
if (file_exists("controller.php")){
    require_once("controller.php");
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

function printWeb($userIData, $productsRecIds, $products)
{
    global $matrix;

    print_r("<h1>Имя текущего пользователя - ".$userIData["name"]."</h1>
Логин текущего пользователя - ".$userIData["login"]."
<br>Email текущего пользователя - ".$userIData["email"]);

    print_r("<br>");

    echo "<h2>Товары</h2>";
    echo "<div class='product-list'><table><tr>";
    foreach ($products as $key => $item){
        if ($matrix[$userIData["id"]-1][$key]){
            print_r("<th>\"".$item["Product Name"]."\"
    (".$matrix[$userIData["id"]-1][$key].")</th>");
        }
    }
    echo "</tr><tr>";
    foreach ($products as $key => $item){
        if($matrix[$userIData["id"]-1][$key]) {
                print_r("<td><b>Бренд</b> - " . $item["BrandName"] . "
    <br><b>Цена </b>- " . $item["MRP"] . "
    <br><b>Размер </b>- " . $item["Product Size"] . "
    <br><b>Категория </b>- " . $item["Category"] . "</td>"
            );
        }
    }
    echo "</tr>";
    echo "</table></div>";

    print_r("<br>");

    echo "<h2>Рекоммендованные товары</h2><ul>";
    foreach ($productsRecIds as $key => $item){
        print_r("<li>\"".$products[$item]["Product Name"]."\"
<ul><li>Бренд - ".$products[$item]["BrandName"]."
</li><li>Цена - ".$products[$item]["MRP"]."
</li><li>Размер - ".$products[$item]["Product Size"]."
</li><li>Категория - ".$products[$item]["Category"]."</li></ul></li>"
        );
    }
    echo "</ul>";
}
?>


<form action="" method="post">
    <label for="userId">Введите id пользователя</label><br>
    <input type="text" id="userId" name="userId" value="0"><br>
    <input type="submit" value="Submit">
</form>

<?php
$userI = 0;
if($_POST["userId"]) {
    $userI = $_POST["userId"];
}
$userIData = $query->getList("Users",$userI+1);

$productsRecIds = getRecommendation($matrix, $predictedMatrix, $userI);

$maxRating = 10;

//даём хоть какие-нибудь рекомендации
while (!$productsRecIds) {
    print_r("Максимальный рейтинг - ".$maxRating."<br>");
    $productsRecIds = getRecommendation($matrix, $predictedMatrix, $userI, --$maxRating);
}
print_r("Максимальный рейтинг - ".$maxRating);
$products = $query->getList("Product");


//print_r($userIData);
printWeb($userIData, $productsRecIds, $products);

print_r("<br>*** Текущий максимальный рейтинг {$maxRating}");
?>

<h1>С этим товаром также покупают</h1>
<form action="" method="post">
    <label for="productId">Введите id товара</label><br>
    <input type="text" id="productId" name="productId" value="0"><br>
    <input type="submit" value="Submit">
</form>
<?php
$productI = 0;
if($_POST["productId"]) {
    $productI = $_POST["productId"];
}
$productsARL = getARLRecommendation($products, $productI);
//print_r("<br>");
if($productsARL) {
    echo "<h2>Товары</h2>";
    echo "<div class='product-list'><table><tr>";
    foreach ($productsARL as $key => $item) {
        print_r("<th>\"" . $item["Product Name"]);
    }
    echo "</tr><tr>";
    foreach ($productsARL as $key => $item) {

        print_r("<td><b>Бренд</b> - " . $item["BrandName"] . "
    <br><b>Цена </b>- " . $item["MRP"] . "
    <br><b>Размер </b>- " . $item["Product Size"] . "
    <br><b>Категория </b>- " . $item["Category"] . "</td>"
        );

    }
    echo "</tr>";
    echo "</table></div>";
}