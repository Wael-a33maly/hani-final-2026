<?php
/**
 * routes/web.php - تعريف مسارات التطبيق
 */
require_once __DIR__ . '/../app/Core/Router.php';

$router = new Router();

// ========== المرحلة 1: المسارات الأساسية ==========

// شاشة الدخول
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// ملف manifest الديناميكي لـ PWA
$router->get('/public/manifest.json', 'SettingsController@manifest');

// الداشبورد (يتطلب تسجيل دخول)
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// المستخدمون
$router->get('/users', 'UserController@index');
$router->get('/users/create', 'UserController@create');
$router->post('/users/store', 'UserController@store');
$router->get('/users/edit/:id', 'UserController@edit');
$router->post('/users/update/:id', 'UserController@update');
$router->post('/users/delete/:id', 'UserController@delete');

// الفروع
$router->get('/branches', 'BranchController@index');
$router->get('/branches/create', 'BranchController@create');
$router->post('/branches/store', 'BranchController@store');
$router->get('/branches/edit/:id', 'BranchController@edit');
$router->post('/branches/update/:id', 'BranchController@update');
$router->post('/branches/delete/:id', 'BranchController@delete');

// الإعدادات (المرحلة 2)
$router->get('/settings', 'SettingsController@index');
$router->post('/settings/update-company', 'SettingsController@updateCompany');
$router->post('/settings/upload-logo', 'SettingsController@uploadLogo');
$router->get('/settings/backup', 'SettingsController@backup');
$router->post('/settings/export-backup', 'SettingsController@exportBackup');
$router->post('/settings/import-backup', 'SettingsController@importBackup');
$router->get('/settings/wipe', 'SettingsController@wipe');
$router->post('/settings/execute-wipe', 'SettingsController@executeWipe');
$router->get('/settings/update-settings', 'SettingsController@updateSettings');
$router->post('/settings/update-settings', 'SettingsController@saveUpdateSettings');

// المخازن
$router->get('/warehouses', 'WarehouseController@index');
$router->get('/warehouses/create', 'WarehouseController@create');
$router->post('/warehouses/store', 'WarehouseController@store');
$router->get('/warehouses/edit/:id', 'WarehouseController@edit');
$router->post('/warehouses/update/:id', 'WarehouseController@update');
$router->post('/warehouses/delete/:id', 'WarehouseController@delete');

// الوحدات
$router->get('/units', 'UnitController@index');
$router->post('/units/store', 'UnitController@store');
$router->post('/units/delete/:id', 'UnitController@delete');

// المواد (المنتجات)
$router->get('/products', 'ProductController@index');
$router->get('/products/create', 'ProductController@create');
$router->post('/products/store', 'ProductController@store');
$router->get('/products/edit/:id', 'ProductController@edit');
$router->post('/products/update/:id', 'ProductController@update');
$router->post('/products/delete/:id', 'ProductController@delete');
$router->get('/products/search', 'ProductController@search');

// الموردين
$router->get('/suppliers', 'SupplierController@index');
$router->get('/suppliers/create', 'SupplierController@create');
$router->post('/suppliers/store', 'SupplierController@store');
$router->get('/suppliers/edit/:id', 'SupplierController@edit');
$router->post('/suppliers/update/:id', 'SupplierController@update');
$router->get('/suppliers/delete/:id', 'SupplierController@delete');
$router->get('/suppliers/statement/:id', 'SupplierController@statement');
$router->get('/suppliers/search', 'SupplierController@search');

// مدفوعات الموردين
$router->get('/supplier-payments', 'SupplierPaymentController@index');
$router->get('/supplier-payments/create', 'SupplierPaymentController@create');
$router->post('/supplier-payments/store', 'SupplierPaymentController@store');
$router->post('/supplier-payments/delete/:id', 'SupplierPaymentController@delete');

// المشتريات
$router->get('/purchases', 'PurchaseController@index');
$router->get('/purchases/create', 'PurchaseController@create');
$router->post('/purchases/store', 'PurchaseController@store');
$router->get('/purchases/show/:id', 'PurchaseController@show');

// العملاء
$router->get('/customers', 'CustomerController@index');
$router->get('/customers/create', 'CustomerController@create');
$router->post('/customers/store', 'CustomerController@store');
$router->get('/customers/edit/:id', 'CustomerController@edit');
$router->post('/customers/update/:id', 'CustomerController@update');
$router->post('/customers/delete/:id', 'CustomerController@delete');
$router->get('/customers/statement/:id', 'CustomerController@statement');
$router->get('/customers/search', 'CustomerController@search');
$router->get('/customers/bulk-create', 'CustomerController@bulkCreate');
$router->post('/customers/bulk-store', 'CustomerController@bulkStore');
$router->get('/customers/import', 'CustomerController@importForm');
$router->post('/customers/import', 'CustomerController@importStore');
$router->get('/customers/import-sample', 'CustomerController@downloadSample');

// المبيعات
$router->get('/sales', 'SalesController@index');
$router->get('/sales/create', 'SalesController@create');
$router->post('/sales/store', 'SalesController@store');
$router->get('/sales/edit/:id', 'SalesController@edit');
$router->post('/sales/update/:id', 'SalesController@update');
$router->get('/sales/show/:id', 'SalesController@show');
$router->get('/sales/customer/:id', 'SalesController@getCustomerData');

// الأقساط (المرحلة 4)
$router->get('/installments', 'InstallmentController@index');
$router->post('/installments/pay/:id', 'InstallmentController@pay');
$router->post('/installments/pay-multiple', 'InstallmentController@payMultiple');
$router->post('/installments/delete/:id', 'InstallmentController@delete');
$router->get('/installments/view-invoice/:id', 'InstallmentController@viewInvoice');

