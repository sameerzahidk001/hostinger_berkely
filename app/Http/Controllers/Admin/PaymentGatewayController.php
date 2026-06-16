<?php 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::all();
        return view('admin.payment_gateways.index', compact('gateways'));
    }

    public function edit($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        return view('admin.payment_gateways.edit', compact('gateway'));
    }

   public function update(Request $request, $id)
    {
        $request->validate([
            'test_client_id' => 'required',
            'test_secret_key' => 'required',
            'prod_client_id' => 'required',
            'prod_secret_key' => 'required',
            'status' => 'required|in:live,test',
        ]);

        $gateway = PaymentGateway::findOrFail($id);

        $gateway->update([
            'test_keys' => [
                'client_id' => $request->test_client_id,
                'secret_key' => $request->test_secret_key,
            ],
            'production_keys' => [
                'client_id' => $request->prod_client_id,
                'secret_key' => $request->prod_secret_key,
            ],
            'status' => $request->status,
            'active' => $request->has('active'),
        ]);

        return redirect()->route('admin.payment-gateways.index')->with('success', 'Payment gateway updated successfully.');
    }
}
