<?php
require_once 'db.php';
$jsonFilePath = 'stations.json';
if (file_exists($jsonFilePath)) {
    $jsonData = file_get_contents($jsonFilePath);
    $stationsData = json_decode($jsonData, true);
    $stations = $stationsData['stations']; 
} else {
    echo 'JSON file not found';
}
$jsonData = file_get_contents($jsonFilePath);
$stations = json_decode($jsonData, true);

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
$jsonFilePath = 'stations.json';
if (file_exists($jsonFilePath)) {
    $jsonData = file_get_contents($jsonFilePath);
    $stationsData = json_decode($jsonData, true);
    $stations = $stationsData['stations'];
} else {
    echo 'JSON file not found';
}
$jsonData = file_get_contents($jsonFilePath);
$stations = json_decode($jsonData, true);



function getTrainStationName($stations, $stid, $train, $pos) {
    // Validate input data
    if ($stations === null) {
        return 'Error decoding the stations data';
    }

    // Get stations list and validate
    $stationsList = $stations['stations'] ?? [];
    if (empty($stationsList)) {
        return 'Error: No stations found';
    }

    // Search for station by ID
    foreach ($stationsList as $station) {
        if (isset($station['id']) && (string)$station['id'] === (string)$stid) {
            return $station['name'];
        }
    }

    // If no station found, try to get foreign station
    return getForeignStation($train, $pos);
}

function getForeignStation($trainInfo, $pos) {
    if (empty($trainInfo)) {
        return "Zahraničie";
    }

    // Match pattern like "EC 283 BUDAPEST -> HAMBURG"
    if (preg_match('/^[A-Z\s]+\s+(.*?)\s*->\s*(.*)$/', $trainInfo, $matches)) {
        $town1 = isset($matches[1]) ? trim($matches[1]) : '';
        $town2 = isset($matches[2]) ? trim($matches[2]) : '';
        
        if ($pos == 1) {
            return removeCapitalWord(removeNumber($town1));
        } elseif ($pos == 2) {
            return removeCapitalWord(removeNumber($town2));
        }
    }
    
    return "Zahraničie";
}
function getMeskanie($string) {
    if (preg_match('/\d+/', $string, $matches)) {
        $number = $matches[0];
        $meska_234 = array(2,3,4);

        if(in_array($number,$meska_234)) {
            $ext = "minúty";
        } elseif($number=1) {
            $ext = "minúta";
        } else {
            $ext = "minút";
        }
        if(($number > 0) && ($number <= 5)) {
            $color = "green";
        } elseif(($number > 5) && ($number <= 15)) {
            $color = "darkorange";
        } elseif(($number > 15) && ($number <= 30)) {
            $color = "red";
        } else {
            $color = "red";
            $meskanie_large = "text-decoration-line: underline;";
        }
        if(strpos($string, '*') !== false) {
            $meskanie_addon = "Meškanie pred odchodom";
        }
        if(strpos($string, 'náskok') !== false) {
            $meskanie_addon_2 = "- ";
        }
        $number_final = $number. " ". $ext;
        return $number_final;
    } elseif($string == "Vlak čakajúci na odchod") {
        return 'Vlak sa ešte nepohol';
    } else {
        return 'Nemá meškanie';
    }
}
// Get the train number from the GET request
$trainNumber = $_GET['trainNumber'] ?? '';

if ($trainNumber) {

    // Query the database to fetch train data based on train number
    $stmt = $conn->prepare("SELECT * FROM train_data WHERE CisloVlaku = ?");
    $stmt->bind_param("s", $trainNumber);  // 's' for string type binding
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $trainData = $result->fetch_assoc();

        // Process the station codes
        $stanicaZCislo = substr($trainData['StanicaZCislo'], 0, -2);
        $stanicaDoCislo = substr($trainData['StanicaDoCislo'], 0, -2);

        // Get the station names for StanicaZCislo and StanicaDoCislo
        $trainData['StanicaZ'] = getTrainstationName($stations,$stanicaZCislo,$trainData["Nazov"],1);
        $trainData['StanicaDo'] = getTrainstationName($stations,$stanicaDoCislo,$trainData["Nazov"],2);
        $trainData['MeskaText'] = getMeskanie($trainData['MeskaText']);
        // Return the modified train data with station names
        echo json_encode($trainData);
    } else {
        echo json_encode([]);  // Return empty if no train is found
    }

    $stmt->close();
} else {
    echo json_encode([]);  // Return empty JSON if no train number is provided
}

$conn->close();
?>
