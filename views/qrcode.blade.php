<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QAQMAX 二维码支付</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
<div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">请扫码支付</h2>
        <p class="text-gray-600 mt-2">完成后会自动跳回账单页面</p>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-700">
        <div class="flex justify-between"><span>账单ID</span><span class="font-mono">{{ $invoiceId }}</span></div>
        <div class="flex justify-between mt-2"><span>金额</span><span class="font-mono">{{ $amount }}</span></div>
    </div>

    <div class="flex items-center justify-center">
        <div id="qrcode" class="p-4 bg-white border rounded-lg"></div>
    </div>

    <p class="text-xs text-gray-500 mt-4 break-all">
        链接：{{ $qrcode }}
    </p>

    <a class="inline-block mt-6 w-full text-center px-4 py-2 rounded-md bg-black text-white hover:opacity-90"
       href="{{ route('extensions.gateways.qaqmax.return', ['invoiceId' => $invoiceId]) }}">
        已支付？返回账单
    </a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-VT0F3Hgn5p5nPG6xQmd6QOB4Jr2A24X7z1Vq3qnhK5o7tQeAOFu+LrNhg0oYI9oCz/dfp2P1lGxYb+4Y6m5Y9A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: @json($qrcode),
    width: 220,
    height: 220
});
</script>
</body>
</html>
