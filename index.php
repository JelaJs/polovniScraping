<?php


require 'vendor/autoload.php';

use Goutte\Client;
$client = new Client();
$url = "https://www.polovniautomobili.com/auto-oglasi/pretraga?brand=ford&model%5B%5D=fiesta&brand2=&price_from=&price_to=4000&year_from=2008&year_to=2011&fuel%5B%5D=45&fuel%5B%5D=2309&flywheel=&atest=&region%5B%5D=Vojvodina&door_num=3013&submit_1=&without_price=1&date_limit=&showOldNew=all&modeltxt=&engine_volume_from=&engine_volume_to=&power_from=&power_to=&mileage_from=&mileage_to=&emission_class=&gearbox%5B%5D=3211&seat_num=&wheel_side=&air_condition%5B%5D=3159&air_condition%5B%5D=3160&registration=&country=&country_origin=&city=&registration_price=&page=&sort=";
$carIds = [];
$carLinks = [];

$pdo = new PDO("sqlite:polovni.db");

// Set error mode to exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$crawler = $client->request("GET", $url);
$crawler->filter('.classified')->each(function ($node) use (&$carIds, &$carLinks) {
    $carIds[] = $node->attr('data-classifiedid');
    $carLinks[] = $node->filter('.firstImage')->attr('href');
});

foreach ($carIds as $index => $carId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cars WHERE car_id = :car_id");
    $stmt->execute(['car_id' => $carId]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $carLink = $carLinks[$index];
        $insert = $pdo->prepare("INSERT INTO cars (car_id, car_link) VALUES (:car_id, :car_link)");
        $insert->execute([
            'car_id' => $carId,
            'car_link' => $carLink
        ]);
    }
}