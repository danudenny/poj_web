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

        DB::transaction(function () {
            $employees = Employee::with('user')->get();

            foreach ($employees as $employee) {
                $userData = [
                    'name' => $employee->name,
                    'email' => $employee->work_email,
                    'email_verified_at' => now(),
                    'password' => '$2y$10$m54GoOajOHJ4AYs2VnfP7e3hPBf3pJw.Omimsct0m6gDcHCt8hTHi',
                    'is_active' => true,
                    'is_new' => true,
                ];

                if ($employee->user) {
                    $employee->user->update($userData);
                } else {
                    $userData['created_at'] = now();
                    $userData['updated_at'] = now();

                    $user = $employee->user()->create($userData);

                    $user->assignRole('staff');
                }
            }

            $usersToDelete = User::whereNotIn('employee_id', $employees->pluck('id'))
                ->where('name', '!=', 'Superadmin')
                ->get();

            User::destroy($usersToDelete->pluck('id')->toArray());
            DB::commit();
        });
    }

}
