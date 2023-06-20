<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

    public function handle()
    {
        $employees = Employee::all();
        $users = User::all();

        $employees->each(function ($employee) use ($users) {
            $user = $users->where('employee_id', $employee->id)->first();

            if ($user) {
                $user->name = $employee->name;
                $user->email = $employee->work_email;
                $user->email_verified_at = now();
                $user->password = '$10$A7iTVOJ0ItCaXMbvLJApceAI0B/373zpRLhFY3tHLMnylaiV32z/S';
                $user->is_active = true;
                $user->created_at = now();
                $user->updated_at = now();
                $user->employee_id = $employee->id;
                $user->save();
            } else {
                User::create([
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->work_email,
                    'email_verified_at' => now(),
                    'password' => '$10$A7iTVOJ0ItCaXMbvLJApceAI0B/373zpRLhFY3tHLMnylaiV32z/S',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'is_new' => true,
                ]);
            }
        });

        // Check for deleted records
        $users->each(function ($user) use ($employees) {
            if (!$employees->contains('id', $user->employee_id)) {
                $user->delete();
            }
        });

    }
}
