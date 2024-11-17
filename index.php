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
        <label for="file">Projektdatei (AnalysisReport.xml):</label><br>
        <input type="file" name="file" id="file" accept=".xml" required><br><br>

        <label for="wordPrice">Wortpreis (€):</label><br>
        <input type="number" step="0.01" name="wordPrice" id="wordPrice" placeholder="z.B. 0.12" required><br><br>

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
            $wordCounts = parseAnalysisReport($filePath);
            $totalCost = calculateTotalCost($wordCounts, $wordPrice);

            // Ergebnisse anzeigen
            echo "<h2>Analyseergebnisse:</h2>";
            echo "<ul>";
            foreach ($wordCounts as $category => $count) {
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

// Funktion zum Parsen der XML-Datei
function parseAnalysisReport($filePath) {
    if (!file_exists($filePath)) {
        die("Die Datei $filePath wurde nicht gefunden.\n");
    }

    $xml = simplexml_load_file($filePath);
    $result = [
        'noMatch' => 0,
        'fuzzyMatch75_99' => 0,
        'exactMatch100' => 0,
        'repetitions' => 0
    ];

    // Analyse der XML-Daten
    foreach ($xml->xpath('//AnalysisBands/AnalysisBand') as $band) {
        $name = (string) $band->Name;
        $wordCount = (int) $band->WordCount;

        switch ($name) {
            case 'Repetitions':
                $result['repetitions'] += $wordCount;
                break;
            case '100%':
                $result['exactMatch100'] += $wordCount;
                break;
            case '75%-99%':
                $result['fuzzyMatch75_99'] += $wordCount;
                break;
            case 'No Match':
                $result['noMatch'] += $wordCount;
                break;
        }
    }

    return $result;
}

// Funktion zur Berechnung der Gesamtkosten
function calculateTotalCost($wordCounts, $wordPrice) {
    $costPerWord = [
        'noMatch' => $wordPrice,           // Vollpreis pro Wort
        'fuzzyMatch75_99' => $wordPrice * 0.5,   // 50 % des Wortpreises
        'exactMatch100' => $wordPrice * 0.2,     // 20 % des Wortpreises
        'repetitions' => $wordPrice * 0.1        // 10 % des Wortpreises
    ];

    $totalCost = 0;

    foreach ($wordCounts as $category => $count) {
        $totalCost += $count * $costPerWord[$category];
    }

    return $totalCost;
}
?>
</body>
</html>
