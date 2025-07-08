<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\CreditReminderNotification;
use App\Models\Setting;

class SendCreditReminders extends Command
{
    protected $signature = 'credits:remind';
    protected $description = 'Send push notifications for credit reminders';

    public function handle()
    {
        // Get reminder interval from settings
        $reminderInterval = (int) Setting::get('credit_reminder_days', 7);
        $lastReminderDate = Setting::get('last_credit_reminder_date');
        
        // // Check if we should send reminders today
        // if ($lastReminderDate && now()->diffInDays($lastReminderDate) < $reminderInterval) {
        //     $this->info('Reminder interval not reached yet.');
        //     return;
        // }

        // Get customers with credit
        $customers = Transaction::where('status', 'unpaid')
            ->with(['customer', 'creditPayment', 'returs.items'])
            ->get()
            ->groupBy('customer_id')
            ->map(function ($transactions, $customerId) {
                $total = $transactions->sum(function ($trx) {
                    $totalReturNominal = $trx
                        ->returs
                        ->flatMap(fn($r) => $r->items)
                        ->sum('subtotal');

                    $effectiveTotal = $trx->total - $totalReturNominal;
                    $creditPaidSoFar = $trx->prePaid + $trx->creditPayment->sum('payment_total');
                    return max(0, $effectiveTotal - $creditPaidSoFar);
                });

                return [
                    'id' => $customerId,
                    'name' => $transactions->first()->customer->name,
                    'amount' => $total
                ];
            })
            ->filter(fn($c) => $c['amount'] > 0)
            ->values()
            ->toArray();

        // Get suppliers with credit
        $suppliers = Purchase::where('status', 'unpaid')
            ->with(['supplier', 'creditPurchase', 'returs.items'])
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($purchases, $supplierId) {
                $total = $purchases->sum(function ($pur) {
                    $totalRetur = $pur->returs
                        ->flatMap(fn($r) => $r->items)
                        ->sum('subtotal');

                    $effectiveTotal = $pur->total - $totalRetur;
                    $paidSoFar = $pur->prePaid + $pur->creditPurchase->sum('payment_total');
                    return max(0, $effectiveTotal - $paidSoFar);
                });

                return [
                    'id' => $supplierId,
                    'name' => $purchases->first()->supplier->name,
                    'amount' => $total
                ];
            })
            ->filter(fn($s) => $s['amount'] > 0)
            ->values()
            ->toArray();

        // Only send if there are credits
        if (count($customers) > 0 || count($suppliers) > 0) {
            // Get all owner users who should receive notifications
            $users = User::where('role', 'owner')->get();
            
            foreach ($users as $user) {
                $user->notify(new CreditReminderNotification($customers, $suppliers));
            }

            // Update last reminder date
            Setting::set('last_credit_reminder_date', now()->toDateTimeString());
            
            $this->info('Credit reminders sent successfully.');
        } else {
            $this->info('No credits to remind about.');
        }
    }
}