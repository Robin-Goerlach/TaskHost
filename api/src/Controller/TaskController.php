<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\TaskService;

final class TaskController
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function indexForList(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $includeCompleted = in_array((string) ($request->query['include_completed'] ?? '0'), ['1', 'true', 'yes'], true);

        return Response::json([
            'data' => $this->taskService->listTasks($id, (int) $user['id'], $includeCompleted),
        ]);
    }

    public function store(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->create($id, (int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->show($id, (int) $user['id']),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->update($id, (int) $user['id'], (array) $request->body),
        ]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->taskService->delete($id, (int) $user['id']);

        return Response::noContent();
    }

    public function complete(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->complete($id, (int) $user['id']),
        ]);
    }

    public function restore(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->restore($id, (int) $user['id']),
        ]);
    }

    public function subtasks(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->subtasks($id, (int) $user['id']),
        ]);
    }

    public function storeSubtask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->createSubtask($id, (int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function updateSubtask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->updateSubtask($id, (int) $user['id'], (array) $request->body),
        ]);
    }

    public function destroySubtask(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->taskService->deleteSubtask($id, (int) $user['id']);

        return Response::noContent();
    }

    public function search(Request $request): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskService->search((int) $user['id'], (string) ($request->query['q'] ?? '')),
        ]);
    }
}
