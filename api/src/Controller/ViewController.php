<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\ViewService;

final class ViewController
{
    public function __construct(private readonly ViewService $viewService)
    {
    }

    public function show(Request $request, string $view): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->viewService->get($view, (int) $user['id']),
        ]);
    }
}
