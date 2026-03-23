<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\NoteService;

final class NoteController
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->noteService->show($id, (int) $user['id']),
        ]);
    }

    public function upsert(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->noteService->upsert($id, (int) $user['id'], (string) $request->input('content', '')),
        ]);
    }
}
