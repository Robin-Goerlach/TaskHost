<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\FolderService;

final class FolderController
{
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->folderService->allForUser((int) $user['id']),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->folderService->create((int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->folderService->update($id, (int) $user['id'], (array) $request->body),
        ]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->folderService->delete($id, (int) $user['id']);

        return Response::noContent();
    }
}
