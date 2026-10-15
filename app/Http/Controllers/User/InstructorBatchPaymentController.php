<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InstructorBatchPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorBatchPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->roles()->where('name', 'instructor')->exists(), 403);
        InstructorBatchPayment::ensureTable();

        $status = trim((string) $request->get('status', ''));
        $query = InstructorBatchPayment::query()
            ->with(['batch.course:id,title'])
            ->where('instructor_id', (int) $user->id)
            ->orderByDesc('updated_at');

        if ($status !== '' && array_key_exists($status, InstructorBatchPayment::statuses())) {
            $query->where('status', $status);
        }

        return view('user.instructor-payments.index', [
            'rows' => $query->paginate(20)->withQueryString(),
            'statuses' => InstructorBatchPayment::statuses(),
            'status' => $status,
        ]);
    }

    public function slip($id)
    {
        $user = Auth::user();
        abort_unless($user && $user->roles()->where('name', 'instructor')->exists(), 403);
        InstructorBatchPayment::ensureTable();

        $payment = InstructorBatchPayment::query()
            ->where('instructor_id', (int) $user->id)
            ->findOrFail($id);
        abort_unless($payment->hasSlip(), 404);

        $absolute = public_path(ltrim((string) $payment->payment_slip_path, '/\\'));
        abort_unless(is_file($absolute), 404);

        return response()->file($absolute);
    }
}
