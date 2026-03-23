<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\CommentService;

final class CommentController
{
    public function __construct(private readonly CommentService $commentService)
    {
    }

    public function indexForTask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->commentService->listForTask($id, (int) $user['id']),
        ]);
    }

    public function store(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->commentService->create($id, (int) $user['id'], (string) $request->input('content', '')),
        ], 201);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->commentService->delete($id, (int) $user['id']);

        return Response::noContent();
    }
}
