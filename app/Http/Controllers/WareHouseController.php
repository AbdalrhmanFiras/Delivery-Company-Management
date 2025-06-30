<?php

namespace App\Http\Controllers;

use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WareHouseController extends Controller
{
    public function store(Request $request)
    {

        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
        ]);

        $warehouse = Warehouse::create($data);

        return response()->json(new WarehouseResource($warehouse));
    }
}
