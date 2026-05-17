<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'WarehouseReport.php';
require_once MODELS_PATH . 'Product.php';
require_once MODELS_PATH . 'Customer.php';
require_once MODELS_PATH . 'User.php';
require_once MODELS_PATH . 'Warehouse.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class SalesRepController extends Controller
{

    // عرض قائمة المندوبين وعهدهم
    public function index()
    {
        requireLogin();
        $db = getDB();
        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';

        $userQuery = "SELECT id, full_name FROM users WHERE role = 'user'";
        if ($roleName !== 'admin') {
            $branchIds = implode(',', array_map('intval', PermissionHelper::getUserBranches($_SESSION['user_id'])));
            if (!empty($branchIds)) {
                $userQuery .= " AND (branch_id IN ({$branchIds}) OR id IN (SELECT user_id FROM user_branches WHERE branch_id IN ({$branchIds})))";
            }
        }
        $userQuery .= " ORDER BY full_name";
        $users = $db->query($userQuery)->fetchAll();

        $selectedRep = null;
        $stock = [];
        if (isset($_GET['sales_rep_id'])) {
            $selectedRep = (new User())->find($_GET['sales_rep_id']);
            $reportModel = new WarehouseReport();
            $stock = $reportModel->getSalesRepStockReport($selectedRep['id']);
        }

        $this->view('salesrep.index', compact('users', 'selectedRep', 'stock'));
    }

    // نموذج إسناد بضاعة لمندوب
    public function assignForm()
    {
        requireRole('admin');
        $userModel = new User();
        $salesReps = $userModel->all();
        $productModel = new Product();
        $products = $productModel->all();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $this->view('salesrep.assign', compact('salesReps', 'products', 'warehouses'));
    }

    // تنفيذ إسناد بضاعة لعدة مواد
    public function assign()
    {
        requireRole('admin');
        $this->verifyCSRF();

        $salesRepId = $_POST['sales_rep_id'];
        $warehouseId = $_POST['warehouse_id'];
        $products = $_POST['products'] ?? [];

        // التحقق من وجود مواد
        if (empty($products)) {
            $_SESSION['error'] = 'يجب إضافة مادة واحدة على الأقل';
            redirect('/salesrep/assign');
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

            if (!$stockModel->checkStock($productId, $warehouseId, $quantity)) {
                $prod = $productModel->find($productId);
                $current = $stockModel->getStock($productId, $warehouseId);
                $errors[] = "المادة ({$prod['name']}): الرصيد الحالي {$current} - المطلوب {$quantity}";
            }
        }

        if (!empty($errors)) {
            $_SESSION['error'] = "الرصيد غير كافٍ:<br>" . implode("<br>", $errors);
            redirect('/salesrep/assign');
            return;
        }

        // تنفيذ الإسناد لكل المواد
        $allSuccess = true;
        foreach ($products as $item) {
            $productId = $item['product_id'] ?? '';
            $quantity = floatval($item['quantity'] ?? 0);

            if (empty($productId) || $quantity <= 0)
                continue;

            $result = $reportModel->assignToSalesRep(
                $salesRepId,
                $productId,
                $quantity,
                $warehouseId,
                $this->userId
            );

            if (!$result) {
                $allSuccess = false;
            }
        }

        if ($allSuccess) {
            logAudit($this->userId, 'إسناد بضاعة لمندوب', 'sales_rep_stock');
            $_SESSION['success'] = 'تم إسناد البضاعة للمندوب';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء الإسناد';
        }
        redirect('/salesrep');
    }

    // عرض نموذج تسجيل مبيعات من العهدة (مودال سيتم استخدامه في view)
    public function getProductDetails($repStockId)
    {
        requireLogin();
        $db = getDB();
        $stmt = $db->prepare("
            SELECT srs.*, p.name as product_name, p.selling_price, p.wholesale_price, p.special_price
            FROM sales_rep_stock srs
            LEFT JOIN products p ON srs.product_id = p.id
            WHERE srs.id = ?
        ");
        $stmt->execute([$repStockId]);
        $product = $stmt->fetch();
        header('Content-Type: application/json');
        echo json_encode($product);
    }

    // تسجيل بيع من عهدة المندوب
    public function recordSale()
    {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();

        $repStockId = $_POST['rep_stock_id'];
        $quantity = (float) $_POST['quantity'];
        $customerId = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $priceType = $_POST['price_type'];

        // جلب السعر حسب النوع
        $stmt = $db->prepare("
            SELECT srs.product_id, p.selling_price, p.wholesale_price, p.special_price
            FROM sales_rep_stock srs
            LEFT JOIN products p ON srs.product_id = p.id
            WHERE srs.id = ?
        ");
        $stmt->execute([$repStockId]);
        $product = $stmt->fetch();

        switch ($priceType) {
            case 'wholesale':
                $price = $product['wholesale_price'];
                break;
            case 'special':
                $price = $product['special_price'];
                break;
            default:
                $price = $product['selling_price'];
        }

        $reportModel = new WarehouseReport();
        $result = $reportModel->recordSalesRepSale($repStockId, $quantity, $customerId, $priceType, $price, $this->userId);

        if ($result) {
            logAudit($this->userId, 'تسجيل بيع من عهدة مندوب', 'sales_rep_sales');
            echo json_encode(['success' => true, 'message' => 'تم تسجيل البيع بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل البيع']);
        }
        exit;
    }

    // نموذج استرداد بضاعة من مندوب
    public function returnForm($repStockId)
    {
        requireLogin();
        $db = getDB();
        $stmt = $db->prepare("
            SELECT srs.*, p.name as product_name, w.name as warehouse_name
            FROM sales_rep_stock srs
            LEFT JOIN products p ON srs.product_id = p.id
            LEFT JOIN warehouses w ON srs.assigned_from_warehouse_id = w.id
            WHERE srs.id = ?
        ");
        $stmt->execute([$repStockId]);
        $stock = $stmt->fetch();
        if (!$stock) {
            $_SESSION['error'] = 'البيانات غير موجودة';
            redirect('/salesrep');
        }
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $this->view('salesrep.return', compact('stock', 'warehouses'));
    }

    // تنفيذ استرداد بضاعة
    public function returnStock()
    {
        requireLogin();
        $this->verifyCSRF();
        $repStockId = $_POST['rep_stock_id'];
        $quantity = (float) $_POST['quantity'];
        $toWarehouseId = $_POST['to_warehouse_id'];

        $reportModel = new WarehouseReport();
        $result = $reportModel->returnFromSalesRep($repStockId, $quantity, $toWarehouseId, $this->userId);

        if ($result) {
            logAudit($this->userId, 'استرداد بضاعة من مندوب', 'sales_rep_return');
            $_SESSION['success'] = 'تم استرداد البضاعة بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء الاسترداد';
        }
        redirect('/salesrep');
    }

    // تقرير العهدة الكامل (جرد مندوب)
    public function fullReport()
    {
        requireLogin();
        $db = getDB();

        // الفلاتر
        $salesRepId = $_GET['sales_rep_id'] ?? '';
        $productId = $_GET['product_id'] ?? '';
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');

        $users = $db->query("SELECT id, full_name FROM users WHERE role = 'user' ORDER BY full_name")->fetchAll();
        $products = $db->query("SELECT id, name FROM products ORDER BY name")->fetchAll();

        $reportData = [];
        $salesRepName = '';
        if (!empty($salesRepId)) {
            $repStmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
            $repStmt->execute([$salesRepId]);
            $repRow = $repStmt->fetch();
            $salesRepName = $repRow ? $repRow['full_name'] : '';

            $sql = "SELECT 
                        p.id as product_id,
                        p.name as product_name,
                        p.barcode,
                        u.name as unit_name,
                        COALESCE(srs.quantity, 0) as assigned_quantity,
                        COALESCE((SELECT SUM(quantity) FROM sales_rep_sales WHERE sales_rep_stock_id = srs.id AND sale_date BETWEEN ? AND ?), 0) as sold_quantity,
                        COALESCE((SELECT SUM(quantity) FROM sales_rep_return WHERE sales_rep_stock_id = srs.id AND return_date BETWEEN ? AND ?), 0) as returned_quantity,
                        (COALESCE(srs.quantity, 0) - 
                         COALESCE((SELECT SUM(quantity) FROM sales_rep_sales WHERE sales_rep_stock_id = srs.id AND sale_date BETWEEN ? AND ?), 0) - 
                         COALESCE((SELECT SUM(quantity) FROM sales_rep_return WHERE sales_rep_stock_id = srs.id AND return_date BETWEEN ? AND ?), 0)
                        ) as current_quantity,
                        p.selling_price
                    FROM sales_rep_stock srs
                    JOIN products p ON srs.product_id = p.id
                    LEFT JOIN units u ON p.unit_id = u.id
                    WHERE srs.sales_rep_id = ?";

            $params = [$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate, $salesRepId];

            if (!empty($productId)) {
                $sql .= " AND p.id = ?";
                $params[] = $productId;
            }
            $sql .= " ORDER BY p.name";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll();
        }

        if (isset($_GET['print'])) {
            require_once MODELS_PATH . 'CompanySetting.php';
            $companyModel = new CompanySetting();
            $company = $companyModel->getSettings();
            $this->view('salesrep.full_report_print', compact('reportData', 'salesRepName', 'salesRepId', 'productId', 'fromDate', 'toDate', 'company'));
            return;
        }

        $this->view('salesrep.full_report', compact('users', 'products', 'salesRepId', 'productId', 'fromDate', 'toDate', 'reportData'));
    }
}