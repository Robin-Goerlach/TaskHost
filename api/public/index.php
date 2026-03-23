<?php

declare(strict_types=1);

use TaskHost\Bootstrap;
use TaskHost\Http\Request;

require_once dirname(__DIR__) . '/src/Bootstrap.php';

$app = Bootstrap::createApplication(dirname(__DIR__));
$request = Request::fromGlobals();
$response = $app->handle($request);
$response->send($app->corsHeaders());
