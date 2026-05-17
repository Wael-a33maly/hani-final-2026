<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Unit.php';

class UnitController extends Controller
{
    public function index()
    {
        requireRole('admin');
        $model = new Unit();
        $units = $model->all();
        $this->view('units.index', compact('units'));
    }

    public function store()
    {
        requireRole('admin');
        $this->verifyCSRF();
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['error'] = 'اسم الوحدة مطلوب';
            redirect('/units');
        }
        $model = new Unit();
        $model->insert(['name' => $name]);
        $_SESSION['success'] = "تم إضافة وحدة: $name";
        redirect('/units');
    }

    public function delete($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Unit();
        $model->delete($id);
        $_SESSION['success'] = 'تم حذف الوحدة';
        redirect('/units');
    }
}
