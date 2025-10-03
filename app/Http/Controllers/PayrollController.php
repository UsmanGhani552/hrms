<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Http\Requests\Payroll\UpdatePayrollRequest;
use App\Models\Payroll;
use App\Traits\ResponseTrait;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use App\Traits\ImageUploadTrait;

class PayrollController extends Controller
{
    use ImageUploadTrait;
    public function index() {
        try {
            $payrolls = Payroll::with('user:id,name,email')->get();
            return ResponseTrait::success('Payrolls fetched successfully', $payrolls);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error fetching payrolls', $th);
        }
    }

    public function store(StorePayrollRequest $request) {
        try {
            $payroll = Payroll::createPayroll($request->validated());
            return ResponseTrait::success('Payroll uploaded successfully', $payroll);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error uploading payroll', $th);
        }
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll) {
        try {
            $payroll->updatePayroll($request->validated());
            return ResponseTrait::success('Payroll updated successfully', $payroll);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error updating payroll', $th);
        }
    }

    public function delete(Payroll $payroll) {
        try {
            $this->deleteImage("images/payrolls/{$payroll->document}");
            $payroll->delete();
            return ResponseTrait::success('Payroll deleted successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Error deleting payroll', $th);
        }
    }
}
