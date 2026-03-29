<?php

declare(strict_types=1);

/**
 * Zentrale Eintrittsstelle der Web-Anwendung.
 *
 * Alle Requests laufen durch diese Datei. Hier wird die Anwendung gebootstrapped,
 * das Routing geladen und die passende Controller-Aktion ausgeführt.
 */

require_once dirname(__DIR__) . '/app/Bootstrap.php';
