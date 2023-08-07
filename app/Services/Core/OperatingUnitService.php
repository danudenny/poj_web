<?php

namespace App\Services\Core;

use App\Http\Requests\OperatingUnit\AssignOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\AssignUserRequest;
use App\Http\Requests\OperatingUnit\RemoveOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\RemoveUserRequest;
use App\Models\KantorPerwakilan;
use App\Models\OperatingUnitCorporate;
use App\Models\OperatingUnitKanwil;
use App\Models\OperatingUnitUser;
use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OperatingUnitService extends BaseService
{
    public function index(Request $request) {
        try {
            $query = OperatingUnitCorporate::query();

            if ($representOfficeID = $request->input('representative_office_id')) {
                $query->where('kantor_perwakilan_id', '=', $representOfficeID);
            }

            return response()->json([
                'status' => false,
                'message' => "Success",
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function kanwils(Request $request) {
        try {
            $query = OperatingUnitKanwil::query()->with(['operatingUnitCorporate']);
            $query->select(['operating_unit_kanwils.*']);

            if ($representOfficeID = $request->input('representative_office_id')) {
                $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_kanwils.operating_unit_corporate_id');
                $query->where('operating_unit_corporates.kantor_perwakilan_id', '=', $representOfficeID);
            }

            if ($name = $request->input('name')) {
                $query->join('units', 'units.relation_id', '=', 'operating_unit_kanwils.kanwil_relation_id');
                $query->whereRaw('units.name ILIKE ?', ["%" . $name . "%"]);
            }

            if ($userID = $request->input('user_id')) {
                $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_kanwils.operating_unit_corporate_id');
                $query->join('operating_unit_users', 'operating_unit_users.operating_unit_corporate_id', '=', 'operating_unit_corporates.id');
                $query->where('operating_unit_users.user_id', '=', $userID);
            }

            return response()->json([
                'status' => false,
                'message' => "Success",
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function corporates(Request $request) {
        try {
            $query = OperatingUnitCorporate::query();

            if ($representativeUnitID = $request->input('representative_unit_id')) {
                $query->where('kantor_perwakilan_id', '=', $representativeUnitID);
            }

            return response()->json([
                'status' => false,
                'message' => "Success",
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function availableKanwil(Request $request) {
        try {
            $query = Unit::query()->with(['operatingUnitKanwil'])
                ->where('units.unit_level', '=', 4);

            $query->leftJoin('operating_unit_kanwils', 'operating_unit_kanwils.kanwil_relation_id', '=', 'units.relation_id');
            $query->whereNull('operating_unit_kanwils.id');

            if ($parentRelationID = $request->input('parent_relation_id')) {
                $query->where('units.parent_unit_id', '=', $parentRelationID);
            }

            return response()->json([
                'status' => false,
                'message' => "Success",
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assign(AssignOperatingUnitRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->hasPermissionName(Permission::AssignOperatingUnit)) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have access!",
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            /**
             * @var KantorPerwakilan $representOffice
             */
            $representOffice = KantorPerwakilan::query()
                ->where('id', '=', $request->input('representative_office_id'))
                ->first();
            if (!$representOffice) {
                return response()->json([
                    'status' => false,
                    'message' => "Kantor perwakilan not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $corporates = $request->input('corporates');

            foreach ($corporates as $corporate) {
                $operatingUnitCorporate = $representOffice
                    ->operatingUnitCorporates()
                    ->where('corporate_relation_id', '=', $corporate['unit_relation_id'])
                    ->first();
                if (!$operatingUnitCorporate) {
                    $operatingUnitCorporate = new OperatingUnitCorporate();
                    $operatingUnitCorporate->kantor_perwakilan_id = $representOffice->id;
                    $operatingUnitCorporate->corporate_relation_id = $corporate['unit_relation_id'];
                    $operatingUnitCorporate->save();
                }

                foreach ($corporate['kanwils'] as $item) {
                    $operatingUnitKanwil = OperatingUnitKanwil::query()
                        ->where('operating_unit_corporate_id', '=', $operatingUnitCorporate->id)
                        ->where('kanwil_relation_id', '=', $item)
                        ->first();
                    if (!$operatingUnitKanwil) {
                        $operatingUnitKanwil = new OperatingUnitKanwil();
                        $operatingUnitKanwil->operating_unit_corporate_id = $operatingUnitCorporate->id;
                        $operatingUnitKanwil->kanwil_relation_id = $item;
                        $operatingUnitKanwil->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeOperatingUnit(RemoveOperatingUnitRequest $request, int $id) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->hasPermissionName(Permission::DeleteOperatingUnit)) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have access!",
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            /**
             * @var OperatingUnitKanwil $operatingUnitKanwil
             */
            $operatingUnitKanwil = OperatingUnitKanwil::query()
                ->where('id', '=', $id)
                ->first();

            if (!$operatingUnitKanwil) {
                return response()->json([
                    'status' => false,
                    'message' => "Operating Unit Not Found!",
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();
            $operatingUnitCorporateIDs = [];

            $operatingUnitCorporateIDs[] = $operatingUnitKanwil->operating_unit_corporate_id;
            $operatingUnitKanwil->delete();

            foreach ($operatingUnitCorporateIDs as $operatingUnitCorporateID) {
                /**
                 * @var OperatingUnitCorporate $operatingUnitCorporate
                 */
                $operatingUnitCorporate = OperatingUnitCorporate::query()
                    ->where('id', '=', $operatingUnitCorporateID)
                    ->first();

                if ($operatingUnitCorporate->operatingUnitKanwils()->count() <= 0) {
                    $operatingUnitCorporate->delete();
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignUser(AssignUserRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->hasPermissionName(Permission::AssignOperatingUnit)) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have access!",
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            DB::beginTransaction();

            $operatingUnitUser = OperatingUnitUser::query()
                ->where('user_id', '=', $request->input('user_id'))
                ->where('operating_unit_corporate_id', '=', $request->input('operating_unit_corporate_id'))
                ->first();

            if (!$operatingUnitUser) {
                $operatingUnitUser = new OperatingUnitUser();
                $operatingUnitUser->user_id = $request->input('user_id');
                $operatingUnitUser->operating_unit_corporate_id = $request->input('operating_unit_corporate_id');
                $operatingUnitUser->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeUser(RemoveUserRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            if (!$user->hasPermissionName(Permission::DeleteOperatingUnit)) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have access!",
                ], ResponseAlias::HTTP_FORBIDDEN);
            }

            $operatingUnitUser = OperatingUnitUser::query()
                ->where('user_id', '=', $request->input('user_id'))
                ->where('operating_unit_corporate_id', '=', $request->input('operating_unit_corporate_id'))
                ->first();

            if (!$operatingUnitUser) {
                return response()->json([
                    'status' => false,
                    'message' => "User in operating unit not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $operatingUnitUser->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
