<?php
require_once 'db.php';
function fetchData() {
    global $pdo;
    $query = "SELECT * FROM train_data";
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error: Failed to fetch data. " . $e->getMessage());
    }
    return $data;
}
function searchData($data, $searchTerms, $searchField) {
    if (empty($searchTerms) || empty($searchField) || $searchField == 'all') {
        return $data;
    }
    $filteredData = [];
    foreach ($data as $item) {
        $matchesAny = false; 
        foreach ($searchTerms as $term) {
            if (isset($item[$searchField]) && $item[$searchField] === $term) {
                $matchesAny = true;
                break; 
            }
        }
        if ($matchesAny) {
            $filteredData[] = $item;
        }
    }
    return $filteredData;
}
$showData = isset($_GET['show']) && $_GET['show'] == 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchField = isset($_GET['field']) ? $_GET['field'] : 'all';
$filteredData = [];

if ($showData || isset($_GET['search'])) {
    $cookie = "";
    $data = fetchData($cookie);
    if (empty($searchTerm)) {
        $filteredData = $data;
    } else {
        $searchTermUpper = strtoupper($searchTerm);
        if (strpos($searchTermUpper, 'LE') !== false || 
            strpos($searchTermUpper, 'ZSSK') !== false || 
            strpos($searchTermUpper, 'RJ') !== false) {
            $searchField = 'Dopravca';
            $searchTerms = [];
            if (strpos($searchTermUpper, 'LE') !== false) {
                $searchTerms[] = "Leo Express Slovensko s.r.o.";
                $searchTerms[] = "Leo Express s.r.o.";
            }
            if (strpos($searchTermUpper, 'ZSSK') !== false) {
                $searchTerms[] = "Železničná spoločnosť Slovensko, a.s.";
            }
            if (strpos($searchTermUpper, 'RJ') !== false) {
                $searchTerms[] = "RegioJet a.s.";
            }
            
        } elseif (preg_match('/\d/', $searchTerm)) {
            $searchField = 'CisloVlaku';
            $searchTerms = array_map('trim', explode(',', $searchTerm));
        } else {
            $searchField = 'TypVlaku';
            $searchTerms = array_map('trim', explode(',', $searchTerm));
        }
        
        $filteredData = searchData($data, $searchTerms, $searchField);
    }
}
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
function getTrainStationName($stations, $stid, $test1, $test2) {
    if ($stations === null) {
        return 'Error decoding the stations data';
    }
    $stationsList = $stations['stations'];
    if (empty($stationsList)) {
        return 'Error: No stations found';
    }
    $stationName = null;
    foreach ($stationsList as $station) {
        if (is_array($station) && array_key_exists('id', $station)) {
            if ((string)$station['id'] === (string)$stid) {
                $stationName = $station['name'];
                break;
            }
        }
    }
    if ($stationName !== null) {
        return $stationName;
    } else {
        switch ($stid) {
            case "101469":
                return '<span class="fi fi-cz fis"></span> priechod Brodské';
            case '100362':
                return '<span class="fi fi-hu fis"></span> priechod Štúrovo';
            case '100859':
                return '<span class="fi fi-cz fis"></span> priechod Čadca';
            case '100461':
                return '<span class="fi fi-at fis"></span> priechod Bratislava';
            case '100008':
                return '<span class="fi fi-ua fis"></span> priechod Čierna nad Tisou';
            case '100503':
                return '<span class="fi fi-hu fis"></span> priechod Čaňa';
            case '100958':
                return '<span class="fi fi-cz fis"></span> priechod Lúky pod Makytou';
            case '101360':
                return '<span class="fi fi-cz fis"></span> priechod Holíč nad Moravou';
            default:
                return 'Station not found';
        }
    }
}
function getForeignStation($trainInfo,$pos) {
    $stationsJson = file_get_contents('stations.json');
    if ($stationsJson === FALSE) {
        die('Error fetching the stations JSON file');
    }
    $stationsData = json_decode($stationsJson, true);
    if ($stationsData === null) {
        die('Error decoding the JSON data');
    }
    foreach ($stationsData['stations'] as $station) {
        if ($station['id'] === $stid) {
            return $station['name'] . ' ' . $stid; 
        }
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
function getDopravca($dopravcaId) {
    if($dopravcaId == "Železničná spoločnosť Slovensko, a.s.") {
        echo "ZSSK";
        $doprava_color = "orange";
    } elseif($dopravcaId == "Leo Express Slovensko s.r.o." || $dopravcaId == "Leo Express s.r.o.") {
        echo "Leo Express";
        $doprava_color = "green";
    } elseif($dopravcaId == "RegioJet a.s.") {
        echo "RegioJet";
        $doprava_color = "yellow";
    }
}
function getMeskanie($string) {
    if (preg_match('/\d+/', $string, $matches)) {
        $number = $matches[0];
        $meska_234 = array(2,3,4);

        if(in_array($number,$meska_234)) {
            $ext = "minúty";
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
            $meskanie_addon = "";
        }
        if(strpos($string, 'náskok') !== false) {
            $meskanie_addon_2 = "- ";
        }
        $number_final = $number. " ". $ext;
        echo '<span id="delay_1" style="color: '.$color.';'.(isset($meskanie_large) ? $meskanie_large : '').'"><strong>'.(isset($meskanie_addon_2) ? $meskanie_addon_2 : '').'' . $number_final . '</strong><br>'.(isset($meskanie_addon) ? $meskanie_addon : '').'</span>';
    } elseif($string == "Vlak čakajúci na odchod") {
        echo '<span id="delay_1" style="color: white">Stojí</span><br>';
    } else {
        echo '<span id="delay_1" style="color: green">Nemá meškanie</span><br>  ';
    }
}
function getTrainColor($type) {
    if($type == "EC") {
        return "lime";
    } elseif($type == "IC") {
        return "blue";
    } elseif($type == "EN") {
        return "blue";
    } elseif($type == "Ex") {
        return "green";
    } elseif($type == "R") {
        return "red";
    } elseif($type == "RJ") {
        return "yellow";
    } elseif($type == "Os") {
        return "gray";
    } elseif($type == "Zr") {
        return "gray";
    } elseif($type == "REX") {
        return "orange";
    }
}
function getTrainStyle($type,$dopravcaId) {
    $train_color = getTrainColor($type);
    if($dopravcaId == "Železničná spoločnosť Slovensko, a.s.") {
        $doprava_color = "orange";
    } elseif($dopravcaId == "Leo Express Slovensko s.r.o." || $dopravcaId == "Leo Express s.r.o.") {
        $doprava_color = "green";
    } elseif($dopravcaId == "RegioJet a.s.") {
        $doprava_color = "yellow";
    }
    if($train_color=="red") {
        $train_color_text = "white";
    } elseif($train_color=="blue") {
        $train_color_text = "white";
    } elseif($train_color=="green" || $train_color=="darkgreen") {
        $train_color_text = "white";
    }
    return "border: 5px solid ".$doprava_color."; padding-left: 10px;";
}
$trainOrder = ['SC', 'EN', 'EC', 'IC', 'RJ', 'R', 'Ex', 'REX', 'Zr','Os'];
function sortByTrainType($a, $b) {
    global $trainOrder;
    $posA = array_search($a['TypVlaku'], $trainOrder);
    $posB = array_search($b['TypVlaku'], $trainOrder);
    if ($posA === false) $posA = count($trainOrder);
    if ($posB === false) $posB = count($trainOrder);
    return $posA - $posB;
}
usort($filteredData, 'sortByTrainType');
function getTrainBadge($dopravcaId, $typVlaku, $cisloVlaku, $nazovVlaku) {
    $logoBasePath = 'clogo/'; 
    switch ($dopravcaId) {
        case "Železničná spoločnosť Slovensko, a.s.":
            $carrierLogo = $logoBasePath . 'zssk.png'; 
            $doprava_color = "white";
            break;
        case "Leo Express Slovensko s.r.o.":
        case "Leo Express s.r.o.":
            $carrierLogo = $logoBasePath . 'leoexpress.png'; 
            $doprava_color = "lime";
            break;
        case "RegioJet a.s.":
            $carrierLogo = $logoBasePath . 'regiojet.png'; 
            $doprava_color = "yellow";
            break;
        default:
            $carrierLogo = $logoBasePath . 'default_logo.png'; 
    }
    $textColor = 'black'; 
    switch ($typVlaku) {
        case 'EN':
            $textColor = 'darkgreen';
            break;
        case 'RJ':
        case 'EC':
        case 'IC':
            $textColor = 'green';
            break;
        case 'R':
            $textColor = 'red';
            break;
        case 'Os':
        case 'REX':
        case 'Zr':
            $textColor = 'blue';
            break;
    }
    $additionalInfo = "";
    $nazovVlaku = ($typVlaku === 'RJ') ? 'RegioJet' : $nazovVlaku;
    $nazovVlaku = ($typVlaku === 'IC') ? '' : $nazovVlaku;
    $nazovVlaku = ($typVlaku === 'Os') ? '' : $nazovVlaku;
    $nazovVlaku = ($typVlaku === 'REX') ? '' : $nazovVlaku;
    $trainIdentifier = $typVlaku . ' ' . $cisloVlaku;
    if (strpos($nazovVlaku, $trainIdentifier) !== false) {
        $nazovVlaku = trim(str_replace($trainIdentifier, '', $nazovVlaku));
    }
    $firstWord = '';
    $words = explode(' ', trim($nazovVlaku)); 
    if (!empty($words[0])) {
        $firstWord = $words[0]; 
        if ($firstWord === 'LEO') {
            $firstWord = 'LeoExpress'; 
        } elseif (strpos($firstWord, '-') !== false) {
            $firstWord = ''; 
        } else {
            $firstWord = mb_strtolower($firstWord, 'UTF-8');  
            $firstWord = mb_strtoupper(mb_substr($firstWord, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($firstWord, 1, null, 'UTF-8');  
        }
    }
    $trainBadge = '<div id="train_badge" style="color: ' . htmlspecialchars($textColor) . '; display: flex; align-items: center; justify-content: start; width:180px; padding: 5px 10px; border: 2px solid #ccc; border-radius: 15px; background-color: ' . htmlspecialchars($doprava_color) . '; cursor: pointer;">';
    $trainBadge .= '<img src="' . htmlspecialchars($carrierLogo) . '" alt="' . htmlspecialchars($dopravcaId) . '" style="border-radius: 15px;width: 30px; height: auto; margin-right: 10px;">';
    $trainBadge .= '<div>';
    $trainBadge .= '<strong>' . htmlspecialchars($typVlaku) . ' ' . htmlspecialchars($cisloVlaku) . '</strong>';
    if (!empty($firstWord)) {
        $trainBadge .= '<br><strong>' . htmlspecialchars($firstWord) . '</strong>';
    }
    $trainBadge .= '</div></div>';
    return $trainBadge;
}
$stations = json_decode(file_get_contents('stations.json'), true);
function stationExists($stationName, $stations) {
    $stationsList = $stations['stations'];
    foreach ($stationsList as $station) {
        if ($station['name'] === $stationName) {
            return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>ZSSK Train Finder</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css"
    />
    <link
        rel="icon"
        href="favicon-light.ico"
        media="screen and (prefers-color-scheme: light)"
    />
    <link 
        rel="icon"
        href="favicon-dark.ico"
        media="screen and (prefers-color-scheme: dark)"
    />
</head>
<body>
    <form method="GET" action="" class="search-form">
        <input 
            type="text" 
            name="search" 
            value="<?php echo (!empty($searchTerm)) ? htmlspecialchars($searchTerm) : ''; ?>" 
            placeholder="Search... (R,EC  /  842,1555  /  Rj,zssk)"
        >
        <button type="submit">Search</button>
    </form>
    <?php if (!empty($filteredData)): ?>
        <table>
            <tr>
                <th style="width: 90px;">Vlak</th>
                <th style="width: 150px;">Odkiaľ</th>
                <th style="width: 20px;"></th> 
                <th style="width: 150px;">Kam</th>
                <th>Meškanie</th>
            </tr>
            <?php foreach ($filteredData as $row): 
                $stanicaZCislo = substr($row['StanicaZCislo'], 0, -2);
                $stanicaDoCislo = substr($row['StanicaDoCislo'], 0, -2);
                $trainColor = '<tr style="' . getTrainStyle($row['TypVlaku'], $row['Dopravca']) . '">';
                $train_badge = getTrainBadge($row['Dopravca'], $row['TypVlaku'], $row['CisloVlaku'], $row['Nazov']);
                echo $trainColor;
            ?>
                <td><?php echo $train_badge; ?></td>
                <td style="width: 80px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php 
                    $stanicaZCisloName = getTrainstationName($stations, $stanicaZCislo, $row['Nazov'], 1);
                    if (stationExists($stanicaZCisloName, $stations)) {
                        echo '<span class="station-name" id="station_from"><a href="https://aplikacie.zsr.sk/tabulezsr/StationDetail.aspx?id=' . 
                            htmlspecialchars($stanicaZCislo) . '&t=2" target="_blank">' . 
                            htmlspecialchars($stanicaZCisloName) . '</a></span>';
                    } else {
                        echo '<span class="station-name" id="station_from">' . getForeignStation($row['Nazov'], 1) . '</span>';
                    }
                ?>
                </td>
                <td style="width: 30px; text-align: center;">&#8608;</td>
                <td style="width: 80px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php 
                    $stanicaDoCisloName = getTrainstationName($stations, $stanicaDoCislo, $row['Nazov'], 2);
                    if (stationExists($stanicaDoCisloName, $stations)) {
                        echo '<span class="station-name" id="station_to"><a href="https://aplikacie.zsr.sk/tabulezsr/StationDetail.aspx?id=' . 
                            htmlspecialchars($stanicaDoCislo) . '&t=2" target="_blank">' . 
                            htmlspecialchars($stanicaDoCisloName) . '</a></span>';
                    } else {
                        echo '<span class="station-name" id="station_to">' . getForeignStation($row['Nazov'], 2) . '</span>';
                    }
                ?>
                </td>
                <td><?php echo htmlspecialchars(getMeskanie($row['MeskaText'])); ?> <span id="delay_2">(<?php echo htmlspecialchars($row['InfoZoStanice']); ?>)</span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <div id="trainModal" style="display:none;">
        <div>
            <span onclick="closeModal()" style="cursor: pointer; float: right;">&times;</span>
            <h2 id="modalTitle"></h2> <p id="modalFromTo"></p>
            <p id="modalContent"></p>
            <p id="modalCarrier"></p>
            <p id="modalDelay"></p>
        </div>
    </div>
<script>
    function wrapText(className, maxChars) {
        const containers = document.querySelectorAll(`.${className}`);
        containers.forEach(container => {
            let text = container.innerText; 
            if (text.length > maxChars) {
                text = text.substring(0, maxChars).trim() + '...'; 
            }
            container.innerText = text;
        });
    }
    wrapText('delay_2', 15);
    let isFetching = false;
    let intervalId; 
    function openModal(trainNumber) {
        clearModal();
        console.log("1." + trainNumber);
        fetchTrainData(trainNumber); 
        const modal = document.getElementById('trainModal');
        modal.style.display = 'flex';
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        intervalId = setInterval(() => {
            fetchTrainData(trainNumber);
        }, 10000); 
    }

    function closeModal() {
        const modal = document.getElementById('trainModal');
        modal.style.display = 'none';
        clearInterval(intervalId);
    }

    function clearModal() {
        document.getElementById('modalTitle').innerText = " ";
        document.getElementById('modalContent').innerText = " ";
        document.getElementById('modalCarrier').innerText = " ";
        document.getElementById('modalFromTo').innerHTML = " ";
        document.getElementById('modalDelay').innerText = " ";
    }
    function fetchTrainData(trainNumber) {
        if (isFetching) return; 
        isFetching = true;
        const xhr = new XMLHttpRequest();
        console.log("2." + trainNumber);
        xhr.open('GET', 'getTrainData.php?trainNumber=' + trainNumber, true); 
        xhr.onload = function() {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (data.CisloVlaku) {
                    document.getElementById('modalTitle').innerText = data.TypVlaku + " " + trainNumber;
                    document.getElementById('modalContent').innerText = data.Nazov;
                    document.getElementById('modalCarrier').innerText = 'Dopravca: ' + data.Dopravca;

                    document.getElementById('modalFromTo').innerHTML = data.StanicaZ + ' &#8608; ' + data.StanicaDo;
                    document.getElementById('modalDelay').innerText = 'Meškanie: ' + data.MeskaText + ' (' + data.InfoZoStanice + ')';
                } else {
                    document.getElementById('modalTitle').innerText = 'API Unavailable';
                }
            }
            isFetching = false; 
        };
        xhr.onerror = function() {
            isFetching = false; 
        };
        xhr.send();
    }
</script>
</body>
</html>
