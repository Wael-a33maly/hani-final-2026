<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Product.php';
require_once MODELS_PATH . 'Unit.php';
require_once MODELS_PATH . 'Warehouse.php';
require_once MODELS_PATH . 'StockModel.php';

require_once __DIR__ . '/../Core/Pagination.php';

class ProductController extends Controller
{
    public function index()
    {
        requireLogin();
        $db = getDB();
        $page = (int) ($_GET['page'] ?? 1);
        $search = trim($_GET['search'] ?? '');

        $baseQuery = "SELECT p.*, u.name as unit_name,
                       COALESCE((SELECT SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE -sm.quantity END) FROM stock_movements sm WHERE sm.product_id = p.id AND sm.reference != 'opening_balance'), 0)
                       + COALESCE((SELECT SUM(pob.quantity) FROM product_opening_balance pob WHERE pob.product_id = p.id), 0) as total_stock
                       FROM products p LEFT JOIN units u ON p.unit_id = u.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM products p WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $baseQuery .= " AND (p.barcode LIKE ? OR p.name LIKE ?)";
            $countQuery .= " AND (p.barcode LIKE ? OR p.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $baseQuery .= " ORDER BY p.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $products = $pagination['data'];

        $this->view('products.index', [
            'products' => $products,
            'pagination' => $pagination,
            'search' => $search
        ]);
    }

    public function create()
    {
        requireRole('admin');
        $unitModel = new Unit();
        $units = $unitModel->all();
        $product_opening_balances = [];
        $this->view('products.form', ['units' => $units, 'product' => null, 'product_opening_balances' => $product_opening_balances]);
    }

    public function store()
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $model = new Product();
        $openingBalancesJson = $_POST['opening_balances_json'] ?? '[]';
        $_POST = sanitizeInput($_POST);
        $_POST['opening_balances_json'] = $openingBalancesJson;
        $barcode = trim($_POST['barcode']);
        if (empty($barcode)) {
            $barcode = 'P' . time() . rand(100, 999);
        }

        $db->beginTransaction();
        try {
            $commissionAmount = max(0, (float) ($_POST['commission_amount'] ?? 0));
            $productId = $model->insert([
                'barcode' => $barcode,
                'name' => trim($_POST['name']),
                'unit_id' => $_POST['unit_id'],
                'purchase_price' => max(0, (float) ($_POST['purchase_price'] ?? 0)),
                'selling_price' => max(0, (float) ($_POST['selling_price'] ?? 0)),
                'wholesale_price' => max(0, (float) ($_POST['wholesale_price'] ?? 0)),
                'special_price' => max(0, (float) ($_POST['special_price'] ?? 0)),
                'commission_amount' => $commissionAmount
            ]);

            $openingBalances = json_decode($_POST['opening_balances_json'] ?? '[]', true);
            $stockModel = new StockModel();
            foreach ($openingBalances as $ob) {
                if (!empty($ob['warehouse_id']) && $ob['quantity'] > 0) {
                    $stmt = $db->prepare("INSERT INTO product_opening_balance (product_id, warehouse_id, quantity, price, total, date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$productId, $ob['warehouse_id'], $ob['quantity'], $ob['price'], $ob['quantity'] * $ob['price']]);

                    $stmt2 = $db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'in', ?, 'opening_balance', ?)");
                    $stmt2->execute([$productId, $ob['warehouse_id'], $ob['quantity'], $productId]);

                    // تحديث الرصيد الحالي
                    $stockModel->updateStock($productId, $ob['warehouse_id'], $ob['quantity'], 'add');
                }
            }
            $db->commit();
            logAudit($this->userId, 'إضافة مادة ورصيد أول مدة', 'products', $productId, null, json_encode($_POST));
            $_SESSION['success'] = 'تم إضافة المادة وحفظ الأرصدة';
            redirect('/products');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/products/create');
        }
    }

    public function edit($id)
    {
        requireRole('admin');
        $model = new Product();
        $product = $model->find($id);
        if (!$product)
            redirect('/products');
        $unitModel = new Unit();
        $units = $unitModel->all();

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM product_opening_balance WHERE product_id = ?");
        $stmt->execute([$id]);
        $product_opening_balances = $stmt->fetchAll();

        $this->view('products.form', ['units' => $units, 'product' => $product, 'product_opening_balances' => $product_opening_balances]);
    }

    public function update($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $model = new Product();
        $oldData = json_encode($model->find($id));

        $db->beginTransaction();
        try {
            $commissionAmount = max(0, (float) ($_POST['commission_amount'] ?? 0));
            $model->update($id, [
                'barcode' => trim($_POST['barcode']),
                'name' => trim($_POST['name']),
                'unit_id' => $_POST['unit_id'],
                'purchase_price' => max(0, (float) ($_POST['purchase_price'] ?? 0)),
                'selling_price' => max(0, (float) ($_POST['selling_price'] ?? 0)),
                'wholesale_price' => max(0, (float) ($_POST['wholesale_price'] ?? 0)),
                'special_price' => max(0, (float) ($_POST['special_price'] ?? 0)),
                'commission_amount' => $commissionAmount
            ]);

            $db->prepare("DELETE FROM product_opening_balance WHERE product_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM stock_movements WHERE product_id = ? AND reference = 'opening_balance'")->execute([$id]);

            $openingBalances = json_decode($_POST['opening_balances_json'] ?? '[]', true);
            if (is_array($openingBalances)) {
                foreach ($openingBalances as $ob) {
                    if (!empty($ob['warehouse_id']) && !empty($ob['quantity']) && $ob['quantity'] > 0) {
                        $stmt = $db->prepare("INSERT INTO product_opening_balance (product_id, warehouse_id, quantity, price, total, date) VALUES (?, ?, ?, ?, ?, CURDATE())");
                        $stmt->execute([$id, $ob['warehouse_id'], $ob['quantity'], $ob['price'] ?? 0, ($ob['quantity'] ?? 0) * ($ob['price'] ?? 0)]);

                        $stmt2 = $db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'in', ?, 'opening_balance', ?)");
                        $stmt2->execute([$id, $ob['warehouse_id'], $ob['quantity'], $id]);
                    }
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/products/edit/' . $id);
        }
        // إعادة حساب الرصيد خارج الترانساكشن (لأنه يستخدم TRUNCATE)
        try {
            require_once MODELS_PATH . 'StockModel.php';
            (new StockModel())->recalculateAllStock();
        } catch (Exception $e) {
            error_log('فشل إعادة حساب الرصيد بعد تعديل المادة: ' . $e->getMessage());
        }
        logAudit($this->userId, 'تعديل مادة وأرصدة أول مدة', 'products', $id, $oldData, json_encode($_POST));
        $_SESSION['success'] = 'تم التحديث بنجاح';
        redirect('/products');
    }

    public function delete($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM sales_invoice_items WHERE product_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف المادة لأنها مستخدمة في فواتير مبيعات';
            redirect('/products');
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM purchase_invoice_items WHERE product_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف المادة لأنها مستخدمة في فواتير مشتريات';
            redirect('/products');
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM current_stock WHERE product_id = ? AND quantity > 0");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف المادة لأن لها رصيد في المخازن';
            redirect('/products');
        }
        $model = new Product();
        $oldData = json_encode($model->find($id));
        $model->delete($id);

        // تنظيف الرصيد الحالي
        $db->prepare("DELETE FROM current_stock WHERE product_id = ?")->execute([$id]);

        logAudit($this->userId, 'حذف مادة', 'products', $id, $oldData, null);
        $_SESSION['success'] = 'تم حذف المادة';
        redirect('/products');
    }

    // أداة للمدير لإعادة حساب كافة الأرصدة
    public function syncStock()
    {
        requireRole('admin');
        require_once MODELS_PATH . 'StockModel.php';
        $stockModel = new StockModel();
        $stockModel->recalculateAllStock();
        $_SESSION['success'] = 'تمت مزامنة الأرصدة بنجاح من سجل الحركات';
        redirect('/products');
    }

    // AJAX بحث
    public function search()
    {
        requireLogin();
        $keyword = $_GET['q'] ?? '';
        if ($keyword === '')
            die(json_encode([]));
        $model = new Product();
        $products = $model->search($keyword);
        header('Content-Type: application/json');
        echo json_encode($products);
    }

    // AJAX جلب رصيد مادة في مخزن معين
    public function getStock()
    {
        requireLogin();
        $productId = (int) ($_GET['product_id'] ?? 0);
        $warehouseId = (int) ($_GET['warehouse_id'] ?? 0);

        if (!$productId || !$warehouseId) {
            echo json_encode(['stock' => 0]);
            return;
        }

        $productModel = new Product();
        $stock = $productModel->getCurrentStock($productId, $warehouseId);

        header('Content-Type: application/json');
        echo json_encode(['stock' => $stock]);
    }
}
