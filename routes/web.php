<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CreditPaymentController;
use App\Http\Controllers\CreditPurchaseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReturController;
use App\Http\Controllers\StockFlowController;
use App\Http\Controllers\StokOpnameScheduleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
});

Route::get('/', function () {
    if (Auth::check()) {
        // User is authenticated
        return redirect('/home');
    } else {
        // User is not authenticated
        return redirect('/login');
    }
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/view/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/pos', [TransactionController::class, 'showPOS'])->name('transactions.create');
    Route::post('/pos', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/view/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/temp/delete-with-auth', [TransactionController::class, 'deleteTempWithAuth'])->name('transactions.temp.deleteWithAuth');
    Route::post('/transactions/authorize/credit', [TransactionController::class, 'authorizeSupervisorCredit'])->name('transactions.auth.credit');
    Route::get('/transaction/temp-transaction', [TransactionController::class, 'getTempTransaction'])->name('transactions.temp');
    Route::post('/transaction/temp-transaction', [TransactionController::class, 'storeTemp'])->name('transactions.temp.store');
    Route::get('/latest-products', [ProductController::class, 'getLatestProducts'])->name('get.products');
    Route::get('/get-customers', [CustomerController::class, 'getCustomer'])->name('get.customers');
    Route::get('/print/{transaction}', [TransactionController::class, 'print'])->name('transactions.print');
    Route::get('/credit/remaining', [CreditPayment::class, 'remaining'])->name('credit.remaining');

    Route::middleware(['auth', 'owner'])->group(function () {
        Route::post('/products/create', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::get('/products/view/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/view/{product}/edit', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}/delete', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{id}/delete', [TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('/transactions/{transaction}/credit-payments', [CreditPaymentController::class, 'store'])->name('credit-payments.store');

        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases/create', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/view/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{id}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
        Route::put('/purchases/{id}/edit', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::post('/purchases/{purchase}/credit-payments', [CreditPurchaseController::class, 'store'])->name('credit-purchase.store');
        Route::get('/get-suppliers', [SupplierController::class, 'getSupplier'])->name('get.suppliers');
        Route::get('/purchase/temp-purchase', [PurchaseController::class, 'getTempPurchase'])->name('purchases.temp');
        Route::post('/purchase/temp-purchase', [PurchaseController::class, 'storeTemp'])->name('purchases.temp.store');
        Route::post('/purchase/temp-delete', [PurchaseController::class, 'deleteTemp'])->name('purchases.temp.delete');

        Route::get('/credit/customer', [CreditPaymentController::class, 'index'])->name('customers.credits.index');
        Route::get('/credit/supplier', [CreditPurchaseController::class, 'index'])->name('suppliers.credits.index');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers/create', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}/edit', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{id}/delete', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('/supplier', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers/create', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}/edit', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{id}/delete', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/returs', [ReturController::class, 'index'])->name('returs.index');
        Route::get('/transactions/{transaction}/retur', [ReturController::class, 'createTransaction'])->name('returs.create.transaction');
        Route::get('/purchases/{purchase}/retur', [ReturController::class, 'createPurchase'])->name('returs.create.purchase');
        Route::post('/transactions/retur', [ReturController::class, 'storeTransaction'])->name('returs.store.transaction');
        Route::post('/purchases/retur', [ReturController::class, 'storePurchase'])->name('returs.store.purchase');
        Route::get('/returs/{id}', [ReturController::class, 'show'])->name('returs.show');

        Route::get('/opnames', [StokOpnameScheduleController::class, 'index'])->name('opnames.index');
        Route::get('/opnames/{stockOpnameSchedule}', [StokOpnameScheduleController::class, 'show'])->name('opnames.show');
        Route::post('/opnames', [StokOpnameScheduleController::class, 'store'])->name('opnames.store');
        Route::post('/opnames/{id}', [StokOpnameScheduleController::class, 'storeDetail'])->name('opnames.storeDetail');

        Route::get('/cashflow', [CashFlowController::class, 'index'])->name('reports.salesIndex');
        Route::get('/cashflow/export', [CashFlowController::class, 'exportCashflowDetail'])->name('cashflow.export');

        Route::get('/stockflow', [StockFlowController::class, 'index'])->name('reports.stockIndex');
        Route::get('/stats/top-selling', [StockFlowController::class, 'topSellingProducts'])->name('stats.topSelling');
        Route::get('/stats/product-movement', [StockFlowController::class, 'productMovement'])->name('stats.productMovement');
        Route::post('report/stock/export', [StockFlowController::class, 'export'])->name('report.stock.export');

        Route::get('/forecast',  [ForecastController::class, 'index'])->name('forecast.index');
        Route::post('/forecast', [ForecastController::class, 'index']);

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        // Route::delete('/users/{users}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/activity-logs', [ActivityController::class, 'index'])->name('activity.logs');

        Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
        Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
    });
});
