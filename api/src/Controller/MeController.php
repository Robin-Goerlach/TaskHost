<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;

final class MeController
{
    public function show(Request $request): Response
    {
        return Response::json([
            'user' => $request->attribute('user'),
        ]);
    }
}
