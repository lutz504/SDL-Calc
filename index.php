<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SDL Trados Analyse Report</title>
<link href="https://fonts.googleapis.com/css?family=Raleway:400,700" rel="stylesheet">
<style>
    body {
        font-family: 'Raleway', sans-serif;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }
    .container {
        width: 400px;
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    h2 {
        margin-bottom: 20px;
    }
    input[type="file"],
    input[type="number"],
    button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    button {
        background: #60b8d4;
        color: #fff;
        cursor: pointer;
    }
    button:hover {
        background: #3745b5;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Analyse Report Auswertung</h2>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xml" required>
        <input type="number" step="0.01" name="wordPrice" placeholder="Wortpreis (€)" required>
        <button type="submit">Analyse starten</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
            $uploadDir = 'uploads/';
            $filePath = $uploadDir . basename($_FILES['file']['name']);

            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                echo "<h3>Analyseergebnisse:</h3>";
                
                $wordPrice = floatval($_POST['wordPrice']);
                $statistics = parseXMLReport($filePath);
                $totalCost = calculateTotalCost($statistics, $wordPrice);

                echo "<ul>";
                foreach ($statistics as $category => $count) {
                    echo "<li>" . ucfirst($category) . ": $count Wörter</li>";
                }
                echo "</ul>";

                echo "<h3>Gesamtkosten: €" . number_format($totalCost, 2) . "</h3>";
            } else {
                echo "<p>Fehler beim Hochladen der Datei.</p>";
            }
        } else {
            echo "<p>Bitte eine gültige Datei hochladen.</p>";
        }
    }

    function parseXMLReport($filePath) {
        $statistics = [
            "newWords" => 0,
            "exactMatches" => 0,
            "fuzzy50_74" => 0,
            "fuzzy75_84" => 0,
            "fuzzy85_94" => 0,
            "fuzzy95_99" => 0,
            "repetitions" => 0,
            "totalWords" => 0
        ];

        $xml = simplexml_load_file($filePath);

        // Extrahieren der relevanten Daten
        $newWordsNode = $xml->xpath(".//new");
        if (!empty($newWordsNode)) {
            $statistics["newWords"] = (int)$newWordsNode[0]['words'];
        }

        $exactMatchesNode = $xml->xpath(".//exact");
        if (!empty($exactMatchesNode)) {
            $statistics["exactMatches"] = (int)$exactMatchesNode[0]['words'];
        }

        $fuzzyNodes = $xml->xpath(".//fuzzy");
        foreach ($fuzzyNodes as $fuzzyNode) {
            $min = (int)$fuzzyNode['min'];
            $max = (int)$fuzzyNode['max'];
            $words = (int)$fuzzyNode['words'];

            if ($min >= 50 && $max <= 74) {
                $statistics["fuzzy50_74"] += $words;
            } elseif ($min >= 75 && $max <= 84) {
                $statistics["fuzzy75_84"] += $words;
            } elseif ($min >= 85 && $max <= 94) {
                $statistics["fuzzy85_94"] += $words;
            } elseif ($min >= 95 && $max <= 99) {
                $statistics["fuzzy95_99"] += $words;
            }
        }

        $repetitionsNode = $xml->xpath(".//repeated");
        if (!empty($repetitionsNode)) {
            $statistics["repetitions"] = (int)$repetitionsNode[0]['words'];
        }

        $totalNode = $xml->xpath(".//total");
        if (!empty($totalNode)) {
            $statistics["totalWords"] = (int)$totalNode[0]['words'];
        }

        return $statistics;
    }

    function calculateTotalCost($statistics, $wordPrice) {
        $costPerWord = [
            "newWords" => $wordPrice,
            "exactMatches" => $wordPrice * 0.2,
            "fuzzy50_74" => $wordPrice * 0.5,
            "fuzzy75_84" => $wordPrice * 0.6,
            "fuzzy85_94" => $wordPrice * 0.7,
            "fuzzy95_99" => $wordPrice * 0.8,
            "repetitions" => $wordPrice * 0.1
        ];

        $totalCost = 0;
        foreach ($statistics as $category => $count) {
            if (isset($costPerWord[$category])) {
                $totalCost += $count * $costPerWord[$category];
            }
        }

        return $totalCost;
    }
    ?>
</div>
</body>
</html>
