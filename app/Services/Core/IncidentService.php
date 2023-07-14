<?php

namespace App\Services\Core;

use App\Models\Approval;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\IncidentImage;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncidentService extends BaseService
{
    public function index(Request $request) {
        $query = Incident::query();
        $query->when($request->filled('category'), function (Builder $query) use ($request) {
            $query->where('category', '=', $request->query('category'));
        });
        $query->when($request->filled('name'), function (Builder $query) use ($request) {
            $query->whereRaw('LOWER(name) LIKE ?', [strtolower('%' . $request->query('name') . '%')]);
        });
        $query->when($request->filled('status'), function (Builder $query) use ($request) {
            $query->where('last_status', '=', $request->query('status'));
        });
        $query->orderBy('id', 'desc');

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $query->get()
        ]);
    }

    public function view(int $incidentID) {
        $incident = Incident::query()->with(['incidentImages', 'incidentImageFollowUp', 'incidentHistories', 'incidentHistories.employee:employees.id,name'])->find($incidentID);
        if(is_null($incident)) {
            return response()->json([
                'message' => 'Incident not found!'
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $incident
        ]);
    }

    public function createIncident(Request $request) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            $request->validate([
                'category' => [
                    'required',
                    Rule::in([Incident::TYPE_INTERNAL, Incident::TYPE_EXTERNAL])
                ],
                'name' => ['required'],
                'latitude' => ['required'],
                'longitude' => ['required'],
                'location_name' => ['required'],
                'incident_time' => ['required'],
                'person' => ['required'],
                'witness' => ['required'],
                'cause' => ['required'],
                'chronology' => ['required'],
                'images' => ['required']
            ]);

            DB::beginTransaction();

            $incident = new Incident();
            $incident->employee_id = $user->employee_id;
            $incident->category = $request->input('category');
            $incident->name = $request->input('name');
            $incident->latitude = $request->input('latitude');
            $incident->longitude = $request->input('longitude');
            $incident->location_name = $request->input('location_name');
            $incident->incident_time = $request->input('incident_time');
            $incident->person = $request->input('person');
            $incident->witness = $request->input('witness');
            $incident->cause = $request->input('cause');
            $incident->chronology = $request->input('chronology');
            $incident->last_status = IncidentHistory::StatusSubmitted;
            $incident->last_stage = IncidentHistory::TypeSubmit;

            $incident->save();

            foreach ($request->input('images') as $item) {
                $incidentImage = new IncidentImage();
                $incidentImage->incident_id = $incident->id;
                $incidentImage->image_type = IncidentImage::TypeIncident;
                $incidentImage->image_url = $item;

                $incidentImage->save();
            }

            $incidentHistory = new IncidentHistory();
            $incidentHistory->incident_id = $incident->id;
            $incidentHistory->history_type = $incident->last_stage;
            $incidentHistory->status = $incident->last_status;
            $incidentHistory->employee_id = $user->employee_id;

            $incidentHistory->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'success',
                'data' => $incident
            ], Response::HTTP_OK);
        } catch (ValidationException $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->errors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function incidentApproval(Request $request, int $incidentID) {
        try {
            $request->validate([
                'status' => [
                    'required',
                    Rule::in([IncidentHistory::StatusApprove, IncidentHistory::StatusReject])
                ]
            ]);

            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var ApprovalUser[] $approvalUsers
             */
            $approvalUsers = ApprovalUser::query()
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
                ->where('approval_modules.name', '=', ApprovalModule::ApprovalIncident)
                ->where('approvals.unit_id', '=', $user->employee->getLastUnitID())
                ->where('approvals.is_active', '=', true)
                ->orderBy('approval_users.id', 'ASC')
                ->get(['approval_users.*']);

            $isValidToCreate = false;
            foreach ($approvalUsers as $approvalUser) {
                if ($approvalUser->user_id == $user->id) {
                    $isValidToCreate = true;
                }
            }

            if (!$isValidToCreate) {
                return response()->json([
                    'message' => 'You don\'t have access to do approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             * @var Incident $incident
             */
            $incident = Incident::query()->find($incidentID);
            if(is_null($incident)) {
                return response()->json([
                    'message' => 'Incident not found!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($incident->last_status == IncidentHistory::StatusApprove) {
                return response()->json([
                    'message' => 'Incident cannot re-approve!'
                ], Response::HTTP_BAD_REQUEST);
            }

            $incidentHistoryClosureTotal = IncidentHistory::query()
                ->where('incident_id', '=', $incident->id)
                ->where('history_type', '=', IncidentHistory::TypeClosure)
                ->count();
            $incidentHistoryApprovalTotal = IncidentHistory::query()
                ->where('incident_id', '=', $incident->id)
                ->where('history_type', '=', IncidentHistory::TypeFollowUp)
                ->where('status', '=', IncidentHistory::StatusReject)
                ->count();

            $totalIncidentHistory = $incidentHistoryClosureTotal + $incidentHistoryApprovalTotal;

            if ($totalIncidentHistory >= count($approvalUsers)) {
                return response()->json([
                    'message' => 'Incident approval is finished!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($approvalUsers[$totalIncidentHistory])) {
                return response()->json([
                    'message' => 'Invalid incident approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($approvalUsers[$totalIncidentHistory]->user_id != $user->id) {
                return response()->json([
                    'message' => 'Last approver not doing approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = [
                'status' => $request->input('status'),
                'reason' => $request->input('reason', ''),
                'incident_analysis' => $request->input('incident_analysis', ''),
                'follow_up_incident' => $request->input('follow_up_incident', ''),
                'file' => $request->input('file', '')
            ];

            if($data['status'] == IncidentHistory::StatusReject && ($data['reason'] == "")) {
                return response()->json([
                    'message' => 'Reason is required!'
                ], Response::HTTP_BAD_REQUEST);
            } else if($data['status'] == IncidentHistory::StatusApprove && ($data['incident_analysis'] == '' || $data['follow_up_incident'] == '' || $data['file'] == '')) {
                return response()->json([
                    'message' => 'Incident Analysis, Follow Up Incident, and File is Required!'
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            $incidentHistory = new IncidentHistory();
            $incidentHistory->employee_id = $user->employee_id;
            $incidentHistory->incident_id = $incident->id;
            $incidentHistory->history_type = IncidentHistory::TypeFollowUp;
            $incidentHistory->status = $data['status'];

            if($data['status'] == IncidentHistory::StatusReject) {
                $incidentHistory->reason = $data['reason'];
            }
            if($data['status'] == IncidentHistory::StatusApprove) {
                $incidentHistory->incident_analysis = $data['incident_analysis'];
                $incidentHistory->follow_up_incident = $data['follow_up_incident'];
            }

            $incidentHistory->save();

            if($data['file'] != '') {
                $incidentImage = new IncidentImage();
                $incidentImage->incident_id = $incident->id;
                $incidentImage->image_type = IncidentImage::TypeFollowup;
                $incidentImage->image_url = $data['file'];

                $incidentImage->save();
            }

            $incident->last_status = $incidentHistory->status;
            $incident->last_stage = $incidentHistory->history_type;
            $incident->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'success',
                'data' => $incident
            ], Response::HTTP_OK);
        } catch (ValidationException $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->errors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function closure(Request $request, int $incidentID) {
        try {
            $request->validate([
                'status' => [
                    'required',
                    Rule::in([IncidentHistory::StatusClose, IncidentHistory::StatusDisclose])
                ]
            ]);

            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var ApprovalUser[] $approvalUsers
             */
            $approvalUsers = ApprovalUser::query()
                ->join('approvals', 'approvals.id', '=', 'approval_users.approval_id')
                ->join('approval_modules', 'approvals.approval_module_id', '=', 'approval_modules.id')
                ->where('approval_modules.name', '=', ApprovalModule::ApprovalIncident)
                ->where('approvals.unit_id', '=', $user->employee->getLastUnitID())
                ->where('approvals.is_active', '=', true)
                ->orderBy('approval_users.id', 'ASC')
                ->get(['approval_users.*']);

            $isValidToCreate = false;
            foreach ($approvalUsers as $approvalUser) {
                if ($approvalUser->user_id == $user->id) {
                    $isValidToCreate = true;
                }
            }

            if (!$isValidToCreate) {
                return response()->json([
                    'message' => 'You don\'t have access to do approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            /**
             * @var Incident $incident
             */
            $incident = Incident::query()->find($incidentID);
            if(is_null($incident)) {
                return response()->json([
                    'message' => 'Incident not found!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($incident->last_status == IncidentHistory::StatusClose || $incident->last_status == IncidentHistory::StatusDisclose) {
                return response()->json([
                    'message' => 'Incident cannot re-approve!'
                ], Response::HTTP_BAD_REQUEST);
            }

            $totalIncidentHistoryClosure = IncidentHistory::query()
                ->where('incident_id', '=', $incident->id)
                ->where('history_type', '=', IncidentHistory::TypeFollowUp)
                ->count();

            if ($totalIncidentHistoryClosure > count($approvalUsers)) {
                return response()->json([
                    'message' => 'Incident approval is finished!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($approvalUsers[($totalIncidentHistoryClosure - 1)])) {
                return response()->json([
                    'message' => 'Invalid incident closure!'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($approvalUsers[($totalIncidentHistoryClosure - 1)]->user_id != $user->id) {
                return response()->json([
                    'message' => 'Last approver not doing approval!'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = [
                'status' => $request->input('status'),
                'reason' => $request->input('reason', '')
            ];

            DB::beginTransaction();

            $incidentHistory = new IncidentHistory();
            $incidentHistory->employee_id = $user->employee_id;
            $incidentHistory->incident_id = $incident->id;
            $incidentHistory->history_type = IncidentHistory::TypeClosure;
            $incidentHistory->status = $data['status'];
            $incidentHistory->reason = $data['reason'];
            $incidentHistory->save();

            $incident->last_status = $incidentHistory->status;
            $incident->last_stage = $incidentHistory->history_type;
            $incident->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'success',
                'data' => $incident
            ], Response::HTTP_OK);
        } catch (ValidationException $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->errors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
