<?php

/**
 * TaskHost API front controller.
 *
 * This file intentionally lives in the API root. The classic public/ webroot is
 * not used for the shared-hosting deployment model targeted by TaskHost. The
 * service is expected to run below a path such as /taskhost and uses a local
 * .htaccess file to route requests into this front controller.
 *
 * @package TaskHost
 */

declare(strict_types=1);

use TaskHost\Bootstrap;
use TaskHost\Http\Request;

require_once __DIR__ . '/src/Bootstrap.php';

$application = Bootstrap::createApplication(__DIR__);

// The request object strips the externally visible service path, for example
// /taskhost, before the router sees the URI. This makes folder renames and
// path-based deployments predictable.
$request = Request::fromGlobals(Bootstrap::detectBasePath());
$response = $application->handle($request);
$response->send($application->corsHeaders());
