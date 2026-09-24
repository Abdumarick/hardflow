<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner\CatalogueController;
use App\Http\Controllers\Owner\CustomerController;
use App\Http\Controllers\Owner\CommitmentController;
use App\Http\Controllers\Owner\ExpenseController;
use App\Http\Controllers\Owner\GovernanceController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\PaymentController;
use App\Http\Controllers\Owner\PurchaseController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\RoleController;
use App\Http\Controllers\Owner\SalesController;
use App\Http\Controllers\Owner\SettingsController;
use App\Http\Controllers\Owner\StaffController;
use App\Http\Controllers\SuperAdmin\BusinessController;
use App\Http\Controllers\TenantSelectionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'selected.locale'])
    ->name('dashboard');

Route::put('dashboard/appearance', [DashboardController::class, 'updateAppearance'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard.appearance.update');

Route::put('interface/locale', [DashboardController::class, 'updateLocale'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('interface.locale.update');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::post('tenant/{business}', TenantSelectionController::class)->name('tenant.select');

    Route::middleware('can:platform.manage-businesses')->prefix('admin')->name('super-admin.')->group(function () {
        Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('businesses/create', [BusinessController::class, 'create'])->name('businesses.create');
        Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');
        Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::post('businesses/{business}/disable', [BusinessController::class, 'disable'])->name('businesses.disable');
    });

    Route::middleware(['tenant', 'tenant.locale'])->prefix('manage')->name('owner.')->group(function () {
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::post('staff/{staff}/disable', [StaffController::class, 'disable'])->name('staff.disable');
        Route::post('staff/{staff}/enable', [StaffController::class, 'enable'])->name('staff.enable');
        Route::put('staff/{staff}/access', [StaffController::class, 'updateAccess'])->name('staff.access.update');
        Route::put('staff/{staff}/details', [StaffController::class, 'updateDetails'])->name('staff.details.update');
        Route::put('staff/{staff}/password', [StaffController::class, 'resetPassword'])->name('staff.password.reset');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
            ->name('roles.permissions.update');

        Route::get('catalogue', [CatalogueController::class, 'index'])->name('catalogue.index');
        Route::post('catalogue/import', [CatalogueController::class, 'import'])->name('catalogue.import');
        Route::post('catalogue/import/units', [CatalogueController::class, 'storeImportUnit'])->name('catalogue.import.units.store');
        Route::post('catalogue/import/continue', [CatalogueController::class, 'continueImportWithMissingUnits'])->name('catalogue.import.continue');
        Route::post('catalogue/import/approve', [CatalogueController::class, 'approveImport'])->name('catalogue.import.approve');
        Route::delete('catalogue/import', [CatalogueController::class, 'rejectImport'])->name('catalogue.import.reject');
        Route::get('catalogue/import-template', [CatalogueController::class, 'importTemplate'])->name('catalogue.import-template');
        Route::get('catalogue/export', [CatalogueController::class, 'export'])->name('catalogue.export');
        Route::post('catalogue/categories', [CatalogueController::class, 'storeCategory'])->name('catalogue.categories.store');
        Route::put('catalogue/categories/{category}', [CatalogueController::class, 'updateCategory'])->name('catalogue.categories.update');
        Route::post('catalogue/brands', [CatalogueController::class, 'storeBrand'])->name('catalogue.brands.store');
        Route::post('catalogue/units', [CatalogueController::class, 'storeUnit'])->name('catalogue.units.store');
        Route::post('catalogue/price-levels', [CatalogueController::class, 'storePriceLevel'])->name('catalogue.price-levels.store');
        Route::post('catalogue/products', [CatalogueController::class, 'storeProduct'])->name('catalogue.products.store');
        Route::put('catalogue/products/{product}', [CatalogueController::class, 'updateProduct'])->name('catalogue.products.update');
        Route::put('catalogue/products/{product}/picture', [CatalogueController::class, 'updateProductPicture'])->name('catalogue.products.picture');
        Route::put('catalogue/products/{product}/active', [CatalogueController::class, 'setProductActive'])->name('catalogue.products.active');
        Route::post('catalogue/products/{product}/units', [CatalogueController::class, 'storeProductUnit'])->name('catalogue.product-units.store');
        Route::post('catalogue/products/{product}/prices', [CatalogueController::class, 'storePrice'])->name('catalogue.prices.store');
        Route::put('catalogue/products/{product}/minimum-stock', [CatalogueController::class, 'storeMinimumStock'])->name('catalogue.minimum-stock.store');
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/opening-stock', [InventoryController::class, 'opening'])->name('inventory.opening.store');
        Route::post('inventory/adjustments', [InventoryController::class, 'requestAdjustment'])->name('inventory.adjustments.store');
        Route::put('inventory/adjustments/{adjustment}/decision', [InventoryController::class, 'decide'])->name('inventory.adjustments.decide');
        Route::post('inventory/counts', [InventoryController::class, 'startCount'])->name('inventory.counts.store');
        Route::put('inventory/counts/{count}/complete', [InventoryController::class, 'completeCount'])->name('inventory.counts.complete');
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::post('purchases/suppliers', [PurchaseController::class, 'storeSupplier'])->name('purchases.suppliers.store');
        Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::post('purchases/{purchase}/order', [PurchaseController::class, 'order'])->name('purchases.order');
        Route::post('purchases/{purchase}/receipts', [PurchaseController::class, 'receive'])->name('purchases.receipts.store');
        Route::post('purchases/receipts/{receipt}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.receipts.confirm');
        Route::post('purchases/{purchase}/returns', [PurchaseController::class, 'requestReturn'])->name('purchases.returns.store');
        Route::put('purchases/returns/{return}/decision', [PurchaseController::class, 'decideReturn'])->name('purchases.returns.decision');
        Route::post('purchases/suppliers/{supplier}/payments', [PurchaseController::class, 'recordPayment'])->name('purchases.suppliers.payments.store');
        Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
        Route::post('sales/{sale}/borrow-neighbour-stock', [SalesController::class, 'borrowNeighbourStock'])->name('sales.borrow-neighbour-stock');
        Route::post('sales/neighbour-borrows/{borrow}/return', [SalesController::class, 'returnNeighbourStock'])->name('sales.neighbour-borrows.return');
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('commitments', [CommitmentController::class, 'index'])->name('commitments.index');
        Route::get('layaway', [CommitmentController::class, 'layaway'])->name('layaway.index');
        Route::get('projects', [CommitmentController::class, 'projects'])->name('projects.index');
        Route::get('layaway/{plan}', [CommitmentController::class, 'showLayaway'])->name('layaway.show');
        Route::get('projects/{project}', [CommitmentController::class, 'showProject'])->name('projects.show');
        Route::post('commitments/layaways', [CommitmentController::class, 'storeLayaway'])->name('commitments.layaways.store');
        Route::post('commitments/layaways/{plan}/payments', [CommitmentController::class, 'recordLayawayPayment'])->name('commitments.layaways.payments.store');
        Route::patch('commitments/layaways/{plan}/target', [CommitmentController::class, 'increaseLayawayTarget'])->name('commitments.layaways.target.increase');
        Route::delete('commitments/layaways/{plan}', [CommitmentController::class, 'destroyLayaway'])->name('commitments.layaways.destroy');
        Route::post('commitments/layaways/{plan}/items', [CommitmentController::class, 'storeLayawayItem'])->name('commitments.layaways.items.store');
        Route::post('commitments/layaway-items', [CommitmentController::class, 'storeSelectedLayawayItem'])->name('commitments.layaways.items.selected');
        Route::post('commitments/layaways/{plan}/collect', [CommitmentController::class, 'collectLayaway'])->name('commitments.layaways.collect');
        Route::post('commitments/projects', [CommitmentController::class, 'storeProject'])->name('commitments.projects.store');
        Route::post('commitments/projects/{project}/requests', [CommitmentController::class, 'storeRequest'])->name('commitments.projects.requests.store');
        Route::post('commitments/projects/{project}/payments', [CommitmentController::class, 'recordProjectPayment'])->name('commitments.projects.payments.store');
        Route::post('commitments/projects/{project}/materials', [CommitmentController::class, 'issueMaterial'])->name('commitments.projects.materials.store');
        Route::post('commitments/projects/{project}/complete', [CommitmentController::class, 'completeProject'])->name('commitments.projects.complete');
        Route::delete('commitments/projects/{project}', [CommitmentController::class, 'destroyProject'])->name('commitments.projects.destroy');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::put('customers/{customer}/active', [CustomerController::class, 'setActive'])->name('customers.active');
        Route::put('customers/sales/{sale}', [CustomerController::class, 'updateSale'])->name('customers.sales.update');
        Route::delete('customers/sales/{sale}', [CustomerController::class, 'destroySale'])->name('customers.sales.destroy');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::post('expenses/{expense}/submit', [ExpenseController::class, 'submit'])->name('expenses.submit');
        Route::get('approvals', [GovernanceController::class, 'approvals'])->name('approvals.index');
        Route::put('approvals/{approval}/decision', [GovernanceController::class, 'decide'])->name('approvals.decision');
        Route::get('notifications', [GovernanceController::class, 'notifications'])->name('notifications.index');
        Route::put('notifications/{notification}/read', [GovernanceController::class, 'readNotification'])->name('notifications.read');
        Route::get('audit', [GovernanceController::class, 'audit'])->name('audit.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/print', [ReportController::class, 'printable'])->name('reports.print');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('reports/spreadsheet', [ReportController::class, 'spreadsheet'])->name('reports.spreadsheet');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business.update');
        Route::put('settings/branches/{branch}', [SettingsController::class, 'updateBranch'])->name('settings.branches.update');
        Route::put('expenses/approvals/{approval}/decision', [ExpenseController::class, 'decide'])->name('expenses.approvals.decision');
        Route::post('expenses/{expense}/post', [ExpenseController::class, 'post'])->name('expenses.post');
        Route::post('expenses/{expense}/reverse', [ExpenseController::class, 'reverse'])->name('expenses.reverse');
        Route::post('payments/accounts', [PaymentController::class, 'storeAccount'])->name('payments.accounts.store');
        Route::put('payments/credit-policy', [PaymentController::class, 'updateCreditPolicy'])->name('payments.credit-policy.update');
        Route::post('payments/customers/{customer}', [PaymentController::class, 'store'])->name('payments.store');
        Route::post('payments/sales/{sale}', [PaymentController::class, 'storeSalePayment'])->name('payments.sales.store');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
        Route::post('payments/transfers', [PaymentController::class, 'requestTransfer'])->name('payments.transfers.store');
        Route::put('payments/transfers/{transfer}/decision', [PaymentController::class, 'decideTransfer'])->name('payments.transfers.decision');
        Route::post('payments/cash-sessions', [PaymentController::class, 'openCashSession'])->name('payments.cash-sessions.store');
        Route::put('payments/cash-sessions/{session}/close', [PaymentController::class, 'closeCashSession'])->name('payments.cash-sessions.close');
        Route::get('payments/customers/{customer}/statement', [PaymentController::class, 'statement'])->name('payments.customers.statement');
        Route::post('sales/customers', [SalesController::class, 'storeCustomer'])->name('sales.customers.store');
        Route::post('sales', [SalesController::class, 'storeSale'])->name('sales.store');
        Route::get('sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
        Route::post('sales/{sale}/checkout', [SalesController::class, 'completeCheckout'])->name('sales.checkout');
        Route::post('sales/{sale}/confirm', [SalesController::class, 'confirmSale'])->name('sales.confirm');
        Route::post('sales/{sale}/cancel', [SalesController::class, 'cancelSale'])->name('sales.cancel');
        Route::post('sales/{sale}/returns', [SalesController::class, 'requestReturn'])->name('sales.returns.store');
        Route::put('sales/returns/{return}/decision', [SalesController::class, 'decideReturn'])->name('sales.returns.decision');
        Route::post('sales/quotations', [SalesController::class, 'storeQuotation'])->name('sales.quotations.store');
        Route::post('sales/quotations/{quotation}/convert', [SalesController::class, 'convert'])->name('sales.quotations.convert');
        Route::post('sales/{sale}/releases', [SalesController::class, 'release'])->name('sales.releases.store');
        Route::post('sales/releases/{release}/confirm', [SalesController::class, 'confirmRelease'])->name('sales.releases.confirm');
        Route::get('sales/{sale}/print', [SalesController::class, 'printSale'])->name('sales.print');
    });
});

require __DIR__.'/auth.php';
