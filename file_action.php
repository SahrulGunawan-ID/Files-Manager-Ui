<?php
$targetDir = "a_uploads/";
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

function getFolderSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : getFolderSize($each);
    }
    return $size;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] == 'list') {
        $files = array_diff(scandir($targetDir), array('.', '..'));
        $fileList = [];
        foreach ($files as $file) {
            $path = $targetDir . $file;
            $fileList[] = [
                'name' => $file,
                'time' => date("Y-m-d H:i", filemtime($path)),
                'size' => round(filesize($path) / 1024, 2) . ' KB'
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($fileList);
        exit;
    }
    if ($_GET['action'] == 'info') {
        $totalBytes = getFolderSize($targetDir);
        echo json_encode(['total_size' => round($totalBytes / (1024 * 1024), 2) . ' MB']);
        exit;
    }
}

if (isset($_FILES['file'])) {
    $targetFile = $targetDir . basename($_FILES["file"]["name"]);
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if (isset($_POST['delete'])) {
    $files = is_array($_POST['delete']) ? $_POST['delete'] : [$_POST['delete']];
    foreach ($files as $f) {
        $p = $targetDir . basename($f);
        if (file_exists($p)) unlink($p);
    }
    echo json_encode(['status' => 'success']);
    exit;
}
?>
