<?php

require 'vendor/autoload.php';

use Goutte\Client;
use Longman\TelegramBot\Request;

$client = new Client();
$url= "https://www.polovniautomobili.com/auto-oglasi/pretraga?brand=fiat&model%5B%5D=punto&brand2=&price_from=400&price_to=1000&year_from=2003&year_to=&fuel%5B%5D=45&fuel%5B%5D=2310&flywheel=&atest=&region%5B%5D=Vojvodina&door_num=&submit_1=&without_price=1&date_limit=&showOldNew=all&modeltxt=&engine_volume_from=&engine_volume_to=&power_from=&power_to=&mileage_from=&mileage_to=&emission_class=&seat_num=&wheel_side=&registration=&country=&country_origin=&city=&registration_price=&page=&sort=";
$carIds = [];
$carLinks = [];
$bot_api_key  = 'apikey';
$bot_username = 'Polovni_krsevi_bot';

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
            'car_link' => $carLink,
        ]);

        try {
            // Create Telegram API object
            $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

            $data = [
                'chat_id' => 'chat_id',
                'text'    => "https://www.polovniautomobili.com" . $carLink,
            ];

            // Send message
            $response = Request::sendMessage($data);

            // Handle telegram getUpdates request
            //$telegram->getUpdates();

        } catch (Longman\TelegramBot\Exception\TelegramException $e) {
            // log telegram errors
            // echo $e->getMessage();
        }
    } else {
        return;
    }
}