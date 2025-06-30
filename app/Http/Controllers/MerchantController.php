<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MerchantController extends Controller
{


    public function PushOrder($id)
    {

        $order = Order::findOrFail($id);
    }
}
