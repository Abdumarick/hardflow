'use client';
import React, { useState, useMemo } from 'react';
import AppLayout, { DashboardLayout } from '@/components/AppLayout';
import ProductFilterBar, { ProductFilters } from './components/ProductFilterBar';
import ProductTable, { ProductRow } from './components/ProductTable';
import ProductDetailDrawer from './components/ProductDetailDrawer';
import { Plus, Download, Upload, BarChart2, Package } from 'lucide-react';
import { toast } from 'sonner';

// Backend integration point: GET /api/products with pagination, search, filter params

const allProducts: ProductRow[] = [
  { id: 'prod-001', name: 'Portland Cement 50kg', sku: 'CEM-50-PORT', barcode: '6001234560001', category: 'Cement', brand: 'Bamburi', unit: 'bag', retailPrice: 28500, wholesalePrice: 27000, costPrice: 24000, stock: 248, minStock: 50, status: 'active' },
  { id: 'prod-002', name: 'Iron Sheet 26G 8ft', sku: 'IRN-26-8FT', barcode: '6001234560002', category: 'Iron Sheets', brand: 'Mabati Rolling Mills', unit: 'sheet', retailPrice: 42000, wholesalePrice: 39500, costPrice: 35000, stock: 94, minStock: 30, status: 'active' },
  { id: 'prod-003', name: 'Round Bar 12mm x 6m', sku: 'RBR-12-6M', barcode: '6001234560003', category: 'Steel', brand: 'Steel Structures', unit: 'piece', retailPrice: 38000, wholesalePrice: 35500, costPrice: 31000, stock: 42, minStock: 20, status: 'active' },
  { id: 'prod-004', name: 'PVC Pipe 4" x 6m', sku: 'PVC-4-6M', barcode: '6001234560004', category: 'Pipes', brand: 'Boresha Pipes', unit: 'piece', retailPrice: 22500, wholesalePrice: 20000, costPrice: 17000, stock: 6, minStock: 20, status: 'active' },
  { id: 'prod-005', name: 'Binding Wire 2kg', sku: 'BWR-2KG', barcode: '6001234560005', category: 'Steel', brand: 'Kenya Wire', unit: 'roll', retailPrice: 8500, wholesalePrice: 7800, costPrice: 6500, stock: 12, minStock: 30, status: 'active' },
  { id: 'prod-006', name: 'Roofing Nail 4" 5kg', sku: 'NAL-4-5KG', barcode: '6001234560006', category: 'Tools', brand: 'General', unit: 'packet', retailPrice: 12000, wholesalePrice: 11000, costPrice: 9000, stock: 67, minStock: 20, status: 'active' },
  { id: 'prod-007', name: 'Paint Primer 20L', sku: 'PNT-PRM-20', barcode: '6001234560007', category: 'Paint', brand: 'Crown Paints', unit: 'tin', retailPrice: 85000, wholesalePrice: 79000, costPrice: 68000, stock: 4, minStock: 15, status: 'active' },
  { id: 'prod-008', name: 'Timber 2x4 12ft', sku: 'TMB-2X4-12', barcode: '6001234560008', category: 'Timber', brand: 'Local', unit: 'piece', retailPrice: 14500, wholesalePrice: 13000, costPrice: 10500, stock: 130, minStock: 30, status: 'active' },
  { id: 'prod-009', name: 'Electrical Conduit 20mm', sku: 'ELC-20MM', barcode: '6001234560009', category: 'Electrical', brand: 'Clipsal', unit: 'piece', retailPrice: 4500, wholesalePrice: 4000, costPrice: 3200, stock: 88, minStock: 30, status: 'active' },
  { id: 'prod-010', name: 'Ball Valve 1/2"', sku: 'BLV-HALF', barcode: '6001234560010', category: 'Plumbing', brand: 'Flomatic', unit: 'piece', retailPrice: 6500, wholesalePrice: 5800, costPrice: 4800, stock: 55, minStock: 20, status: 'active' },
  { id: 'prod-011', name: 'Iron Sheet 28G 10ft', sku: 'IRN-28-10FT', barcode: '6001234560011', category: 'Iron Sheets', brand: 'Mabati Rolling Mills', unit: 'sheet', retailPrice: 48000, wholesalePrice: 45000, costPrice: 39000, stock: 72, minStock: 25, status: 'active' },
  { id: 'prod-012', name: 'CCA Treated Post 4x4 6ft', sku: 'TMB-4X4-6', barcode: '6001234560012', category: 'Timber', brand: 'Local', unit: 'piece', retailPrice: 18000, wholesalePrice: 16500, costPrice: 13000, stock: 45, minStock: 20, status: 'active' },
  { id: 'prod-013', name: 'Paint Emulsion 20L White', sku: 'PNT-EMU-20W', barcode: '6001234560013', category: 'Paint', brand: 'Crown Paints', unit: 'tin', retailPrice: 92000, wholesalePrice: 86000, costPrice: 74000, stock: 22, minStock: 10, status: 'active' },
  { id: 'prod-014', name: 'Square Bar 10mm x 6m', sku: 'SBR-10-6M', barcode: '6001234560014', category: 'Steel', brand: 'Steel Structures', unit: 'piece', retailPrice: 32000, wholesalePrice: 29500, costPrice: 25000, stock: 38, minStock: 15, status: 'active' },
  { id: 'prod-015', name: 'PPRC Pipe 25mm x 4m', sku: 'PPR-25-4M', barcode: '6001234560015', category: 'Plumbing', brand: 'Wavin', unit: 'piece', retailPrice: 15500, wholesalePrice: 14000, costPrice: 11500, stock: 34, minStock: 15, status: 'active' },
  { id: 'prod-016', name: 'Circuit Breaker 32A', sku: 'CB-32A', barcode: '6001234560016', category: 'Electrical', brand: 'Schneider', unit: 'piece', retailPrice: 28000, wholesalePrice: 25000, costPrice: 20000, stock: 19, minStock: 10, status: 'active' },
  { id: 'prod-017', name: 'Concrete Blocks 6"', sku: 'BLK-6IN', barcode: '6001234560017', category: 'Cement', brand: 'Local', unit: 'piece', retailPrice: 1200, wholesalePrice: 1050, costPrice: 850, stock: 1250, minStock: 200, status: 'active' },
  { id: 'prod-018', name: 'Angle Iron 40x40x3mm', sku: 'ANG-40-3', barcode: '6001234560018', category: 'Steel', brand: 'Steel Structures', unit: 'piece', retailPrice: 28500, wholesalePrice: 26500, costPrice: 22000, stock: 0, minStock: 15, status: 'active' },
  { id: 'prod-019', name: 'Gypsum Board 12mm', sku: 'GYP-12MM', barcode: '6001234560019', category: 'Roofing', brand: 'Gyproc', unit: 'sheet', retailPrice: 35000, wholesalePrice: 32000, costPrice: 27000, stock: 28, minStock: 10, status: 'active' },
  { id: 'prod-020', name: 'Claw Hammer 16oz', sku: 'HMR-CLW-16', barcode: '6001234560020', category: 'Tools', brand: 'Stanley', unit: 'piece', retailPrice: 18500, wholesalePrice: 16500, costPrice: 13000, stock: 31, minStock: 10, status: 'active' },
  { id: 'prod-021', name: 'Gate Valve 1"', sku: 'GTV-1IN', barcode: '6001234560021', category: 'Plumbing', brand: 'Flomatic', unit: 'piece', retailPrice: 9500, wholesalePrice: 8500, costPrice: 6800, stock: 42, minStock: 15, status: 'inactive' },
  { id: 'prod-022', name: 'Wire Mesh 50x50 6ft', sku: 'WMS-50-6FT', barcode: '6001234560022', category: 'Steel', brand: 'Kenya Wire', unit: 'roll', retailPrice: 85000, wholesalePrice: 80000, costPrice: 68000, stock: 15, minStock: 8, status: 'active' },
  { id: 'prod-023', name: 'Door Hinge 4" Heavy', sku: 'HNG-4-HVY', barcode: '6001234560023', category: 'Fasteners', brand: 'General', unit: 'pair', retailPrice: 4500, wholesalePrice: 3800, costPrice: 3000, stock: 120, minStock: 30, status: 'active' },
  { id: 'prod-024', name: 'Ceiling Board 6mm', sku: 'CLG-6MM', barcode: '6001234560024', category: 'Timber', brand: 'Local', unit: 'sheet', retailPrice: 22000, wholesalePrice: 20000, costPrice: 16500, stock: 58, minStock: 20, status: 'active' },
  { id: 'prod-025', name: 'Galvanized Pipe 1" x 6m', sku: 'GPP-1IN-6M', barcode: '6001234560025', category: 'Pipes', brand: 'Boresha Pipes', unit: 'piece', retailPrice: 32000, wholesalePrice: 29500, costPrice: 24500, stock: 27, minStock: 12, status: 'active' },
];

