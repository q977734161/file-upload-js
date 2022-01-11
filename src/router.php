<?php
// 设置允许其他域名访问
header('Access-Control-Allow-Origin:*');
// 设置允许的响应类型
header('Access-Control-Allow-Methods:POST, GET');
// 设置允许的响应头
header('Access-Control-Allow-Headers:x-requested-with,content-type'); 
// router.php
if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
} else {
    $fileName = $_POST['file_name'];
    $start = $_POST['start'];
    $length = $_POST['length'];
    $fileSize = $_POST['file_size'];
    $absPath = __DIR__. '/../files/' . $fileName;
    $absPathTemp = __DIR__. '/../files/' . $fileName . "_tmp";
    file_put_contents($absPathTemp, 
            file_get_contents($_FILES['file']['tmp_name']), 
            FILE_APPEND);
    if($fileSize == ($length + $start)) {
        rename($absPathTemp, $absPath);
    }
    $result = array(
        "name" => $fileName,
        "start" => $start,
        "end" => $start + $length,
        "file_size" => $fileSize
    );
    
    echo json_encode($result);
}
?>