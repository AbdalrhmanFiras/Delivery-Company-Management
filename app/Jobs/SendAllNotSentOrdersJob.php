<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\WarehouseReceipts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrderLog;

class SendAllNotSentOrdersJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    public function handle()
    {
        $processedCount = 0;
        $failedCount = 0;
        $batchLogs = [];

        Log::info('Starting SendAllNotSentOrdersJob');

        try {
            Order::where('upload', Order::STATUS_NOT_SENT)
                ->chunkById(100, function ($orders) use (&$processedCount, &$failedCount, &$batchLogs) {
                    foreach ($orders as $order) {
                        try {
                            DB::transaction(function () use ($order, &$batchLogs) {
                                $originalData = $order->toArray();

                                $order->upload = Order::STATUS_SENT;
                                $order->status = 1;
                                $order->warehouse_id = $order->merchant->warehouse_id;
                                $order->save();

                                WarehouseReceipts::create([
                                    'order_id' => $order->id,
                                    'received_by' => $order->merchant->user_id,
                                    'received_at' => now(),
                                ]);

                                $batchLogs[] = [
                                    'order_id' => $order->id,
                                    'merchant_id' => $order->merchant_id,
                                    'action' => 'bulk_sent_to_warehouse',
                                    'original_data' => json_encode($originalData),
                                    'new_data' => json_encode($order->fresh()->toArray()),
                                    'processed_by' => 'system',
                                    'created_at' => now()
                                ];
                            });

                            $processedCount++;
                        } catch (\Exception $e) {
                            $failedCount++;
                            Log::error("Failed to process order {$order->id}", [
                                'error' => $e->getMessage(),
                                'order_id' => $order->id
                            ]);
                        }
                    }

                    if (count($batchLogs) >= 100) {
                        $this->insertBatchLogs($batchLogs);
                        $batchLogs = [];
                    }
                });

            if (!empty($batchLogs)) {
                $this->insertBatchLogs($batchLogs);
            }

            Log::info('SendAllNotSentOrdersJob completed', [
                'processed_count' => $processedCount,
                'failed_count' => $failedCount
            ]);
        } catch (\Exception $e) {
            Log::error('SendAllNotSentOrdersJob failed', [
                'error' => $e->getMessage(),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount
            ]);
            throw $e;
        }
    }

    private function insertBatchLogs(array $logs)
    {
        try {
            OrderLog::insert($logs);
        } catch (\Exception $e) {
            Log::error('Failed to insert batch logs', ['error' => $e->getMessage()]);
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error('SendAllNotSentOrdersJob failed completely', [
            'error' => $exception->getMessage()
        ]);
    }
}
