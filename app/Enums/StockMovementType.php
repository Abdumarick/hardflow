<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OpeningStock = 'opening_stock';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case PurchaseReceipt = 'purchase_receipt';
    case SaleRelease = 'sale_release';
    case CustomerReturn = 'customer_return';
    case SupplierReturn = 'supplier_return';
    case Damage = 'damage';
    case Expiry = 'expiry';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
}
