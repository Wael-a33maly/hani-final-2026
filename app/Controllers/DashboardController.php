<?php
/**
 * app/Controllers/DashboardController.php
 */
require_once __DIR__ . '/../Core/Controller.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class DashboardController extends Controller {

    private function getBranchStats($db, $branchId)
    {
        $sales = $db->prepare("SELECT COALESCE(SUM(total),0) as t, COALESCE(SUM(paid_upfront),0) as upfront, COUNT(*) as c FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)");
        $sales->execute([$branchId]); $s = $sales->fetch();

        $purchases = $db->prepare("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as c FROM purchase_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)");
        $purchases->execute([$branchId]); $p = $purchases->fetch();

        $installments = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total FROM installments WHERE sales_invoice_id IN (SELECT id FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?))");
        $installments->execute([$branchId]); $inst = $installments->fetch();

        $collected = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM installment_payments WHERE installment_id IN (SELECT id FROM installments WHERE sales_invoice_id IN (SELECT id FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)))");
        $collected->execute([$branchId]); $coll = $collected->fetch();

        $remaining = $inst['total'] - $coll['t'];

        $customers = $db->prepare("SELECT COUNT(DISTINCT customer_id) as c FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)");
        $customers->execute([$branchId]); $cust = $customers->fetch();

        $users = $db->prepare("SELECT COUNT(*) as c FROM users WHERE is_active = 1 AND (branch_id = ? OR id IN (SELECT user_id FROM user_branches WHERE branch_id = ?))");
        $users->execute([$branchId, $branchId]); $u = $users->fetch();

        $overdue = $db->prepare("SELECT COUNT(*) as c FROM installments WHERE status='pending' AND due_date < CURDATE() AND sales_invoice_id IN (SELECT id FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?))");
        $overdue->execute([$branchId]); $ov = $overdue->fetch();

        $overdueTotal = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM installments WHERE status='pending' AND due_date < CURDATE() AND sales_invoice_id IN (SELECT id FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?))");
        $overdueTotal->execute([$branchId]); $ovt = $overdueTotal->fetch();

        $paidInstallments = $db->prepare("SELECT COUNT(*) as c FROM installments WHERE status='paid' AND sales_invoice_id IN (SELECT id FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?))");
        $paidInstallments->execute([$branchId]); $pi = $paidInstallments->fetch();

        $paidSupplier = $db->prepare("SELECT COALESCE(SUM(amount),0) as t FROM supplier_payments WHERE supplier_id IN (SELECT id FROM suppliers)");
        $paidSupplier->execute([]); $ps = $paidSupplier->fetch();

        $totalSupplierPurchases = $db->prepare("SELECT COALESCE(SUM(total),0) as t FROM purchase_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)");
        $totalSupplierPurchases->execute([$branchId]); $tsp = $totalSupplierPurchases->fetch();

        $remainingSupplier = $tsp['t'] - $ps['t'];

        return [
            'sales_total' => (float)$s['t'],
            'sales_count' => (int)$s['c'],
            'paid_upfront' => (float)$s['upfront'],
            'purchases_total' => (float)$p['t'],
            'purchases_count' => (int)$p['c'],
            'installments_total' => (float)$inst['total'],
            'installments_count' => (int)$inst['cnt'],
            'collected_total' => (float)$coll['t'],
            'remaining_to_collect' => max(0, $remaining),
            'customers_count' => (int)$cust['c'],
            'users_count' => (int)$u['c'],
            'overdue_count' => (int)$ov['c'],
            'overdue_total' => (float)$ovt['t'],
            'paid_installments' => (int)$pi['c'],
            'remaining_to_suppliers' => max(0, $remainingSupplier),
        ];
    }

    private function getMonthlyBranchStats($db, $branchId)
    {
        $months = [];
        $sales = $db->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') as m, COALESCE(SUM(total),0) as t FROM sales_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m ORDER BY m");
        $sales->execute([$branchId]);
        while ($r = $sales->fetch()) $months[$r['m']]['sales'] = (float)$r['t'];

        $purchases = $db->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') as m, COALESCE(SUM(total),0) as t FROM purchase_invoices WHERE warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m ORDER BY m");
        $purchases->execute([$branchId]);
        while ($r = $purchases->fetch()) $months[$r['m']]['purchases'] = (float)$r['t'];

        $collections = $db->prepare("SELECT DATE_FORMAT(ip.payment_date,'%Y-%m') as m, COALESCE(SUM(ip.amount),0) as t FROM installment_payments ip JOIN installments i ON ip.installment_id = i.id JOIN sales_invoices si ON i.sales_invoice_id = si.id JOIN warehouses w ON si.warehouse_id = w.id WHERE w.branch_id = ? AND ip.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY m ORDER BY m");
        $collections->execute([$branchId]);
        while ($r = $collections->fetch()) $months[$r['m']]['collections'] = (float)$r['t'];

        $labels = []; $salesData = []; $purchasesData = []; $collectionsData = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months"));
            $labels[] = $m;
            $salesData[] = $months[$m]['sales'] ?? 0;
            $purchasesData[] = $months[$m]['purchases'] ?? 0;
            $collectionsData[] = $months[$m]['collections'] ?? 0;
        }
        return compact('labels', 'salesData', 'purchasesData', 'collectionsData');
    }

    public function index() {
        requireLogin();
        $db = getDB();
        $userId = $_SESSION['user_id'];
        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';

        $branchStats = [];
        $allBranches = [];
        $grandTotal = [
            'sales_total' => 0, 'purchases_total' => 0, 'installments_total' => 0,
            'collected_total' => 0, 'remaining_to_collect' => 0, 'customers_count' => 0,
            'users_count' => 0, 'overdue_count' => 0, 'overdue_total' => 0,
            'remaining_to_suppliers' => 0, 'paid_upfront' => 0, 'sales_count' => 0,
            'purchases_count' => 0, 'installments_count' => 0, 'paid_installments' => 0,
        ];
        $productsCount = 0;
        $monthlyStats = [];
        $chartDataJson = '{}';

        try {
            if ($roleName === 'admin') {
                $allBranches = $db->query("SELECT id, name FROM branches ORDER BY name")->fetchAll();
            } else {
                $userBranches = PermissionHelper::getUserBranches($userId);
                $ids = implode(',', array_map('intval', $userBranches));
                if (!empty($ids)) {
                    $allBranches = $db->query("SELECT id, name FROM branches WHERE id IN ($ids) ORDER BY name")->fetchAll();
                }
            }

            foreach ($allBranches as $br) {
                $stats = $this->getBranchStats($db, $br['id']);
                $branchStats[$br['id']] = $stats;
                foreach ($grandTotal as $k => &$v) {
                    if (isset($stats[$k])) $v += $stats[$k];
                }
                $monthlyStats[$br['id']] = $this->getMonthlyBranchStats($db, $br['id']);
            }

            $productsCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $_SESSION['overdue_installments'] = $grandTotal['overdue_count'] ?? 0;
            $chartDataJson = json_encode($monthlyStats, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
        }

        $currentTime = date('Y-m-d H:i:s');
        $currentDateAr = [
            'Saturday' => 'السبت', 'Sunday' => 'الأحد', 'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس', 'Friday' => 'الجمعة',
        ];
        $dayName = $currentDateAr[date('l')] ?? date('l');

        $this->view('dashboard.index', [
            'branchStats' => $branchStats,
            'allBranches' => $allBranches,
            'grandTotal' => $grandTotal,
            'productsCount' => $productsCount,
            'roleName' => $roleName,
            'currentTime' => $currentTime,
            'dayName' => $dayName,
            'chartDataJson' => $chartDataJson,
            'monthlyStats' => $monthlyStats,
        ]);
    }
}
