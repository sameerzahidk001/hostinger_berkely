<?php

class FileObject {
    public $filePath;
    public $fileName;
    public $fileHash;
    public $versionCheckerIdentifier;
    public $found = false;

    public function __toString()
    {
        return json_encode([
            'filePath' => $this->filePath,
            'fileName' => $this->fileName,
            'fileHash' => $this->fileHash,
            'versionCheckerIdentifier' => $this->versionCheckerIdentifier,
        ]);
    }
}

$SSS_EXTENSIONS = array(
    '.php' => true,
    '.htm' => true,
    '.phtml' => true,
    '.shtml' => true,
    '.js' => true,
    '.pl' => true,
    '.py' => true,
    '.sh' => true,
    '.cgi' => true,
    '.asp' => true,
    '.asa' => true,
    '.module' => true,
);

function zipStatusString( $status )
{
    switch( (int) $status )
    {
        case ZipArchive::ER_OK           : return 'N No error';
        case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
        case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
        case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
        case ZipArchive::ER_SEEK         : return 'S Seek error';
        case ZipArchive::ER_READ         : return 'S Read error';
        case ZipArchive::ER_WRITE        : return 'S Write error';
        case ZipArchive::ER_CRC          : return 'N CRC error';
        case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
        case ZipArchive::ER_NOENT        : return 'N No such file';
        case ZipArchive::ER_EXISTS       : return 'N File already exists';
        case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
        case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
        case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
        case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
        case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
        case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
        case ZipArchive::ER_EOF          : return 'N Premature EOF';
        case ZipArchive::ER_INVAL        : return 'N Invalid argument';
        case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
        case ZipArchive::ER_INTERNAL     : return 'N Internal error';
        case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
        case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
        case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
        
        default: return sprintf('Unknown status %s', $status );
    }
}
    

$VERSION_CHECKS_FILES = array(
    '/includes/version.php' => 'configuration.php',
    '/libraries/joomla/version.php' => 'configuration.php',
    '/libraries/cms/version.php' => 'configuration.php',
    '/libraries/cms/version/version.php' => 'configuration.php',
    '/libraries/src/Version.php' => 'configuration.php',
    '/includes/version.php' => 'checkout_shipping_address.php',
    '/includes/version.txt' => 'checkout_shipping_address.php',
    '/includes/OSC/version.txt' => 'checkout_shipping_address.php',
    '/config/autoload.php' => 'TranslatedConfiguration.php',
    '/docs/readme_en.txt' => 'TranslatedConfiguration.php',
    '/admin/index.php' => 'manufacturer.php'
);

$chunkSize = 10 * 1024 * 1024;

/**
 * Check if a file exists and is readable
 * Automatically collects errors in the global $collectedErrors array
 * 
 * @param string $filePath Full path to the file
 * @return bool True if the file exists and is readable, false otherwise
 */
function isFileValidAndReadable(string $filePath): bool {
    global $collectedErrors;
    
    if (!file_exists($filePath)) {
        $collectedErrors[] = "File does not exist: $filePath";
        return false;
    }
    
    if (!is_readable($filePath)) {
        $collectedErrors[] = "Permission denied: Cannot read file $filePath";
        return false;
    }
    
    return true;
}

function hashFile(string $fileName, $path, array &$hashCache = []) {
    $fullPath = $path . '/' . $fileName;
    
    $cacheKey = md5($fullPath);
    if (isset($hashCache[$cacheKey])) {
        return $hashCache[$cacheKey];
    }
    
    if (!isFileValidAndReadable($fullPath)) {
        return '';
    }
    
    $hash = @hash_file('sha256', $fullPath);
    if ($hash === false) {
        global $collectedErrors;
        $collectedErrors[] = "Failed to hash file: $fullPath";
        return '';
    }
    
    $hashCache[$cacheKey] = $hash;
    return $hash;
}

