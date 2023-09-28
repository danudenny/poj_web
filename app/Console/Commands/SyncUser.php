<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;

class SyncUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $employees = Employee::query()->chunk(1000, function ($employeesChunk) {
            foreach ($employeesChunk as $employee) {
                $user = User::query()->where('employee_id', '=', $employee->id)->first();
                if (!$user) {
                    $this->info('Sync : ' . $employee->work_email);
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

                if ($user->email == 'fahmi@optimajasa.co.id') {
                    $user->assignRole('superadmin');
                } else {
                    $user->assignRole('staff');
                }
            }
        });

        $this->info('Employee data synced successfully!');
        return Command::SUCCESS;
    }
}
