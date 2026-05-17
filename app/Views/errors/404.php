<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>404 - الصفحة غير موجودة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="text-center bg-white p-8 rounded-lg shadow-lg max-w-md">
        <i class="fas fa-exclamation-triangle text-6xl text-yellow-500 mb-4"></i>
        <h1 class="text-3xl font-bold mb-2">404</h1>
        <p class="text-gray-600 mb-4">عذراً، الصفحة التي تبحث عنها غير موجودة.</p>
        <a href="<?php echo APP_URL; ?>/dashboard" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-home"></i> العودة للرئيسية
        </a>
    </div>
</body>
</html>
