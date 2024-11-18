<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDL Trados Kostenrechner</title>
</head>
<body>
    <h1>SDL Trados Kostenrechner</h1>

    <form action="" method="post" enctype="multipart/form-data">
        <label for="file">Projektdatei (.sdlproj):</label><br>
        <input type="file" name="file" id="file" accept=".sdlproj" required><br><br>

        <label for="wordPrice">Wortpreis (€):</label><br>
        <input type="number" step="0.01" name="wordPrice" id="wordPrice" placeholder="z.B. 0.12" value="0.12" required><br><br>

        <input type="submit" name="submit" value="Berechnen">
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Überprüfe, ob die Datei hochgeladen wurde
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $uploadDir = 'uploads/';
        $filePath = $uploadDir . basename($_FILES['file']['name']);

        // Datei in den Upload-Ordner verschieben
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            echo "<p>Datei erfolgreich hochgeladen: " . htmlspecialchars($_FILES['file']['name']) . "</p>";

            // Wortpreis abrufen
            $wordPrice = floatval($_POST['wordPrice']);

            // Analyse durchführen
            $statistics = parseProjectFile($filePath);
            $totalCost = calculateTotalCost($statistics, $wordPrice);

            // Ergebnisse anzeigen
            echo "<h2>Analyseergebnisse:</h2>";
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

// Funktion zum Parsen der .sdlproj-Datei
function parseProjectFile($filePath) {
    $statistics = [
        "newWords" => 0,
        "repetitions" => 0,
        "exactMatch100" => 0,
        "fuzzyMatch95_99" => 0,
        "fuzzyMatch85_94" => 0,
        "fuzzyMatch75_84" => 0,
        "noMatch" => 0
    ];

    // XML-Datei parsen
    $xml = simplexml_load_file($filePath);

    // New Words auslesen
    $newWordsNode = $xml->xpath(".//New");
    if (!empty($newWordsNode)) {
        $statistics["newWords"] = (int)$newWordsNode[0]['Words'];
    }

    // AnalysisStatistics auslesen
    foreach ($xml->xpath(".//AnalysisStatistics/WordCounts") as $stats) {
        foreach ($stats as $wordCount) {
            $tag = (string)$wordCount->getName();
            $count = (int)$wordCount;

            switch ($tag) {
                case 'Repetitions':
                    $statistics["repetitions"] += $count;
                    break;
                case 'Exact100Match':
                    $statistics["exactMatch100"] += $count;
                    break;
                case 'Fuzzy95To99Match':
                    $statistics["fuzzyMatch95_99"] += $count;
                    break;
                case 'Fuzzy85To94Match':
                    $statistics["fuzzyMatch85_94"] += $count;
                    break;
                case 'Fuzzy75To84Match':
                    $statistics["fuzzyMatch75_84"] += $count;
                    break;
                case 'NoMatch':
                    $statistics["noMatch"] += $count;
                    break;
            }
        }
    }

    return $statistics;
}

// Funktion zur Berechnung der Gesamtkosten
function calculateTotalCost($statistics, $wordPrice) {
    $costPerWord = [
        "newWords" => $wordPrice,
        "repetitions" => $wordPrice * 0.1,
        "exactMatch100" => $wordPrice * 0.2,
        "fuzzyMatch95_99" => $wordPrice * 0.5,
        "fuzzyMatch85_94" => $wordPrice * 0.6,
        "fuzzyMatch75_84" => $wordPrice * 0.7,
        "noMatch" => $wordPrice
    ];

    $totalCost = 0;

    foreach ($statistics as $category => $count) {
        $totalCost += $count * $costPerWord[$category];
    }

    return $totalCost;
}
?>
</body>
</html>
