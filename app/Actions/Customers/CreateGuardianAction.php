<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CreateGuardianAction
{
    public function handle(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $dependents = $data['dependents'] ?? [];
            unset($data['dependents'], $data['guardian_id']);

            $guardian = Customer::create([
                ...$data,
                'guardian_id' => null,
            ]);

            $addedDependents = [];

            foreach ($dependents as $dependent) {
                $name = trim((string) ($dependent['name_ar'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $dependentKey = mb_strtolower(implode('|', [
                    $name,
                    trim((string) ($dependent['name_en'] ?? '')),
                    trim((string) ($dependent['phone'] ?? '')),
                ]));

                if (isset($addedDependents[$dependentKey])) {
                    continue;
                }

                $addedDependents[$dependentKey] = true;

                $guardian->dependents()->create([
                    'name_ar' => $name,
                    'name_en' => $dependent['name_en'] ?? null,
                    'phone' => $dependent['phone'] ?? null,
                    'note' => $dependent['note'] ?? null,
                ]);
            }

            return $guardian->load('dependents');
        });
    }
}
