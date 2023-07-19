<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Core\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $service) {
        return $service->index($request);
    }
}
