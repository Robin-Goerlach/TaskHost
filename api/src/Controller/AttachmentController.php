<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\AttachmentService;

final class AttachmentController
{
    public function __construct(private readonly AttachmentService $attachmentService)
    {
    }

    public function indexForTask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->attachmentService->listForTask($id, (int) $user['id']),
        ]);
    }

    public function store(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $file = $request->files['file'] ?? null;

        return Response::json([
            'data' => $this->attachmentService->upload($id, (int) $user['id'], is_array($file) ? $file : []),
        ], 201);
    }

    public function download(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $download = $this->attachmentService->download($id, (int) $user['id']);

        return Response::binary(
            (string) $download['content'],
            (string) $download['attachment']['mime_type'],
            (string) $download['attachment']['original_name']
        );
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->attachmentService->delete($id, (int) $user['id']);

        return Response::noContent();
    }
}