export default function ProductManagementPage() {
  const [layout] = useState<DashboardLayout>('classic');
  const [filters, setFilters] = useState<ProductFilters>({
    search: '', category: '', brand: '', stockStatus: '', priceLevel: '', branch: '',
  });
  const [selectedProductId, setSelectedProductId] = useState<string | null>(null);

  const filtered = useMemo(() => {
    return allProducts.filter(p => {
      if (filters.search) {
        const q = filters.search.toLowerCase();
        if (!p.name.toLowerCase().includes(q) && !p.sku.toLowerCase().includes(q) && !p.barcode.includes(q)) return false;
      }
      if (filters.category && p.category !== filters.category) return false;
      if (filters.brand && p.brand !== filters.brand) return false;
      if (filters.stockStatus) {
        if (filters.stockStatus === 'In Stock' && (p.stock === 0 || p.stock <= p.minStock)) return false;
        if (filters.stockStatus === 'Low Stock' && !(p.stock > 0 && p.stock <= p.minStock)) return false;
        if (filters.stockStatus === 'Out of Stock' && p.stock !== 0) return false;
      }
      if (filters.branch) { /* branch filter — backend will handle */ }
      return true;
    });
  }, [filters]);

  const selectedProduct = selectedProductId ? allProducts.find(p => p.id === selectedProductId) || null : null;

  const handleDelete = (id: string) => {
    // Backend integration point: DELETE /api/products/:id
    toast.success('Product deleted successfully');
  };

  const handleEdit = (id: string) => {
    toast.info(`Opening edit form for product ${id}`);
  };

  return (
    <AppLayout layout={layout}>
      <div className="space-y-4">
        {/* Page header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Product Management</h1>
            <p className="text-sm text-muted-foreground mt-0.5">
              Manage your product catalogue · {allProducts.length.toLocaleString()} products
            </p>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            <button className="btn-ghost text-sm gap-1.5">
              <BarChart2 size={15} /> Analytics
            </button>
            <button className="btn-secondary text-sm gap-1.5">
              <Upload size={15} /> Import
            </button>
            <button className="btn-secondary text-sm gap-1.5">
              <Download size={15} /> Export
            </button>
            <button className="btn-primary text-sm gap-1.5">
              <Plus size={15} /> Add Product
            </button>
          </div>
        </div>

        {/* Summary KPIs */}
        <div className="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-3">
          {[
            { label: 'Total Products', value: allProducts.length.toLocaleString(), icon: <Package size={16} className="text-primary" />, bg: 'bg-primary/10' },
            { label: 'Active Products', value: allProducts.filter(p => p.status === 'active').length.toLocaleString(), icon: <Package size={16} className="text-success" />, bg: 'bg-success/10' },
            { label: 'Low / Out of Stock', value: allProducts.filter(p => p.stock <= p.minStock).length.toLocaleString(), icon: <Package size={16} className="text-warning" />, bg: 'bg-warning/10', alert: true },
            { label: 'Total Stock Value', value: `TZS ${(allProducts.reduce((s, p) => s + p.stock * p.costPrice, 0) / 1_000_000).toFixed(1)}M`, icon: <Package size={16} className="text-info" />, bg: 'bg-info/10' },
          ].map(kpi => (
            <div key={`pm-kpi-${kpi.label}`} className={`card p-3 flex items-center gap-3 ${kpi.alert ? 'border-warning/30 bg-warning/5' : ''}`}>
              <div className={`w-9 h-9 rounded-lg ${kpi.bg} flex items-center justify-center shrink-0`}>
                {kpi.icon}
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">{kpi.label}</p>
                <p className="text-lg font-bold text-foreground tabular-nums">{kpi.value}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Filter bar */}
        <ProductFilterBar
          filters={filters}
          onFiltersChange={setFilters}
          totalCount={allProducts.length}
          filteredCount={filtered.length}
        />

        {/* Table */}
        <ProductTable
          products={filtered}
          onView={id => setSelectedProductId(id)}
          onEdit={handleEdit}
          onDelete={handleDelete}
        />
      </div>

      {/* Detail drawer */}
      {selectedProductId && (
        <ProductDetailDrawer
          product={selectedProduct}
          onClose={() => setSelectedProductId(null)}
          onEdit={handleEdit}
        />
      )}
    </AppLayout>
  );
}