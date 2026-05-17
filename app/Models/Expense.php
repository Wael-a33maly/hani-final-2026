<?php
require_once __DIR__ . '/../Core/Model.php';

class ExpenseCategory extends Model {
    protected $table = 'expense_categories';
    public function getAll() { return $this->db->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll(); }
}

class Expense extends Model {
    protected $table = 'expenses';
    public function allWithCategory() {
        $stmt = $this->db->query("SELECT e.*, ec.name as category_name FROM expenses e LEFT JOIN expense_categories ec ON e.category_id = ec.id ORDER BY e.id DESC");
        return $stmt->fetchAll();
    }
}

class ExpenseVoucher extends Model {
    protected $table = 'expense_vouchers';
    
    public function generateVoucherNumber() {
        $stmt = $this->db->query("SELECT MAX(id) as last_id FROM expense_vouchers");
        $last = $stmt->fetch()['last_id'];
        return 'EXP-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }
    
    public function allWithDetails($filters = []) {
        $sql = "SELECT ev.*, e.name as expense_name, ec.name as category_name, b.name as branch_name, u.full_name as created_by_name
                FROM expense_vouchers ev
                LEFT JOIN expenses e ON ev.expense_id = e.id
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN branches b ON ev.branch_id = b.id
                LEFT JOIN users u ON ev.created_by = u.id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['from_date'])) {
            $sql .= " AND ev.date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= " AND ev.date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND ev.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        if (!empty($filters['expense_id'])) {
            $sql .= " AND ev.expense_id = ?";
            $params[] = $filters['expense_id'];
        }
        $sql .= " ORDER BY ev.date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getTotalExpenses($filters = []) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expense_vouchers WHERE 1=1";
        $params = [];
        if (!empty($filters['from_date'])) {
            $sql .= " AND date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= " AND date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
}
