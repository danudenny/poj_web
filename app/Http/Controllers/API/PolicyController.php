<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Polict\CreatePolicyRequest;
use App\Services\Core\PolicyService;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    private PolicyService $service;

    public function __construct(PolicyService $service)
    {
        $this->service = $service;
    }

    public function get() {
        return $this->service->getLatestPolicy();
    }

    public function set(CreatePolicyRequest $request) {
        return $this->service->setPolicy($request);
    }

    public function confirmPolicy(Request $request) {
        return $this->service->confirmPolicy($request);
    }
}
