<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QAQMAX 支付</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
<div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">继续支付</h2>
        <p class="text-gray-600 mt-2">点击按钮拉起支付（如果你的环境支持）</p>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-700">
        <div class="flex justify-between"><span>账单ID</span><span class="font-mono">{{ $invoiceId }}</span></div>
        <div class="flex justify-between mt-2"><span>金额</span><span class="font-mono">{{ $amount }}</span></div>
    </div>

    <a class="inline-block w-full text-center px-4 py-2 rounded-md bg-black text-white hover:opacity-90" href="{{ $urlscheme }}">
        拉起支付
    </a>

    <p class="text-xs text-gray-500 mt-4 break-all">urlscheme：{{ $urlscheme }}</p>

    <a class="inline-block mt-4 w-full text-center px-4 py-2 rounded-md border" href="{{ route('extensions.gateways.qaqmax.return', ['invoiceId' => $invoiceId]) }}">
        返回账单
    </a>
</div>
</body>
</html>
