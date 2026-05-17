<?php
require_once __DIR__ . '/../Core/Controller.php';

class TestPrintController extends Controller {
    public function receipt($id) {
        echo "Test Receipt ID: " . $id; exit;
    }
}
