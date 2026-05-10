<?php

namespace App\Http\Controllers;

use App\Actions\Customers\DestroyCustomerAction;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BulkCustomersController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', Customer::class);

        $errors = [];
        $successCount = 0;

        foreach ($request->input('ids', []) as $id) {
            $customer = Customer::find($id);

            if (is_null($customer)) {
                $errors[] = trans('admin/customers/message.delete.not_found');

                continue;
            }

            try {
                DestroyCustomerAction::run($customer);
                $successCount++;
            } catch (RuntimeException $e) {
                $errors[] = $customer->name.': '.$e->getMessage();
            } catch (\Exception $e) {
                report($e);
                $errors[] = trans('general.something_went_wrong');
            }
        }

        if (count($errors) > 0) {
            if ($successCount > 0) {
                return redirect()->route('customers.index')
                    ->with('success', trans_choice('admin/customers/message.delete.partial_success', $successCount, ['count' => $successCount]))
                    ->with('multi_error_messages', $errors);
            }

            return redirect()->route('customers.index')->with('multi_error_messages', $errors);
        }

        return redirect()->route('customers.index')->with('success', trans('admin/customers/message.delete.bulk_success'));
    }
}
