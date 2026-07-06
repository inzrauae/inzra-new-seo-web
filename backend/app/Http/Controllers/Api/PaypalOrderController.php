<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PaypalOrderController extends Controller
{
    public function options(Request $request)
    {
        if (! $this->isOriginAllowed($request)) {
            return response('', 403)->withHeaders($this->corsHeaders($request));
        }

        return response('', 204)->withHeaders($this->corsHeaders($request));
    }

    public function create(Request $request): JsonResponse
    {
        if (! $this->isOriginAllowed($request)) {
            return $this->jsonError('Origin is not allowed to use the order API.', 403, $request);
        }

        $validator = Validator::make($request->all(), [
            'itemNumber' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'Invalid order request.',
                'errors' => $validator->errors(),
            ], 422, $request);
        }

        try {
            $product = $this->findProduct((string) $validator->validated()['itemNumber']);
            $paypalOrder = $this->paypalRequest('POST', '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => (string) $product['itemNumber'],
                        'description' => sprintf('%s (Item %s)', $product['title'], $product['itemNumber']),
                        'amount' => [
                            'currency_code' => (string) $product['currency'],
                            'value' => (string) $product['price'],
                        ],
                    ],
                ],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'shipping_preference' => 'NO_SHIPPING',
                            'user_action' => 'PAY_NOW',
                        ],
                    ],
                ],
            ]);

            $orderId = (string) data_get($paypalOrder, 'id', '');

            if ($orderId === '') {
                throw new RuntimeException('PayPal did not return an order id.');
            }

            return $this->jsonResponse([
                'ok' => true,
                'id' => $orderId,
                'orderId' => $orderId,
            ], 200, $request);
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), 500, $request);
        } catch (RequestException $exception) {
            return $this->paypalErrorResponse($exception, $request, 'Could not create the PayPal order.');
        }
    }

    public function capture(Request $request): JsonResponse
    {
        if (! $this->isOriginAllowed($request)) {
            return $this->jsonError('Origin is not allowed to use the order API.', 403, $request);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => ['required', 'string', 'max:191'],
            'itemNumber' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'Invalid capture request.',
                'errors' => $validator->errors(),
            ], 422, $request);
        }

        $validated = $validator->validated();

        try {
            $product = $this->findProduct((string) $validated['itemNumber']);

            try {
                $paypalPayload = $this->paypalRequest('POST', '/v2/checkout/orders/'.urlencode((string) $validated['orderId']).'/capture', []);
            } catch (RequestException $exception) {
                if ($exception->response?->status() !== 422) {
                    throw $exception;
                }

                $paypalPayload = $this->paypalRequest('GET', '/v2/checkout/orders/'.urlencode((string) $validated['orderId']));
            }

            $purchaseUnit = data_get($paypalPayload, 'purchase_units.0');
            $capture = data_get($purchaseUnit, 'payments.captures.0');
            $captureId = (string) data_get($capture, 'id', '');
            $captureStatus = strtoupper((string) data_get($capture, 'status', data_get($paypalPayload, 'status', '')));
            $currency = (string) data_get($capture, 'amount.currency_code', data_get($purchaseUnit, 'amount.currency_code', $product['currency']));
            $paidAmount = (string) data_get($capture, 'amount.value', data_get($purchaseUnit, 'amount.value', $product['price']));

            if ($captureStatus !== 'COMPLETED' || $captureId === '') {
                return $this->jsonError('PayPal capture was not completed for this order.', 422, $request);
            }

            if ($currency !== $product['currency'] || $paidAmount !== $product['price']) {
                return $this->jsonError('PayPal amount does not match the catalog price for this product.', 422, $request);
            }

            $payerName = trim(sprintf(
                '%s %s',
                (string) data_get($paypalPayload, 'payer.name.given_name', ''),
                (string) data_get($paypalPayload, 'payer.name.surname', '')
            ));

            $order = Order::updateOrCreate(
                ['order_id' => (string) $validated['orderId']],
                [
                    'paypal_order_status' => (string) data_get($paypalPayload, 'status', ''),
                    'capture_id' => $captureId,
                    'capture_status' => $captureStatus,
                    'payer_name' => $payerName !== '' ? $payerName : 'Customer',
                    'payer_email' => (string) data_get($paypalPayload, 'payer.email_address', ''),
                    'item_number' => (string) $product['itemNumber'],
                    'title' => (string) $product['title'],
                    'price' => (string) $product['price'],
                    'currency' => (string) $product['currency'],
                    'paid_at' => $this->parseTimestamp((string) data_get($capture, 'create_time', data_get($paypalPayload, 'create_time', ''))),
                    'raw_payload' => $request->all(),
                    'paypal_payload' => $paypalPayload,
                ]
            );

            if ($order->mail_sent_at === null) {
                $this->sendOrderNotification($order);
            }

            return $this->jsonResponse([
                'ok' => true,
                'orderId' => $order->order_id,
                'captureId' => $order->capture_id,
                'payerName' => $order->payer_name,
                'mailSent' => $order->mail_sent_at !== null,
            ], 200, $request);
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), 500, $request);
        } catch (RequestException $exception) {
            return $this->paypalErrorResponse($exception, $request, 'Could not capture the PayPal order.');
        }
    }

    private function sendOrderNotification(Order $order): void
    {
        try {
            Mail::raw($this->notificationBody($order), function ($message) use ($order): void {
                $message
                    ->to((string) config('services.paypal.notification_email'))
                    ->subject(sprintf('New Inzra order %s for item %s', $order->order_id, $order->item_number));

                if ($order->payer_email !== null && $order->payer_email !== '') {
                    $message->replyTo($order->payer_email, $order->payer_name ?: $order->payer_email);
                }
            });

            $order->forceFill([
                'mail_sent_at' => now(),
                'mail_error' => null,
            ])->save();
        } catch (\Throwable $exception) {
            report($exception);

            $order->forceFill([
                'mail_error' => $exception->getMessage(),
            ])->save();
        }
    }

    private function notificationBody(Order $order): string
    {
        return implode(PHP_EOL, [
            'A new purchase has been recorded on inzra.com.',
            '',
            'Order details',
            '-------------',
            'Order ID: '.$order->order_id,
            'Capture ID: '.($order->capture_id ?: 'Not provided'),
            'Capture status: '.($order->capture_status ?: 'Not provided'),
            'Recorded at UTC: '.($order->created_at?->copy()->utc()->toIso8601String() ?: now()->utc()->toIso8601String()),
            '',
            'Payer details',
            '-------------',
            'Name: '.($order->payer_name ?: 'Customer'),
            'Email: '.($order->payer_email ?: 'Not provided'),
            '',
            'Product details',
            '---------------',
            'Item number: '.$order->item_number,
            'Title: '.$order->title,
            'Price: '.number_format((float) $order->price, 2, '.', '').' '.$order->currency,
        ]);
    }

    private function findProduct(string $itemNumber): array
    {
        $catalogPath = (string) config('services.paypal.catalog_path');

        if ($catalogPath === '' || ! is_file($catalogPath)) {
            throw new RuntimeException('Store catalog file was not found. Set STORE_CATALOG_PATH in the Laravel environment.');
        }

        $catalog = json_decode((string) file_get_contents($catalogPath), true);

        if (! is_array($catalog)) {
            throw new RuntimeException('Store catalog JSON is invalid.');
        }

        foreach ($catalog as $product) {
            if ((string) data_get($product, 'itemNumber') !== $itemNumber) {
                continue;
            }

            return [
                'itemNumber' => (string) data_get($product, 'itemNumber'),
                'title' => (string) data_get($product, 'title', 'Inzra Product'),
                'price' => number_format((float) data_get($product, 'price', 0), 2, '.', ''),
                'currency' => 'USD',
            ];
        }

        throw new RuntimeException('Product not found in the store catalog.');
    }

    private function parseTimestamp(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function paypalRequest(string $method, string $path, array $payload = []): array
    {
        $clientId = trim((string) config('services.paypal.client_id'));
        $clientSecret = trim((string) config('services.paypal.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('PayPal credentials are not configured in Laravel.');
        }

        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->withBasicAuth($clientId, $clientSecret)
            ->post($this->paypalBaseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ])
            ->throw();

        $accessToken = (string) $tokenResponse->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('PayPal did not return an access token.');
        }

        $request = Http::acceptJson()
            ->withToken($accessToken)
            ->withHeaders([
                'PayPal-Request-Id' => 'inzra-'.md5($method.$path.json_encode($payload)),
            ]);

        if ($method === 'GET') {
            return $request->get($this->paypalBaseUrl().$path)->throw()->json();
        }

        return $request->asJson()->send($method, $this->paypalBaseUrl().$path, [
            'json' => $payload,
        ])->throw()->json();
    }

    private function paypalBaseUrl(): string
    {
        return match (strtolower((string) config('services.paypal.mode', 'live'))) {
            'sandbox' => 'https://api-m.sandbox.paypal.com',
            'live' => 'https://api-m.paypal.com',
            default => throw new RuntimeException('PAYPAL_MODE must be either live or sandbox.'),
        };
    }

    private function paypalErrorResponse(RequestException $exception, Request $request, string $fallbackMessage): JsonResponse
    {
        $payload = $exception->response?->json();
        $message = (string) data_get($payload, 'message', data_get($payload, 'details.0.description', $fallbackMessage));
        $status = $exception->response?->status() ?? 502;

        return $this->jsonResponse([
            'ok' => false,
            'message' => $message !== '' ? $message : $fallbackMessage,
        ], $status, $request);
    }

    private function jsonError(string $message, int $status, Request $request): JsonResponse
    {
        return $this->jsonResponse([
            'ok' => false,
            'message' => $message,
        ], $status, $request);
    }

    private function jsonResponse(array $payload, int $status, Request $request): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders($this->corsHeaders($request));
    }

    private function corsHeaders(Request $request): array
    {
        $headers = [
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
            'Vary' => 'Origin',
        ];

        $origin = $request->headers->get('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if ($allowedOrigin !== null) {
            $headers['Access-Control-Allow-Origin'] = $allowedOrigin;
        }

        return $headers;
    }

    private function isOriginAllowed(Request $request): bool
    {
        $origin = $request->headers->get('Origin');

        if ($origin === null || $origin === '') {
            return true;
        }

        return $this->resolveAllowedOrigin($origin) !== null;
    }

    private function resolveAllowedOrigin(?string $origin): ?string
    {
        $allowedOrigins = array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) config('services.paypal.frontend_origins', ''))
        )));

        if ($origin === null || $origin === '') {
            return null;
        }

        if (in_array('*', $allowedOrigins, true)) {
            return '*';
        }

        return in_array($origin, $allowedOrigins, true) ? $origin : null;
    }
}
