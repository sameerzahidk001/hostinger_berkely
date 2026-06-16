<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }
}

$scannerId = $_GET['scanner-id'] ?? '';
$forceDownload = isset($_GET['force_download']) && ($_GET['force_download'] === '1' || $_GET['force_download'] === 'true');

$scriptHash = hash("sha256", file_get_contents(__FILE__));

error_log("SSS Downloader called with scanner-id: $scannerId, force_download: " . ($forceDownload ? 'true' : 'false'));

function executeSecurityLogic($scannerId, $forceDownload, $scriptHash) {
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $scannerId)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Invalid scanner ID format']);
        exit;
    }
    
    $detectedDomain = $_SERVER["HTTP_HOST"] ?? $_SERVER["SERVER_NAME"] ?? "localhost";
    $CONFIG = [
        "script_path" => __DIR__ . "/sucuri-sss-uploader_" . $scannerId . ".php",
        "download_url" => "https://8vcp8xkmr3.execute-api.us-west-2.amazonaws.com/gddeploy",
        "domain" => $detectedDomain,
        "max_download_attempts" => 3,
        "allowed_extensions" => [".php"],
        "trusted_ips" => ["54.184.226.94", "35.162.140.124", "44.230.234.157", "89.238.253.62", "95.76.17.68", "66.228.34.49", "50.116.36.92", "100.92.38.0/23", "100.92.36.0/23", "132.148.54.247", "160.153.192.0/20", "118.139.184.0/22", "146.255.32.0/22", "208.109.0.0/22", "192.88.134.0/23", "185.93.228.0/22", "66.248.200.0/22"]
    ];

    class SecurityManager {
        private $config;
        private $candidateIPs = [];
        public function __construct($config) {
            $this->config = $config;
        }
        public function validateHeaders() {
            $requiredHeaders = ["x-sss-key", "x-request-id", "x-timestamp"];
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            foreach ($requiredHeaders as $header) {
                if (!isset($headers[$header])) {
                    $this->handleError("Missing required header: $header", 400);
                }
            }
            return $headers;
        }
        public function validateSignature($headers, $scannerId, $scriptHash) {
            $timestamp = $headers["x-timestamp"] ?? "";
            $signature = $headers["x-sss-key"] ?? "";
            $currentTime = time();
            $requestTime = intval($timestamp);
            if (abs($currentTime - $requestTime) > 300) {
                $this->handleError("Request timestamp expired", 401);
            }
            $expectedSignature = hash_hmac("sha256", $scannerId . $timestamp, $scriptHash);
            if (!hash_equals($expectedSignature, $signature)) {
                $this->handleError("Invalid signature", 401);
            }
            return true;
        }
        public function validateIP() {
            $clientIP = $this->getClientIP();
            if ($clientIP === "0.0.0.0") {
                if (empty($this->candidateIPs)) {
                    $remoteAddr = $_SERVER["REMOTE_ADDR"] ?? "unknown";
                    $this->handleError("Unable to determine client IP address. REMOTE_ADDR: $remoteAddr", 403);
                }
                $this->handleError("Access denied: IP not in trusted list. Candidates: " . implode(", ", $this->candidateIPs), 403);
            }
            return $clientIP;
        }
        public function validateScriptIntegrity($scriptPath) {
            if (!file_exists($scriptPath)) {
                return false;
            }
            $content = file_get_contents($scriptPath);
            if ($content === false) {
                return false;
            }
            
            $requiredFunctions = ["validateHeaders", "handleError", "hash_equals"];
            foreach ($requiredFunctions as $function) {
                if (strpos($content, $function) === false) {
                    return false;
                }
            }
            return true;
        }
        public function downloadScript($url, $targetPath, $maxAttempts = 3) {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $parsedUrl = parse_url($url);
                    $queryParams = [];
                    if (isset($parsedUrl["query"])) {
                        parse_str($parsedUrl["query"], $queryParams);
                    }
                    $queryParams["domain"] = $this->config["domain"];
                    $queryParams["scriptType"] = "scanner";
                    $fullUrl = $parsedUrl["scheme"] . "://" . $parsedUrl["host"] . $parsedUrl["path"] . "?" . http_build_query($queryParams);
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $fullUrl, 
                        CURLOPT_RETURNTRANSFER => true, 
                        CURLOPT_FOLLOWLOCATION => true, 
                        CURLOPT_MAXREDIRS => 3, 
                        CURLOPT_TIMEOUT => 30, 
                        CURLOPT_SSL_VERIFYPEER => true, 
                        CURLOPT_SSL_VERIFYHOST => 2, 
                        CURLOPT_USERAGENT => "Sucuri-Security-Scanner/1.0", 
                        CURLOPT_HTTPHEADER => ["Accept: text/plain, application/octet-stream", "Cache-Control: no-cache"],
                        CURLOPT_HEADER => true
                    ]);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $error = curl_error($ch);
                    curl_close($ch);
                    if ($error) {
                        throw new Exception("cURL error: $error");
                    }
                    if ($httpCode !== 200) {
                        throw new Exception("HTTP error: $httpCode");
                    }
                    
                    $headers = substr($response, 0, $headerSize);
                    $content = substr($response, $headerSize);
                    
                    if (empty($content) || strlen($content) < 100) {
                        throw new Exception("Invalid content received");
                    }
                    
                    $filename = $this->extractFilenameFromHeaders($headers);
                    if ($filename) {
                        if (!$this->validateFilename($filename)) {
                            throw new Exception("Invalid filename format: $filename");
                        }
                        $targetDir = dirname($targetPath);
                        $targetPath = $targetDir . "/" . $filename;
                    }
                    
                    if (file_put_contents($targetPath, $content) === false) {
                        throw new Exception("Failed to write file");
                    }
                    return true;
                } catch (Exception $e) {
                    error_log("Download attempt $attempt failed: " . $e->getMessage());
                    if ($attempt === $maxAttempts) {
                        $this->handleError("Failed to download script after $maxAttempts attempts: " . $e->getMessage(), 500);
                    }
                    sleep(2);
                }
            }
            return false;
        }
        private function getClientIP() {
            $headersPriority = ["HTTP_X_SUCURI_CLIENTIP", "HTTP_X_SUCURI_REAL_IP", "HTTP_INCAP_CLIENT_IP", "HTTP_X_WAFPROXY_REAL_IP", "HTTP_X_FIREWALL_IP", "HTTP_CF_CONNECTING_IP", "HTTP_FASTLY_CLIENT_IP", "HTTP_CLOUDFRONT_VIEWER_ADDRESS", "HTTP_X_AKAMAI_EDGESCAPE", "HTTP_X_AKAMAI_CONFIG_LOG_DETAIL", "HTTP_AKAMAI_ORIGIN_HOP", "HTTP_X_AZURE_CLIENTIP", "HTTP_X_AZURE_SOCKETIP", "HTTP_FLY_CLIENT_IP", "HTTP_X_APPENGINE_USER_IP", "HTTP_X_CDN_SRC_IP", "HTTP_X_STACKPATH_EDGE_IP", "HTTP_X_LIMELIGHT_CLIENT_IP", "HTTP_X_NETLIFY_CLIENT_IP", "HTTP_DO_CONNECTING_IP", "HTTP_X_VERCEL_FORWARDED_FOR", "HTTP_X_VERCEL_PROXIED_FOR", "HTTP_TRUE_CLIENT_IP", "HTTP_X_REAL_CLIENT_IP", "HTTP_X_REAL_IP", "HTTP_X_CLIENT_IP", "HTTP_X_CLUSTER_CLIENT_IP", "HTTP_X_ORIGINAL_FORWARDED_FOR", "HTTP_X_FORWARDED", "HTTP_FORWARDED_FOR", "HTTP_FORWARDED", "HTTP_X_FORWARDED_FOR", "HTTP_X_PROXYUSER_IP", "HTTP_X_COMING_FROM", "HTTP_X_ORIGINATING_IP", "HTTP_X_ORIG_CLIENT_IP", "HTTP_CLIENT_IP", "REMOTE_ADDR"];
            
            $this->candidateIPs = [];
            foreach ($headersPriority as $header) {
                if (isset($_SERVER[$header])) {
                    $parsedIPs = $this->parseIPsFromHeader($_SERVER[$header]);
                    foreach ($parsedIPs as $ip) {
                        $this->candidateIPs[] = $ip;
                    }
                }
            }
            
            if (empty($this->config["trusted_ips"])) {
                return !empty($this->candidateIPs) ? $this->candidateIPs[0] : "0.0.0.0";
            }
            
            foreach ($this->candidateIPs as $candidateIP) {
                foreach ($this->config["trusted_ips"] as $trustedIP) {
                    if ($this->ipMatches($candidateIP, $trustedIP)) {
                        return $candidateIP;
                    }
                }
            }
            
            return "0.0.0.0";
        }
        
        private function parseIPsFromHeader($value) {
            $ips = [];
            
            $parts = array_map('trim', explode(',', $value));
            
            foreach ($parts as $part) {
                if (strpos($part, ':') !== false && filter_var($part, FILTER_VALIDATE_IP) === false) {
                    $colonParts = explode(':', $part);
                    $part = $colonParts[0];
                }
                
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    $ips[] = $part;
                }
            }
            
            return $ips;
        }
        private function ipMatches($clientIP, $pattern) {
            if ($pattern === $clientIP) {
                return true;
            }
            if (strpos($pattern, "/") !== false) {
                [$subnet, $mask] = explode("/", $pattern);
                $subnet = ip2long($subnet);
                $clientIPLong = ip2long($clientIP);
                $mask = ~((1 << (32 - $mask)) - 1);
                return ($subnet & $mask) === ($clientIPLong & $mask);
            }
            return false;
        }
        public function handleError($message, $statusCode = 400) {
            http_response_code($statusCode);
            echo json_encode(["error" => true, "message" => $message, "timestamp" => time()], JSON_PRETTY_PRINT);
            exit;
        }
        
        private function extractFilenameFromHeaders($headers) {
            $lines = explode("\n", $headers);
            foreach ($lines as $line) {
                if (stripos($line, 'Content-Disposition:') === 0) {
                    if (preg_match('/filename="([^"]+)"/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
            return null;
        }
        
        private function validateFilename($filename) {
            $pattern = '/^(sucuri-sss-uploader_[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|sucuri-sss-downloader_[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\.php$/';
            return preg_match($pattern, $filename) === 1;
        }
    }

    try {
        $security = new SecurityManager($CONFIG);
        $headers = $security->validateHeaders();
        if (empty($scannerId)) {
            $security->handleError("Missing scanner-id parameter", 400);
        }
        $security->validateIP();
        $security->validateSignature($headers, $scannerId, $scriptHash);
        $scriptExists = file_exists($CONFIG["script_path"]);
        $scriptValid = $scriptExists ? $security->validateScriptIntegrity($CONFIG["script_path"]) : false;
        if (!$scriptExists || !$scriptValid || $forceDownload) {
            if (!$security->downloadScript($CONFIG["download_url"], $CONFIG["script_path"], $CONFIG["max_download_attempts"])) {
                $security->handleError("Failed to obtain valid script", 500);
            }
            if (!$security->validateScriptIntegrity($CONFIG["script_path"])) {
                $security->handleError("Downloaded script failed integrity check", 500);
            }
        }
    } catch (Exception $e) {
        error_log("Secure wrapper error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Internal server error',
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

executeSecurityLogic($scannerId, $forceDownload, $scriptHash);