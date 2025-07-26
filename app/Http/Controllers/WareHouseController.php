<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Warehouse;
use App\Enums\Governorate;
use Illuminate\Http\Request;
use App\Models\DeliveryCompany;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\EmpolyeeResource;
use App\Http\Resources\WarehouseResource;
use App\Http\Requests\EmployeeNameRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\DeliveryCompanyResource;
use App\Http\Requests\AddDeliveryCompanyRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateDeliveryCompanyWarehouseRequest;



class WareHouseController extends BaseController {}
