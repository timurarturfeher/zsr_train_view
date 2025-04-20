<?php
require 'db.php';
header('Content-Type: application/json');
$mysqli = $conn;
$mysqli->set_charset("utf8");

$trainNumber = isset($_GET['trainNumber']) ? $_GET['trainNumber'] : null;
if (!$trainNumber) {
    echo json_encode(['error' => 'No train number provided']);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM train_data WHERE CisloVlaku = ? LIMIT 1");
$stmt->bind_param("s", $trainNumber);
$stmt->execute();
$result = $stmt->get_result();
$trainData = $result->fetch_assoc();

$stations = json_decode(file_get_contents('stations.json'), true);

function removeCapitalWord($input) {
    $result = preg_replace('/\b[A-ZŔŇŤÝÁÍÉĽŠČŽ]+\b/u', '', $input);
    $result = preg_replace('/\s+/', ' ', $result);
    return trim($result);
}
function removeNumber($input) {
    $result = preg_replace('/\b\d+\b/', '', $input);
    $result = preg_replace('/\s+/', ' ', $result);
    return trim($result);
}
function getForeignStation($trainInfo, $pos) {
    $stationsJson = file_get_contents('stations.json');
    if ($stationsJson === FALSE) {
        die('Error fetching the stations JSON file');
    }
    $stationsData = json_decode($stationsJson, true);
    if ($stationsData === null) {
        die('Error decoding the JSON data');
    }
    $matches = [];
    if (preg_match('/^[A-Z\s]+\s+(.*)\s*->\s*(.*)$/', $trainInfo, $matches) === 1) {
        $town1 = isset($matches[1]) ? trim($matches[1]) : '';
        $town2 = isset($matches[2]) ? trim($matches[2]) : '';
        if ($pos == 1) {
            $town1 = removeCapitalWord(removeNumber($town1));
            return $town1;
        } elseif ($pos == 2) {
            $town2 = removeCapitalWord(removeNumber($town2));
            return $town2;
        }
    } else {
        return "Zahraničie";
    }
}
function getStation($station, $data, $type) {
    global $stations;
    if ($stations === null) {
        return 'Error decoding the stations data';
    }
    $stationsList = $stations['stations'];
    if (empty($stationsList)) {
        return 'Error: No stations found';
    }
    $station = substr($station, 0, -2);
    $stationName = null;
    foreach ($stationsList as $stationItem) {
        if (is_array($stationItem) && array_key_exists('id', $stationItem)) {
            if ((string)$stationItem['id'] === (string)$station) {
                $stationName = $stationItem['name'];
                break;
            }
        }
    }
    if ($stationName !== null) {
        return $stationName;
    } else {
        return getForeignStation($data, $type);
    }
}
if ($trainData) {
    $trainData['StanicaZ'] = getStation($trainData['StanicaZCislo'], $trainData["Nazov"], 1);
    $trainData['StanicaDo'] = getStation($trainData['StanicaDoCislo'], $trainData["Nazov"], 2);
    echo json_encode($trainData);
} else {
    echo json_encode(['error' => 'No data found for this train']);
}
?>