function zipFiles(array $filesToZip) {
    if (empty($filesToZip)) {
        handleError("No files to zip");
    }

    if (!class_exists('ZipArchive')) {
        return 'FILE_BY_FILE';
    }

    $zip = new ZipArchive();
    $uniqueId = bin2hex(random_bytes(16));
    $zipFileName = '/tmp/filesToUpload-' . $uniqueId . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
        handleError("Failed to create zip file: " . zipStatusString($zip->getStatusString()));
    }

    $addedFiles = 0;
    $errors = [];
    global $collectedErrors;

    foreach ($filesToZip as $file) {
        if (empty($file->filePath) || empty($file->fileName)) {
            $errors[] = "filePath or fileName is not set.";
            continue;
        }
        
        $filePath = $file->filePath . '/' . $file->fileName;
        
        if (!isFileValidAndReadable($filePath)) {
            continue;
        }
        
        if (!$zip->addFile($filePath, $file->fileName)) {
            $collectedErrors[] = "Failed to add file to zip: $filePath";
        } else {
            $addedFiles++;
        }
    }

    if (!$zip->close()) {
        $error_msg = "Failed to close zip file: " . $zip->getStatusString();
        handleError($error_msg);
    }
    
    if ($addedFiles === 0 && !empty($errors)) {
        handleError("Failed to add any files to zip: " . implode(", ", $errors));
    }
    
    return $zipFileName;
}

/**
 * @param string $monitorsEndpoint
 * @param array $filesToUpload
 * @return void
 */
function curlMonitors(string $monitorsEndpoint, array $files, bool $upload = false) {
    $curl = curl_init($monitorsEndpoint);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FAILONERROR, true);
   
    if ($upload) {
        $zipName = zipFiles($files);
        if ($zipName === false) {
            handleError("Failed to create zip file.");
        }

        $fileHandle = fopen($zipName, 'r');

        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_INFILE, $fileHandle);

        $zipFileSize = filesize($zipName);
        $ssuHeaders[] = 'Content-Length: ' . $zipFileSize;
        curl_setopt($curl, CURLOPT_INFILESIZE, $zipFileSize);
    
        $ssuHeaders[] = 'Content-Type: multipart/form-data';
        $ssuHeaders[] = 'Accept: application/json';        
        $ssuHeaders[] = 'x-amz-acl: bucket-owner-full-control';
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $ssuHeaders);

    curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        handleError("cURL ERROR: " . curl_error($curl));
    }
    
    curl_close($curl);
    if ($upload && isset($fileHandle)) {
        fclose($fileHandle);
    }

    return $httpCode === 200 || $httpCode === 204;
}

function isFileInArray(array $fileObjects, string $filePath, string $fileName): ?FileObject {
    foreach ($fileObjects as $fileObject) {
        if ($fileObject->filePath === $filePath && $fileObject->fileName === $fileName) {
            $fileObject->found = true;
            return $fileObject;
        }
    }
    return null;
}

function buildFilePathMap(array $files): array {
    $map = [];
    foreach ($files as $index => $file) {
        $key = $file->filePath . '|' . $file->fileName;
        $map[$key] = $index;
    }
    return $map;
}

function handleError(string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    echo "ERROR: $message\n";
    exit;
}

function validateHeaders(array $requiredHeaders): void {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    foreach ($requiredHeaders as $header) {
        if (!isset($headers[$header])) {
            handleError("Missing $header header");
        }
    }
}

function extractFilesFromSss(array $data) : array {
    $filesReceivedFromSSS = [];

    if (!is_array($data) || empty($data)) {
        handleError("No valid data received.");
    }

    foreach ($data as $item) {
        if (isset($item['filePath'], $item['fileName'], $item['fileHash'])) {
            $fileObject = new FileObject();
            $fileObject->filePath = $item['filePath'];
            $fileObject->fileName = $item['fileName'];
            $fileObject->fileHash = $item['fileHash'];
            $fileObject->versionCheckerIdentifier = $item['versionCheckerIdentifier'] ?? '';

            $filesReceivedFromSSS[] = $fileObject;
        } else {
            handleError("Invalid data format. Missing required fields: filePath, fileName, or fileHash.");
        }
    }

    return $filesReceivedFromSSS;
}

