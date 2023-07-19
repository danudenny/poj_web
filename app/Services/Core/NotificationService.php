<?php

namespace App\Services\Core;

use App\Models\EmployeeNotification;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class NotificationService extends BaseService
{
    /**
     * @var bool
     */
    private bool $isSendPushNotification = false;

    /**
     * @var bool
     */
    private bool $isSilent = false;

    /**
     * @var EmployeeNotification|null
     */
    private EmployeeNotification|null $currentEmployeeNotification = null;

    /**
     * @var User|null
     */
    private User|null $activeUser = null;

    /**
     * @param int $employee_id
     * @param string $title
     * @param string $subtitle
     * @param string $description
     * @param string $referenceType
     * @param int|null $reference_id
     * @return $this
     */
    public function createNotification(int $employee_id, string $title, string $subtitle, string $description = "", string $referenceType = "", int|null $reference_id = null): NotificationService {
        /**
         * @var User $user
         */
        $user = User::query()->where('employee_id', '=', $employee_id)->first();
        $this->activeUser = $user;

        $notification = new EmployeeNotification();
        $notification->employee_id = $employee_id;
        $notification->title = $title;
        $notification->sub_title = $subtitle;
        $notification->description = $description;
        $notification->reference_type = $referenceType;
        $notification->reference_id = $reference_id;
        $notification->is_read = false;

        $this->currentEmployeeNotification = $notification;

        return $this;
    }

    /**
     * @return $this
     */
    public function withSendPushNotification(): NotificationService {
        $this->isSendPushNotification = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function silent(): NotificationService {
        $this->isSilent = true;
        return $this;
    }

    public function send() {
        if (is_null($this->currentEmployeeNotification)) {
            return;
        }

        if (!$this->isSilent) {
            $this->currentEmployeeNotification->save();
        }
        if ($this->isSendPushNotification) {
            $this->sendPushNotification($this->currentEmployeeNotification->title, $this->currentEmployeeNotification->sub_title);
        }
    }

    /**
     * @param string $title
     * @param string $body
     * @return void
     */
    private function sendPushNotification(string $title, string $body) {
        if (is_null($this->activeUser)) {
            return;
        }

        fcm()
            ->to([$this->activeUser->fcm_token])
            ->priority('high')
            ->timeToLive(0)
            ->notification([
                'title' => $title,
                'body' => $body
            ])
            ->enableResponseLog()
            ->send();
    }

    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = EmployeeNotification::query()
            ->where('employee_id', '=', $user->employee_id)
            ->orderBy('created_at', 'DESC');

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $this->list($query, $request)
        ], ResponseAlias::HTTP_OK);
    }
}
