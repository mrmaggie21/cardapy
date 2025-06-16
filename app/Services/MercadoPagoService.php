<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment as MercadoPagoPayment;
use MercadoPago\Resources\Preference;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private Restaurant $restaurant;
    private PaymentClient $paymentClient;
    private PreferenceClient $preferenceClient;

    public function __construct(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
        
        // Configura token de acesso específico do restaurante
        MercadoPagoConfig::setAccessToken($restaurant->mercadopago_access_token);
        
        $this->paymentClient = new PaymentClient();
        $this->preferenceClient = new PreferenceClient();
    }

    /**
     * Cria preferência de pagamento para checkout
     */
    public function createPreference(Order $order): Preference
    {
        try {
            $items = [];
            
            foreach ($order->items as $orderItem) {
                $items[] = [
                    'id' => $orderItem->menu_item_id,
                    'title' => $orderItem->item_name,
                    'description' => $orderItem->item_description ?? '',
                    'quantity' => $orderItem->quantity,
                    'unit_price' => (float) $orderItem->price,
                    'currency_id' => 'BRL'
                ];
            }

            // Adiciona taxa de entrega como item separado
            if ($order->delivery_fee > 0) {
                $items[] = [
                    'id' => 'delivery',
                    'title' => 'Taxa de Entrega',
                    'description' => 'Taxa de entrega do pedido',
                    'quantity' => 1,
                    'unit_price' => (float) $order->delivery_fee,
                    'currency_id' => 'BRL'
                ];
            }

            $preferenceData = [
                'items' => $items,
                'payer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => [
                        'number' => $order->customer_phone
                    ]
                ],
                'payment_methods' => [
                    'default_payment_method_id' => null,
                    'excluded_payment_types' => [],
                    'excluded_payment_methods' => [],
                    'installments' => 12,
                    'default_installments' => 1
                ],
                'shipments' => [
                    'cost' => (float) $order->delivery_fee,
                    'mode' => 'not_specified'
                ],
                'notification_url' => route('api.mercadopago.webhook'),
                'back_urls' => [
                    'success' => route('order.success', $order->id),
                    'pending' => route('order.pending', $order->id),
                    'failure' => route('order.failure', $order->id)
                ],
                'auto_return' => 'approved',
                'external_reference' => $order->id,
                'expires' => true,
                'expiration_date_from' => now()->toISOString(),
                'expiration_date_to' => now()->addHours(24)->toISOString(),
                'metadata' => [
                    'restaurant_id' => $this->restaurant->id,
                    'order_id' => $order->id
                ]
            ];

            $preference = $this->preferenceClient->create($preferenceData);
            
            Log::info('MercadoPago preference created', [
                'order_id' => $order->id,
                'preference_id' => $preference->id
            ]);

            return $preference;

        } catch (\Exception $e) {
            Log::error('Erro ao criar preferência MercadoPago', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erro ao processar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Cria pagamento PIX
     */
    public function createPixPayment(Order $order): MercadoPagoPayment
    {
        try {
            $paymentData = [
                'transaction_amount' => (float) $order->total,
                'description' => "Pedido #{$order->order_number} - {$this->restaurant->name}",
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $order->customer_email,
                    'first_name' => explode(' ', $order->customer_name)[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $order->customer_name), 1)) ?: 'Cliente',
                    'identification' => [
                        'type' => 'CPF',
                        'number' => '00000000000' // Em produção, capturar CPF real
                    ]
                ],
                'notification_url' => route('api.mercadopago.webhook'),
                'external_reference' => (string) $order->id,
                'metadata' => [
                    'restaurant_id' => $this->restaurant->id,
                    'order_id' => $order->id
                ]
            ];

            $payment = $this->paymentClient->create($paymentData);
            
            // Salva informações do pagamento
            Payment::create([
                'order_id' => $order->id,
                'mercadopago_payment_id' => $payment->id,
                'payment_method' => 'pix',
                'amount' => $order->total,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'external_reference' => $order->id,
                'qr_code' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                'ticket_url' => $payment->point_of_interaction->transaction_data->ticket_url ?? null
            ]);

            Log::info('PIX payment created', [
                'order_id' => $order->id,
                'payment_id' => $payment->id
            ]);

            return $payment;

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erro ao processar pagamento PIX: ' . $e->getMessage());
        }
    }

    /**
     * Consulta status do pagamento
     */
    public function getPayment(string $paymentId): MercadoPagoPayment
    {
        try {
            return $this->paymentClient->get($paymentId);
        } catch (\Exception $e) {
            Log::error('Erro ao consultar pagamento', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Erro ao consultar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Processa webhook do MercadoPago
     */
    public function processWebhook(array $data): void
    {
        try {
            $type = $data['type'] ?? null;
            $dataId = $data['data']['id'] ?? null;

            if ($type === 'payment' && $dataId) {
                $payment = $this->getPayment($dataId);
                $this->updatePaymentStatus($payment);
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook MercadoPago', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Atualiza status do pagamento baseado no webhook
     */
    private function updatePaymentStatus(MercadoPagoPayment $mercadoPagoPayment): void
    {
        $externalReference = $mercadoPagoPayment->external_reference;
        
        if (!$externalReference) {
            return;
        }

        $order = Order::find($externalReference);
        if (!$order) {
            return;
        }

        $payment = Payment::where('mercadopago_payment_id', $mercadoPagoPayment->id)->first();
        
        if ($payment) {
            $payment->update([
                'status' => $mercadoPagoPayment->status,
                'status_detail' => $mercadoPagoPayment->status_detail
            ]);
        }

        // Atualiza status do pedido baseado no pagamento
        $newOrderStatus = match($mercadoPagoPayment->status) {
            'approved' => Order::STATUS_CONFIRMED,
            'rejected', 'cancelled' => Order::STATUS_CANCELLED,
            default => $order->status
        };

        if ($newOrderStatus !== $order->status) {
            $order->update([
                'payment_status' => $mercadoPagoPayment->status,
                'status' => $newOrderStatus
            ]);

            if ($newOrderStatus === Order::STATUS_CONFIRMED) {
                $order->update(['confirmed_at' => now()]);
            } elseif ($newOrderStatus === Order::STATUS_CANCELLED) {
                $order->update([
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Pagamento rejeitado'
                ]);
            }
        }

        Log::info('Payment status updated', [
            'order_id' => $order->id,
            'payment_status' => $mercadoPagoPayment->status,
            'order_status' => $newOrderStatus
        ]);
    }

    /**
     * Verifica se as credenciais estão configuradas
     */
    public function hasValidCredentials(): bool
    {
        return !empty($this->restaurant->mercadopago_access_token) && 
               !empty($this->restaurant->mercadopago_public_key);
    }
} 