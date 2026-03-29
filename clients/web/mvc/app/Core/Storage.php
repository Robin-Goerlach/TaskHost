<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Kleine JSON-Dateiablage als persistenter Speicher.
 *
 * Warum kein echtes DBMS?
 * In manchen PHP-Umgebungen fehlen SQLite- oder MySQL-Treiber.
 * Dieser Speicher sorgt dafür, dass die Anwendung trotzdem sofort läuft.
 *
 * Für kleine Einzelplatz- oder Demo-Anwendungen reicht dieses Verfahren gut aus.
 * Für größere Anwendungen solltest du später auf eine richtige Datenbank wechseln.
 */
class Storage
{
    public function __construct(private string $filePath)
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_file($filePath)) {
            file_put_contents($filePath, json_encode($this->emptyStructure(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Führt eine Änderung oder Leseoperation mit Dateisperre aus.
     *
     * Die Callback-Funktion erhält das komplette Datenmodell als Array per Referenz.
     * So bleiben Lese- und Schreiboperationen an zentraler Stelle konsistent.
     */
    public function transaction(callable $callback): mixed
    {
        $handle = fopen($this->filePath, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Die Datendatei konnte nicht geöffnet werden.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Die Datendatei konnte nicht gesperrt werden.');
            }

            $contents = stream_get_contents($handle);
            $data = $contents !== false && trim($contents) !== ''
                ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR)
                : $this->emptyStructure();

            if (!is_array($data)) {
                $data = $this->emptyStructure();
            }

            $result = $callback($data);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);

            return $result;
        } catch (\Throwable $exception) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw $exception;
        }
    }

    private function emptyStructure(): array
    {
        return [
            'users' => [],
            'task_lists' => [],
            'tasks' => [],
            'meta' => [
                'user_auto_id' => 1,
                'task_list_auto_id' => 1,
                'task_auto_id' => 1,
            ],
        ];
    }
}
