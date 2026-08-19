<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use DateTime;
use OCP\IConfig;
use OCP\IDBConnection;
use ZipArchive;

class BackupService {
	public function __construct(
		private IDBConnection $db,
		private IConfig $config,
	) {}

	public function createBackup(): string {
		$timestamp = (new DateTime())->format('Y-m-d_H-i-s');
		$filename = "weinsteig-finance-backup_$timestamp.zip";

		$dataDir = $this->config->getSystemValue('datadirectory');
		$backupDir = "$dataDir/backup";
		if (!is_dir($backupDir)) {
			mkdir($backupDir, 0750, true);
		}

		$backupFile = "$backupDir/$filename";
		$tempDir = sys_get_temp_dir() . '/weinsteig-backup-' . bin2hex(random_bytes(8));
		mkdir($tempDir);

		try {
			// 1. SQL Dump
			$sqlDump = $this->createSqlDump();
			file_put_contents("$tempDir/database.sql", $sqlDump);

			// 2. Mandate-Dateien kopieren (OHNE backup/ Ordner!)
			$generatedPath = "$dataDir/generated";
			if (is_dir($generatedPath)) {
				$this->copyDirectory($generatedPath, "$tempDir/generated");
			}

			// 3. ZIP erstellen
			$zip = new ZipArchive();
			if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
				throw new \Exception('Cannot create ZIP file');
			}

			$this->addDirectoryToZip($tempDir, $zip);
			$zip->close();

			// Cleanup temp
			$this->removeDirectory($tempDir);

			return $backupFile;
		} catch (\Exception $e) {
			$this->removeDirectory($tempDir);
			throw $e;
		}
	}

	private function createSqlDump(): string {
		$tables = [
			'weinsteig_members',
			'weinsteig_user_members',
			'weinsteig_vorschreibungen',
			'weinsteig_zahlungen',
			'weinsteig_zahlung_vorschreibung',
			'weinsteig_config',
			'weinsteig_mandate_approvals',
		];

		$sql = "-- Weinsteig Finance Database Backup\n";
		$sql .= "-- Created: " . (new DateTime())->format('Y-m-d H:i:s') . "\n\n";

		foreach ($tables as $table) {
			$sql .= "DROP TABLE IF EXISTS `oc_$table`;\n\n";

			$qb = $this->db->getQueryBuilder();
			$result = $this->db->executeQuery("SHOW CREATE TABLE `oc_$table`");
			$row = $result->fetch();
			if ($row) {
				$sql .= $row['Create Table'] . ";\n\n";
			}

			$qb = $this->db->getQueryBuilder();
			$rows = $qb->select('*')
				->from($table)
				->executeQuery()
				->fetchAll();

			if ($rows) {
				foreach ($rows as $dataRow) {
					$values = array_map(fn($v) => $v === null ? 'NULL' : "'" . str_replace("'", "''", (string)$v) . "'", $dataRow);
					$sql .= "INSERT INTO `oc_$table` VALUES (" . implode(', ', $values) . ");\n";
				}
				$sql .= "\n";
			}
		}

		return $sql;
	}

	private function copyDirectory(string $source, string $dest): void {
		if (!is_dir($dest)) {
			mkdir($dest, 0750, true);
		}

		$files = scandir($source);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$srcPath = "$source/$file";
			$destPath = "$dest/$file";

			if (is_dir($srcPath)) {
				$this->copyDirectory($srcPath, $destPath);
			} else {
				copy($srcPath, $destPath);
			}
		}
	}

	private function addDirectoryToZip(string $dir, ZipArchive $zip, string $zipPath = ''): void {
		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = "$dir/$file";
			$archivePath = $zipPath ? "$zipPath/$file" : $file;

			if (is_dir($filePath)) {
				$this->addDirectoryToZip($filePath, $zip, $archivePath);
			} else {
				$zip->addFile($filePath, $archivePath);
			}
		}
	}

	private function removeDirectory(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}

		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = "$dir/$file";
			if (is_dir($filePath)) {
				$this->removeDirectory($filePath);
			} else {
				unlink($filePath);
			}
		}
		rmdir($dir);
	}

	public function getBackupInfo(): array {
		$dataDir = $this->config->getSystemValue('datadirectory');
		$backupDir = "$dataDir/backup";

		$lastBackupTime = (int)$this->config->getAppValue('weinsteigfinance', 'last_backup_run', '0');
		$lastBackupDate = $lastBackupTime ? (new DateTime())->setTimestamp($lastBackupTime)->format('d.m.Y H:i') : 'Nie';

		// Nächstes Backup: täglich um 2 AM
		$nextBackupTime = $this->getNextBackupTime();
		$nextBackupDate = (new DateTime())->setTimestamp($nextBackupTime)->format('d.m.Y H:i');

		$remaining = $nextBackupTime - time();
		$hours = floor($remaining / 3600);
		$minutes = floor(($remaining % 3600) / 60);
		$remainingTime = sprintf('%d:%02d Stunden', $hours, $minutes);

		// Liste der Backups
		$backups = [];
		if (is_dir($backupDir)) {
			$files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
			foreach ($files as $file) {
				if (preg_match('/^weinsteig-finance-backup_(.+)\.zip$/', $file, $m)) {
					$backups[] = [
						'filename' => $file,
						'date' => $m[1],
						'size' => filesize("$backupDir/$file"),
						'url' => '/index.php/apps/weinsteigfinance/api/backup/download/' . urlencode($file),
					];
				}
			}
		}

		return [
			'lastBackupDate' => $lastBackupDate,
			'nextBackupDate' => $nextBackupDate,
			'remainingTime' => $remainingTime,
			'backups' => array_slice($backups, 0, 10), // Letzte 10 Backups
		];
	}

	private function getNextBackupTime(): int {
		$now = time();
		$today2am = strtotime('today 02:00', $now);

		if ($now < $today2am) {
			return $today2am;
		}

		return strtotime('+1 day', $today2am);
	}
}
