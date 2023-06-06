<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOdooData extends Command
{
    protected $signature = 'sync:odoo:employees';
    protected $description = 'Sync employee data from Odoo';

    public function handle()
    {
        // Configure Odoo JSON-RPC API credentials
        $odooUrl = env('ODOO_URL');
        $odooDb = env('ODOO_DATABASE');
        $odooUsername = env('ODOO_USERNAME');
        $odooPassword = env('ODOO_PASSWORD');
        $odooUri = env('ODOO_URI');


        // Connect to Odoo JSON-RPC API
        $client = new Client(['base_uri' => $odooUrl . $odooUri ]);

        // Authenticate with Odoo JSON-RPC API
        $response = $client->post('', [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'db' => $odooDb,
                    'login' => $odooUsername,
                    'password' => $odooPassword,
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (array_key_exists('error', $result)) {
            $this->error('Failed to authenticate with Odoo: ' . $result['error']['message']);
            return;
        }

        // Fetch employee data from Odoo
        $response = $client->post('', [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'db' => $odooDb,
                    'uid' => $result['result']['uid'],
                    'password' => $odooPassword,
                    'model' => 'hr.employee',
                    'method' => 'search_read',
                    'args' => [[]],
                    'kwargs' => ['fields' => ['name', 'email', 'phone'], 'limit' => 1000],
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (array_key_exists('error', $result)) {
            $this->error('Failed to fetch employee data from Odoo: ' . $result['error']['message']);
            return;
        }

        // Sync employee data with PostgreSQL database
        foreach ($result['result'] as $employee) {
            $existingEmployee = DB::table('employees')->where('id', $employee['id'])->first();

            if ($existingEmployee) {
                // Check if the 'updated_at' timestamp is greater than 'created_at'
                if ($employee['write_date'] > $employee['create_date']) {
                    DB::table('employees')
                        ->where('id', $employee['id'])
                        ->update([
                            'name' => $employee['name'],
                            'email' => $employee['email'],
                            'phone' => $employee['phone'],
                            'created_at' => $employee['create_date'],
                            'updated_at' => $employee['write_date'],
                        ]);
                }
            } else {
                // Check if 'created_at' equals 'updated_at' and the 'id' doesn't exist
                if ($employee['create_date'] === $employee['write_date']) {
                    DB::table('employees')->insert([
                        'id' => $employee['id'],
                        'name' => $employee['name'],
                        'email' => $employee['email'],
                        'phone' => $employee['phone'],
                        'created_at' => $employee['create_date'],
                        'updated_at' => $employee['write_date'],
                    ]);
                }
            }

            // Update syncs table
            DB::table('syncs')->updateOrInsert(
                [
                    'odoo_model' => 'hr.employee',
                    'odoo_record_id' => $employee['id'],
                ],
                [
                    'status' => 'synced',
                    'last_synced_at' => now(),
                ]
            );
        }

        $this->info('Employee data synced successfully!');
    }
}
