<?php

namespace Paymenter\Extensions\Gateways\QAQMAX;

use App\Classes\Extension\Gateway;
use App\Helpers\ExtensionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class QAQMAX extends Gateway
{
    public function boot()
    {
        if (file_exists(__DIR__ . '/routes/web.php')) {
            require __DIR__ . '/routes/web.php';
        }

        if (is_dir(__DIR__ . '/views')) {
            View::addNamespace('extensions.gateways.qaqmax', __DIR__ . '/views');
        }
    }

    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'base_url',
                'label' => '接口地址（Base URL）',
                'description' => '你的商户接口地址，例如：https://b3a83171.qaqmax.com（不要以 / 结尾）',
                'type' => 'text',
                'required' => true,
                'default' => 'https://b3a83171.qaqmax.com',
            ],
            [
                'name' => 'pid',
                'label' => '商户ID（pid）',
                'description' => 'QAQMAX 商户ID',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'key',
                'label' => '商户密钥（KEY）',
                'description' => '用于 MD5 签名的商户密钥 KEY',
                'type' => 'password',
                'required' => true,
            ],
            [
                'name' => 'mode',
                'label' => '发起支付模式',
                'description' => 'submit：前台表单跳转；mapi：服务端请求接口获取 payurl/qrcode',
                'type' => 'select',
                'required' => true,
                'default' => 'submit',
                'options' => [
                    'submit' => 'submit.php（推荐：最简单）',
                    'mapi' => 'mapi.php（返回 payurl/qrcode）',
                ],
            ],
            [
                'name' => 'type',
                'label' => '支付方式（type）',
                'description' => '可选：alipay / wxpay / usdt；mapi 模式支持 cashier（收银台）',
                'type' => 'select',
                'required' => false,
                'default' => 'cashier',
                'options' => [
                    'cashier' => '收银台（cashier，仅 mapi 推荐）',
                    'alipay' => '支付宝（alipay）',
                    'wxpay' => '微信（wxpay）',
                    'usdt' => 'USDT（usdt）',
                    '' => '不指定（submit 跳转收银台）',
                ],
            ],
            [
                'name' => 'device',
                'label' => '设备类型（device，仅 mapi）',
                'description' => 'pc / mobile / qq / wechat / alipay',
                'type' => 'select',
                'required' => false,
                'default' => 'pc',
                'options' => [
                    'pc' => 'pc',
                    'mobile' => 'mobile',
                    'qq' => 'qq',
                    'wechat' => 'wechat',
                    'alipay' => 'alipay',
                ],
            ],
            [
                'name' => 'rawurl',
                'label' => '返回二维码链接（rawurl，仅 mapi）',
                'description' => '勾选后 rawurl=1，接口可能返回 qrcode 字段',
                'type' => 'checkbox',
                'required' => false,
                'default' => false,
            ],
            [
                'name' => 'product_name',
                'label' => '商品名称（name）',
                'description' => '会显示在支付页面（超过长度可能被截断）',
                'type' => 'text',
                'required' => false,
                'default' => 'Paymenter Invoice',
            ],
            [
                'name' => 'debug_log',
                'label' => '开启调试日志',
                'description' => '会记录请求与回调信息到日志（生产建议关闭）',
                'type' => 'checkbox',
                'required' => false,
                'default' => false,
            ],
        ];
    }

    public function pay($invoice, $total)
    {
        $baseUrl = rtrim((string) $this->config('base_url'), '/');
        $pid = (string) $this->config('pid');
        $key = (string) $this->config('key');
        $mode = (string) ($this->config('mode') ?: 'submit');
        $type = (string) ($this->config('type') ?? '');
        $productName = (string) ($this->config('product_name') ?: ('Invoice #' . ($invoice->id ?? '')));
        $debug = (bool) $this->config('debug_log');

        if (!$baseUrl || !$pid || !$key) {
            return view('extensions.gateways.qaqmax::error', [
                'error' => 'QAQMAX 配置不完整：请先在后台填写 base_url / pid / key。',
            ]);
        }

        $outTradeNo = (string) ($invoice->id ?? '');
        if ($outTradeNo === '') {
            return view('extensions.gateways.qaqmax::error', [
                'error' => 'Invoice ID 缺失，无法发起支付。',
            ]);
        }

        $money = number_format((float) $total, 2, '.', '');

        $notifyUrl = route('extensions.gateways.qaqmax.webhook');
        $returnUrl = route('extensions.gateways.qaqmax.return', ['invoiceId' => $outTradeNo]);

        try {
            if ($mode === 'mapi') {
                return $this->payViaMApi($baseUrl, $pid, $key, $type, $productName, $money, $outTradeNo, $notifyUrl, $returnUrl, $debug);
            }

            return $this->payViaSubmit($baseUrl, $pid, $key, $type, $productName, $money, $outTradeNo, $notifyUrl, $returnUrl, $debug);
        } catch (\Throwable $e) {
            Log::error('QAQMAX pay() exception: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id ?? null,
                'total' => $total ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return view('extensions.gateways.qaqmax::error', [
                'error' => '支付初始化失败，请稍后再试或联系管理员。',
            ]);
        }
    }

    /**
     * submit.php：前台表单跳转（最接近“模板插件”的写法）
     */
    private function payViaSubmit(string $baseUrl, string $pid, string $key, string $type, string $name, string $money, string $outTradeNo, string $notifyUrl, string $returnUrl, bool $debug)
    {
        $endpoint = $baseUrl . '/submit.php';

        $params = [
            'pid' => $pid,
            // type 为空时，QAQMAX 会跳转到收银台选择支付方式
            'type' => $type,
            'out_trade_no' => $outTradeNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => $name,
            'money' => $money,
            // 原样回传（可选）
            'param' => $outTradeNo,
            'sign_type' => 'MD5',
        ];

        // 按文档规则生成 sign
        $params['sign'] = $this->makeSign($params, $key);

        if ($debug) {
            Log::info('QAQMAX submit params', ['endpoint' => $endpoint, 'params' => $this->maskSensitive($params)]);
        }

        return view('extensions.gateways.qaqmax::pay', [
            'endpoint' => $endpoint,
            'params' => $params,
            'invoiceId' => $outTradeNo,
            'amount' => $money,
        ]);
    }

    /**
     * mapi.php：服务端请求，返回 payurl/qrcode/urlscheme
     */
    private function payViaMApi(string $baseUrl, string $pid, string $key, string $type, string $name, string $money, string $outTradeNo, string $notifyUrl, string $returnUrl, bool $debug)
    {
        $endpoint = $baseUrl . '/mapi.php';

        $clientip = request()->ip() ?: '127.0.0.1';
        $device = (string) ($this->config('device') ?: 'pc');
        $rawurl = (bool) $this->config('rawurl');

        $mapiType = $type !== '' ? $type : 'cashier';

        $params = [
            'pid' => $pid,
            'type' => $mapiType,
            'out_trade_no' => $outTradeNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => $name,
            'money' => $money,
            'clientip' => $clientip,
            'device' => $device,
            'rawurl' => $rawurl ? 1 : 0,
            'param' => $outTradeNo,
            'sign_type' => 'MD5',
        ];

        $params['sign'] = $this->makeSign($params, $key);

        if ($debug) {
            Log::info('QAQMAX mapi request', ['endpoint' => $endpoint, 'params' => $this->maskSensitive($params)]);
        }

        $resp = Http::asForm()->timeout(20)->post($endpoint, $params);
        $body = $resp->body();

        if ($debug) {
            Log::info('QAQMAX mapi response', ['status' => $resp->status(), 'body' => $body]);
        }

        if (!$resp->ok()) {
            return view('extensions.gateways.qaqmax::error', [
                'error' => 'mapi.php 请求失败（HTTP ' . $resp->status() . '）',
            ]);
        }

        $json = @json_decode($body, true);
        if (!is_array($json)) {
            return view('extensions.gateways.qaqmax::error', [
                'error' => 'mapi.php 返回不是有效 JSON：' . substr($body, 0, 200),
            ]);
        }

        if (($json['code'] ?? 0) != 1) {
            return view('extensions.gateways.qaqmax::error', [
                'error' => '支付创建失败：' . ($json['msg'] ?? 'unknown error'),
            ]);
        }

        // 优先 payurl 直接跳转
        if (!empty($json['payurl'])) {
            return redirect()->away($json['payurl']);
        }

        // 其次用 qrcode 展示二维码
        if (!empty($json['qrcode'])) {
            return view('extensions.gateways.qaqmax::qrcode', [
                'qrcode' => $json['qrcode'],
                'invoiceId' => $outTradeNo,
                'amount' => $money,
            ]);
        }

        // 再其次 urlscheme（通常用于小程序/拉起支付）
        if (!empty($json['urlscheme'])) {
            return view('extensions.gateways.qaqmax::urlscheme', [
                'urlscheme' => $json['urlscheme'],
                'invoiceId' => $outTradeNo,
                'amount' => $money,
            ]);
        }

        return view('extensions.gateways.qaqmax::error', [
            'error' => 'mapi.php 未返回 payurl/qrcode/urlscheme，请检查通道设置。',
        ]);
    }

    /**
     * notify_url 回调处理（文档：GET 参数）
     * 成功需输出 "success"
     */
    public function webhook(Request $request)
    {
        $debug = (bool) $this->config('debug_log');

        $data = $request->all();
        if ($debug) {
            Log::info('QAQMAX webhook received', [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'query' => $request->query(),
                'all' => $data,
                'ua' => $request->userAgent(),
            ]);
        }

        // 兼容浏览器访问（方便测试）
        if ($request->method() === 'GET' && empty($data)) {
            return response('QAQMAX webhook endpoint is working.', 200);
        }

        $pid = (string) $this->config('pid');
        $key = (string) $this->config('key');

        $receivedSign = strtolower((string) ($data['sign'] ?? ''));
        $computedSign = strtolower($this->makeSign($data, $key));

        if ($receivedSign === '' || $receivedSign !== $computedSign) {
            Log::warning('QAQMAX webhook signature invalid', [
                'received' => $receivedSign,
                'computed' => $computedSign,
                'data' => $this->maskSensitive($data),
            ]);
            return response('fail', 400);
        }

        // 可选：校验 pid
        if (!empty($data['pid']) && (string) $data['pid'] !== $pid) {
            Log::warning('QAQMAX webhook pid mismatch', [
                'expected' => $pid,
                'received' => (string) $data['pid'],
            ]);
            return response('fail', 400);
        }

        $tradeStatus = (string) ($data['trade_status'] ?? '');
        if ($tradeStatus !== 'TRADE_SUCCESS') {
            if ($debug) {
                Log::info('QAQMAX webhook not success status', ['trade_status' => $tradeStatus]);
            }
            // 按常见易支付模板，这里仍然返回 success，避免反复通知
            return response('success', 200);
        }

        $invoiceId = (string) ($data['out_trade_no'] ?? '');
        $amount = (string) ($data['money'] ?? '');
        $transactionId = (string) ($data['trade_no'] ?? '');

        if ($invoiceId === '' || $amount === '' || $transactionId === '') {
            Log::warning('QAQMAX webhook missing fields', ['data' => $this->maskSensitive($data)]);
            return response('fail', 400);
        }

        // 记录入账
        try {
            ExtensionHelper::addPayment($invoiceId, 'QAQMAX', $amount, null, $transactionId);
            if ($debug) {
                Log::info('QAQMAX payment recorded via ExtensionHelper', [
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                ]);
            }
        } catch (\Throwable $e) {
            // fallback：直接写 InvoiceItem（参考常见网关插件做法）
            Log::warning('QAQMAX ExtensionHelper failed, trying direct method: ' . $e->getMessage());

            try {
                $invoice = \App\Models\Invoice::find($invoiceId);
                if (!$invoice) {
                    return response('fail', 404);
                }

                $payment = new \App\Models\InvoiceItem();
                $payment->invoice_id = $invoiceId;
                $payment->description = 'QAQMAX Payment';
                $payment->amount = $amount;
                $payment->type = 'payment';
                $payment->gateway = 'QAQMAX';
                $payment->transaction_id = $transactionId;
                $payment->payment_method = (string) ($data['type'] ?? 'qaqmax');
                $payment->save();

                $invoice->refresh();
            } catch (\Throwable $e2) {
                Log::error('QAQMAX direct payment record failed: ' . $e2->getMessage(), [
                    'trace' => $e2->getTraceAsString(),
                ]);
                return response('fail', 500);
            }
        }

        // 文档要求：返回 success
        return response('success', 200);
    }

    /**
     * QAQMAX MD5 签名：
     * - 参数按 ASCII 升序
     * - sign / sign_type / 空值或 0 不参与
     * - value 不 URL 编码
     * - sign = md5(query + KEY)，小写
     */
    private function makeSign(array $params, string $key): string
    {
        unset($params['sign'], $params['sign_type']);

        // 过滤空值或 0（按文档）
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v === null) continue;
            if (is_string($v) && trim($v) === '') continue;
            if ($v === 0 || $v === '0') continue;
            $filtered[$k] = $v;
        }

        ksort($filtered, SORT_STRING);

        $pairs = [];
        foreach ($filtered as $k => $v) {
            // 注意：不要 urlencode value
            $pairs[] = $k . '=' . $v;
        }

        $query = implode('&', $pairs);
        return strtolower(md5($query . $key));
    }

    private function maskSensitive(array $data): array
    {
        $masked = $data;
        if (isset($masked['sign'])) $masked['sign'] = '***';
        if (isset($masked['key'])) $masked['key'] = '***';
        return $masked;
    }
}