function sendChunkedFileResponse(string $zipName, int $chunkNumber, int $chunkSize): void {
    $fullFilePath = '/tmp/' . $zipName;
    $fileSize = filesize($fullFilePath);

    $start = $chunkNumber * $chunkSize;
    $end = min($start + $chunkSize - 1, $fileSize - 1);
    $length = $end - $start + 1;

    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . $length);
    header('X-Chunk-Number: ' . $chunkNumber);
    header('X-Total-Chunks: ' . max(1, ceil($fileSize / $chunkSize)));
    header('X-File-Size: ' . $fileSize);

    $file = fopen($fullFilePath, 'rb');
    if (!$file) {
        handleError("Could not open file.");
    }

    if (fseek($file, $start) === -1) {
        handleError("Could not seek to position", 500);
    }
    echo fread($file, $length);
    fclose($file);
}

function getFileSize(string $filePath): int {
    try {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return 0;
        }
        
        $size = filesize($filePath);
        return $size === false ? 0 : $size;
    } catch (Exception $e) {
        return 0;
    }
}

function sendFullFileResponse(int $chunkSize, string $zipName, $scriptHash, array $newFiles, $modifiedFiles, $deletedFiles, bool $metadataOnly = false): void {
    global $isBatchRequest;
    global $collectedErrors;
    
    $metadataArray = [
        'added' => $newFiles,
        'modified' => $modifiedFiles,
        'removed' => $deletedFiles,
        'scriptHash' => $scriptHash,
        'errors' => $collectedErrors,
        'chunking' => [
            'enabled' => true,
            'fileSize' => getFileSize($zipName),
            'chunkSize' => $chunkSize,
            'totalChunks' => max(1, ceil(getFileSize($zipName) / $chunkSize)),
            'zipFileName' => basename($zipName)
        ]
    ];

    if ($zipName !== 'FILE_BY_FILE') {
        $metadataArray['chunking']['fileSize'] = getFileSize($zipName);
        $metadataArray['chunking']['totalChunks'] = max(1, ceil(getFileSize($zipName) / $chunkSize));
        $metadataArray['chunking']['zipFileName'] = basename($zipName);
    } else {
        $totalSize = 0;
        foreach ($newFiles as $file) {
            $filePath = $file->filePath . '/' . $file->fileName;
            if (file_exists($filePath)) {
                $totalSize += getFileSize($filePath);
            }
        }
        foreach ($modifiedFiles as $file) {
            $filePath = $file->filePath . '/' . $file->fileName;
            if (file_exists($filePath)) {
                $totalSize += getFileSize($filePath);
            }
        }
        $metadataArray['chunking']['fileSize'] = $totalSize;
        $metadataArray['chunking']['totalChunks'] = max(1, ceil($totalSize / $chunkSize));
    }

    if ($metadataOnly) {
        header('Content-Type: application/json');
        echo json_encode($metadataArray, JSON_PRETTY_PRINT);
        return;
    }

    $metadataJson = json_encode($metadataArray, JSON_PRETTY_PRINT);
    $boundary = "boundary" . bin2hex(random_bytes(16));

    header("Content-Type: multipart/form-data; boundary=$boundary");

    echo "--$boundary\r\n";
    echo "Content-Disposition: form-data; name=\"metadata\"\r\n";
    echo "Content-Type: application/json\r\n\r\n";
    echo $metadataJson . "\r\n";

    if ($zipName === 'FILE_BY_FILE') {
        foreach ($newFiles as $file) {
            $filePath = $file->filePath . '/' . $file->fileName;
            
            if (!isFileValidAndReadable($filePath)) {
                continue;
            }

            echo "--$boundary\r\n";
            echo "Content-Disposition: form-data; name=\"file\"; filename=\"" . $file->fileName . "\"\r\n";
            echo "Content-Type: application/octet-stream\r\n";
            echo "Content-Length: " . getFileSize($filePath) . "\r\n\r\n";

            $fileHandle = fopen($filePath, 'rb');
            if (!$fileHandle) {
                continue;
            }

            while (!feof($fileHandle)) {
                echo fread($fileHandle, 8192);
                flush();
            }
            fclose($fileHandle);
            echo "\r\n";
        }

        foreach ($modifiedFiles as $file) {
            $filePath = $file->filePath . '/' . $file->fileName;
            
            if (!isFileValidAndReadable($filePath)) {
                continue;
            }

            echo "--$boundary\r\n";
            echo "Content-Disposition: form-data; name=\"file\"; filename=\"" . $file->fileName . "\"\r\n";
            echo "Content-Type: application/octet-stream\r\n";
            echo "Content-Length: " . getFileSize($filePath) . "\r\n\r\n";

            $fileHandle = fopen($filePath, 'rb');
            if (!$fileHandle) {
                continue;
            }

            while (!feof($fileHandle)) {
                echo fread($fileHandle, 8192);
                flush();
            }
            fclose($fileHandle);
            echo "\r\n";
        }
    } else {
        echo "--$boundary\r\n";
        echo "Content-Disposition: form-data; name=\"zipFile\"; filename=\"" . basename($zipName) . "\"\r\n";
        echo "Content-Type: application/zip\r\n";
        echo "Content-Length: " . getFileSize($zipName) . "\r\n\r\n";

        $fileHandle = fopen($zipName, 'rb');
        if (!$fileHandle) {
            handleError("Could not open file.");
        }

        while (!feof($fileHandle)) {
            echo fread($fileHandle, 8192);
            flush();
        }
        fclose($fileHandle);
    }

    echo "\r\n--$boundary--\r\n";
    
    if ($zipName !== 'FILE_BY_FILE') {
        cleanupZipFile($zipName, $isBatchRequest);
    }
}

