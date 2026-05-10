<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DestroyCustomerAction
{
    public static function run(Customer $customer): bool
    {
        $customer->loadCount([
            'contracts as contracts_count',
            'documentAssignments as document_assignments_count',
        ]);

        if ($customer->contracts_count > 0 || $customer->document_assignments_count > 0) {
            throw new RuntimeException(trans('admin/customers/message.delete.associations'));
        }

        if ($customer->image) {
            try {
                Storage::disk('public')->delete('customers/'.$customer->image);
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }

        $customer->delete();

        return true;
    }
}