// التقارير والمخازن المتقدمة
$router->get('/reports', 'ReportController@index');
$router->get('/reports/product-stock', 'ReportController@productStock');
$router->get('/reports/product-movements', 'ReportController@productMovements');
$router->get('/reports/warehouse-stock', 'ReportController@warehouseStock');
$router->get('/reports/branch-stock', 'ReportController@branchStock');
$router->get('/reports/transfers', 'ReportController@transfers');
$router->get('/reports/transfer-form', 'ReportController@createTransferForm');
$router->post('/reports/transfer-store', 'ReportController@storeTransfer');

// عهدة المندوبين (المهمة 5)
$router->get('/salesrep', 'SalesRepController@index');
$router->get('/salesrep/assign', 'SalesRepController@assignForm');
$router->post('/salesrep/assign', 'SalesRepController@assign');
$router->get('/salesrep/product-details/:id', 'SalesRepController@getProductDetails');
$router->post('/salesrep/record-sale', 'SalesRepController@recordSale');
$router->get('/salesrep/return-form/:id', 'SalesRepController@returnForm');
$router->post('/salesrep/return-stock', 'SalesRepController@returnStock');
$router->get('/salesrep/full-report', 'SalesRepController@fullReport');

$router->get('/reports/sales-rep-stock', 'ReportController@salesRepStock');
$router->get('/reports/assign-stock-form', 'ReportController@assignStockForm');
$router->post('/reports/assign-stock', 'ReportController@assignStock');

// المقبوضات والمدفوعات
$router->get('/payments', 'PaymentReportController@index');

// جلب رصيد مادة في مخزن معين (AJAX)
$router->get('/products/stock', 'ProductController@getStock');

// عمولات المندوبين (للأدمن فقط)
$router->get('/commissions', 'CommissionController@index');
$router->get('/commissions/agent/:id', 'CommissionController@agentReport');
$router->get('/commissions/agent/:id/export-pdf', 'CommissionController@exportPDF');
$router->get('/commissions/pay/:id', 'CommissionController@showPayForm');
$router->post('/commissions/pay/:id', 'CommissionController@pay');

// AJAX: حساب المستحق في فترة
$router->get('/commissions/calculate/:id', 'CommissionController@calculatePeriod');

// إغلاق حساب المندوب
$router->post('/commissions/close-account/:id', 'CommissionController@closeAccount');

// المصروفات
$router->get('/expenses/categories', 'ExpenseController@categories');
$router->post('/expenses/categories/store', 'ExpenseController@storeCategory');
$router->post('/expenses/categories/delete/:id', 'ExpenseController@deleteCategory');
$router->get('/expenses', 'ExpenseController@index');
$router->get('/expenses/create', 'ExpenseController@create');
$router->post('/expenses/store', 'ExpenseController@store');
$router->get('/expenses/edit/:id', 'ExpenseController@edit');
$router->post('/expenses/update/:id', 'ExpenseController@update');
$router->post('/expenses/delete/:id', 'ExpenseController@delete');
$router->get('/expenses/vouchers', 'ExpenseController@vouchers');
$router->get('/expenses/vouchers/create', 'ExpenseController@createVoucher');
$router->post('/expenses/vouchers/store', 'ExpenseController@storeVoucher');
$router->get('/expenses/vouchers/print/:id', 'ExpenseController@printVoucher');

// طباعة الأقساط
$router->get('/print/installments', 'PrintController@installments');
$router->get('/print/receipt/:id', 'PrintController@receipt');
$router->get('/print/opening-receipt/:id', 'PrintController@openingReceipt');
$router->get('/print/bulk-receipts', 'PrintController@bulkReceipts');

// ========== API للبحث العام ==========
$router->get('/api/search', 'ApiController@search');
$router->get('/api/next-customer-code', 'ApiController@nextCustomerCode');

// ========== إدارة التحديثات (أدمن فقط) ==========
$router->get('/updates', 'UpdateController@index');
$router->get('/updates/form', 'UpdateController@uploadForm');
$router->post('/updates/upload', 'UpdateController@upload');
$router->get('/updates/preview', 'UpdateController@preview');
$router->get('/updates/execute', 'UpdateController@execute');
$router->post('/updates/execute', 'UpdateController@execute');
$router->post('/updates/rollback/:id', 'UpdateController@rollback');
$router->post('/updates/delete/:id', 'UpdateController@deleteMigration');

// ========== API التحديثات ==========
$router->get('/api/updates/current-version', 'UpdateApiController@currentVersion');
$router->get('/api/updates/latest', 'UpdateApiController@latest');
$router->get('/api/updates/check', 'UpdateApiController@check');

// ========== نظام الأدوار والصلاحيات (RBAC) ==========
$router->get('/role-permissions/roles', 'RolePermissionController@rolesIndex');
$router->get('/role-permissions/roles/create', 'RolePermissionController@rolesCreate');
$router->post('/role-permissions/roles/store', 'RolePermissionController@rolesStore');
$router->get('/role-permissions/roles/edit/:id', 'RolePermissionController@rolesEdit');
$router->post('/role-permissions/roles/update/:id', 'RolePermissionController@rolesUpdate');
$router->post('/role-permissions/roles/delete/:id', 'RolePermissionController@rolesDelete');
$router->get('/role-permissions/permissions', 'RolePermissionController@permissionsIndex');
$router->get('/role-permissions/users/permissions/:id', 'RolePermissionController@userPermissionsEdit');
$router->post('/role-permissions/users/permissions/update/:id', 'RolePermissionController@userPermissionsUpdate');

// ========== سيتم إضافة باقي المسارات في المراحل القادمة ==========

return $router;
