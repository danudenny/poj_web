<?php

namespace App\Helpers\Notification;

use App\Models\Setting;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailNotification
{
    public static function SendMailable(string $email, Mailable $mailable) {
        /**
         * @var Setting $enabledEmails
         */
        $enabledEmails = Setting::query()->where('key', '=', 'ENABLED_MAIL')->first();

        if ($enabledEmails && !Str::contains($enabledEmails->value, $email)) {
            return null;
        }

        return Mail::to($email)->send($mailable);
    }
}
