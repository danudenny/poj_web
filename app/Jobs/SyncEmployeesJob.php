<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\ExtendRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    public function handle(): void
    {

        $employees = Employee::query()->chunk(1000, function ($employeesChunk) {
            foreach ($employeesChunk as $employee) {
                $user = User::query()->where('employee_id', '=', $employee->id)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $employee->name,
                        'email' => $employee->work_email,
                        'email_verified_at' => now(),
                        'employee_id' => $employee->id,
                        'password' => '$2y$10$m54GoOajOHJ4AYs2VnfP7e3hPBf3pJw.Omimsct0m6gDcHCt8hTHi',
                        'is_active' => true,
                        'is_new' => true,
                    ]);
                }

                if ($user->email == 'fahmi@koinworks.com') {
                    $user->assignRole('superadmin');
                } else {
                    $user->assignRole('staff');
                }
            }
        });

    }

}
