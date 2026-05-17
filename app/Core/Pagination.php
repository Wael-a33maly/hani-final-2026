<?php
/**
 * Pagination.php - نظام ترقيم الصفحات البسيط
 * يعمل مع PDO ويولد HTML لأزرار الترقيم بتصميم Tailwind
 */
class Pagination {
    /**
     * تنفيذ استعلام مع pagination
     * @param PDO $db اتصال قاعدة البيانات
     * @param string $baseQuery الاستعلام الأساسي (بدون LIMIT)
     * @param string $countQuery استعلام لحساب العدد الكلي
     * @param array $params قيم الـ parameters
     * @param int $page رقم الصفحة الحالية
     * @param int $perPage عدد النتائج في الصفحة
     * @return array ['data', 'total', 'current_page', 'last_page', 'per_page', 'links']
     */
    public static function paginate($db, $baseQuery, $countQuery, $params, $page = 1, $perPage = 20) {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $perPage;
        
        // جلب العدد الكلي
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        
        // حساب عدد الصفحات
        $lastPage = ceil($total / $perPage);
        $lastPage = max(1, $lastPage);
        
        // تعديل الصفحة الحالية إذا كانت أكبر من الأخيرة
        if ($page > $lastPage && $total > 0) {
            $page = $lastPage;
            $offset = ($page - 1) * $perPage;
        }
        
        // جلب البيانات مع LIMIT
        $query = $baseQuery . " LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // توليد روابط الترقيم
        $baseUrl = self::getBaseUrl();
        $links = self::renderLinks($page, $lastPage, $baseUrl);
        
        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'links' => $links
        ];
    }
    
    /**
     * الحصول على رابط الصفحة الحالية بدون معامل page
     */
    private static function getBaseUrl() {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $url = preg_replace('/[?&]page=\d+/', '', $url);
        $url = preg_replace('/\?$/', '', $url);
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        return $url;
    }
    
    /**
     * توليد HTML لأزرار الترقيم (تصميم Tailwind)
     * @param int $currentPage
     * @param int $lastPage
     * @param string $baseUrl
     * @return string HTML
     */
    public static function renderLinks($currentPage, $lastPage, $baseUrl) {
        if ($lastPage <= 1) {
            return '';
        }
        
        $html = '<div class="flex justify-center items-center space-x-1 mt-4 space-x-reverse rtl:flex-row-reverse">';
        
        // زر السابق
        if ($currentPage > 1) {
            $html .= '<a href="' . htmlspecialchars($baseUrl . 'page=' . ($currentPage - 1)) . '" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-gray-700 text-sm"><i class="fas fa-chevron-right ml-1"></i> السابق</a>';
        } else {
            $html .= '<span class="px-3 py-1 bg-gray-100 rounded text-gray-400 cursor-not-allowed text-sm"><i class="fas fa-chevron-right ml-1"></i> السابق</span>';
        }
        
        // الأرقام
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $currentPage + 2);
        
        if ($start > 1) {
            $html .= '<a href="' . htmlspecialchars($baseUrl . 'page=1') . '" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">1</a>';
            if ($start > 2) {
                $html .= '<span class="px-2 text-gray-400">...</span>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $currentPage) {
                $html .= '<span class="px-3 py-1 bg-blue-600 text-white rounded font-bold text-sm">' . $i . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($baseUrl . 'page=' . $i) . '" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">' . $i . '</a>';
            }
        }
        
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $html .= '<span class="px-2 text-gray-400">...</span>';
            }
            $html .= '<a href="' . htmlspecialchars($baseUrl . 'page=' . $lastPage) . '" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">' . $lastPage . '</a>';
        }
        
        // زر التالي
        if ($currentPage < $lastPage) {
            $html .= '<a href="' . htmlspecialchars($baseUrl . 'page=' . ($currentPage + 1)) . '" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-gray-700 text-sm">التالي <i class="fas fa-chevron-left mr-1"></i></a>';
        } else {
            $html .= '<span class="px-3 py-1 bg-gray-100 rounded text-gray-400 cursor-not-allowed text-sm">التالي <i class="fas fa-chevron-left mr-1"></i></span>';
        }
        
        $html .= '</div>';
        return $html;
    }
}
