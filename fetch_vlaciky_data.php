<?php
require_once 'db.php';
function getInitialCookie() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://mapa.zsr.sk/index.aspx");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    if ($response === false) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
    $cookies = [];
    foreach ($matches[1] as $cookie) {
        $cookies[] = $cookie;
    }
    return implode('; ', $cookies);
}

function fetchData($cookie) {
    $url = "https://mapa.zsr.sk/api/action";
    $postData = [
        'action' => 'gtall',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Cookie: " . $cookie
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        die("cURL Error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}
function saveToDatabase($data) {
    global $pdo;
    $truncateSql = "TRUNCATE TABLE train_data";
    $pdo->exec($truncateSql);
    $sql = "INSERT INTO train_data (
                StanicaZCislo, StanicaDoCislo, Nazov, TypVlaku, CisloVlaku, NazovVlaku, 
                Popis, Meska, Dopravca, InfoZoStanice, MeskaText
            ) VALUES (
                :StanicaZCislo, :StanicaDoCislo, :Nazov, :TypVlaku, :CisloVlaku, :NazovVlaku, 
                :Popis, :Meska, :Dopravca, :InfoZoStanice, :MeskaText
            )";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $train) {
        $stmt->execute([
            ':StanicaZCislo' => $train['StanicaZCislo'] ?? null,
            ':StanicaDoCislo' => $train['StanicaDoCislo'] ?? null,
            ':Nazov' => $train['Nazov'] ?? null,
            ':TypVlaku' => $train['TypVlaku'] ?? null,
            ':CisloVlaku' => $train['CisloVlaku'] ?? null,
            ':NazovVlaku' => $train['NazovVlaku'] ?? null,
            ':Popis' => $train['Popis'] ?? null,
            ':Meska' => $train['Meska'] ?? null,
            ':Dopravca' => $train['Dopravca'] ?? null,
            ':InfoZoStanice' => $train['InfoZoStanice'] ?? null,
            ':MeskaText' => $train['MeskaText'] ?? null
        ]);
    }
    echo "Data saved to the database successfully.\n";
}
function removeTrasaData(&$data) {
    foreach ($data as &$train) {
        if (isset($train['Trasa'])) {
            unset($train['Trasa']);
        }
    }
}
$cookie = getInitialCookie();
$data = fetchData($cookie);
removeTrasaData($data);
saveToDatabase($data);
