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
        $employees = Employee::all();
        $users = User::whereIn('employee_id', $employees->pluck('id'))->get();

        $employees->chunk(1000, function ($employeesChunk) use ($users) {
            $usersChunk = $users->whereIn('employee_id', $employeesChunk->pluck('id'));

            User::withoutBroadcasting(function () use ($usersChunk, $employeesChunk) {
                foreach ($usersChunk as $user) {
                    $employee = $employeesChunk->firstWhere('id', $user->employee_id);

                    $userData = [
                        'name' => $employee->name,
                        'email' => $employee->work_email,
                        'email_verified_at' => now(),
                        'password' => '$2y$10$m54GoOajOHJ4AYs2VnfP7e3hPBf3pJw.Omimsct0m6gDcHCt8hTHi',
                        'is_active' => true,
                        'is_new' => true,
                    ];

                    if ($user->exists) {
                        $user->update($userData);
                    } else {
                        $userData['created_at'] = now();
                        $userData['updated_at'] = now();

                        $user = User::create($userData);
                    }

                    $user->assignRole('staff');
                }
            });
        });

        $usersToDelete = $users->filter(function ($user) use ($employees) {
            return !$employees->contains('id', $user->employee_id) && $user->name !== 'Superadmin';
        });

        User::destroy($usersToDelete->pluck('id')->toArray());
    }

}
