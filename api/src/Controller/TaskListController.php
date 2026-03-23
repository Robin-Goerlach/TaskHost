<?php

declare(strict_types=1);

namespace TaskHost\Controller;

use TaskHost\Http\Request;
use TaskHost\Http\Response;
use TaskHost\Service\TaskListService;

final class TaskListController
{
    public function __construct(private readonly TaskListService $taskListService)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->allForUser((int) $user['id']),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->create((int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->show($id, (int) $user['id']),
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->update($id, (int) $user['id'], (array) $request->body),
        ]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $this->taskListService->delete($id, (int) $user['id']);

        return Response::noContent();
    }

    public function members(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->members($id, (int) $user['id']),
        ]);
    }

    public function share(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->share($id, (int) $user['id'], (array) $request->body),
        ], 201);
    }

    public function invitations(Request $request, int $id): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->pendingInvitations($id, (int) $user['id']),
        ]);
    }


    public function resendInvitation(Request $request, int $id, int $invitationId): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->resendInvitation($id, $invitationId, (int) $user['id']),
        ], 202);
    }

    public function acceptInvitation(Request $request, string $token): Response
    {
        $user = $request->attribute('user');

        return Response::json([
            'data' => $this->taskListService->acceptInvitation($token, (int) $user['id']),
        ]);
    }

    public function removeMember(Request $request, int $id, int $userId): Response
    {
        $user = $request->attribute('user');
        $this->taskListService->removeMember($id, $userId, (int) $user['id']);

        return Response::noContent();
    }
}
