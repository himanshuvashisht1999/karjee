<?php
/**
 * Table-wise Database Dump with Schema Generator
 * Location: krf-orderandsales/temp/dbdump.php
 *
 * Description:
 *   Creates a table-wise database dump (schema + data) fully compatible with:
 *   1. MySQL Workbench "Import from Dump Project Folder" (exact comment headers & routines.sql)
 *   2. MySQL Workbench "Import from Self-Contained File"
 *   3. phpMyAdmin "Import" tab (single complete .sql or .sql.zip)
 *   4. Direct CLI execution: php dbdump.php <dbname>
 */

@ini_set('memory_limit', '1024M');
@set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// -----------------------------------------------------------------------------
// 1. DATABASE CONNECTION RESOLUTION
// -----------------------------------------------------------------------------
$defaultHost = '127.0.0.1';
$defaultUser = 'root';
$defaultPass = '';

$connectionFiles = [
    __DIR__ . '/../../connection.php',
    __DIR__ . '/../connection.php',
    dirname(dirname(__DIR__)) . '/connection.php'
];

foreach ($connectionFiles as $cf) {
    if (file_exists($cf)) {
        (function() use ($cf, &$defaultHost, &$defaultUser, &$defaultPass) {
            @include $cf;
            if (isset($config) && is_array($config)) {
                if (!empty($config['servername'])) $defaultHost = $config['servername'];
                if (!empty($config['username']))   $defaultUser = $config['username'];
                if (!empty($config['dbname_krf']) && empty($config['username'])) $defaultUser = $config['dbname_krf'];
                if (isset($config['password']))    $defaultPass = $config['password'];
            }
        })();
        break;
    }
}

function getDbConnection($database = null) {
    global $defaultHost, $defaultUser, $defaultPass;
    mysqli_report(MYSQLI_REPORT_OFF);

    $hosts = [$defaultHost];
    if ($defaultHost !== 'localhost' && $defaultHost !== '127.0.0.1') {
        $hosts[] = '127.0.0.1';
        $hosts[] = 'localhost';
    }

    $conn = null;
    $lastErr = '';

    foreach ($hosts as $h) {
        $conn = @mysqli_connect($h, $defaultUser, $defaultPass, $database ?: '');
        if ($conn) break;
        $lastErr = mysqli_connect_error();
    }

    if (!$conn) {
        throw new Exception("MySQL Connection Failed (" . ($database ?: 'server') . "): " . $lastErr);
    }

    @mysqli_set_charset($conn, "utf8mb4");
    @mysqli_query($conn, "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    @mysqli_query($conn, "SET SESSION group_concat_max_len = 1048576");
    @mysqli_query($conn, "SET SESSION time_zone = '+05:30'");

    return $conn;
}

// -----------------------------------------------------------------------------
// 2. DUMP DIRECTORY SETUP
// -----------------------------------------------------------------------------
$dumpsBaseDir = __DIR__ . DIRECTORY_SEPARATOR . 'dumps';
if (!is_dir($dumpsBaseDir)) {
    @mkdir($dumpsBaseDir, 0777, true);
}
if (!file_exists($dumpsBaseDir . DIRECTORY_SEPARATOR . 'index.html')) {
    @file_put_contents($dumpsBaseDir . DIRECTORY_SEPARATOR . 'index.html', '<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h3>Direct access forbidden</h3></body></html>');
}

// -----------------------------------------------------------------------------
// 3. PURE PHP ZIP CREATOR FALLBACK
// -----------------------------------------------------------------------------
class PurePhpZip {
    private $files = [];

    public function addFile($filename, $content) {
        $this->files[] = [
            'name' => str_replace('\\', '/', $filename),
            'data' => $content,
            'time' => time()
        ];
    }

    public function save($targetPath) {
        $handle = @fopen($targetPath, 'wb');
        if (!$handle) return false;

        $cdEntries = '';
        $offset = 0;

        foreach ($this->files as $file) {
            $name = $file['name'];
            $data = $file['data'];
            $uncompressedSize = strlen($data);
            $crc = crc32($data);

            if (function_exists('gzdeflate')) {
                $compressedData = gzdeflate($data);
                $compressedSize = strlen($compressedData);
                $method = 8; // DEFLATE
            } else {
                $compressedData = $data;
                $compressedSize = $uncompressedSize;
                $method = 0; // STORE
            }

            $nameLen = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50, 20, 0, $method, 0, 0, $crc,
                $compressedSize, $uncompressedSize, $nameLen, 0
            ) . $name;

            fwrite($handle, $localHeader);
            fwrite($handle, $compressedData);

            $cdEntries .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50, 20, 20, 0, $method, 0, 0, $crc,
                $compressedSize, $uncompressedSize, $nameLen,
                0, 0, 0, 0, 32, $offset
            ) . $name;

            $offset += strlen($localHeader) + $compressedSize;
        }

        $cdSize = strlen($cdEntries);
        fwrite($handle, $cdEntries);

        $eocd = pack(
            'VvvvvVVv',
            0x06054b50, 0, 0, count($this->files), count($this->files),
            $cdSize, $offset, 0
        );
        fwrite($handle, $eocd);
        fclose($handle);

        return file_exists($targetPath) && filesize($targetPath) > 0;
    }
}

function createZipFromFiles($filesMap, $zipFilePath) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($filesMap as $localName => $absPath) {
                if (file_exists($absPath)) {
                    $zip->addFile($absPath, $localName);
                }
            }
            $zip->close();
            if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
                return true;
            }
        }
    }

    $pureZip = new PurePhpZip();
    foreach ($filesMap as $localName => $absPath) {
        if (file_exists($absPath)) {
            $content = @file_get_contents($absPath);
            if ($content !== false) {
                $pureZip->addFile($localName, $content);
            }
        }
    }
    return $pureZip->save($zipFilePath);
}

// -----------------------------------------------------------------------------
// 4. CORE DUMP FUNCTIONS (MYSQL WORKBENCH COMPLIANT FORMAT)
// -----------------------------------------------------------------------------

function getStandardWorkbenchHeader($conn, $database) {
    $serverVer = mysqli_get_server_info($conn);
    $header  = "-- MySQL dump 10.13  Distrib 8.0.32, for Win64 (x86_64)\n";
    $header .= "--\n";
    $header .= "-- Host: localhost    Database: {$database}\n";
    $header .= "-- ------------------------------------------------------\n";
    $header .= "-- Server version\t{$serverVer}\n\n";

    $header .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $header .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $header .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $header .= "/*!40101 SET NAMES utf8mb4 */;\n";
    $header .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
    $header .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
    $header .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
    $header .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
    $header .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
    $header .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";

    $header .= "USE `{$database}`;\n\n";
    return $header;
}

