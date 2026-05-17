<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>500 - خطأ داخلي في الخادم</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="text-center bg-white p-8 rounded-lg shadow-lg max-w-md">
        <i class="fas fa-bug text-6xl text-red-500 mb-4"></i>
        <h1 class="text-3xl font-bold mb-2">500</h1>
        <p class="text-gray-600 mb-4">حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً أو الاتصال بالدعم الفني.</p>
        <a href="<?php echo APP_URL; ?>/dashboard" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-home"></i> العودة للرئيسية
        </a>
    </div>
</body>
</html>
