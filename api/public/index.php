<?php

/**
 * Classic webroot front controller for TaskHost API.
 *
 * This entry point exists as an optional compatibility layer for deployments
 * that can use a dedicated webroot such as taskhost.sasd.de. The current
 * shared-hosting target for TaskHost uses the project-root index.php instead,
 * but keeping this file allows a future switch back to a conventional PHP
 * deployment model without changing the application bootstrap itself.
 *
 * Behavioural summary:
 * - project root remains one level above this file
 * - registered routes still live below /v1 and /api/v1
 * - base-path detection naturally resolves to an empty service prefix when
 *   this public/ directory is configured as the document root
 *
 * @package TaskHost
 */

declare(strict_types=1);

use TaskHost\Bootstrap;
use TaskHost\Http\Request;

require_once dirname(__DIR__) . '/src/Bootstrap.php';

// Boot the same application as the root-level front controller. The only
// difference is the deployment shape: here the web server points directly to
// public/, so the externally visible base path usually becomes ''.
$application = Bootstrap::createApplication(dirname(__DIR__));
$request = Request::fromGlobals(Bootstrap::detectBasePath());
$response = $application->handle($request);
$response->send($application->corsHeaders());
