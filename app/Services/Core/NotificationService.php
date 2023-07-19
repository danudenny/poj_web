<?php

namespace App\Services\Core;

use App\Models\Notification;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class NotificationService extends BaseService
{
    public function createNotification(int $employee_id, string $title, string $subtitle, string $description, string $referenceType, int|null $reference_id = null) {
        $notification = new Notification();
        $notification->employee_id = $employee_id;
        $notification->title = $title;
        $notification->sub_title = $subtitle;
        $notification->description = $description;
        $notification->reference_type = $referenceType;
        $notification->reference_id = $reference_id;
        $notification->is_read = false;
        $notification->save();
    }

    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = Notification::query()
            ->where('employee_id', '=', $user->employee_id)
            ->orderBy('created_at', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ], ResponseAlias::HTTP_OK);
    }
}
