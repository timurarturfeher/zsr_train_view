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
    global $conn;
    $mysqli = $conn;
    $mysqli->set_charset("utf8");

    // Truncate the table
    if (!$mysqli->query("TRUNCATE TABLE train_data")) {
        logMessage("Truncate Error: " . $mysqli->error);
        die("Truncate Error: " . $mysqli->error);
    }

    // Prepare the insert statement
    $sql = "INSERT INTO train_data (
                StanicaZCislo, StanicaDoCislo, Nazov, TypVlaku, CisloVlaku, NazovVlaku, 
                Popis, Meska, Dopravca, InfoZoStanice, MeskaText
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        logMessage("Prepare Error: " . $mysqli->error);
        die("Prepare Error: " . $mysqli->error);
    }

    foreach ($data as $train) {
        // Bind parameters (all as strings, adjust types if needed)
        $stmt->bind_param(
            "iisssssssss",
            $train['StanicaZCislo'] ?? null,
            $train['StanicaDoCislo'] ?? null,
            $train['Nazov'] ?? null,
            $train['TypVlaku'] ?? null,
            $train['CisloVlaku'] ?? null,
            $train['NazovVlaku'] ?? null,
            $train['Popis'] ?? null,
            $train['Meska'] ?? null,
            $train['Dopravca'] ?? null,
            $train['InfoZoStanice'] ?? null,
            $train['MeskaText'] ?? null
        );

        if ($stmt->execute()) {
            logMessage("Insert OK for train: " . json_encode($train));
        } else {
            logMessage("Insert Error for train: " . json_encode($train) . " - Error: " . $stmt->error);
        }
    }

    $stmt->close();
    $mysqli->close();

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
