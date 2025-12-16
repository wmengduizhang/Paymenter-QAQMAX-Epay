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
        <h2 class="text-2xl font-bold text-gray-900">正在跳转到 QAQMAX</h2>
        <p class="text-gray-600 mt-2">请勿关闭页面，正在为你创建支付…</p>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-700">
        <div class="flex justify-between"><span>账单ID</span><span class="font-mono">{{ $invoiceId }}</span></div>
        <div class="flex justify-between mt-2"><span>金额</span><span class="font-mono">{{ $amount }}</span></div>
    </div>

    <form id="paymentForm" method="POST" action="{{ $endpoint }}">
        @foreach($params as $k => $v)
            @if($v !== '' && $v !== null)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach

        <button type="submit" class="w-full px-4 py-2 rounded-md bg-black text-white hover:opacity-90">
            如果没有自动跳转，请点击继续
        </button>
    </form>

    <p class="text-xs text-gray-500 mt-4 text-center">
        5 秒后自动提交…
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let countdown = 5;
    const el = document.querySelector('p.text-xs');
    const timer = setInterval(function () {
        countdown--;
        if (el) el.textContent = countdown + ' 秒后自动提交…';
        if (countdown <= 0) {
            clearInterval(timer);
            document.getElementById('paymentForm').submit();
        }
    }, 1000);
});
</script>
</body>
</html>
