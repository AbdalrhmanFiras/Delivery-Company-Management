<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Merchant;
use App\Models\Warehouse;
use App\Models\WarehouseReceipts;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReceiveMerchantOrdersJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $merchantId;
    protected $warehouseId;

    public function __construct($merchantId, $warehouseId)
    {
        $this->merchantId = $merchantId;
        $this->warehouseId = $warehouseId;
    }

    public function handle()
    {

        try {
            Log::info("Starting job to receive orders for merchant #{$this->merchantId}");

            $orders = Order::merchantId($this->merchantId)
                ->where('warehouse_id', $this->warehouseId)
                ->whereNotIn('id', function ($q) {
                    $q->select('order_id')->from('warehouse_receipts');
                })
                ->orderStatus(1)
                ->get();

            if ($orders->isEmpty()) {
                Log::info("No orders to receive for merchant #{$this->merchantId}");
                return;
            }

            DB::beginTransaction();
            foreach ($orders as $order) {
                WarehouseReceipts::create([
                    'order_id' => $order->id,
                    'received_by' => $order->merchant ? $order->merchant->user_id : null,
                    'received_at' => now(),
                ]);
            }
            DB::commit();

            Log::info("Successfully received {$orders->count()} orders for merchant #{$this->merchantId}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Job failed receiving orders for merchant #{$this->merchantId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
