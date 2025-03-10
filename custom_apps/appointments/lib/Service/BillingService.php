<?php

declare(strict_types=1);

namespace OCA\Appointments\Service;

use OCA\Appointments\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\ILogger;

/**
 * Service class for billing-related operations
 */
class BillingService {
    /** @var IConfig */
    private IConfig $config;
    
    /** @var IUserManager */
    private IUserManager $userManager;
    
    /** @var ILogger */
    private ILogger $logger;
    
    /**
     * Constructor for BillingService
     * 
     * @param IConfig $config The configuration service
     * @param IUserManager $userManager The user manager
     * @param ILogger $logger The logger
     */
    public function __construct(IConfig $config, IUserManager $userManager, ILogger $logger) {
        $this->config = $config;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }
    
    /**
     * Create an invoice for an appointment
     * 
     * @param string $appointmentId The appointment ID
     * @param string $therapistId The therapist ID
     * @param string $clientId The client ID
     * @param float $amount The invoice amount
     * @param string $description The invoice description
     * @param array $items The invoice items
     * @return array|null The created invoice, or null if creation failed
     */
    public function createInvoice(
        string $appointmentId,
        string $therapistId,
        string $clientId,
        float $amount,
        string $description = '',
        array $items = []
    ): ?array {
        // Create the invoice
        $invoiceId = uniqid('inv_');
        $invoice = [
            'id' => $invoiceId,
            'appointmentId' => $appointmentId,
            'therapistId' => $therapistId,
            'clientId' => $clientId,
            'amount' => $amount,
            'description' => $description,
            'items' => $items,
            'status' => 'pending',
            'createdAt' => time(),
            'dueDate' => time() + (30 * 24 * 60 * 60), // 30 days from now
            'paidAt' => null
        ];
        
        // Save the invoice
        $invoices = $this->getInvoicesForTherapist($therapistId);
        $invoices[] = $invoice;
        
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'invoices',
            json_encode($invoices)
        );
        
