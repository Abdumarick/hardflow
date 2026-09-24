<?php

namespace App\Enums;

enum PermissionName: string
{
    case BusinessesView = 'businesses.view';
    case BusinessesUpdate = 'businesses.update';
    case BranchesView = 'branches.view';
    case BranchesCreate = 'branches.create';
    case BranchesUpdate = 'branches.update';
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDisable = 'users.disable';
    case UsersManageRoles = 'users.manage_roles';
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesAssign = 'roles.assign';
    case SettingsView = 'settings.view';
    case SettingsUpdate = 'settings.update';
    case AuditView = 'audit.view';
    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsChangePrice = 'products.change_price';
    case InventoryView = 'inventory.view';
    case InventoryReceive = 'inventory.receive';
    case InventoryOpening = 'inventory.opening';
    case InventoryAdjustRequest = 'inventory.adjust.request';
    case InventoryAdjustApprove = 'inventory.adjust.approve';
    case PurchasesView = 'purchases.view';
    case PurchasesCreate = 'purchases.create';
    case PurchasesUpdate = 'purchases.update';
    case PurchasesOrder = 'purchases.order';
    case PurchasesReceive = 'purchases.receive';
    case PurchasesConfirmReceipt = 'purchases.confirm_receipt';
    case PurchasesReturnRequest = 'purchases.return.request';
    case PurchasesReturnApprove = 'purchases.return.approve';
    case SuppliersView = 'suppliers.view';
    case SuppliersCreate = 'suppliers.create';
    case SuppliersUpdate = 'suppliers.update';
    case CustomersView = 'customers.view';
    case CustomersCreate = 'customers.create';
    case CustomersUpdate = 'customers.update';
    case QuotationsView = 'quotations.view';
    case QuotationsCreate = 'quotations.create';
    case QuotationsUpdate = 'quotations.update';
    case QuotationsConvert = 'quotations.convert';
    case SalesView = 'sales.view';
    case SalesCreate = 'sales.create';
    case SalesConfirm = 'sales.confirm';
    case SalesRelease = 'sales.release';
    case SalesOverridePrice = 'sales.override_price';
    case SalesViewProfit = 'sales.view_profit';
    case SalesCancel = 'sales.cancel';
    case SalesConfirmRelease = 'sales.confirm_release';
    case SalesReturnRequest = 'sales.return.request';
    case SalesReturnApprove = 'sales.return.approve';
    case PaymentsView = 'payments.view';
    case PaymentsCreate = 'payments.create';
    case PaymentsReverse = 'payments.reverse';
    case PaymentAccountsManage = 'payments.accounts.manage';
    case AccountTransfersCreate = 'payments.transfers.create';
    case AccountTransfersApprove = 'payments.transfers.approve';
    case CashSessionsManage = 'payments.cash_sessions.manage';
    case ExpensesView = 'expenses.view';
    case ExpensesCreate = 'expenses.create';
    case ExpensesSubmit = 'expenses.submit';
    case ExpensesApprove = 'expenses.approve';
    case ExpensesPost = 'expenses.post';
    case ExpensesReverse = 'expenses.reverse';
    case ApprovalsView = 'approvals.view';
    case ApprovalsDecide = 'approvals.decide';
    case NotificationsView = 'notifications.view';
    case ReportsView = 'reports.view';
    case ReportsProfit = 'reports.profit';

    public function module(): string
    {
        return str($this->value)->before('.')->toString();
    }

    public function label(): string
    {
        return str($this->value)->replace('.', ' ')->headline()->toString();
    }
}
