<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付错误</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
<div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-900">支付初始化失败</h2>
    <p class="text-gray-700 mt-3">{{ $error ?? 'Unknown error' }}</p>

    <a href="javascript:history.back()" class="inline-block mt-6 w-full text-center px-4 py-2 rounded-md bg-black text-white hover:opacity-90">
        返回上一页
    </a>
</div>
</body>
</html>
