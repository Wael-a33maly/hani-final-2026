<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'WarehouseReport.php';
require_once MODELS_PATH . 'Product.php';
require_once MODELS_PATH . 'Warehouse.php';
require_once MODELS_PATH . 'Branch.php';
require_once MODELS_PATH . 'User.php';

class ReportController extends Controller
{
    public function index()
    {
        requireLogin();
        $this->view('reports.index');
    }

    // جرد مادة
    public function productStock()
    {
        requireLogin();
        $productModel = new Product();
        $products = $productModel->all();
        $selectedProduct = null;
        $stockData = [];
        if (isset($_GET['product_id'])) {
            $reportModel = new WarehouseReport();
            $selectedProduct = $productModel->find($_GET['product_id']);
            $stockData = $reportModel->getProductStockInAllWarehouses($selectedProduct['id']);
        }
        $this->view('reports.product_stock', compact('products', 'selectedProduct', 'stockData'));
    }

    // حركة مادة
    public function productMovements()
    {
        requireLogin();
        $productModel = new Product();
        $products = $productModel->all();
        $selectedProduct = null;
        $movements = [];
        $openingBalance = 0;
        $openingDate = null;
        if (isset($_GET['product_id'])) {
            $selectedProduct = $productModel->find($_GET['product_id']);
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $reportModel = new WarehouseReport();

            $ob = $reportModel->getProductOpeningBalance($selectedProduct['id']);
            $openingBalance = (float)($ob['total_qty'] ?? 0);
            $openingDate = $ob['bal_date'] ?? $from;

            $movements = $reportModel->getProductMovements($selectedProduct['id'], $from, $to);
        }

        if (isset($_GET['print'])) {
            require_once MODELS_PATH . 'CompanySetting.php';
            $companyModel = new CompanySetting();
            $company = $companyModel->getSettings();
            $this->view('reports.product_movements_print', compact('selectedProduct', 'movements', 'openingBalance', 'openingDate', 'from', 'to', 'company'));
            return;
        }

        $this->view('reports.product_movements', compact('products', 'selectedProduct', 'movements', 'openingBalance', 'openingDate'));
    }

    // جرد مستودع
    public function warehouseStock()
    {
        requireLogin();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $selectedWarehouse = null;
        $stock = [];
        if (isset($_GET['warehouse_id'])) {
            $selectedWarehouse = $warehouseModel->find($_GET['warehouse_id']);
            $reportModel = new WarehouseReport();
            $stock = $reportModel->getWarehouseStock($selectedWarehouse['id']);
        }
        $this->view('reports.warehouse_stock', compact('warehouses', 'selectedWarehouse', 'stock'));
    }

    // جرد فرع
    public function branchStock()
    {
        requireLogin();
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $selectedBranch = null;
        $stock = [];
        if (isset($_GET['branch_id'])) {
            $selectedBranch = $branchModel->find($_GET['branch_id']);
            $reportModel = new WarehouseReport();
            $stock = $reportModel->getBranchStock($selectedBranch['id']);
        }
        $this->view('reports.branch_stock', compact('branches', 'selectedBranch', 'stock'));
    }

    // تحويل بين المخازن
    public function transfers()
    {
        requireLogin();
        $db = getDB();
        $transfers = $db->query("SELECT wt.*, p.name as product_name, w1.name as from_warehouse, w2.name as to_warehouse 
                                 FROM warehouse_transfers wt
                                 LEFT JOIN products p ON wt.product_id = p.id
                                 LEFT JOIN warehouses w1 ON wt.from_warehouse_id = w1.id
                                 LEFT JOIN warehouses w2 ON wt.to_warehouse_id = w2.id
                                 ORDER BY wt.transfer_date DESC")->fetchAll();
        $this->view('reports.transfers', ['transfers' => $transfers]);
    }

    public function createTransferForm()
    {
        requireLogin();
        $productModel = new Product();
        $products = $productModel->all();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $this->view('reports.transfer_form', compact('products', 'warehouses'));
    }

    public function storeTransfer()
    {
        requireLogin();
        $this->verifyCSRF();

        $fromWarehouseId = $_POST['from_warehouse_id'];
        $toWarehouseId = $_POST['to_warehouse_id'];
        $products = $_POST['products'] ?? [];
        $notes = $_POST['notes'] ?? '';

        // التحقق من وجود مواد
        if (empty($products)) {
            $_SESSION['error'] = 'يجب إضافة مادة واحدة على الأقل';
            redirect('/reports/transfer-form');
            return;
        }

        // التحقق من المخازن
        if ($fromWarehouseId === $toWarehouseId) {
            $_SESSION['error'] = 'لا يمكن التحويل إلى نفس المخزن';
            redirect('/reports/transfer-form');
            return;
        }

        require_once MODELS_PATH . 'StockModel.php';
        $stockModel = new StockModel();
        $reportModel = new WarehouseReport();
        $productModel = new Product();

        // التحقق من الرصيد لكل مادة
        $errors = [];
        foreach ($products as $item) {
            $productId = $item['product_id'] ?? '';
            $quantity = floatval($item['quantity'] ?? 0);

            if (empty($productId) || $quantity <= 0)
                continue;

            if (!$stockModel->checkStock($productId, $fromWarehouseId, $quantity)) {
                $prod = $productModel->find($productId);
                $current = $stockModel->getStock($productId, $fromWarehouseId);
                $errors[] = "المادة ({$prod['name']}): الرصيد الحالي {$current} - المطلوب {$quantity}";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = "الرصيد غير كافٍ:<br>" . implode("<br>", $errors);
            redirect('/reports/transfer-form');
            return;
        }

        // تنفيذ التحويل لكل المواد
        $allSuccess = true;
        foreach ($products as $item) {
            $productId = $item['product_id'] ?? '';
            $quantity = floatval($item['quantity'] ?? 0);

            if (empty($productId) || $quantity <= 0)
                continue;

            $result = $reportModel->createTransfer(
                $fromWarehouseId,
                $toWarehouseId,
                $productId,
                $quantity,
                $this->userId,
                $notes
            );

            if (!$result) {
                $allSuccess = false;
            }
        }

        if ($allSuccess) {
            $_SESSION['success'] = 'تم التحويل بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء التحويل';
        }
        redirect('/reports/transfers');
    }

    // عهدة بضاعة لمندوب
    public function salesRepStock()
    {
        requireLogin();
        $userModel = new User();
        $salesReps = $userModel->all();
        $selectedRep = null;
        $stock = [];
        if (isset($_GET['sales_rep_id'])) {
            $selectedRep = $userModel->find($_GET['sales_rep_id']);
            $reportModel = new WarehouseReport();
            $stock = $reportModel->getSalesRepStockReport($selectedRep['id']);
        }
        $this->view('reports.sales_rep_stock', compact('salesReps', 'selectedRep', 'stock'));
    }

    public function assignStockForm()
    {
        requireLogin();
        $userModel = new User();
        $salesReps = $userModel->all();
        $productModel = new Product();
        $products = $productModel->all();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $this->view('reports.assign_stock', compact('salesReps', 'products', 'warehouses'));
    }

    public function assignStock()
    {
        requireLogin();
        $this->verifyCSRF();
        $reportModel = new WarehouseReport();
        $result = $reportModel->assignToSalesRep($_POST['sales_rep_id'], $_POST['product_id'], $_POST['quantity'], $_POST['warehouse_id'], $this->userId);
        if ($result) {
            $_SESSION['success'] = 'تم إسناد البضاعة للمندوب';
        } else {
            $_SESSION['error'] = 'حدث خطأ';
        }
        redirect('/reports/sales-rep-stock');
    }
}