        return $invoice;
    }
    
    /**
     * Create a superbill for an appointment
     * 
     * @param string $appointmentId The appointment ID
     * @param string $therapistId The therapist ID
     * @param string $clientId The client ID
     * @param float $amount The superbill amount
     * @param string $description The superbill description
     * @param array $diagnosisCodes The diagnosis codes
     * @param array $procedureCodes The procedure codes
     * @return array|null The created superbill, or null if creation failed
     */
    public function createSuperbill(
        string $appointmentId,
        string $therapistId,
        string $clientId,
        float $amount,
        string $description = '',
        array $diagnosisCodes = [],
        array $procedureCodes = []
    ): ?array {
        // Create the superbill
        $superbillId = uniqid('sb_');
        $superbill = [
            'id' => $superbillId,
            'appointmentId' => $appointmentId,
            'therapistId' => $therapistId,
            'clientId' => $clientId,
            'amount' => $amount,
            'description' => $description,
            'diagnosisCodes' => $diagnosisCodes,
            'procedureCodes' => $procedureCodes,
            'status' => 'pending',
            'createdAt' => time()
        ];
        
        // Save the superbill
        $superbills = $this->getSuperbillsForTherapist($therapistId);
        $superbills[] = $superbill;
        
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'superbills',
            json_encode($superbills)
        );
        
        return $superbill;
    }
    
    /**
     * Get all invoices for a therapist
     * 
     * @param string $therapistId The therapist ID
     * @return array The invoices
     */
    public function getInvoicesForTherapist(string $therapistId): array {
        $invoices = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'invoices',
            '[]'
        );
        
        return json_decode($invoices, true);
    }
    
    /**
     * Get all superbills for a therapist
     * 
     * @param string $therapistId The therapist ID
     * @return array The superbills
     */
    public function getSuperbillsForTherapist(string $therapistId): array {
        $superbills = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'superbills',
            '[]'
        );
        
        return json_decode($superbills, true);
    }
    
    /**
     * Get all invoices for a client
     * 
     * @param string $clientId The client ID
     * @return array The invoices
     */
    public function getInvoicesForClient(string $clientId): array {
        $invoices = [];
        
        // Get all therapists
        $this->userManager->callForAllUsers(function($user) use (&$invoices, $clientId) {
            $therapistId = $user->getUID();
            $therapistInvoices = $this->getInvoicesForTherapist($therapistId);
            
            // Filter invoices for the current client
            foreach ($therapistInvoices as $invoice) {
                if (isset($invoice['clientId']) && $invoice['clientId'] === $clientId) {
                    $invoice['therapistName'] = $user->getDisplayName();
                    $invoices[] = $invoice;
                }
            }
        });
        
        return $invoices;
    }
    
    /**
     * Get all superbills for a client
     * 
     * @param string $clientId The client ID
     * @return array The superbills
     */
    public function getSuperbillsForClient(string $clientId): array {
        $superbills = [];
        
        // Get all therapists
        $this->userManager->callForAllUsers(function($user) use (&$superbills, $clientId) {
            $therapistId = $user->getUID();
            $therapistSuperbills = $this->getSuperbillsForTherapist($therapistId);
            
            // Filter superbills for the current client
            foreach ($therapistSuperbills as $superbill) {
                if (isset($superbill['clientId']) && $superbill['clientId'] === $clientId) {
                    $superbill['therapistName'] = $user->getDisplayName();
                    $superbills[] = $superbill;
                }
            }
        });
        
        return $superbills;
    }
    
    /**
     * Get a specific invoice
     * 
     * @param string $invoiceId The invoice ID
     * @return array|null The invoice, or null if not found
     */
    public function getInvoice(string $invoiceId): ?array {
        // Search for the invoice among all therapists
        $invoice = null;
        
        $this->userManager->callForAllUsers(function($user) use (&$invoice, $invoiceId) {
            if ($invoice !== null) {
                return;
            }
            
            $therapistId = $user->getUID();
            $therapistInvoices = $this->getInvoicesForTherapist($therapistId);
            
            foreach ($therapistInvoices as $inv) {
                if (isset($inv['id']) && $inv['id'] === $invoiceId) {
                    $inv['therapistName'] = $user->getDisplayName();
                    $invoice = $inv;
                    return;
                }
            }
        });
        
        return $invoice;
    }
    
    /**
     * Get a specific superbill
     * 
     * @param string $superbillId The superbill ID
     * @return array|null The superbill, or null if not found
     */
    public function getSuperbill(string $superbillId): ?array {
        // Search for the superbill among all therapists
        $superbill = null;
        
        $this->userManager->callForAllUsers(function($user) use (&$superbill, $superbillId) {
            if ($superbill !== null) {
                return;
            }
            
            $therapistId = $user->getUID();
            $therapistSuperbills = $this->getSuperbillsForTherapist($therapistId);
            
            foreach ($therapistSuperbills as $sb) {
                if (isset($sb['id']) && $sb['id'] === $superbillId) {
                    $sb['therapistName'] = $user->getDisplayName();
                    $superbill = $sb;
                    return;
                }
            }
        });
        
        return $superbill;
    }
    
    /**
     * Update an invoice
     * 
     * @param string $invoiceId The invoice ID
     * @param array $updateData The data to update
     * @return array|null The updated invoice, or null if update failed
     */
    public function updateInvoice(string $invoiceId, array $updateData): ?array {
        // Find the invoice and its therapist
        $invoice = $this->getInvoice($invoiceId);
        
        if ($invoice === null || !isset($invoice['therapistId'])) {
            return null;
        }
        
        $therapistId = $invoice['therapistId'];
        $invoices = $this->getInvoicesForTherapist($therapistId);
        
        // Find the invoice index
        $invoiceIndex = -1;
        foreach ($invoices as $index => $inv) {
            if (isset($inv['id']) && $inv['id'] === $invoiceId) {
                $invoiceIndex = $index;
                break;
            }
        }
        
        if ($invoiceIndex === -1) {
            return null;
        }
        
        // Update the invoice
        foreach ($updateData as $key => $value) {
            if ($key !== 'id' && $key !== 'therapistId' && $key !== 'clientId') {
                $invoices[$invoiceIndex][$key] = $value;
            }
        }
        
        // Save the updated invoices
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'invoices',
            json_encode($invoices)
        );
        
        return $invoices[$invoiceIndex];
    }
    
    /**
     * Update a superbill
     * 
     * @param string $superbillId The superbill ID
     * @param array $updateData The data to update
     * @return array|null The updated superbill, or null if update failed
     */
    public function updateSuperbill(string $superbillId, array $updateData): ?array {
        // Find the superbill and its therapist
        $superbill = $this->getSuperbill($superbillId);
        
        if ($superbill === null || !isset($superbill['therapistId'])) {
            return null;
        }
        
        $therapistId = $superbill['therapistId'];
        $superbills = $this->getSuperbillsForTherapist($therapistId);
        
        // Find the superbill index
        $superbillIndex = -1;
        foreach ($superbills as $index => $sb) {
            if (isset($sb['id']) && $sb['id'] === $superbillId) {
                $superbillIndex = $index;
                break;
            }
        }
        
        if ($superbillIndex === -1) {
            return null;
        }
        
        // Update the superbill
        foreach ($updateData as $key => $value) {
            if ($key !== 'id' && $key !== 'therapistId' && $key !== 'clientId') {
                $superbills[$superbillIndex][$key] = $value;
            }
        }
        
        // Save the updated superbills
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'superbills',
            json_encode($superbills)
        );
        
        return $superbills[$superbillIndex];
    }
    
    /**
     * Process a payment for an invoice using Square
     *
     * @param string $invoiceId The invoice ID
     * @param string $nonce The payment nonce from Square
     * @param float $amount The payment amount
     * @return array|null The payment result, or null if payment failed
     */
    public function processPayment(string $invoiceId, string $nonce, float $amount): ?array {
        // Get the invoice
        $invoice = $this->getInvoice($invoiceId);
        
        if ($invoice === null) {
            return null;
        }
        
        // Get Square credentials
        $credentials = $this->getSquareCredentials();
        $accessToken = $credentials['accessToken'];
        $environment = $credentials['environment'];
        
        if (empty($accessToken)) {
            $this->logger->error('Square access token not configured');
            return null;
        }
        
        // Set up Square API endpoint
        $apiUrl = $environment === 'sandbox'
            ? 'https://connect.squareupsandbox.com/v2/payments'
            : 'https://connect.squareup.com/v2/payments';
        
        // Convert amount to cents (Square requires amount in cents)
        $amountInCents = (int) ($amount * 100);
        
        // Prepare payment request
        $paymentData = [
            'source_id' => $nonce,
            'idempotency_key' => uniqid('payment_'),
            'amount_money' => [
                'amount' => $amountInCents,
                'currency' => 'USD'
            ],
            'reference_id' => $invoiceId,
            'note' => 'Payment for appointment #' . $invoice['appointmentId']
        ];
        
        try {
            // Make API request to Square
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Square-Version: 2023-09-25',
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response, true);
                
                if (isset($responseData['payment']) && $responseData['payment']['status'] === 'COMPLETED') {
                    // Payment successful
                    $paymentId = $responseData['payment']['id'];
                    $payment = [
                        'id' => $paymentId,
                        'invoiceId' => $invoiceId,
                        'amount' => $amount,
                        'status' => 'succeeded',
                        'squarePaymentId' => $paymentId,
                        'createdAt' => time()
                    ];
                    
                    // Update the invoice status
                    $this->updateInvoice($invoiceId, [
                        'status' => 'paid',
                        'paidAt' => time(),
                        'paymentId' => $paymentId
                    ]);
                    
                    return $payment;
                } else {
                    // Payment failed
                    $this->logger->error('Square payment failed: ' . $response);
                    return null;
                }
            } else {
                // API request failed
                $this->logger->error('Square API request failed: ' . $response);
                return null;
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception during Square payment processing: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get Square API credentials
     * 
     * @return array The Square API credentials
     */
    public function getSquareCredentials(): array {
        $environment = $this->config->getAppValue(
            Application::APP_ID,
            'square_environment',
            'sandbox'
        );
        
        $accessToken = $this->config->getAppValue(
            Application::APP_ID,
            'square_access_token',
            ''
        );
        
        $applicationId = $this->config->getAppValue(
            Application::APP_ID,
            'square_application_id',
            ''
        );
        
        return [
            'environment' => $environment,
            'accessToken' => $accessToken,
            'applicationId' => $applicationId
        ];
    }
    
    /**
     * Set Square API credentials
     * 
     * @param string $environment The Square environment (sandbox or production)
     * @param string $accessToken The Square access token
     * @param string $applicationId The Square application ID
     * @return bool True if successful, false otherwise
     */
    public function setSquareCredentials(string $environment, string $accessToken, string $applicationId): bool {
        $this->config->setAppValue(
            Application::APP_ID,
            'square_environment',
            $environment
        );
        
        $this->config->setAppValue(
            Application::APP_ID,
            'square_access_token',
            $accessToken
        );
        
        $this->config->setAppValue(
            Application::APP_ID,
            'square_application_id',
            $applicationId
        );
        
        return true;
    }
}