<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\ReminderService;

final class ReminderController
{
    public function __construct(private readonly ReminderService $reminderService)
    {
    }

    public function indexForTask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->reminderService->listForTask($id, (int) $user['id']),
        ]);
    }

    public function store(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->reminderService->create($id, (int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->reminderService->update($id, (int) $user['id'], (array) $request->body),
        ]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->reminderService->delete($id, (int) $user['id']);

        return Response::noContent();
    }
}
