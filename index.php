<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "media_gallery";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}


/* DELETE MEDIA */

if (isset($_GET["action"]) && $_GET["action"] == "delete") {

    $id = intval($_GET["id"]);

    $stmt = $conn->prepare(
        "SELECT file_name FROM media WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $fileName = $row["file_name"];

        $filePath = "uploads/" . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $deleteStmt = $conn->prepare(
            "DELETE FROM media WHERE id = ?"
        );

        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {

            header("Content-Type: application/json");

            echo json_encode([
                "success" => true,
                "message" => "Media deleted successfully."
            ]);

        } else {

            header("Content-Type: application/json");

            echo json_encode([
                "success" => false,
                "message" => "Database delete failed."
            ]);
        }

        $deleteStmt->close();

    } else {

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Media not found."
        ]);
    }

    $stmt->close();

    exit;
}


/* LOAD MEDIA */

if (isset($_GET["action"]) && $_GET["action"] == "load") {

    $result = $conn->query(
        "SELECT * FROM media ORDER BY id DESC"
    );

    $media = [];

    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }

    header("Content-Type: application/json");

    echo json_encode($media);

    exit;
}


/* UPLOAD MEDIA */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["upload"])) {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);

    if (!isset($_FILES["media"])) {

        echo "<script>
                alert('Please select an image.');
              </script>";

    } else if ($_FILES["media"]["error"] != 0) {

        echo "<script>
                alert('There was an error uploading the image.');
              </script>";

    } else {

        $originalName = $_FILES["media"]["name"];

        $temporaryName = $_FILES["media"]["tmp_name"];

        $fileSize = $_FILES["media"]["size"];

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $allowedExtensions = [
            "jpg",
            "jpeg",
            "png",
            "gif",
            "webp"
        ];


        if (!in_array($extension, $allowedExtensions)) {

            echo "<script>
                    alert('Only JPG, JPEG, PNG, GIF and WEBP files are allowed.');
                  </script>";

        } else if ($fileSize > 10 * 1024 * 1024) {

            echo "<script>
                    alert('File size must be less than 10 MB.');
                  </script>";

        } else {

            $newFileName =
                time() . "_" .
                uniqid() . "." .
                $extension;

            $uploadPath =
                "uploads/" . $newFileName;


            if (move_uploaded_file(
                $temporaryName,
                $uploadPath
            )) {

                $mediaType =
                    $_FILES["media"]["type"];


                $stmt = $conn->prepare(
                    "INSERT INTO media
                    (title, file_name, media_type, description)
                    VALUES (?, ?, ?, ?)"
                );


                $stmt->bind_param(
                    "ssss",
                    $title,
                    $newFileName,
                    $mediaType,
                    $description
                );


                if ($stmt->execute()) {

                    echo "<script>
                            alert('Media uploaded successfully!');
                          </script>";

                } else {

                    unlink($uploadPath);

                    echo "<script>
                            alert('Database error.');
                          </script>";
                }


                $stmt->close();

            } else {

                echo "<script>
                        alert('Could not save the uploaded image.');
                      </script>";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Media Gallery</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root {
            --bg: #12141c;
            --panel: #1c1f2b;
            --panel-2: #262a39;
            --ink: #f2f3f7;
            --muted: #9aa0b4;
            --line: #2f3447;
            --accent: #ffb547;
            --accent-dark: #e89a1f;
            --danger: #ff5d6c;
            --display: "Bricolage Grotesque", "Segoe UI", Arial, sans-serif;
            --body: "DM Sans", "Segoe UI", Arial, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--body);
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        .container {
            width: 94%;
            max-width: 1360px;
            margin: 0 auto;
            padding: 64px 0 80px;
        }

        /* ---------- Hero ---------- */
        .hero { margin-bottom: 44px; max-width: 700px; }

        h1 {
            font-family: var(--display);
            font-weight: 800;
            font-size: clamp(3rem, 8vw, 5.8rem);
            line-height: 0.92;
            letter-spacing: -0.04em;
            margin-bottom: 18px;
        }

        .hero p {
            color: var(--muted);
            font-size: 1.1rem;
            max-width: 520px;
            margin-bottom: 28px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        button {
            font: inherit;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s, transform .1s;
        }

        button:active { transform: translateY(1px); }

        button:focus-visible,
        input:focus-visible,
        textarea:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }

        #loadMedia,
        .upload-btn {
            background: var(--accent);
            color: #1a1305;
            border: none;
            padding: 13px 26px;
            border-radius: 999px;
            font-size: 0.98rem;
            font-weight: 700;
        }

        #loadMedia:hover,
        .upload-btn:hover { background: var(--accent-dark); }

        .ghost-btn {
            background: transparent;
            color: var(--ink);
            border: 1.5px solid var(--line);
            padding: 12px 24px;
            border-radius: 999px;
            font-size: 0.98rem;
            font-weight: 700;
        }

        .ghost-btn:hover { border-color: var(--ink); }

        #status {
            font-size: 0.92rem;
            color: var(--muted);
            font-weight: 500;
        }

        #status:empty { display: none; }

        /* ---------- Masonry gallery ---------- */
        #gallery {
            columns: 4 250px;
            column-gap: 18px;
        }

        .media-card {
            position: relative;
            break-inside: avoid;
            margin-bottom: 18px;
            border-radius: 16px;
            overflow: hidden;
            background: var(--panel);
            cursor: zoom-in;
        }

        .media-card img {
            width: 100%;
            height: auto;
            display: block;
            min-height: 120px;
            transition: transform .5s ease;
        }

        .media-card:hover img { transform: scale(1.05); }

        .media-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 14px;
            background:
                linear-gradient(to bottom, rgba(10,11,16,.55) 0%, rgba(10,11,16,0) 30%),
                linear-gradient(to top, rgba(10,11,16,.9) 0%, rgba(10,11,16,0) 62%);
            opacity: 0;
            transition: opacity .25s ease;
        }

        .media-card:hover .media-overlay,
        .media-card:focus-within .media-overlay { opacity: 1; }

        .overlay-top { display: flex; justify-content: flex-end; }

        .media-info h3 {
            font-family: var(--display);
            font-size: 1.12rem;
            letter-spacing: -0.01em;
            margin-bottom: 4px;
            overflow-wrap: anywhere;
        }

        .media-info p {
            color: #d0d3df;
            font-size: 0.88rem;
            margin-bottom: 10px;
            overflow-wrap: anywhere;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .media-type {
            display: inline-block;
            background: rgba(255,255,255,.16);
            backdrop-filter: blur(6px);
            color: #fff;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .delete-btn {
            background: rgba(10,11,16,.6);
            backdrop-filter: blur(6px);
            color: #fff;
            border: 1px solid rgba(255,255,255,.25);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .delete-btn:hover { background: var(--danger); border-color: var(--danger); }

        /* touch screens have no hover, so keep captions visible */
        @media (hover: none) {
            .media-overlay { opacity: 1; }
        }

        /* ---------- Loading placeholders ---------- */
        .skeleton {
            break-inside: avoid;
            margin-bottom: 18px;
            border-radius: 16px;
            background: linear-gradient(100deg, var(--panel) 30%, var(--panel-2) 50%, var(--panel) 70%);
            background-size: 220% 100%;
            animation: shimmer 1.3s linear infinite;
        }

        @keyframes shimmer { to { background-position: -220% 0; } }

        .empty-message {
            column-span: all;
            border: 2px dashed var(--line);
            padding: 64px 24px;
            border-radius: 16px;
            text-align: center;
            color: var(--muted);
            font-weight: 500;
        }

        /* ---------- Dialogs ---------- */
        dialog {
            border: none;
            background: var(--panel);
            color: var(--ink);
            border-radius: 20px;
            padding: 0;
            margin: auto;
        }

        dialog::backdrop {
            background: rgba(6, 7, 11, .78);
            backdrop-filter: blur(4px);
        }

        /* upload dialog */
        #uploadDialog { width: min(480px, 92vw); }

        .upload-box { padding: 30px; }

        .upload-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .upload-box h2 {
            font-family: var(--display);
            font-size: 1.5rem;
            letter-spacing: -0.02em;
        }

        .close-btn {
            background: var(--panel-2);
            color: var(--ink);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.2rem;
            line-height: 1;
        }

        .close-btn:hover { background: var(--line); }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font: inherit;
            font-size: 0.95rem;
            background: var(--bg);
            color: var(--ink);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder { color: #6b7185; }

        textarea { resize: vertical; min-height: 96px; }

        input[type="file"] { padding: 9px; cursor: pointer; }

        input[type="file"]::file-selector-button {
            font: inherit;
            font-weight: 700;
            font-size: 0.85rem;
            border: none;
            background: var(--panel-2);
            color: var(--ink);
            padding: 7px 14px;
            border-radius: 999px;
            margin-right: 12px;
            cursor: pointer;
        }

        .upload-btn { width: 100%; margin-top: 6px; }

        /* lightbox */
        #viewer {
            width: min(1100px, 94vw);
            max-height: 94vh;
            background: transparent;
            overflow: visible;
        }

        .viewer-inner {
            display: flex;
            flex-direction: column;
            gap: 16px;
            align-items: center;
        }

        #viewerImg {
            max-width: 100%;
            max-height: 74vh;
            border-radius: 14px;
            display: block;
            object-fit: contain;
        }

        .viewer-caption { text-align: center; max-width: 640px; }

        .viewer-caption h3 {
            font-family: var(--display);
            font-size: 1.5rem;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .viewer-caption p { color: var(--muted); }

        #viewer .close-btn {
            position: absolute;
            top: -14px;
            right: -6px;
            z-index: 2;
            background: var(--ink);
            color: var(--bg);
        }

        @media (max-width: 600px) {
            .container { padding-top: 40px; }
            #gallery { columns: 2 150px; column-gap: 12px; }
            .media-card, .skeleton { margin-bottom: 12px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }
    </style>
</head>

<body>

<div class="container">

    <header class="hero">
        <h1>Media Gallery</h1>
        <p>Your images and GIFs in one wall. Upload new ones, load the collection, and delete what you no longer need without a page reload.</p>

        <div class="actions">
            <button id="loadMedia">Load media</button>
            <button type="button" class="ghost-btn" id="openUpload">Upload media</button>
            <div id="status"></div>
        </div>
    </header>

    <div id="gallery"></div>

</div>


<!-- Upload dialog -->
<dialog id="uploadDialog">
    <div class="upload-box">

        <div class="upload-head">
            <h2>Upload media</h2>
            <button type="button" class="close-btn" id="closeUpload" aria-label="Close">&times;</button>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="Enter media title" required>
            </div>

            <div class="form-group">
                <label for="media">Image or GIF</label>
                <input type="file" id="media" name="media" accept=".jpg,.jpeg,.png,.gif,.webp" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Enter description" required></textarea>
            </div>

            <button type="submit" name="upload" class="upload-btn">Upload media</button>

        </form>
    </div>
</dialog>


<!-- Full-size viewer -->
<dialog id="viewer">
    <button type="button" class="close-btn" id="closeViewer" aria-label="Close">&times;</button>
    <div class="viewer-inner">
        <img id="viewerImg" src="" alt="">
        <div class="viewer-caption">
            <h3 id="viewerTitle"></h3>
            <p id="viewerDesc"></p>
        </div>
    </div>
</dialog>


<script src="script.js"></script>

</body>
</html>