function cleanupZipFile(string $zipName, bool $isBatch = false): void {
    if (!empty($zipName) && file_exists($zipName)) {
        $realPath = realpath($zipName);
        if ($realPath && strpos($realPath, '/tmp/') === 0 && is_file($realPath) && !is_link($realPath)) {
            unlink($realPath);
        }
    }
    
    if ($isBatch) {
        $zipDir = dirname($zipName);
        $zipBaseName = basename($zipName);
        
        if (preg_match('/^(.+?)(-batch-\d+)(\..+)$/', $zipBaseName, $matches)) {
            $filePrefix = $matches[1];
            $fileExt = $matches[3];
            
            foreach (glob("{$zipDir}/{$filePrefix}-batch-*{$fileExt}") as $batchFile) {
                if (is_file($batchFile) && !is_link($batchFile)) {
                    unlink($batchFile);
                }
            }
        }
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['increase_memory']) && $_GET['increase_memory'] === '1') {
    ini_set('memory_limit', '256M');
}

validateHeaders(['x-sss-key', 'x-request-id', 'x-timestamp']);
$timestamp = $_SERVER['HTTP_X_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_SSS_KEY'] ?? '';
$scannerId = $_GET['scanner-id'] ?? '';

$scriptHash = hash('sha256', file_get_contents(__FILE__));
$expectedSignature = hash_hmac('sha256', $scannerId . $timestamp, $scriptHash);
if (!hash_equals($expectedSignature, $signature)) {
    handleError("Invalid signature", 401);
}

$chunked = isset($_GET['chunked']) && ($_GET['chunked'] === '1' || $_GET['chunked'] === 1);
$metadataOnly = isset($_GET['metadata-only']) && ($_GET['metadata-only'] === '1' || $_GET['metadata-only'] === 1);
$isBatchRequest = isset($_GET['batch']) && ($_GET['batch'] === 'true' || $_GET['batch'] === '1' || $_GET['batch'] === 1);

