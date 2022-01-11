思路：
1. 把文件按 chunkSize 大小分割成N份

2. 每次上传时，判断当前 offset 是否是大于等于文件大小。

3. 如果大于等于，则上传完成，否则就是进行下分片的上传。

4. 在上传完成的回调函数中

   1. 设置当前上传之后进度条显示内容；
   2. 判断是否是被暂停，如果暂停则不进行下一次上传，否则进行下一分片的上传。

5. 前端代码、

   ```java
   <!doctype html>
   <html lang="en">
   
   <head>
       <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
       <link rel="stylesheet" href="css/bootstrap.min.css" />
       <link rel="stylesheet" href="css/bootstrap-theme.min.css" />
       <script src="js/jquery.min.js"></script>
       <script src="js/bootstrap.min.js"></script>
       <script src="js/bs-custom-file-input.js"></script>
       <script type="text/javascript">
           $(document).ready(function () {
               bsCustomFileInput.init()
           });
           
           let uploadFlag = true;
           let currentEnd = 0;
   
           function stopOrRun() {
               uploadFlag = !uploadFlag;
               if(uploadFlag) {
                   upload(currentEnd);
                   $("#pauseBtn").text("Pause");
               } else {
                   $("#pauseBtn").text("Run");
               }
           }
   
           function fileInfo() {
               let fileObj = document.getElementById('customFile').files[0];
               console.log(fileObj);
           }
   
           // 文件切块大小为10KB
           const chunkSize = 1024;
   
           // 从start字节处开始上传
           function upload(start) {
   
               let fileObj = document.getElementById('customFile').files[0];
               // 上传完成
               if (start >= fileObj.size) {
                   return;
               }
   
               if (fileObj) {
                   $("#uploadBtn").addClass("disabled");
               }
   
               // 获取文件块的终止字节
               let end = (start + chunkSize > fileObj.size) ? fileObj.size : (start + chunkSize);
   
               // 将文件切块上传
               let fd = new FormData();
               fd.append('file', fileObj.slice(start, end));
               fd.append('file_name', fileObj.name);
               fd.append('file_size', fileObj.size);
               fd.append('start', start);
               fd.append('length', end - start);
               //上传太快，为了效果sleep 200ms
               sleepFor(20);
               // POST表单数据
               let xhr = new XMLHttpRequest();
               xhr.open('post', 'http://localhost:8080', true);
               xhr.onload = function () {
                   if (this.readyState == 4 && this.status == 200) {
                       // 上传一块完成后修改进度条信息，然后上传下一块
                       let progress = $('#progress');
                       let progressPercent = end / fileObj.size;
                       $(progress).attr("style", "width:" + Math.floor(progressPercent * 100) + "%");
                       $(progress).text(Math.floor(progressPercent * 100) + "%");
                       $(progress).attr("aria-valuenow", end);
                       $(progress).attr("aria-valuemax", fileObj.size);
                       if (end == fileObj.size) {
                           $("#uploadBtn").removeClass("disabled");
                       }
                       currentEnd = end;
                       if(uploadFlag) {
                           upload(end);
                       }
                   }
               }
               xhr.send(fd);
           }
           function sleepFor(sleepDuration) {
               var now = new Date().getTime();
               while (new Date().getTime() < now + sleepDuration) { /* Do nothing */ }
           }
   
       </script>
   </head>
   
   <body>
       <div id="container-fluid">
           <!-- Columns start at 50% wide on mobile and bump up to 33.3% wide on desktop -->
           <div class="row">
               <div class="col-xs-18 col-md-12"><br></div>
           </div>
           <div class="row">
               <div class="col-xs-2 col-md-2"></div>
               <div class="col-xs-10 col-md-8">
                   <div class="input-group mb-3">
                       <div class="custom-file">
                           <input type="file" class="custom-file-input" id="customFile" onchange="fileInfo();">
                           <label class="custom-file-label" for="customFile">Choose file</label>
                       </div>
                   </div>
                   <div class="progress">
                       <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated"
                           role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%
                       </div>
                   </div>
                   <div class="row">
                       <div class="col-xs-18 col-md-12"><br></div>
                   </div>
                   <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                       <button type="button" class="btn btn-primary btn-block" id="uploadBtn"
                           onclick="upload(0);">Upload</button>
   
                   </div>
                   <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                     
                       <button type="button" class="btn btn-primary btn-block" id="pauseBtn"
                           onclick="stopOrRun();">Stop</button>
                   </div>
                   <div class="col-xs-2 col-md-2"></div>
               </div>
   
           </div>
   </body>
   
   </html>
   ```

   

6. 后端代码

后端使用简单的php server进行，只需要执行 php -S localhost:8080 server.php即可，server.php 的代码如下：

```php
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
```

