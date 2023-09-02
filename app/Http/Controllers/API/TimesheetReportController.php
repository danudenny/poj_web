<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimesheetReport\CreateTimesheetReport;
use App\Services\Core\TimesheetReportService;
use Illuminate\Http\Request;

class TimesheetReportController extends Controller
{
    private TimesheetReportService $service;

    public function __construct(TimesheetReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request) {
        return $this->service->index($request);
    }

    public function listTimesheetDetail(Request $request) {
        return $this->service->listTimesheetDetail($request);
    }

    public function view(Request $request, int $id) {
        return $this->service->view($request, $id);
    }

    public function create(CreateTimesheetReport $request) {
        return $this->service->createTimesheetReport($request);
    }

    public function sync(Request $request, int $id) {
        return $this->service->syncTimesheetReport($request, $id);
    }
}
