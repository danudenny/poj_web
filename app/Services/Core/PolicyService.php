<?php

namespace App\Services\Core;

use App\Http\Requests\Polict\CreatePolicyRequest;
use App\Models\Policy;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PolicyService extends BaseService
{
    public function getLatestPolicy() {
        $policy = Policy::query()
            ->orderBy('id', 'DESC')
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $policy
        ]);
    }

    public function setPolicy(CreatePolicyRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            DB::beginTransaction();

            $policy = new Policy();
            $policy->content = $request->input('content');
            $policy->created_by = $user->email;
            $policy->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sukses'
            ]);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $throwable->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirmPolicy(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            DB::beginTransaction();

            $user->is_policy_confirmed = true;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sukses'
            ]);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $throwable->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
