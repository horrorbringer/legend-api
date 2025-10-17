<?php

namespace App\Services;

use KHQR\BakongKHQR;
use KHQR\Models\MerchantInfo;
use KHQR\Models\IndividualInfo;
use KHQR\Helpers\KHQRData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use BaconQrCode\Renderer\Color\Color;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class KHQRService
{
    protected string $baseUrl;
    protected string $token;
    protected ?BakongKHQR $bakongInstance = null;
    protected array $merchantConfig;

    public function __construct()
    {
        $this->baseUrl = config('services.bakong.base_url', '');
        $this->token = config('services.bakong.token', '');

        // Store merchant config for reuse
        $this->merchantConfig = [
            'bakongAccountID' => config('services.bakong.account_id'),
            'merchantName' => config('services.bakong.merchant_name', 'Legend Cinema'),
            'merchantCity' => config('services.bakong.merchant_city', 'Phnom Penh'),
            'merchantID' => config('services.bakong.merchant_id'),
            'acquiringBank' => config('services.bakong.acquiring_bank'),
            'mobileNumber' => config('services.bakong.mobile_number')
        ];

        // Log actual config values for debugging
        Log::debug('KHQRService configuration:', [
            'bakong_config' => array_map(function ($value) {
                return is_string($value) ? $value : gettype($value);
            }, $this->merchantConfig)
        ]);

        // Initialize BakongKHQR instance if token is available
        if (!empty($this->token)) {
            try {
                $this->bakongInstance = new BakongKHQR($this->token);
                Log::info('BakongKHQR instance initialized successfully');
            } catch (\Exception $e) {
                Log::error('Failed to initialize BakongKHQR instance: ' . $e->getMessage());
            }
        }

        // Log configuration for debugging
        Log::info('KHQRService initialized', [
            'baseUrl' => $this->baseUrl,
            'token_exists' => !empty($this->token),
            'bakong_instance_ready' => $this->bakongInstance !== null,
            'merchant_config' => array_map(fn($value) =>
                is_string($value) ? (strlen($value) > 4 ? substr($value, 0, 4) . '...' : $value) : $value,
                $this->merchantConfig
            )
        ]);
    }

    /**
     * Generate KHQR code for a booking
     *
     * @param string $bookingId
     * @param float $amount
     * @param string $currency
     * @return array
     */
    protected function createIndividualQR($accountId, $name, $city, $amount, $currency)
    {
        try {
            // Prepare data in the format that works
            $data = [
                'bakongAccountID' => $accountId,
                'merchantName' => substr($name, 0, 15),
                'merchantCity' => substr($city, 0, 15),
                'amount' => number_format(floatval($amount), 2, '.', ''),
                'currency' => $currency
            ];

            Log::info('Generating individual KHQR', $data);

            // Create IndividualInfo object using named parameters
            $info = new IndividualInfo(
                bakongAccountID: $data['bakongAccountID'],
                merchantName: $data['merchantName'],
                merchantCity: $data['merchantCity'],
                currency: $data['currency'],
                amount: $data['amount']
            );

            // Generate QR code using the static method
            $result = BakongKHQR::generateIndividual($info);

            Log::info('KHQR generated successfully', ['result' => $result]);

            return [
                'success' => true,
                'qr_code' => $result->data['qr'] ?? null,
                'md5_hash' => $result->data['md5'] ?? null,
                'data' => $result->data ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate individual KHQR: ' . $e->getMessage(), [
                'data' => $data ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateQRCode($bookingId, $amount, $currency = 'USD')
    {
        try {
            Log::info('Generating KHQR for booking', [
                'bookingId' => $bookingId,
                'amount' => $amount,
                'currency' => $currency
            ]);

            // Validate amount
            if ($amount <= 0) {
                throw new \Exception('Amount must be greater than 0');
            }

            // Validate configuration
            if (empty($this->merchantConfig['bakongAccountID'])) {
                throw new \Exception('Bakong Account ID is not configured');
            }

            // Generate KHQR using individual info
            $currencyCode = $currency === 'USD' ? 840 : 116; // 840 for USD, 116 for KHR

            Log::debug('Attempting KHQR generation with:', [
                'account' => $this->merchantConfig['bakongAccountID'],
                'amount' => $amount,
                'currency_code' => $currencyCode
            ]);

            // Generate QR code
            try {
                $qrResult = $this->createIndividualQR(
                    $this->merchantConfig['bakongAccountID'],
                    $this->merchantConfig['merchantName'],
                    $this->merchantConfig['merchantCity'],
                    number_format($amount, 2, '.', ''),
                    $currencyCode
                );

                if (!$qrResult['success']) {
                    throw new \Exception($qrResult['error'] ?? 'Failed to generate KHQR code');
                }

                $qrString = $qrResult['qr_code'];
                $md5Hash = $qrResult['md5_hash'];

                if (!$qrString || !$md5Hash) {
                    throw new \Exception('Failed to generate KHQR: Missing required data');
                }

                $shortHash = substr($md5Hash, 0, 8);

            } catch (\Exception $e) {
                Log::error('Failed to generate KHQR code:', [
                    'error' => $e->getMessage(),
                    'booking_id' => $bookingId,
                    'amount' => $amount,
                    'currency' => $currency
                ]);
                throw $e;
            }

            Log::info('KHQR generated successfully', [
                'booking_id' => $bookingId,
                'amount' => $amount,
                'currency' => $currency,
                'short_hash' => $shortHash
            ]);

            // Generate QR code image
            try {
                // Configure QR code renderer with optimal settings for KHQR
                $renderer = new ImageRenderer(
                    new RendererStyle(400, 4), // Increased size for better scanning
                    new SvgImageBackEnd()
                );

                $writer = new Writer($renderer);

                // Generate QR code
                $qrImage = $writer->writeString($qrString);
                $qrBase64 = base64_encode($qrImage);

            } catch (\Exception $e) {
                Log::error('QR Code image generation failed: ' . $e->getMessage());
                throw new \Exception('Failed to generate QR code image');
            }

            $result = [
                'success' => true,
                'qr_code' => $qrBase64,
                'qr_string' => $qrString,
                'reference_number' => $bookingId,
                'amount' => $amount,
                'currency' => $currency,
                'merchant_name' => $this->merchantConfig['merchantName'],
                'short_hash' => $shortHash,
                'md5_hash' => $md5Hash,
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
            ];

            Log::info('KHQR generated successfully', array_merge(
                $result,
                ['qr_code' => '[BASE64_ENCODED_IMAGE]'] // Don't log the full image
            ));

            return $result;

        } catch (\Exception $e) {
            Log::error('KHQR Generation Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate QR code image from string
     *
     * @param string $qrString
     * @return string Base64 encoded image
     */
    protected function generateQRImage($qrString)
    {
        // Using simple QR code generator
        // You can use any QR library like endroid/qr-code

        try {
            // Create QR code using a library
            $writer = new \BaconQrCode\Writer(
                new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                )
            );

            $qrCode = $writer->writeString($qrString);
            return base64_encode($qrCode);

        } catch (\Exception $e) {
            Log::error('QR Image Generation Failed: ' . $e->getMessage());

            // Fallback: return QR string
            return base64_encode($qrString);
        }
    }

    /**
     * Check payment status using MD5 hash
     */
    public function checkTransactionByMd5(string $md5)
    {
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('BakongKHQR service not properly configured');
            }

            Log::info('Checking transaction by MD5', ['md5' => $md5]);

            $response = $this->bakongInstance->checkTransactionByMD5($md5);

            Log::info('Transaction check response', [
                'response' => $response
            ]);

            if ($response && isset($response['responseCode'])) {
                return [
                    'success' => true,
                    'status' => $response['responseCode'] === 0 ? 'success' : 'failed',
                    'transaction_id' => $response['hash'] ?? null,
                    'amount' => $response['amount'] ?? null,
                    'currency' => $response['currency'] ?? null,
                    'bill_number' => $response['billNumber'] ?? null,
                    'timestamp' => $response['timestamp'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check transaction by MD5: ' . $e->getMessage(), [
                'md5' => $md5,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check multiple transactions by MD5 list
     */
    public function checkTransactionsByMd5List(array $md5List)
    {
        try {
            if (!$this->isConfigured()) {
                throw new \Exception('BakongKHQR service not properly configured');
            }

            Log::info('Checking transactions by MD5 list', ['md5_list' => $md5List]);

            $response = $this->bakongInstance->checkTransactionByMD5List($md5List);

            Log::info('Batch transaction check response', [
                'response' => $response
            ]);

            return [
                'success' => true,
                'results' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check transactions by MD5 list: ' . $e->getMessage(), [
                'md5_list' => $md5List,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to verify payments: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process webhook callback from Bakong
     */
    public function processWebhook(array $data)
    {
        try {
            Log::info('Processing Bakong webhook', [
                'data' => $data
            ]);

            // Validate webhook data
            if (!isset($data['hash']) || !isset($data['responseCode'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid webhook data',
                ];
            }

            // Verify the transaction using the hash from webhook
            if ($this->isConfigured()) {
                $verificationResult = $this->bakongInstance->checkTransactionByFullHash($data['hash'], true);

                if (!$verificationResult || $verificationResult['responseCode'] !== 0) {
                    Log::warning('Transaction verification failed', [
                        'webhook_data' => $data,
                        'verification' => $verificationResult
                    ]);
                }
            }

            // Process based on response code
            if ($data['responseCode'] !== 0) {
                return [
                    'success' => false,
                    'message' => 'Payment failed',
                    'response_code' => $data['responseCode'],
                ];
            }

            $result = [
                'success' => true,
                'transaction_id' => $data['hash'],
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'bill_number' => $data['billNumber'] ?? null,
                'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
            ];

            Log::info('Webhook processed successfully', $result);

            return $result;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process webhook: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && $this->bakongInstance !== null;
    }

    /**
     * Get the current token status
     */
    public function getTokenStatus(): array
    {
        return [
            'token_configured' => !empty($this->token),
            'instance_ready' => $this->bakongInstance !== null,
            'base_url' => $this->baseUrl
        ];
    }
}
