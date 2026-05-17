<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Customer.php';

class ApiController extends Controller
{
    public function search()
    {
        requireLogin();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $db = getDB();
        $results = [];
        $likeQ = "%$q%";

        // البحث عن منتجات
        $stmt = $db->prepare("SELECT id, name, code FROM products WHERE name LIKE ? OR code LIKE ? LIMIT 5");
        $stmt->execute([$likeQ, $likeQ]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'id' => 'p' . $row['id'],
                'url' => APP_URL . '/products/edit/' . $row['id'],
                'icon' => 'fas fa-box',
                'title' => $row['name'],
                'subtitle' => 'كود: ' . $row['code']
            ];
        }

        // البحث عن عملاء
        $stmt = $db->prepare("SELECT id, name, phone FROM customers WHERE name LIKE ? OR phone LIKE ? LIMIT 5");
        $stmt->execute([$likeQ, $likeQ]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'id' => 'c' . $row['id'],
                'url' => APP_URL . '/customers/edit/' . $row['id'],
                'icon' => 'fas fa-user',
                'title' => $row['name'],
                'subtitle' => 'هاتف: ' . $row['phone']
            ];
        }

        // البحث عن فواتير
        $stmt = $db->prepare("SELECT si.id, si.invoice_number, c.name as customer_name FROM sales_invoices si LEFT JOIN customers c ON si.customer_id = c.id WHERE si.invoice_number LIKE ? LIMIT 5");
        $stmt->execute([$likeQ]);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = [
                'id' => 'si' . $row['id'],
                'url' => APP_URL . '/sales/show/' . $row['id'],
                'icon' => 'fas fa-file-invoice',
                'title' => 'فاتورة: ' . $row['invoice_number'],
                'subtitle' => $row['customer_name'] ?? ''
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    public function nextCustomerCode()
    {
        requireLogin();
        $model = new Customer();
        echo $model->generateCode();
    }
}
