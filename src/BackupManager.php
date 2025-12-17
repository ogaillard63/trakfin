<?php

namespace App;

use ZipArchive;

class BackupManager {
    private $backupDir;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $mysqlPath;
    private $mysqldumpPath;
    private $lastError = '';

    public function getLastError() {
        return $this->lastError;
    }

    public function __construct() {
        $this->backupDir = __DIR__ . '/../database/backups';
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }

        // Recuperation des paramètres depuis la config
        $this->dbHost = defined('DB_HOST') ? DB_HOST : null;
        $this->dbName = defined('DB_NAME') ? DB_NAME : null;
        $this->dbUser = defined('DB_USER') ? DB_USER : null;
        $this->dbPass = defined('DB_PASS') ? DB_PASS : null;
        
        // Détection automatique des exécutables
        $this->mysqlPath = 'mysql';
        $this->mysqldumpPath = 'mysqldump';

        // Si on est sous Windows/MAMP
        $mampMysql = 'C:\\Mamp\\bin\\mysql\\bin\\mysql.exe';
        $mampDump = 'C:\\Mamp\\bin\\mysql\\bin\\mysqldump.exe';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (isset($_ENV['MYSQL_PATH'])) {
                $this->mysqlPath = $_ENV['MYSQL_PATH'];
            } elseif (file_exists($mampMysql)) {
                $this->mysqlPath = $mampMysql;
            }
            
