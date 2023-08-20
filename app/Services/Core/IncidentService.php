<?php

namespace App\Services\Core;

use App\Http\Requests\Incident\ApprovalRequest;
use App\Http\Requests\Incident\ClosureRequest;
use App\Models\Approval;
use App\Models\ApprovalModule;
use App\Models\ApprovalUser;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\IncidentApproval;
use App\Models\IncidentHistory;
use App\Models\IncidentImage;
use App\Models\Role;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class IncidentService extends BaseService
{

    private ApprovalService $approvalService;

    public function __construct()
    {
        $this->approvalService = new ApprovalService();
    }

    public function index(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $query = Incident::query();
        $query->when($request->filled('category'), function (Builder $query) use ($request) {
            $query->where('incidents.category', '=', $request->query('category'));
        });
        $query->when($request->filled('name'), function (Builder $query) use ($request) {
            $query->whereRaw('LOWER(incidents.name) LIKE ?', [strtolower('%' . $request->query('name') . '%')]);
        });
        $query->when($request->filled('status'), function (Builder $query) use ($request) {
            $query->where('incidents.last_status', '=', $request->query('status'));
        });

        if ($user->isHighestRole(Role::RoleStaff)) {
            $query->where('incidents.employee_id', '=', $user->employee_id);
        } else if ($user->isHighestRole(Role::RoleAdmin)) {
            $query->join('employees', 'employees.id', '=', 'incidents.employee_id');
            $query->where(function(Builder $builder) use ($user) {
                $lastUnitID = $user->employee->getLastUnitID();
                if ($requestUnitID = $this->getRequestedUnitID()) {
                    $lastUnitID = $requestUnitID;
                }

                $builder->orWhere(function(Builder $builder) use ($lastUnitID) {
                    $builder->orWhere('employees.outlet_id', '=', $lastUnitID)
                        ->orWhere('employees.cabang_id', '=', $lastUnitID)
                        ->orWhere('employees.area_id', '=', $lastUnitID)
                        ->orWhere('employees.kanwil_id', '=', $lastUnitID)
                        ->orWhere('employees.corporate_id', '=', $lastUnitID);
                })->orWhere('incidents.employee_id', '=', $user->employee_id);
            });
        }

        $query->select(['incidents.*']);
        $query->orderBy('incidents.id', 'desc');

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

    public function listApproval(Request $request) {
        try {
            /**
             * @var Employee $employee
             */
            $employee = $request->user()->employee;

            $query = IncidentApproval::query()->with(['incident', 'incident.employee'])
                ->where('employee_id', '=', $employee->id);

            if ($status = $request->query('status')) {
                $query->where('status', '=', $status);
            }

            $query->orderBy('id', 'DESC');

            return response()->json([
                'status' => true,
                'message' => 'Success!',
                'data' => $this->list($query, $request)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
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

            $approverEmployeeIDs = [];
            $approverEmployees = $this->approvalService->getApprovalUser($user->employee, ApprovalModule::ApprovalIncident);
            foreach ($approverEmployees as $approverEmployee) {
                $approverEmployeeIDs[] = $approverEmployee->employee_id;
            }

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

            foreach ($approverEmployeeIDs as $idx => $approverEmployeeID) {
                $incidentApproval = new IncidentApproval();
                $incidentApproval->priority = $idx;
                $incidentApproval->incident_id = $incident->id;
                $incidentApproval->employee_id = $approverEmployeeID;
                $incidentApproval->status = IncidentApproval::StatusPending;
                $incidentApproval->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'success'
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function incidentApproval(ApprovalRequest $request, int $incidentID) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Incident $incident
             */
            $incident = Incident::query()->where('id', '=', $incidentID)->first();
            if(is_null($incident)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Incident not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var IncidentApproval $incidentApproval
             */
            $incidentApproval = IncidentApproval::query()
                ->where('incident_id', '=', $incident->id)
                ->where('employee_id', '=', $user->employee_id)
                ->where('status', '=', IncidentApproval::StatusPending)
                ->first();
            if (!$incidentApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access to do approval!'
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($incidentApproval->priority > 0) {
                $isBeforePending = $incident->incidentApprovals()
                    ->where('priority', '<', $incidentApproval->priority)
                    ->where(function(Builder $builder) {
                        $builder->orWhere('status', '=', IncidentApproval::StatusPending)
                            ->orWhere('status', '=', IncidentApproval::StatusApprove);
                    })->exists();
                if ($isBeforePending) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver is pending!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
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

            $incidentApproval->status = $data['status'];
            $incidentApproval->notes = $data['reason'];
            $incidentApproval->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'success',
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function closure(ClosureRequest $request, int $incidentID) {
        try {
            /**
             * @var User $user
             */
            $user = $request->user();

            /**
             * @var Incident $incident
             */
            $incident = Incident::query()->where('id', '=', $incidentID)->first();
            if(is_null($incident)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Incident not found!'
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            /**
             * @var IncidentApproval $incidentApproval
             */
            $incidentApproval = IncidentApproval::query()
                ->where('incident_id', '=', $incident->id)
                ->where('employee_id', '=', $user->employee_id)
                ->where('status', '=', IncidentApproval::StatusApprove)
                ->first();
            if (!$incidentApproval) {
                return response()->json([
                    'status' => false,
                    'message' => 'You don\'t have access to do approval!'
                ], ResponseAlias::HTTP_UNAUTHORIZED);
            }

            if ($incidentApproval->priority > 0) {
                $isBeforePending = $incident->incidentApprovals()
                    ->where('priority', '<', $incidentApproval->priority)
                    ->where(function(Builder $builder) {
                        $builder->orWhere('status', '=', IncidentApproval::StatusPending)
                            ->orWhere('status', '=', IncidentApproval::StatusApprove);
                    })->exists();
                if ($isBeforePending) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Last approver is pending!'
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
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

            $incidentApproval->status = $data['status'];
            $incidentApproval->notes = $data['reason'];
            $incidentApproval->save();

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
