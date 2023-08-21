<?php

namespace App\Services\Core;

use App\Http\Requests\OperatingUnit\AssignCentralOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\AssignOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\AssignUserRequest;
use App\Http\Requests\OperatingUnit\RemoveOperatingUnitRequest;
use App\Http\Requests\OperatingUnit\RemoveUserRequest;
use App\Models\CentralOperatingUnitUser;
use App\Models\KantorPerwakilan;
use App\Models\OperatingUnitCorporate;
use App\Models\OperatingUnitDetail;
use App\Models\OperatingUnitUser;
use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OperatingUnitService extends BaseService
{
    public function index(Request $request) {
        try {
            $query = OperatingUnitCorporate::query();

            if ($representOfficeID = $request->input('operating_unit_relation_id')) {
                $query->where('operating_unit_relation_id', '=', $representOfficeID);
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
            $query = OperatingUnitDetail::query()->with(['operatingUnitCorporate']);
            $query->select(['operating_unit_details.*']);

            if ($representOfficeID = $request->input('representative_office_id')) {
                $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_details.operating_unit_corporate_id');
                $query->join('units', 'units.relation_id', '=', 'operating_unit_corporates.operating_unit_relation_id');
                $query->where('units.id', '=', $representOfficeID);
            }

            if ($operatingUnitRelationID = $request->input('operating_unit_relation_id')) {
                if (!($request->input('representative_office_id'))) {
                    $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_details.operating_unit_corporate_id');
                }
                $query->where('operating_unit_corporates.operating_unit_relation_id', '=', $operatingUnitRelationID);
            }

            if ($corporateRelationID = $request->input('corporate_relation_id')) {
                if (!($request->input('representative_office_id')) && !($request->input('operating_unit_relation_id'))) {
                    $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_details.operating_unit_corporate_id');
                }
                $query->where('operating_unit_corporates.corporate_relation_id', '=', $corporateRelationID);
            }

            if ($name = $request->input('name')) {
                $query->join('units', 'units.relation_id', '=', 'operating_unit_details.unit_relation_id');
                $query->whereRaw('units.name ILIKE ?', ["%" . $name . "%"]);
            }

            if ($userID = $request->input('user_id')) {
                $query->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_details.operating_unit_corporate_id');
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
                $query->where('operating_unit_relation_id', '=', $representativeUnitID);
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
            $query = Unit::query()->with(['operatingUnitDetail'])
                ->where('units.unit_level', '=', 4)
                ->select(['units.*']);

            $query->leftJoin('operating_unit_details', 'operating_unit_details.unit_relation_id', '=', 'units.relation_id');
            $query->whereNull('operating_unit_details.id');

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

//            if (!$user->hasPermissionName(Permission::AssignOperatingUnit)) {
//                return response()->json([
//                    'status' => false,
//                    'message' => "You don't have access!",
//                ], ResponseAlias::HTTP_FORBIDDEN);
//            }

            /**
             * @var Unit $operatingUnit
             */
            $operatingUnit = Unit::query()
                ->where('id', '=', $request->input('representative_office_id'))
                ->where('unit_level', '=', Unit::UnitLevelOperatingUnit)
                ->first();
            if (!$operatingUnit) {
                return response()->json([
                    'status' => false,
                    'message' => "Operating Unit not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $corporates = $request->input('corporates');

            foreach ($corporates as $corporate) {
                $operatingUnitCorporate = $operatingUnit
                    ->operatingUnitCorporates()
                    ->where('corporate_relation_id', '=', $corporate['unit_relation_id'])
                    ->where('unit_level', '=', $corporate['unit_level'])
                    ->first();
                if (!$operatingUnitCorporate) {
                    $operatingUnitCorporate = new OperatingUnitCorporate();
                    $operatingUnitCorporate->operating_unit_relation_id = $operatingUnit->relation_id;
                    $operatingUnitCorporate->corporate_relation_id = $corporate['unit_relation_id'];
                    $operatingUnitCorporate->unit_level = $corporate['unit_level'];
                    $operatingUnitCorporate->save();
                }

                if (Unit::query()->where('parent_unit_id', '=', $corporate['unit_relation_id'])->count() > 0 && count($corporate['kanwils']) == 0) {
                    return response()->json([
                        'status' => false,
                        'message' => "You need to add kanwil for this corporate!",
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }

                if (count($corporate['kanwils']) == 0) {
                    $operatingUnitDetail = OperatingUnitDetail::query()
                        ->where('operating_unit_corporate_id', '=', $operatingUnitCorporate->id)
                        ->where('unit_relation_id', '=', $corporate['unit_relation_id'])
                        ->first();
                    if (!$operatingUnitDetail) {
                        $operatingUnitDetail = new OperatingUnitDetail();
                        $operatingUnitDetail->operating_unit_corporate_id = $operatingUnitCorporate->id;
                        $operatingUnitDetail->unit_relation_id = $corporate['unit_relation_id'];
                        $operatingUnitDetail->unit_level = $corporate['unit_level'];
                        $operatingUnitDetail->save();
                    }
                } else {
                    foreach ($corporate['kanwils'] as $item) {
                        $operatingUnitDetail = OperatingUnitDetail::query()
                            ->where('operating_unit_corporate_id', '=', $operatingUnitCorporate->id)
                            ->where('unit_relation_id', '=', $item['relation_id'])
                            ->where('unit_level', '=', $item['unit_level'])
                            ->first();
                        if (!$operatingUnitDetail) {
                            $operatingUnitDetail = new OperatingUnitDetail();
                            $operatingUnitDetail->operating_unit_corporate_id = $operatingUnitCorporate->id;
                            $operatingUnitDetail->unit_relation_id = $item['relation_id'];
                            $operatingUnitDetail->unit_level = $item['unit_level'];
                            $operatingUnitDetail->save();
                        }
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

//            if (!$user->hasPermissionName(Permission::DeleteOperatingUnit)) {
//                return response()->json([
//                    'status' => false,
//                    'message' => "You don't have access!",
//                ], ResponseAlias::HTTP_FORBIDDEN);
//            }

            /**
             * @var OperatingUnitDetail $operatingUnitDetail
             */
            $operatingUnitDetail = OperatingUnitDetail::query()
                ->where('id', '=', $id)
                ->first();

            if (!$operatingUnitDetail) {
                return response()->json([
                    'status' => false,
                    'message' => "Operating Unit Not Found!",
                ], ResponseAlias::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();
            $operatingUnitCorporateIDs = [];

            $operatingUnitCorporateIDs[] = $operatingUnitDetail->operating_unit_corporate_id;
            $operatingUnitDetail->delete();

            foreach ($operatingUnitCorporateIDs as $operatingUnitCorporateID) {
                /**
                 * @var OperatingUnitCorporate $operatingUnitCorporate
                 */
                $operatingUnitCorporate = OperatingUnitCorporate::query()
                    ->where('id', '=', $operatingUnitCorporateID)
                    ->first();

                if ($operatingUnitCorporate->operatingUnitDetails()->count() <= 0) {
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

//            if (!$user->hasPermissionName(Permission::AssignOperatingUnit)) {
//                return response()->json([
//                    'status' => false,
//                    'message' => "You don't have access!",
//                ], ResponseAlias::HTTP_FORBIDDEN);
//            }

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

//            if (!$user->hasPermissionName(Permission::DeleteOperatingUnit)) {
//                return response()->json([
//                    'status' => false,
//                    'message' => "You don't have access!",
//                ], ResponseAlias::HTTP_FORBIDDEN);
//            }

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

    public function assignCentralOperatingUnitUser(AssignCentralOperatingUnitRequest $request) {
        try {
            /**
             * @var User $user
             */
            $user = User::query()->where('id', '=', $request->input('user_id'))->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => "User not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var Unit $unit
             */
            $unit = Unit::query()->where('relation_id', '=', $request->input('unit_relation_id'))->first();
            if (!$unit) {
                return response()->json([
                    'status' => false,
                    'message' => "Unit not found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $centralOperatingUnitUser = CentralOperatingUnitUser::query()
                ->where('user_id', '=', $user->id)
                ->where('unit_relation_id', '=', $unit->relation_id)
                ->first();
            if (!$centralOperatingUnitUser) {
                $centralOperatingUnitUser = new CentralOperatingUnitUser();
                $centralOperatingUnitUser->user_id = $user->id;
                $centralOperatingUnitUser->unit_relation_id = $unit->relation_id;
                $centralOperatingUnitUser->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Success!",
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeCentralOperatingUnitUser(Request $request, int $id) {
        try {
            /**
             * @var CentralOperatingUnitUser $centralOperatingUnit
             */
            $centralOperatingUnit = CentralOperatingUnitUser::query()->where('id', '=', $id)->first();
            if (!$centralOperatingUnit) {
                return response()->json([
                    'status' => false,
                    'message' => "Central Operating Unit Not Found!",
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();
            $centralOperatingUnit->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Success!",
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listCentralOperatingUnitUser(Request $request) {
        try {
            $query = CentralOperatingUnitUser::query()
                ->select(['central_operating_unit_users.*']);

            if ($userID = $request->input('user_id')) {
                $query->where('central_operating_unit_users.user_id', '=', $userID);
            }

            return response()->json([
                'status' => true,
                'message' => "Success!",
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
