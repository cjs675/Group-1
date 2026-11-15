<?php
// Website file locations
$slides_dir = __DIR__ . "/slides";
$index_dir = "/var/www/lecture-index";

// Get the search input
$search = trim($_GET["search"] ?? "");

// Load the lecture titles and keywords
$metadata = [];
$csv_path = $index_dir . "/lectures.csv";

if (($handle = fopen($csv_path, "r")) !== false) {
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {
        $filename = trim($row[0] ?? "");

        if ($filename === "") {
            continue;
        }

        $metadata[$filename] = [
            "title" => trim($row[1] ?? ""),
            "keywords" => trim($row[2] ?? "")
        ];
    }

    fclose($handle);
}

// Find the available lecture PDFs
$lectures = [];

foreach (scandir($slides_dir) ?: [] as $file) {

    if (!preg_match('/\.pdf$/i', $file)) {
        continue;
    }

    if (!is_file($slides_dir . "/" . $file)) {
        continue;
    }

    // Get the lecture information
    $title = $metadata[$file]["title"] ?? $file;
    $keywords = $metadata[$file]["keywords"] ?? "";

    // Search the filename, title, keywords, and extracted PDF text
    if ($search !== "") {

        $searchable = $file . " " . $title . " " . $keywords;

        $text_file = $index_dir . "/" .
            pathinfo($file, PATHINFO_FILENAME) . ".txt";

        if (is_file($text_file)) {
            $content = file_get_contents($text_file);

            if ($content !== false) {
                $searchable .= " " . $content;
            }
        }

        if (stripos($searchable, $search) === false) {
            continue;
        }
    }

    $lectures[] = [
        "file" => $file,
        "title" => $title
    ];
}

// Sort lectures by their filenames
usort($lectures, function ($a, $b) {
    return strnatcasecmp($a["file"], $b["file"]);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>COP 3350 Resource Hub</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>COP 3350 Resource Hub</h1>
    <p>Systems Administration & Programming</p>
</header>

<main>

    <section class="welcome">
        <h2>Course Materials</h2>
        <p>Browse and search all course lectures.</p>
    </section>

    <section class="search-section">
        <form method="GET">

            <input
                type="text"
                name="search"
                placeholder="Search lecture topics or content..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            >

            <button type="submit">Search</button>
            <a href="/">Clear</a>

        </form>
    </section>

    <section class="lecture-section">

        <h2>Available Lectures (<?= count($lectures) ?>)</h2>

        <?php if (empty($lectures)): ?>

            <p>No matching lectures found.</p>

        <?php else: ?>

            <div class="lecture-grid">

                <?php foreach ($lectures as $lecture): ?>

                    <div class="lecture-card">

                        <h3>
                            <?= htmlspecialchars($lecture["title"], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($lecture["file"], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <a href="slides/<?= rawurlencode($lecture["file"]) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            View / Download
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>COP 3350 | Systems Administration Group Project</p>
</footer>

</body>
</html>
