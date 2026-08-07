<?php

namespace App\Http\Controllers\Api;

use App\Services\CelebrationService;
use Illuminate\Http\Request;

class CelebrationController extends BaseController
{
    public function __construct(
        protected CelebrationService $celebrationService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return $this->sendError('Unauthorized', [], 401);
        }

        $user->loadMissing('employee');

        $days = (int) $request->query('days', 7);
        $data = $this->celebrationService->forUser($user, $days);

        return $this->sendResponse($data, 'Celebrations fetched successfully.');
    }
}
