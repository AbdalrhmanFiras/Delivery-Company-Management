<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\WarehouseReceipts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendAllNotSentOrdersJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        DB::beginTransaction();

        try {
            $orders = Order::where('upload', Order::STATUS_NOT_SENT)->get();

            if ($orders->isEmpty()) {
                DB::commit();
                return;
            }

            foreach ($orders as $order) {
                $order->upload = Order::STATUS_SENT;
                $order->status = 1;
                $order->save();

                WarehouseReceipts::create([
                    'order_id' => $order->id,
                    'received_by' => $order->merchant->user_id,
                    'received_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
