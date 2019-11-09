<?php

namespace App\Http\Controllers\Admin;

use App\Expense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\PatientHistory;
use Illuminate\Support\Facades\Auth;

use App\Payment;
use App\Role;
use App\User;
use Illuminate\Http\JsonResponse;

class GraphicsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAdmin() && !Auth::user()->isSellManager()) {
                if ($request->ajax()) {
                    return new JsonResponse(null, 403);
                }

                return redirect()->route('home');
            }

            return $next($request);
        });
    }
    public function paymentCharts()
    {
        return view('admin.graphics.paymentCharts');
    }

    public function commmissionCharts() 
    {
        return view('admin.graphics.commissiontCharts');
    }

    public function getDataPaymentCharts($dateSatrt = NULL, $dateEnd = NULL, $type = NULL)
    {
        return new JsonResponse([
            'success' => 200,
            'totalAmounForType' => Payment::getTotalAmountForType($this->getDateRange($dateSatrt, $dateEnd), $type),
            'totalPayments' => Payment::getTotalPayments($this->getDateRange($dateSatrt, $dateEnd), $type)
        ]);
    }

    public function getCommmissionCharts($dateSatrt = NULL, $dateEnd = NULL, $type = NULL)
    {
        $doctors = User::all();
        $start = new \DateTime($dateSatrt);
        $start->setTime(00, 00, 00);
        $end = new \DateTime($dateEnd);
        $end->setTime(23, 59, 59);
        $totalInCommissions = 0;
        $totalPayments = 0;
        $totalExpenses = 0;


        foreach ($doctors as $data) {
            $amountPayments = 0;
            $amountExpenses = 0;
            $amountCommission = 0;
            $discounts = 0;
            if ($data->hasRole['doctor'] && $data->id > 2) {
                $doctor = User::where('public_id', $data->public_id)->with('commissionProducts')->firstOrFail();

                $patientHistoryIds = PatientHistory::select('patient_history.id')
                    ->leftJoin('payments', 'patient_history.id', '=', 'payments.patient_history_id')
                    ->leftJoin('expenses', 'patient_history.id', '=', 'expenses.patient_history_id')
                    ->where('doctor_id', $doctor->id)
                    ->where(function ($query) use ($start, $end) {

                        $query->where([
                            ['payments.date', '>=', $start],
                            ['payments.date', '<=', $end]
                        ])
                            ->orWhere([
                                ['patient_history.created_at', '>=', $start],
                                ['patient_history.created_at', '<=', $end]
                            ])
                            ->orWhere([
                                ['expenses.date', '>=', $start],
                                ['expenses.date', '<=', $end]
                            ]);
                    })
                    ->distinct()
                    ->get();

                $patientHistory = PatientHistory::whereIn('id', $patientHistoryIds->toArray())
                    ->with([
                        'patient',
                        'product',
                        'expenses',
                        'payments'
                    ])
                    ->get();

                foreach ($patientHistory as $history) {
                    $patient = $history->patient;

                    if ($patient->trashed()) {
                        continue;
                    }

                    // Obtengo la comision configurada para este doctor y producto
                    $commission = $doctor->commissionProducts()->where('product_id', $history->product->id)->first()->pivot->commission;
                    if (isset($commission)) {
                        $amountCommission = $commission;
                    }

                    $amountPayments += $history->price;
                    // Todos los gastos asociados al servicio y al laboratorio
                    $expenses = $history->expenses()
                        ->join('suppliers', 'suppliers.id', '=', 'expenses.supplier_id')
                        ->where('expenses.date', '<=', $end)
                        ->get();

                    foreach ($expenses as $expense) {

                        $amountExpenses += $expense->amount;
                    }

                    $payments = $history->payments()->where('payments.date', '<=', $end)->get();

                    foreach ($payments as $payment) {
                        if ($payment->isDiscount()) {
                            $discounts += $payment->amount;
                        }
                    }
                }
                
                $totalPayments += $amountPayments - $discounts;
                $totalExpenses += $amountExpenses;
                $totalInCommissions += (($amountPayments - $discounts) - $amountExpenses) * ($amountCommission / 100);
            }
        }

        return new JsonResponse([
            'success' => 200,
            'totalPayments' => $totalPayments,
            'totalExpenses' => $totalExpenses,
            'totalCommission' => $totalInCommissions
        ]);
    }

    private function getDateRange($dateSatrt, $dateEnd)
    {
        $start = new \DateTime();
        $start->setTime(00, 00, 00);
        $end = new \DateTime();
        $end->setTime(23, 59, 59);

        if ($dateSatrt) {
            $start = new \DateTime($dateSatrt);
            $start->setTime(00, 00, 00);
        }

        if ($dateEnd) {
            $end = new \DateTime($dateEnd);
            $end->setTime(23, 59, 59);
        }

        return [$start, $end];
    }
}