if ($chunked) {    
    $chunkNumber = isset($_GET['chunk']) ? intval($_GET['chunk']) : 0;
    $lastChunk = isset($_GET['lastchunk']) ? intval($_GET['lastchunk']) : 0;
    $zipName = isset($_GET['zipname']) ? $_GET['zipname'] : null;

    if (empty($zipName) || !preg_match('/^[a-zA-Z0-9\-]+.zip$/', $zipName)) {
        handleError("Invalid or missing zip name");
    }

    sendChunkedFileResponse($zipName, $chunkNumber, $chunkSize);
    if ($lastChunk) {
        $realPath = realpath($zipName);
        if ($realPath && strpos($realPath, '/tmp/') === 0 && is_file($realPath) && !is_link($realPath)) {
            unlink($realPath);
        }
    }
    exit;
}

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$files = [];
$whitelist = [];

if (isset($data['files'])) {
    $files = $data['files'];
}

if (isset($data['whitelist'])) {
    $whitelist = $data['whitelist'];
}

$directory = '.';
$baseDir = $_SERVER['DOCUMENT_ROOT'];

$filesToUpload = [];
$modifiedFiles = [];
$deletedFiles = [];
$newFiles = [];
$hashCache = [];
$filesReceivedFromSSS = [];
$fileMap = [];
$collectedErrors = [];

if (!empty($files)) {
    $filesReceivedFromSSS = extractFilesFromSss($files);
    $fileMap = buildFilePathMap($filesReceivedFromSSS);
} else {
    $filesReceivedFromSSS = [];
    $fileMap = [];
}

$validFiles = [];

if ($isBatchRequest) {
    $validFiles = array_fill(0, count($filesReceivedFromSSS), null);
    $i = 0;
    
    foreach ($filesReceivedFromSSS as $fileObj) {
        $validFiles[$i++] = [
            'path' => $fileObj->filePath,
            'name' => $fileObj->fileName,
            'file' => null
        ];
    }
    
    if ($i < count($validFiles)) {
        $validFiles = array_slice($validFiles, 0, $i);
    }
} else {
    try {
        $directoryIterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $filesIterator = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $filesIterator->setMaxDepth(50);
        
        $pattern = '/\.(' . implode('|', array_map(function($ext) {
            return preg_quote(substr($ext, 1), '/');
        }, array_keys($SSS_EXTENSIONS))) . ')$/i';
        
        $filesIterator = new RegexIterator($filesIterator, $pattern, RegexIterator::MATCH);
    } catch (UnexpectedValueException $e) {
        $collectedErrors[] = "Root directory access error: " . $e->getMessage();
        $filesIterator = new ArrayIterator([]);
    }
    foreach($filesIterator as $file) {
        try {
            if (!$file->isFile()) {
                continue;
            }
            
            $path = realpath($baseDir . ltrim($file->getPath(), '.'));
            $fileName = $file->getFilename();
        } catch (RuntimeException $e) {
            $collectedErrors[] = "Access error: " . $e->getMessage();
            continue;
        } catch (UnexpectedValueException $e) {
            $collectedErrors[] = "Directory access error: " . $e->getMessage();
            continue;
        }
        
        $isWhitelisted = false;
        foreach ($whitelist as $whitelistedPath) {
            if (preg_match('/(^|\/)' . preg_quote($whitelistedPath, '/') . '(\/|$)/', $path)) {
                $isWhitelisted = true;
                break;
            }
        }
        
        if ($isWhitelisted) {
            continue;
        }
        
        $validFiles[] = [
            'path' => $path,
            'name' => $fileName,
            'file' => $file
        ];
    }
}

$batchSize = 100;
$totalFiles = count($validFiles);