            if (isset($_ENV['MYSQLDUMP_PATH'])) {
                $this->mysqldumpPath = $_ENV['MYSQLDUMP_PATH'];
            } elseif (file_exists($mampDump)) {
                $this->mysqldumpPath = $mampDump;
            }
        }
        // Sur Linux/Hosting, on laisse par défaut 'mysql' et 'mysqldump'
        // sauf si spécifié dans .env
        else {
             if (isset($_ENV['MYSQL_PATH'])) $this->mysqlPath = $_ENV['MYSQL_PATH'];
             if (isset($_ENV['MYSQLDUMP_PATH'])) $this->mysqldumpPath = $_ENV['MYSQLDUMP_PATH'];
        }
    }

    public function createBackup() {
        if (!$this->dbName || !$this->dbUser) {
            $this->lastError = "Configuration DB manquante (DB_NAME ou DB_USER vide)";
            error_log("Backup Error: " . $this->lastError);
            return false;
        }

        $date = date('Y-m-d_H-i-s');
        $filename = "backup_{$this->dbName}_{$date}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        $success = false;
        
        // 1. Essayer avec mysqldump si exec() est disponible
        if (function_exists('exec')) {
            // Construction de la commande avec redirection d'erreur
            $passwordCmd = $this->dbPass ? "--password=\"{$this->dbPass}\"" : "";
            $command = "\"{$this->mysqldumpPath}\" --host={$this->dbHost} --user={$this->dbUser} {$passwordCmd} --single-transaction --quick --lock-tables=false {$this->dbName} > \"{$filepath}\" 2>&1";
            
            $output = [];
            $returnVar = null;
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                $success = true;
            } else {
                $cmdOutput = implode("\n", $output);
                $this->lastError = "Erreur mysqldump (Code $returnVar): " . ($cmdOutput ?: 'Aucune sortie');
                error_log("Backup Error: " . $this->lastError);
                // On continue pour essayer la méthode native
            }
        } else {
            $this->lastError = "exec() indisponible";
        }

        // 2. Fallback: Méthode PHP native si mysqldump a échoué
        if (!$success) {
            error_log("Backup: Tentative avec la méthode native PHP...");
            if ($this->createBackupNative($filepath)) {
                $success = true;
                $this->lastError = ''; // Clear error if native worked
            }
        }
        
        if ($success && file_exists($filepath)) {
            // Zip the file
            $zipFilename = str_replace('.sql', '.zip', $filename);
            $zipFilepath = $this->backupDir . '/' . $zipFilename;
            
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($zipFilepath, ZipArchive::CREATE) === TRUE) {
                    $zip->addFile($filepath, $filename);
                    $zip->close();
                    unlink($filepath); // Delete .sql after zipping
                    return $zipFilename;
                }
            }
            return $filename; // Return .sql if zip failed or missing
        }
        
        return false;
    }

    private function createBackupNative($filepath) {
        try {
            $dsn = "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $pdo = new \PDO($dsn, $this->dbUser, $this->dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);

            $handle = fopen($filepath, 'w');
            if (!$handle) {
                $this->lastError = "Impossible d'ouvrir le fichier de destination";
                return false;
            }

            fwrite($handle, "-- Backup Native PHP generated at " . date('Y-m-d H:i:s') . "\n\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Ignore views if any (optional check) but for now dump all
                
                // Drop table
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");

                // Create table
                $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                $createSql = $createRow['Create Table'] ?? $createRow['Create View']; 
                fwrite($handle, $createSql . ";\n\n");

                // Insert data
                $rows = $pdo->query("SELECT * FROM `$table`");
                while ($row = $rows->fetch()) {
                    $keys = array_keys($row);
                    $values = array_map(function($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        return $pdo->quote($value);
                    }, array_values($row));
                    
                    fwrite($handle, "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $values) . ");\n");
                }
                fwrite($handle, "\n");
            }

            fclose($handle);
            return true;
        } catch (\Exception $e) {
            $this->lastError = "Erreur Native Backup: " . $e->getMessage();
            error_log($this->lastError);
            if (isset($handle) && is_resource($handle)) fclose($handle);
            if (file_exists($filepath)) unlink($filepath);
            return false;
        }
    }

    public function restoreBackup($filename) {
        $filepath = $this->backupDir . '/' . $filename;
        if (!file_exists($filepath)) {
            $this->lastError = "Fichier de sauvegarde introuvable";
            return false;
        }

        $isZip = pathinfo($filename, PATHINFO_EXTENSION) === 'zip';
        $sqlFile = $filepath;
        $tempSqlFile = null;

        // Préparation du fichier SQL (extraction si ZIP)
        if ($isZip) {
            if (!class_exists('ZipArchive')) {
                $this->lastError = "ZipArchive non disponible";
                return false;
            }
            
            $zip = new ZipArchive();
            if ($zip->open($filepath) === TRUE) {
                $zip->extractTo($this->backupDir);
                $originalName = $zip->getNameIndex(0);
                $tempSqlFile = $this->backupDir . '/' . $originalName;
                $sqlFile = $tempSqlFile;
                $zip->close();
            } else {
                $this->lastError = "Impossible d'extraire le fichier ZIP";
                return false;
            }
        }
        
        $success = false;

        // 1. Tentative via ligne de commande si exec() est dispo
        if (function_exists('exec')) {
            $passwordCmd = $this->dbPass ? "--password=\"{$this->dbPass}\"" : "";
            $command = "\"{$this->mysqlPath}\" --host={$this->dbHost} --user={$this->dbUser} {$passwordCmd} {$this->dbName} < \"{$sqlFile}\" 2>&1";
            
            $output = [];
            $returnVar = null;
            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $success = true;
            } else {
                $this->lastError = "Erreur commande mysql: " . implode("\n", $output);
            }
        } else {
            $this->lastError = "exec() indisponible";
        }

        // 2. Fallback: Méthode Native PHP
        if (!$success) {
            if ($this->restoreBackupNative($sqlFile)) {
                $success = true;
                $this->lastError = '';
            }
        }

        // Nettoyage
        if ($tempSqlFile && file_exists($tempSqlFile)) {
            unlink($tempSqlFile);
        }

        return $success;
    }

    private function restoreBackupNative($sqlFile) {
        try {
            $dsn = "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
            $pdo = new \PDO($dsn, $this->dbUser, $this->dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // Désactiver les contraintes pour l'import
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $handle = fopen($sqlFile, "r");
            if ($handle) {
                $query = '';
                while (($line = fgets($handle)) !== false) {
                    $trimLine = trim($line);
                    
                    // Ignorer les commentaires et lignes vides
                    if ($trimLine === '' || strpos($trimLine, '--') === 0 || strpos($trimLine, '/*') === 0) {
                        continue;
                    }
                    
                    $query .= $line;
                    
                    // Si la ligne finit par un point-virgule, on exécute
                    if (substr(rtrim($trimLine), -1) === ';') {
                        try {
                            $pdo->exec($query);
                        } catch (\Exception $e) {
                            throw new \Exception("Erreur SQL sur la requête: " . substr($query, 0, 100) . "... " . $e->getMessage());
                        }
                        $query = '';
                    }
                }
                fclose($handle);
            } else {
                throw new \Exception("Impossible d'ouvrir le fichier SQL");
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "Erreur Restore Native: " . $e->getMessage();
            error_log($this->lastError);
            if (isset($handle) && is_resource($handle)) fclose($handle);
            return false;
        }
    }

    public function getList($limit = 5) {
        $files = glob($this->backupDir . '/*.{sql,zip}', GLOB_BRACE);
        if (!$files) return [];
        
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $files = array_slice($files, 0, $limit);
        
        return array_map(function($file) {
            return [
                'name' => basename($file),
                'size' => $this->formatSize(filesize($file)),
                'date' => date('d/m/Y H:i', filemtime($file)),
                'timestamp' => filemtime($file)
            ];
        }, $files);
    }
    
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