function getStandardWorkbenchFooter() {
    $now = date('Y-m-d H:i:s');
    $footer  = "\n/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
    $footer .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
    $footer .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
    $footer .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $footer .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $footer .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
    $footer .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n\n";
    $footer .= "-- Dump completed on {$now}\n";
    return $footer;
}

function dumpSingleTable($conn, $database, $tableName, $targetFilePath, $options = []) {
    $includeSchema = $options['include_schema'] ?? true;
    $includeData   = $options['include_data'] ?? true;
    $dropTable     = $options['drop_table'] ?? true;

    $fp = @fopen($targetFilePath, 'wb');
    if (!$fp) {
        throw new Exception("Unable to open file for writing: " . basename($targetFilePath));
    }

    fwrite($fp, getStandardWorkbenchHeader($conn, $database));

    $safeTable = str_replace('`', '``', $tableName);

    $isView = false;
    $tableTypeQuery = @mysqli_query($conn, "SHOW FULL TABLES IN `{$database}` WHERE `Tables_in_{$database}` = '" . mysqli_real_escape_string($conn, $tableName) . "'");
    if ($tableTypeQuery && ($typeRow = mysqli_fetch_row($tableTypeQuery))) {
        if (isset($typeRow[1]) && strtoupper($typeRow[1]) === 'VIEW') {
            $isView = true;
        }
    }

    $exportedRows = 0;

    if ($isView) {
        if ($includeSchema) {
            fwrite($fp, "--\n-- Table structure for table `{$safeTable}`\n--\n\n");
            if ($dropTable) {
                fwrite($fp, "DROP VIEW IF EXISTS `{$safeTable}`;\n");
            }
            $viewQuery = @mysqli_query($conn, "SHOW CREATE VIEW `{$safeTable}`");
            if ($viewQuery && ($viewRow = mysqli_fetch_assoc($viewQuery))) {
                $createViewSql = $viewRow['Create View'] ?? $viewRow['Create Table'] ?? '';
                fwrite($fp, $createViewSql . ";\n\n");
            }
        }
    } else {
        if ($includeSchema) {
            fwrite($fp, "--\n-- Table structure for table `{$safeTable}`\n--\n\n");
            if ($dropTable) {
                fwrite($fp, "DROP TABLE IF EXISTS `{$safeTable}`;\n");
            }

            fwrite($fp, "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
            fwrite($fp, "/*!40101 SET character_set_client = utf8mb4 */;\n");

            $schemaQuery = @mysqli_query($conn, "SHOW CREATE TABLE `{$safeTable}`");
            if ($schemaQuery && ($schemaRow = mysqli_fetch_assoc($schemaQuery))) {
                $createTableSql = $schemaRow['Create Table'] ?? '';
                fwrite($fp, $createTableSql . ";\n");
            } else {
                throw new Exception("Failed to retrieve schema for table `{$tableName}`: " . mysqli_error($conn));
            }

            fwrite($fp, "/*!40101 SET character_set_client = @saved_cs_client */;\n\n");
        }

        if ($includeData) {
            fwrite($fp, "--\n-- Dumping data for table `{$safeTable}`\n--\n\n");
            fwrite($fp, "LOCK TABLES `{$safeTable}` WRITE;\n");
            fwrite($fp, "/*!40000 ALTER TABLE `{$safeTable}` DISABLE KEYS */;\n");

            $selectSql = "SELECT * FROM `{$safeTable}`";
            $dataResult = @mysqli_query($conn, $selectSql, MYSQLI_USE_RESULT);

            if ($dataResult) {
                $fields = mysqli_fetch_fields($dataResult);
                $fieldCount = count($fields);
                $colNames = [];
                $numericTypes = [
                    MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG,
                    MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_INT24
                ];
                $isNumericField = [];
                foreach ($fields as $idx => $f) {
                    $colNames[] = '`' . str_replace('`', '``', $f->name) . '`';
                    $isNumericField[$idx] = in_array($f->type, $numericTypes);
                }
                $colsSql = '(' . implode(',', $colNames) . ')';

                $batchRows = [];
                $batchSize = 100;
                $approxBytes = 0;
                $maxBatchBytes = 512 * 1024;

                while ($row = mysqli_fetch_row($dataResult)) {
                    $exportedRows++;
                    $values = [];
                    for ($i = 0; $i < $fieldCount; $i++) {
                        $val = $row[$i];
                        if ($val === null) {
                            $values[] = 'NULL';
                        } elseif ($isNumericField[$i] && is_numeric($val)) {
                            $values[] = $val;
                        } else {
                            $values[] = "'" . mysqli_real_escape_string($conn, $val) . "'";
                        }
                    }
                    $rowSql = '(' . implode(',', $values) . ')';
                    $batchRows[] = $rowSql;
                    $approxBytes += strlen($rowSql);

                    if (count($batchRows) >= $batchSize || $approxBytes >= $maxBatchBytes) {
                        $insertSql = "INSERT INTO `{$safeTable}` {$colsSql} VALUES\n" . implode(",\n", $batchRows) . ";\n";
                        fwrite($fp, $insertSql);
                        $batchRows = [];
                        $approxBytes = 0;
                    }
                }
                mysqli_free_result($dataResult);

                if (!empty($batchRows)) {
                    $insertSql = "INSERT INTO `{$safeTable}` {$colsSql} VALUES\n" . implode(",\n", $batchRows) . ";\n";
                    fwrite($fp, $insertSql);
                }
            }

            fwrite($fp, "/*!40000 ALTER TABLE `{$safeTable}` ENABLE KEYS */;\n");
            fwrite($fp, "UNLOCK TABLES;\n");
        }
    }

    fwrite($fp, getStandardWorkbenchFooter());
    fclose($fp);

    return [
        'rows' => $exportedRows,
        'size' => filesize($targetFilePath),
        'is_view' => $isView
    ];
}

function dumpRoutines($conn, $database, $targetFilePath) {
    $fp = @fopen($targetFilePath, 'wb');
    if (!$fp) return false;

    fwrite($fp, getStandardWorkbenchHeader($conn, $database));
    fwrite($fp, "--\n-- Dumping routines for database '{$database}'\n--\n\n");

    $routinesCount = 0;

    // 1. Procedures
    $procRes = @mysqli_query($conn, "SHOW PROCEDURE STATUS WHERE Db = '" . mysqli_real_escape_string($conn, $database) . "'");
    if ($procRes) {
        while ($proc = mysqli_fetch_assoc($procRes)) {
            $pName = $proc['Name'];
            $defRes = @mysqli_query($conn, "SHOW CREATE PROCEDURE `{$pName}`");
            if ($defRes && ($defRow = mysqli_fetch_assoc($defRes))) {
                $createSql = $defRow['Create Procedure'] ?? '';
                fwrite($fp, "/*!50003 DROP PROCEDURE IF EXISTS `{$pName}` */;\n");
                fwrite($fp, "/*!50003 SET @saved_cs_client = @@character_set_client */;\n");
                fwrite($fp, "/*!50003 SET character_set_client = utf8mb4 */;\n");
                fwrite($fp, $createSql . ";\n");
                fwrite($fp, "/*!50003 SET character_set_client = @saved_cs_client */;\n\n");
                $routinesCount++;
            }
        }
    }

    // 2. Functions
    $funcRes = @mysqli_query($conn, "SHOW FUNCTION STATUS WHERE Db = '" . mysqli_real_escape_string($conn, $database) . "'");
    if ($funcRes) {
        while ($func = mysqli_fetch_assoc($funcRes)) {
            $fName = $func['Name'];
            $defRes = @mysqli_query($conn, "SHOW CREATE FUNCTION `{$fName}`");
            if ($defRes && ($defRow = mysqli_fetch_assoc($defRes))) {
                $createSql = $defRow['Create Function'] ?? '';
                fwrite($fp, "/*!50003 DROP FUNCTION IF EXISTS `{$fName}` */;\n");
                fwrite($fp, "/*!50003 SET @saved_cs_client = @@character_set_client */;\n");
                fwrite($fp, "/*!50003 SET character_set_client = utf8mb4 */;\n");
                fwrite($fp, $createSql . ";\n");
                fwrite($fp, "/*!50003 SET character_set_client = @saved_cs_client */;\n\n");
                $routinesCount++;
            }
        }
    }

    // 3. Triggers
    $trigRes = @mysqli_query($conn, "SHOW TRIGGERS FROM `{$database}`");
    if ($trigRes) {
        while ($trig = mysqli_fetch_assoc($trigRes)) {
            $tName = $trig['Trigger'];
            $defRes = @mysqli_query($conn, "SHOW CREATE TRIGGER `{$tName}`");
            if ($defRes && ($defRow = mysqli_fetch_assoc($defRes))) {
                $createSql = $defRow['SQL Original Statement'] ?? '';
                fwrite($fp, "/*!50003 DROP TRIGGER IF EXISTS `{$tName}` */;\n");
                fwrite($fp, $createSql . ";\n\n");
                $routinesCount++;
            }
        }
    }

    // 4. Views
    $viewsRes = @mysqli_query($conn, "SHOW FULL TABLES IN `{$database}` WHERE Table_type = 'VIEW'");
    if ($viewsRes) {
        while ($vRow = mysqli_fetch_row($viewsRes)) {
            $vName = $vRow[0];
            $defRes = @mysqli_query($conn, "SHOW CREATE VIEW `{$vName}`");
            if ($defRes && ($defRow = mysqli_fetch_assoc($defRes))) {
                $createSql = $defRow['Create View'] ?? '';
                fwrite($fp, "DROP VIEW IF EXISTS `{$vName}`;\n");
                fwrite($fp, "/*!50001 VIEW `{$vName}` */;\n");
                fwrite($fp, $createSql . ";\n\n");
                $routinesCount++;
            }
        }
    }

    if ($routinesCount === 0) {
        fwrite($fp, "-- No stored procedures, functions, triggers or views found.\n\n");
    }

    fwrite($fp, getStandardWorkbenchFooter());
    fclose($fp);

    return [
        'count' => $routinesCount,
        'size' => filesize($targetFilePath)
    ];
}

// -----------------------------------------------------------------------------
// 5. CLI EXECUTION HANDLER
// -----------------------------------------------------------------------------
if (php_sapi_name() === 'cli') {
    $targetDb = $argv[1] ?? null;
    if (empty($targetDb)) {
        echo "Usage: php dbdump.php <database_name>\n";
        echo "Example: php dbdump.php karjeefarm\n";
        exit(1);
    }

    echo "=== Starting Workbench & phpMyAdmin Compatible Dump for: {$targetDb} ===\n";
    try {
        $conn = getDbConnection($targetDb);
        $res = mysqli_query($conn, "SHOW FULL TABLES WHERE Table_type IN ('BASE TABLE', 'VIEW')");
        $tables = [];
        while ($r = mysqli_fetch_row($res)) {
            $tables[] = ['name' => $r[0], 'type' => $r[1] ?? 'BASE TABLE'];
        }

        $timestamp = date('Ymd_His');
        $folderName = "{$targetDb}_dump_{$timestamp}";
        $projectFolderPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folderName;
        @mkdir($projectFolderPath, 0777, true);

        $totalTables = count($tables);
        echo "Found {$totalTables} tables/views.\n";
        echo "Project Folder: {$projectFolderPath}\n\n";

        $totalRows = 0;
        $totalBytes = 0;
        $projectFilesMap = [];

        foreach ($tables as $idx => $t) {
            $tName = $t['name'];
            $num = $idx + 1;
            echo sprintf("[%d/%d] Dumping table `%s`...", $num, $totalTables, $tName);

            $fileName = "{$targetDb}_{$tName}.sql";
            $targetSql = $projectFolderPath . DIRECTORY_SEPARATOR . $fileName;

            $res = dumpSingleTable($conn, $targetDb, $tName, $targetSql, [
                'include_schema' => true,
                'include_data' => true,
                'drop_table' => true
            ]);

            $projectFilesMap[$fileName] = $targetSql;
            $totalRows += $res['rows'];
            $totalBytes += $res['size'];

            echo sprintf(" Done! (%d rows, %s KB)\n", $res['rows'], round($res['size'] / 1024, 1));
        }

        echo "\nGenerating routines file: {$targetDb}_routines.sql...";
        $routinesFileName = "{$targetDb}_routines.sql";
        $routinesPath = $projectFolderPath . DIRECTORY_SEPARATOR . $routinesFileName;
        dumpRoutines($conn, $targetDb, $routinesPath);
        $projectFilesMap[$routinesFileName] = $routinesPath;
        echo " Done!\n";

        echo "Generating single self-contained complete SQL file for phpMyAdmin...";
        $selfContainedFileName = "{$targetDb}_complete.sql";
        $selfContainedPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folderName . "_complete.sql";
        $scHandle = fopen($selfContainedPath, 'wb');
        fwrite($scHandle, "-- =============================================================\n");
        fwrite($scHandle, "-- Single Self-Contained Database Dump\n");
        fwrite($scHandle, "-- Database: `{$targetDb}`\n");
        fwrite($scHandle, "-- Suitable for: phpMyAdmin (Import) & MySQL Workbench (Self-Contained File)\n");
        fwrite($scHandle, "-- =============================================================\n\n");
        fwrite($scHandle, "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `{$targetDb}` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;\n");
        fwrite($scHandle, "USE `{$targetDb}`;\n\n");

        foreach ($projectFilesMap as $fName => $fPath) {
            if ($fName === $routinesFileName) continue;
            fwrite($scHandle, file_get_contents($fPath) . "\n\n");
        }
        if (file_exists($routinesPath)) {
            fwrite($scHandle, file_get_contents($routinesPath) . "\n\n");
        }
        fclose($scHandle);
        echo " Done! Size: " . round(filesize($selfContainedPath) / (1024 * 1024), 2) . " MB\n";

        echo "Compressing Workbench Project Folder into ZIP archive...";
        $zipPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . "{$folderName}.zip";
        createZipFromFiles($projectFilesMap, $zipPath);
        echo " Done! Size: " . round(filesize($zipPath) / (1024 * 1024), 2) . " MB\n";

        echo "\n=============================================================\n";
        echo "DUMP COMPLETE & WORKBENCH READY!\n";
        echo "Database: {$targetDb}\n";
        echo "Tables: {$totalTables}\n";
        echo "Workbench Project Folder: {$projectFolderPath}\n";
        echo "Workbench ZIP: {$zipPath}\n";
        echo "phpMyAdmin Self-Contained File: {$selfContainedPath}\n";
        echo "=============================================================\n";
        exit(0);
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// -----------------------------------------------------------------------------
// 6. HTTP API ROUTER
// -----------------------------------------------------------------------------
$reqAction = $_REQUEST['action'] ?? '';
$reqDb = trim($_REQUEST['db'] ?? $_REQUEST['database'] ?? $_REQUEST['dbname'] ?? '');

function sendJson($data, $statusCode = 200) {
    @ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($data);
    exit;
}

if ($reqAction === 'download_file') {
    $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['folder'] ?? '');
    $file   = basename($_GET['file'] ?? '');

    if (empty($file)) {
        die("Invalid file parameter.");
    }

    if (!empty($folder)) {
        $filePath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file;
    } else {
        $filePath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $file;
    }

    if (!file_exists($filePath)) {
        die("File not found on server.");
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeType = ($ext === 'zip') ? 'application/zip' : 'text/plain';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

if ($reqAction === 'delete_dump' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['folder'] ?? '');
    if (empty($folder)) {
        sendJson(['success' => false, 'message' => 'Invalid folder name'], 400);
    }
    $targetDir = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folder;
    if (is_dir($targetDir)) {
        $files = glob($targetDir . DIRECTORY_SEPARATOR . '*');
        foreach ($files as $f) {
            if (is_file($f)) @unlink($f);
        }
        @rmdir($targetDir);

        $companionFiles = glob($dumpsBaseDir . DIRECTORY_SEPARATOR . "{$folder}*");
        foreach ($companionFiles as $cf) {
            if (is_file($cf)) @unlink($cf);
        }

        sendJson(['success' => true, 'message' => 'Dump deleted successfully']);
    }
    sendJson(['success' => false, 'message' => 'Folder does not exist']);
}

if ($reqAction === 'list_dbs') {
    try {
        $conn = getDbConnection();
        $res = mysqli_query($conn, "SHOW DATABASES");
        $dbs = [];
        $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];
        while ($r = mysqli_fetch_row($res)) {
            $name = $r[0];
            $dbs[] = [
                'name' => $name,
                'is_system' => in_array($name, $systemDbs)
            ];
        }
        sendJson(['success' => true, 'databases' => $dbs]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($reqAction === 'init_dump') {
    if (empty($reqDb)) {
        sendJson(['success' => false, 'message' => 'Database name is required.'], 400);
    }

    try {
        $conn = getDbConnection($reqDb);
        $res = mysqli_query($conn, "SHOW TABLE STATUS");
        if (!$res) {
            throw new Exception("Unable to fetch tables from database `{$reqDb}`: " . mysqli_error($conn));
        }

        $tables = [];
        $totalEstRows = 0;
        $totalEstDataBytes = 0;

        while ($row = mysqli_fetch_assoc($res)) {
            $tName = $row['Name'];
            $tEngine = $row['Engine'] ?? 'VIEW';
            $tRows = intval($row['Rows'] ?? 0);
            $tDataLen = intval($row['Data_length'] ?? 0);
            $isView = ($row['Comment'] === 'VIEW' || empty($tEngine));

            $tables[] = [
                'name' => $tName,
                'engine' => $tEngine,
                'rows' => $tRows,
                'data_length' => $tDataLen,
                'is_view' => $isView
            ];

            $totalEstRows += $tRows;
            $totalEstDataBytes += $tDataLen;
        }

        if (empty($tables)) {
            throw new Exception("Database `{$reqDb}` has no tables or views.");
        }

        $timestamp = date('Ymd_His');
        $folderName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $reqDb) . "_dump_{$timestamp}";
        $folderPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folderName;
        @mkdir($folderPath, 0777, true);

        sendJson([
            'success' => true,
            'database' => $reqDb,
            'folder' => $folderName,
            'table_count' => count($tables),
            'tables' => $tables,
            'estimated_rows' => $totalEstRows,
            'estimated_size' => $totalEstDataBytes
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

if ($reqAction === 'dump_table') {
    $table  = trim($_REQUEST['table'] ?? '');
    $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['folder'] ?? '');

    if (empty($reqDb) || empty($table) || empty($folder)) {
        sendJson(['success' => false, 'message' => 'Missing db, table, or folder parameter.'], 400);
    }

    $folderPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($folderPath)) {
        sendJson(['success' => false, 'message' => 'Dump session folder does not exist.'], 400);
    }

    $sqlFileName = "{$reqDb}_{$table}.sql";
    $targetSqlFile = $folderPath . DIRECTORY_SEPARATOR . $sqlFileName;

    try {
        $conn = getDbConnection($reqDb);
        $options = [
            'include_schema' => isset($_REQUEST['include_schema']) ? filter_var($_REQUEST['include_schema'], FILTER_VALIDATE_BOOLEAN) : true,
            'include_data'   => isset($_REQUEST['include_data']) ? filter_var($_REQUEST['include_data'], FILTER_VALIDATE_BOOLEAN) : true,
            'drop_table'     => isset($_REQUEST['drop_table']) ? filter_var($_REQUEST['drop_table'], FILTER_VALIDATE_BOOLEAN) : true
        ];

        $res = dumpSingleTable($conn, $reqDb, $table, $targetSqlFile, $options);

        sendJson([
            'success' => true,
            'table' => $table,
            'rows' => $res['rows'],
            'size' => $res['size'],
            'size_formatted' => round($res['size'] / 1024, 2) . ' KB',
            'is_view' => $res['is_view'],
            'file' => $sqlFileName,
            'download_url' => "?action=download_file&folder={$folder}&file=" . urlencode($sqlFileName)
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'table' => $table, 'message' => $e->getMessage()], 500);
    }
}

if ($reqAction === 'finalize_dump') {
    $folder = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['folder'] ?? '');
    if (empty($reqDb) || empty($folder)) {
        sendJson(['success' => false, 'message' => 'Missing database or folder name.'], 400);
    }

    $folderPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($folderPath)) {
        sendJson(['success' => false, 'message' => 'Folder does not exist.'], 400);
    }

    try {
        $conn = getDbConnection($reqDb);

        // 1. Generate <database>_routines.sql
        $routinesFileName = "{$reqDb}_routines.sql";
        $routinesPath = $folderPath . DIRECTORY_SEPARATOR . $routinesFileName;
        $routinesInfo = dumpRoutines($conn, $reqDb, $routinesPath);

        // 2. Collect all project folder SQL files
        $projectSqlFiles = glob($folderPath . DIRECTORY_SEPARATOR . '*.sql');
        $filesMap = [];
        $totalSqlBytes = 0;

        foreach ($projectSqlFiles as $sFile) {
            $bName = basename($sFile);
            $filesMap[$bName] = $sFile;
            $totalSqlBytes += filesize($sFile);
        }

        // 3. Create Workbench Dump Project Folder ZIP
        $zipFilename = "{$folder}.zip";
        $zipFilePath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $zipFilename;
        $zipGenerated = createZipFromFiles($filesMap, $zipFilePath);

        // 4. Generate Single Complete .SQL File for phpMyAdmin & Workbench Self-Contained Import
        $selfContainedFilename = "{$reqDb}_complete.sql";
        $selfContainedPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . "{$folder}_complete.sql";
        $scHandle = fopen($selfContainedPath, 'wb');
        if ($scHandle) {
            fwrite($scHandle, "-- =============================================================\n");
            fwrite($scHandle, "-- Single Self-Contained Database Dump\n");
            fwrite($scHandle, "-- Database: `{$reqDb}`\n");
            fwrite($scHandle, "-- Suitable for: phpMyAdmin (Import) & MySQL Workbench (Self-Contained File)\n");
            fwrite($scHandle, "-- =============================================================\n\n");
            fwrite($scHandle, "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `{$reqDb}` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;\n");
            fwrite($scHandle, "USE `{$reqDb}`;\n\n");

            foreach ($projectSqlFiles as $sFile) {
                if (basename($sFile) === $routinesFileName) continue;
                $content = file_get_contents($sFile);
                if ($content !== false) {
                    fwrite($scHandle, $content . "\n\n");
                }
            }
            if (file_exists($routinesPath)) {
                fwrite($scHandle, file_get_contents($routinesPath) . "\n\n");
            }
            fclose($scHandle);
        }

        // Zipped version of self-contained file
        $scZipFilename = "{$folder}_complete.sql.zip";
        $scZipPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . $scZipFilename;
        createZipFromFiles([$selfContainedFilename => $selfContainedPath], $scZipPath);

        sendJson([
            'success' => true,
            'database' => $reqDb,
            'folder' => $folder,
            'folder_path' => $folderPath,
            'total_sql_files' => count($projectSqlFiles),
            'total_size' => $totalSqlBytes,
            'total_size_formatted' => round($totalSqlBytes / (1024 * 1024), 2) . ' MB',
            'zip_filename' => $zipGenerated ? $zipFilename : null,
            'zip_size' => $zipGenerated ? filesize($zipFilePath) : 0,
            'zip_download_url' => $zipGenerated ? "?action=download_file&file=" . urlencode($zipFilename) : null,
            'self_contained_filename' => file_exists($selfContainedPath) ? basename($selfContainedPath) : null,
            'self_contained_size' => file_exists($selfContainedPath) ? filesize($selfContainedPath) : 0,
            'self_contained_download_url' => file_exists($selfContainedPath) ? "?action=download_file&file=" . urlencode(basename($selfContainedPath)) : null,
            'sc_zip_filename' => file_exists($scZipPath) ? $scZipFilename : null,
            'sc_zip_size' => file_exists($scZipPath) ? filesize($scZipPath) : 0,
            'sc_zip_download_url' => file_exists($scZipPath) ? "?action=download_file&file=" . urlencode($scZipFilename) : null
        ]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// Read existing dump folders
$existingDumps = [];
if (is_dir($dumpsBaseDir)) {
    $dirs = glob($dumpsBaseDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    if ($dirs) {
        usort($dirs, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach (array_slice($dirs, 0, 10) as $d) {
            $folder = basename($d);
            $sqlCount = count(glob($d . DIRECTORY_SEPARATOR . '*.sql'));
            $zipPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . "{$folder}.zip";
            $hasZip = file_exists($zipPath);
            $scPath = $dumpsBaseDir . DIRECTORY_SEPARATOR . "{$folder}_complete.sql";
            $hasSc = file_exists($scPath);

            $existingDumps[] = [
                'folder' => $folder,
                'time' => date('Y-m-d H:i:s', filemtime($d)),
                'sql_count' => $sqlCount,
                'has_zip' => $hasZip,
                'zip_name' => "{$folder}.zip",
                'has_sc' => $hasSc,
                'sc_name' => basename($scPath)
            ];
        }
    }
}

// Fetch databases
$availableDatabases = [];
try {
    $rootConn = getDbConnection();
    $dbRes = mysqli_query($rootConn, "SHOW DATABASES");
    $systemDbs = ['information_schema', 'performance_schema', 'mysql', 'sys'];
    while ($r = mysqli_fetch_row($dbRes)) {
        if (!in_array($r[0], $systemDbs)) {
            $availableDatabases[] = $r[0];
        }
    }
} catch (Throwable $t) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Table-wise Dump Generator (Workbench & phpMyAdmin Ready)</title>
    <style>
        :root {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --border-color: #334155;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-amber: #f59e0b;
            --accent-red: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --radius-md: 10px;
            --radius-lg: 14px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-bar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 22px 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon {
            background: #2563eb22;
            color: #60a5fa;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            border: 1px solid #3b82f644;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .header-title p {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 2px;
        }

        .server-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            border: 1px solid var(--border-color);
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .server-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent-green);
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .input-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .text-input {
            flex: 1;
            min-width: 260px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: #ffffff;
            font-size: 15px;
            font-family: monospace;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            outline: none;
            transition: all 0.2s;
        }

        .text-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .btn {
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-green {
            background: var(--accent-green);
        }
        .btn-green:hover {
            background: #059669;
        }

        .btn-purple {
            background: var(--accent-purple);
        }
        .btn-purple:hover {
            background: #7c3aed;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: #cbd5e1;
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: #64748b;
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        .btn-danger:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .quick-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .pill-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: #94a3b8;
            font-size: 12px;
            font-family: monospace;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .pill-btn:hover, .pill-btn.active {
            background: rgba(59, 130, 246, 0.15);
            border-color: var(--primary);
            color: #93c5fd;
        }

        .options-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-top: 16px;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #cbd5e1;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        #progressCard {
            display: none;
        }

        .progress-track {
            background: #0f172a;
            border: 1px solid var(--border-color);
            height: 16px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            width: 0%;
            transition: width 0.3s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px 18px;
        }

        .stat-box .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .stat-box .stat-val {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-top: 4px;
            font-family: monospace;
        }

        .completion-banner {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.12) 100%);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: var(--radius-md);
            padding: 24px;
            margin-bottom: 24px;
        }

        .completion-header {
            margin-bottom: 16px;
        }

        .completion-header h3 {
            color: #34d399;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .completion-header p {
            color: #cbd5e1;
            font-size: 14px;
            margin-top: 4px;
        }

        .download-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .download-card {
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .download-card h4 {
            font-size: 15px;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .download-card p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .filter-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-box {
            background: #0f172a;
            border: 1px solid var(--border-color);
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            min-width: 220px;
        }

        .table-responsive {
            max-height: 480px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
        }

        table.dump-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        table.dump-table th {
            background: #0f172a;
            position: sticky;
            top: 0;
            padding: 12px 14px;
            font-weight: 600;
            color: #94a3b8;
            border-bottom: 1px solid var(--border-color);
            z-index: 2;
        }

        table.dump-table td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.4);
            color: #cbd5e1;
        }

        table.dump-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .badge-pending { background: #334155; color: #94a3b8; }
        .badge-dumping { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #3b82f644; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b98144; }
        .badge-error   { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef444444; }
        .badge-view    { background: rgba(139, 92, 246, 0.2); color: #c084fc; border: 1px solid #8b5cf644; }

        .guide-box {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid #334155;
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-top: 20px;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.6;
        }

        .guide-box h4 {
            color: #60a5fa;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .guide-box ol {
            margin-left: 20px;
        }

        .code-pill {
            font-family: monospace;
            background: #0f172a;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-bar">
            <div class="header-title">
                <div class="header-icon">🗄️</div>
                <div>
                    <h1>MySQL Workbench & phpMyAdmin Database Dump</h1>
                    <p>Creates table-wise schema and data dumps fully compatible with Workbench Project Folders and phpMyAdmin</p>
                </div>
            </div>
            <div class="server-badge">
                <span class="server-dot"></span>
                <span>Host: <?= htmlspecialchars($defaultHost) ?> | User: <?= htmlspecialchars($defaultUser) ?></span>
            </div>
        </div>

        <!-- Form Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>⚡ Database Selection</span>
                </div>
                <span style="font-size: 12px; color: var(--text-muted);">Pass only the database name</span>
            </div>

            <form id="dumpForm" onsubmit="handleFormSubmit(event)">
                <div class="form-group">
                    <label for="dbNameInput">Target Database Name</label>
                    <div class="input-row">
                        <input
                            type="text"
                            id="dbNameInput"
                            name="db"
                            class="text-input"
                            placeholder="Enter database name (e.g. karjeefarm, krf_accounting)..."
                            value="<?= htmlspecialchars($reqDb) ?>"
                            required
                            autocomplete="off"
                            list="dbDatalist"
                        >
                        <datalist id="dbDatalist">
                            <?php foreach ($availableDatabases as $dbItem): ?>
                                <option value="<?= htmlspecialchars($dbItem) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <button type="submit" id="startBtn" class="btn">
                            <span>🚀 Start Table-wise Dump</span>
                        </button>
                    </div>

                    <?php if (!empty($availableDatabases)): ?>
                        <div class="quick-pills">
                            <span style="font-size: 12px; color: #64748b; line-height: 26px;">Quick select:</span>
                            <?php foreach ($availableDatabases as $ad): ?>
                                <button type="button" class="pill-btn <?= ($reqDb === $ad) ? 'active' : '' ?>" onclick="selectDb('<?= htmlspecialchars($ad) ?>')">
                                    <?= htmlspecialchars($ad) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="options-row">
                    <label class="checkbox-label">
                        <input type="checkbox" id="optSchema" checked>
                        <span>Include Schema (CREATE TABLE)</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="optData" checked>
                        <span>Include Data (INSERT INTO)</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="optDrop" checked>
                        <span>Add DROP TABLE IF EXISTS</span>
                    </label>
                    <span style="color: #64748b; font-size: 12px; margin-left: auto;">
                        ✓ Generates both Workbench Project Folder (ZIP) and Single Self-Contained file (.sql)
                    </span>
                </div>
            </form>
        </div>

        <!-- Progress Card -->
        <div class="card" id="progressCard">
            <div class="card-header">
                <div class="card-title">
                    <span id="currentProcessTitle">Exporting Tables...</span>
                </div>
                <div id="controlsGroup" style="display: flex; gap: 8px;">
                    <button class="btn btn-outline btn-sm" id="pauseResumeBtn" onclick="togglePause()">⏸️ Pause</button>
                    <button class="btn btn-danger btn-sm" id="cancelBtn" onclick="cancelDump()">✖ Cancel</button>
                </div>
            </div>

            <div class="progress-track">
                <div class="progress-bar-fill" id="progressBar"></div>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Database</div>
                    <div class="stat-val" id="statDb" style="color: #60a5fa; font-size: 16px;">-</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Tables Progress</div>
                    <div class="stat-val" id="statTablesCount">0 / 0</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Rows Exported</div>
                    <div class="stat-val" id="statRowsCount">0</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Size Generated</div>
                    <div class="stat-val" id="statTotalSize">0 KB</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Elapsed Time</div>
                    <div class="stat-val" id="statElapsed">00:00</div>
                </div>
            </div>

            <!-- Completion Banner -->
            <div class="completion-banner" id="completionBanner" style="display: none;">
                <div class="completion-header">
                    <h3>✅ Database Dump Successfully Generated!</h3>
                    <p id="completionSummaryText">All tables were exported with full schema, table comments, and routines.</p>
                </div>

                <div class="download-boxes">
                    <!-- Option 1: MySQL Workbench Project Folder -->
                    <div class="download-card">
                        <div>
                            <h4>📦 MySQL Workbench Project Folder (ZIP)</h4>
                            <p>Contains individual table files named <span class="code-pill">[db]_[table].sql</span> and <span class="code-pill">[db]_routines.sql</span> with official mysqldump headers.</p>
                        </div>
                        <a id="wbZipBtn" href="#" class="btn btn-green">
                            ⬇️ Download Workbench Project ZIP
                        </a>
                    </div>

                    <!-- Option 2: phpMyAdmin / Workbench Self-Contained -->
                    <div class="download-card">
                        <div>
                            <h4>📄 phpMyAdmin Single Complete .SQL</h4>
                            <p>Single self-contained file with CREATE DATABASE, USE, and all tables/data. Upload directly into phpMyAdmin's <strong>Import</strong> tab or select <strong>"Import from Self-Contained File"</strong> in Workbench.</p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a id="scSqlBtn" href="#" class="btn btn-purple" style="flex: 1;">
                                ⬇️ .SQL File
                            </a>
                            <a id="scZipBtn" href="#" class="btn btn-outline" style="flex: 1;">
                                🗜️ .SQL.ZIP
                            </a>
                        </div>
                    </div>
                </div>

                <!-- How to Import Guide -->
                <div class="guide-box">
                    <h4>🛠️ How to Import into MySQL Workbench:</h4>
                    <ol>
                        <li><strong>Method A (Dump Project Folder):</strong> Download the ZIP above, extract it to a folder. In Workbench, select <strong>"Import from Dump Project Folder"</strong>, select the extracted folder, and click <strong>"Load Folder Contents"</strong>. The Schema and all Tables will now appear in the boxes! Click <strong>"Start Import"</strong>.</li>
                        <li><strong>Method B (Self-Contained File):</strong> In Workbench, select the second radio button: <strong>"Import from Self-Contained File"</strong>, browse to the downloaded <span class="code-pill">_complete.sql</span>, choose your target schema, and click <strong>"Start Import"</strong>.</li>
                    </ol>
                    <h4 style="margin-top: 12px;">🌐 How to Import into phpMyAdmin:</h4>
                    <ol>
                        <li>Open phpMyAdmin -> click your target database (or create a new one).</li>
                        <li>Click the <strong>Import</strong> tab at the top.</li>
                        <li>Browse and select the downloaded <span class="code-pill">_complete.sql</span> (or <span class="code-pill">_complete.sql.zip</span>) and click <strong>Go / Import</strong>.</li>
                    </ol>
                </div>
            </div>

            <!-- Table Breakdown List -->
            <div class="filter-row">
                <div style="font-size: 14px; font-weight: 600; color: #cbd5e1;">Table Dump Breakdown</div>
                <input type="text" class="search-box" id="tableFilterInput" placeholder="Filter tables by name..." onkeyup="filterTables()">
            </div>

            <div class="table-responsive">
                <table class="dump-table" id="dumpTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Table Name</th>
                            <th>Type</th>
                            <th>Est. Rows</th>
                            <th>Exported Rows</th>
                            <th>File Size</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Recent Dumps History -->
        <?php if (!empty($existingDumps)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📁 Recent Generated Dumps on Server</div>
                    <span style="font-size: 12px; color: var(--text-muted);">Stored in <code>krf-orderandsales/temp/dumps/</code></span>
                </div>
                <div class="table-responsive">
                    <table class="dump-table">
                        <thead>
                            <tr>
                                <th>Dump Session</th>
                                <th>Created At</th>
                                <th>Tables</th>
                                <th>Workbench Project ZIP</th>
                                <th>phpMyAdmin Complete File</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existingDumps as $ed): ?>
                                <tr id="row-<?= htmlspecialchars($ed['folder']) ?>">
                                    <td><span class="code-pill"><?= htmlspecialchars($ed['folder']) ?></span></td>
                                    <td><?= htmlspecialchars($ed['time']) ?></td>
                                    <td><?= intval($ed['sql_count']) ?> files</td>
                                    <td>
                                        <?php if ($ed['has_zip']): ?>
                                            <a class="btn btn-outline btn-sm" href="?action=download_file&file=<?= urlencode($ed['zip_name']) ?>">
                                                📦 Workbench ZIP
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ed['has_sc']): ?>
                                            <a class="btn btn-outline btn-sm" href="?action=download_file&file=<?= urlencode($ed['sc_name']) ?>">
                                                📄 Complete .SQL
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="btn btn-danger btn-sm" onclick="deleteDumpFolder('<?= htmlspecialchars($ed['folder']) ?>')">🗑️ Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JS Logic -->
    <script>
        let currentQueue = [];
        let completedCount = 0;
        let totalRows = 0;
        let totalBytes = 0;
        let isPaused = false;
        let isCancelled = false;
        let activeFolder = '';
        let targetDatabase = '';
        let timerInterval = null;
        let startTime = null;

        function selectDb(db) {
            document.getElementById('dbNameInput').value = db;
            document.querySelectorAll('.pill-btn').forEach(btn => {
                btn.classList.toggle('active', btn.textContent.trim() === db);
            });
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatElapsed(ms) {
            const totalSec = Math.floor(ms / 1000);
            const min = String(Math.floor(totalSec / 60)).padStart(2, '0');
            const sec = String(totalSec % 60).padStart(2, '0');
            return `${min}:${sec}`;
        }

        function startTimer() {
            startTime = Date.now();
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                if (!isPaused && startTime) {
                    const elapsed = Date.now() - startTime;
                    document.getElementById('statElapsed').innerText = formatElapsed(elapsed);
                }
            }, 500);
        }

        function stopTimer() {
            clearInterval(timerInterval);
        }

        async function handleFormSubmit(e) {
            if (e) e.preventDefault();
            const dbName = document.getElementById('dbNameInput').value.trim();
            if (!dbName) {
                alert('Please enter or select a database name.');
                return;
            }

            targetDatabase = dbName;
            document.getElementById('startBtn').disabled = true;
            document.getElementById('startBtn').innerText = '⏳ Initializing...';

            const progressCard = document.getElementById('progressCard');
            progressCard.style.display = 'block';
            progressCard.scrollIntoView({ behavior: 'smooth' });

            document.getElementById('completionBanner').style.display = 'none';
            document.getElementById('currentProcessTitle').innerText = `Preparing dump for database: ${targetDatabase}...`;
            document.getElementById('statDb').innerText = targetDatabase;
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('tableBody').innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 24px; color: #94a3b8;">Inspecting database schema and table definitions...</td></tr>';

            completedCount = 0;
            totalRows = 0;
            totalBytes = 0;
            isPaused = false;
            isCancelled = false;

            try {
                const initRes = await fetch(`?action=init_dump&db=${encodeURIComponent(targetDatabase)}`);
                const initData = await initRes.json();

                if (!initData.success) {
                    throw new Error(initData.message || 'Failed to initialize dump');
                }

                activeFolder = initData.folder;
                currentQueue = initData.tables;

                renderTableList(currentQueue);

                document.getElementById('statTablesCount').innerText = `0 / ${currentQueue.length}`;
                document.getElementById('startBtn').innerText = '🚀 Dumping in progress...';
                startTimer();

                processNextTable(0);

            } catch (err) {
                alert('Initialization error: ' + err.message);
                document.getElementById('startBtn').disabled = false;
                document.getElementById('startBtn').innerText = '🚀 Start Table-wise Dump';
            }
        }

        function renderTableList(tables) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            tables.forEach((t, idx) => {
                const tr = document.createElement('tr');
                tr.id = `tbl-row-${idx}`;
                tr.setAttribute('data-name', t.name.toLowerCase());
                tr.innerHTML = `
                    <td style="color: #64748b;">${idx + 1}</td>
                    <td><strong>${escapeHtml(t.name)}</strong></td>
                    <td><span class="badge ${t.is_view ? 'badge-view' : 'badge-pending'}">${t.is_view ? 'VIEW' : escapeHtml(t.engine || 'BASE TABLE')}</span></td>
                    <td>${Number(t.rows).toLocaleString()}</td>
                    <td id="rows-${idx}">-</td>
                    <td id="size-${idx}">-</td>
                    <td><span class="badge badge-pending" id="badge-${idx}">Pending</span></td>
                    <td style="text-align: right;" id="act-${idx}">-</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        async function processNextTable(index) {
            if (isCancelled) {
                document.getElementById('currentProcessTitle').innerText = 'Dump cancelled by user.';
                document.getElementById('startBtn').disabled = false;
                document.getElementById('startBtn').innerText = '🚀 Start Table-wise Dump';
                stopTimer();
                return;
            }

            if (isPaused) {
                setTimeout(() => processNextTable(index), 500);
                return;
            }

            if (index >= currentQueue.length) {
                finalizeDump();
                return;
            }

            const table = currentQueue[index];
            const badge = document.getElementById(`badge-${index}`);
            badge.className = 'badge badge-dumping';
            badge.innerText = 'Dumping...';
            document.getElementById('currentProcessTitle').innerText = `Dumping [${index + 1}/${currentQueue.length}]: \`${table.name}\`...`;

            const optSchema = document.getElementById('optSchema').checked;
            const optData = document.getElementById('optData').checked;
            const optDrop = document.getElementById('optDrop').checked;

            try {
                const url = `?action=dump_table&db=${encodeURIComponent(targetDatabase)}&table=${encodeURIComponent(table.name)}&folder=${encodeURIComponent(activeFolder)}&include_schema=${optSchema}&include_data=${optData}&drop_table=${optDrop}`;
                const res = await fetch(url);
                const data = await res.json();

                if (data.success) {
                    badge.className = 'badge badge-success';
                    badge.innerText = 'Completed';
                    document.getElementById(`rows-${index}`).innerText = Number(data.rows).toLocaleString();
                    document.getElementById(`size-${index}`).innerText = data.size_formatted;
                    document.getElementById(`act-${index}`).innerHTML = `
                        <a href="${data.download_url}" class="btn btn-outline btn-sm">⬇️ SQL</a>
                    `;

                    completedCount++;
                    totalRows += data.rows;
                    totalBytes += data.size;

                    const percent = Math.round((completedCount / currentQueue.length) * 100);
                    document.getElementById('progressBar').style.width = percent + '%';
                    document.getElementById('statTablesCount').innerText = `${completedCount} / ${currentQueue.length} (${percent}%)`;
                    document.getElementById('statRowsCount').innerText = totalRows.toLocaleString();
                    document.getElementById('statTotalSize').innerText = formatBytes(totalBytes);

                } else {
                    badge.className = 'badge badge-error';
                    badge.innerText = 'Failed';
                    document.getElementById(`act-${index}`).innerHTML = `<span style="color: #f87171; font-size: 11px;">${escapeHtml(data.message)}</span>`;
                }

            } catch (err) {
                badge.className = 'badge badge-error';
                badge.innerText = 'Network Error';
                document.getElementById(`act-${index}`).innerHTML = `<span style="color: #f87171; font-size: 11px;">${escapeHtml(err.message)}</span>`;
            }

            processNextTable(index + 1);
        }

        async function finalizeDump() {
            document.getElementById('currentProcessTitle').innerText = 'Generating routines, Workbench ZIP, and self-contained archives...';
            stopTimer();

            try {
                const res = await fetch(`?action=finalize_dump&db=${encodeURIComponent(targetDatabase)}&folder=${encodeURIComponent(activeFolder)}`);
                const data = await res.json();

                if (data.success) {
                    document.getElementById('currentProcessTitle').innerText = `✅ Dump Complete for Database \`${targetDatabase}\`!`;
                    document.getElementById('completionSummaryText').innerText =
                        `Successfully dumped ${completedCount} table(s) (${totalRows.toLocaleString()} rows, ${formatBytes(totalBytes)}) + routines and schema.`;

                    if (data.zip_download_url) {
                        const wbBtn = document.getElementById('wbZipBtn');
                        wbBtn.href = data.zip_download_url;
                        wbBtn.innerText = `⬇️ Download Workbench Project ZIP (${formatBytes(data.zip_size)})`;
                    }

                    if (data.self_contained_download_url) {
                        const scBtn = document.getElementById('scSqlBtn');
                        scBtn.href = data.self_contained_download_url;
                        scBtn.innerText = `⬇️ Complete .SQL (${formatBytes(data.self_contained_size)})`;
                    }

                    if (data.sc_zip_download_url) {
                        const sczBtn = document.getElementById('scZipBtn');
                        sczBtn.href = data.sc_zip_download_url;
                        sczBtn.innerText = `🗜️ Zipped .SQL (${formatBytes(data.sc_zip_size)})`;
                    }

                    document.getElementById('completionBanner').style.display = 'block';
                    document.getElementById('completionBanner').scrollIntoView({ behavior: 'smooth' });
                }

            } catch (err) {
                alert('Finalization error: ' + err.message);
            }

            document.getElementById('startBtn').disabled = false;
            document.getElementById('startBtn').innerText = '🚀 Start Another Dump';
        }

        function togglePause() {
            isPaused = !isPaused;
            const btn = document.getElementById('pauseResumeBtn');
            btn.innerText = isPaused ? '▶️ Resume' : '⏸️ Pause';
            btn.className = isPaused ? 'btn btn-green btn-sm' : 'btn btn-outline btn-sm';
        }

        function cancelDump() {
            if (confirm('Are you sure you want to cancel the dump operation?')) {
                isCancelled = true;
                stopTimer();
            }
        }

        function filterTables() {
            const query = document.getElementById('tableFilterInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tableBody tr');
            rows.forEach(r => {
                const name = r.getAttribute('data-name') || '';
                r.style.display = name.includes(query) ? '' : 'none';
            });
        }

        async function deleteDumpFolder(folder) {
            if (!confirm(`Are you sure you want to delete dump folder "${folder}"?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_dump');
            formData.append('folder', folder);

            try {
                const res = await fetch('dbdump.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById(`row-${folder}`);
                    if (row) row.remove();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                alert('Network error while deleting: ' + err.message);
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const dbParam = urlParams.get('db') || urlParams.get('database') || urlParams.get('dbname');
            if (dbParam && (urlParams.has('auto') || urlParams.get('run') === '1')) {
                handleFormSubmit();
            }
        });
    </script>
</body>
</html>