if ($isBatchRequest) {
    $newFiles = array_fill(0, $totalFiles, null);
    $newFilesIndex = 0;
} else {
    $newFiles = [];
    $modifiedFiles = [];
}

for ($i = 0; $i < $totalFiles; $i += $batchSize) {
    $batch = array_slice($validFiles, $i, $batchSize);
    
    foreach ($batch as $fileData) {
        $path = $fileData['path'];
        $fileName = $fileData['name'];
        $file = $fileData['file'];
        
        $fileInfo = new FileObject();
        $fileInfo->filePath = $path;
        $fileInfo->fileName = $fileName;
        
        if ($isBatchRequest) {
            $lookupKey = $path . '|' . $fileName;
            if (isset($fileMap[$lookupKey])) {
                $index = $fileMap[$lookupKey];
                $receivedFile = $filesReceivedFromSSS[$index];
                
                $fileInfo->fileHash = $receivedFile->fileHash;
                $fileInfo->versionCheckerIdentifier = $receivedFile->versionCheckerIdentifier;
                
                $newFiles[$newFilesIndex++] = $fileInfo;
            }
        } else {
            $key = $path . '|' . $fileName;
            $fileHash = hashFile($fileName, $path, $hashCache);
            $versionCheckerIdentifier = '';
            
            $fullFilePath = $path . "/" . $fileName;
            foreach ($VERSION_CHECKS_FILES as $vcKey => $value) {
                if (strpos($fullFilePath, $vcKey) !== false) {
                    $versionCheckerIdentifier = $value;
                    break;
                }
            }
            
            $fileInfo->fileHash = $fileHash;
            $fileInfo->versionCheckerIdentifier = $versionCheckerIdentifier;
            
            if (empty($fileMap)) {
                $newFiles[] = $fileInfo;
            } else if (isset($fileMap[$key])) {
                $index = $fileMap[$key];
                $filesReceivedFromSSS[$index]->found = true;
                
                if ($filesReceivedFromSSS[$index]->fileHash !== $fileHash) {
                    $modifiedFiles[] = $fileInfo;
                }
            } else {
                $newFiles[] = $fileInfo;
            }
        }
    }
    
    unset($batch);
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
}

if ($isBatchRequest && $newFilesIndex < count($newFiles)) {
    $newFiles = array_slice($newFiles, 0, $newFilesIndex);
}

if (!empty($filesReceivedFromSSS) && !$isBatchRequest) {
    $deletedFiles = array_values(array_filter($filesReceivedFromSSS, function($file) {
        return !$file->found;
    }));
}

unset($hashCache);
unset($validFiles);
unset($fileMap);

$batchId = '';
if ($isBatchRequest) {
    $batchId = '-batch-' . (isset($_GET['batch-id']) ? $_GET['batch-id'] : bin2hex(random_bytes(4)));
}

$filesToUpload = array_merge($newFiles, $modifiedFiles);
if (!empty($filesToUpload)) {
    $zipName = zipFiles($filesToUpload);
    if ($zipName === false) {
        handleError("Failed to create zip file.");
    }

    if ($isBatchRequest) {
        $zipNameParts = pathinfo($zipName);
        $zipName = '/tmp/' . $zipNameParts['filename'] . $batchId . '.' . $zipNameParts['extension'];
        rename($originalZipName, $zipName);
    }
    
    sendFullFileResponse($chunkSize, $zipName, $scriptHash, $newFiles, $modifiedFiles, $deletedFiles, $metadataOnly);
} else if (!empty($deletedFiles)) {
    header('Content-Type: application/json');
    $response = [
        'added' => $newFiles,
        'modified' => $modifiedFiles,
        'removed' => $deletedFiles,
        'errors' => $collectedErrors,
        'scriptHash' => $scriptHash,
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    header('Content-Type: application/json');
    $response = [
        'added' => [],
        'modified' => [],
        'removed' => [],
        'errors' => $collectedErrors,
        'scriptHash' => $scriptHash,
        'message' => 'No changes detected'
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
}
