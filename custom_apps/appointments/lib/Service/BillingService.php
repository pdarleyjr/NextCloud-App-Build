<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 NextCloud App Build
 *
 * @author NextCloud App Build
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Appointments\Service;

use OCA\Appointments\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\ILogger;
use Square\SquareClient;
use Square\Environment;
use Square\Exceptions\ApiException;
use Square\Models\Money;
use Square\Models\CreatePaymentRequest;

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
     * @param IUserManager $userManager The user manager service
     * @param ILogger $logger The logger service
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
     * @param string $userId The user ID creating the invoice
     * @param float|null $customAmount Custom amount for the invoice
     * @param string $description Description for the invoice
     * @return array|null The created invoice, or null if creation failed
     */
    public function createInvoice(
        string $appointmentId,
        string $therapistId,
        string $userId,
        ?float $customAmount = null,
        string $description = 'Therapy Session'
    ): ?array {
        // Check if therapist exists and is a therapist
        $therapist = $this->userManager->get($therapistId);
        
        if ($therapist === null) {
            return null;
        }
        
        $isTherapist = $this->config->getUserValue(
            $therapistId, 
            Application::APP_ID, 
            Application::CONFIG_IS_THERAPIST, 
            'false'
        ) === 'true';
        
        if (!$isTherapist) {
            return null;
        }
        
        // Find the appointment
        $appointment = null;
        $appointments = json_decode($this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            '[]'
        ), true);
        
        foreach ($appointments as $appt) {
            if (isset($appt['id']) && $appt['id'] === $appointmentId) {
                $appointment = $appt;
                break;
            }
        }
        
        if ($appointment === null) {
            return null;
        }
        
        // Check if the current user is the therapist or the client
        if ($userId !== $therapistId && 
            (!isset($appointment['clientId']) || $appointment['clientId'] !== $userId)) {
            return null;
        }
        
        // Calculate the amount if not provided
        $amount = $customAmount;
        
        if ($amount === null) {
            $hourlyRate = (float)$this->config->getUserValue(
                $therapistId, 
                Application::APP_ID, 
                Application::CONFIG_HOURLY_RATE, 
                '0'
            );
            
            if ($hourlyRate <= 0) {
                return null;
            }
            
            // Calculate duration in hours
            $startTime = strtotime($appointment['startTime']);
            $endTime = strtotime($appointment['endTime']);
            $durationHours = ($endTime - $startTime) / 3600;
            
            // Calculate amount
            $amount = $hourlyRate * $durationHours;
        }
        
        // Create the invoice
        $invoiceId = uniqid('inv_');
        $invoice = [
            'id' => $invoiceId,
            'appointmentId' => $appointmentId,
            'therapistId' => $therapistId,
            'therapistName' => $therapist->getDisplayName(),
            'clientId' => $appointment['clientId'],
            'clientName' => $appointment['clientName'],
            'amount' => $amount,
            'description' => $description,
            'status' => 'pending',
            'createdAt' => time(),
            'dueDate' => strtotime('+7 days')
        ];
        
        // Save the invoice
        $invoices = json_decode($this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_INVOICES,
            '[]'
        ), true);
        
        $invoices[] = $invoice;
        
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_INVOICES,
            json_encode($invoices)
        );
        
        return $invoice;
    }
    
    /**
     * Get an invoice by ID
     * 
     * @param string $invoiceId The invoice ID
     * @param string $userId The user ID
     * @return array|null The invoice, or null if not found
     */
    public function getInvoice(string $invoiceId, string $userId): ?array {
        // Find the invoice
        $invoice = null;
        $therapistId = null;
        
        // Check if the current user is a therapist
        $isTherapist = $this->config->getUserValue(
            $userId, 
            Application::APP_ID, 
            Application::CONFIG_IS_THERAPIST, 
            'false'
        ) === 'true';
        
        if ($isTherapist) {
            // Check if the invoice belongs to this therapist
            $invoices = json_decode($this->config->getUserValue(
                $userId,
                Application::APP_ID,
                Application::CONFIG_INVOICES,
                '[]'
            ), true);
            
            foreach ($invoices as $inv) {
                if (isset($inv['id']) && $inv['id'] === $invoiceId) {
                    $invoice = $inv;
                    $therapistId = $userId;
                    break;
                }
            }
        }
        
        // If not found and user is not a therapist or invoice doesn't belong to them,
        // check all therapists for an invoice where this user is the client
        if ($invoice === null) {
            $therapists = $this->userManager->search('');
            
            foreach ($therapists as $therapist) {
                $tId = $therapist->getUID();
                $isTherapistRole = $this->config->getUserValue(
                    $tId, 
                    Application::APP_ID, 
                    Application::CONFIG_IS_THERAPIST, 
                    'false'
                ) === 'true';
                
                if ($isTherapistRole) {
                    $therapistInvoices = json_decode($this->config->getUserValue(
                        $tId,
                        Application::APP_ID,
                        Application::CONFIG_INVOICES,
                        '[]'
                    ), true);
                    
                    foreach ($therapistInvoices as $inv) {
                        if (isset($inv['id']) && $inv['id'] === $invoiceId && 
                            isset($inv['clientId']) && $inv['clientId'] === $userId) {
                            $invoice = $inv;
                            $therapistId = $tId;
                            break 2;
                        }
                    }
                }
            }
        }
        
        return $invoice;
    }
    
    /**
     * Process a payment using Square
     * 
     * @param string $invoiceId The invoice ID
     * @param string $sourceId The Square source ID (nonce)
     * @param string $userId The user ID
     * @return array|null The payment result, or null if payment failed
     */
    public function processPayment(string $invoiceId, string $sourceId, string $userId): ?array {
        // Find the invoice
        $invoice = null;
        $therapistId = null;
        $invoiceIndex = -1;
        $invoices = [];
        
        // Check all therapists for the invoice
        $therapists = $this->userManager->search('');
        
        foreach ($therapists as $therapist) {
            $tId = $therapist->getUID();
            $isTherapistRole = $this->config->getUserValue(
                $tId, 
                Application::APP_ID, 
                Application::CONFIG_IS_THERAPIST, 
                'false'
            ) === 'true';
            
            if ($isTherapistRole) {
                $therapistInvoices = json_decode($this->config->getUserValue(
                    $tId,
                    Application::APP_ID,
                    Application::CONFIG_INVOICES,
                    '[]'
                ), true);
                
                foreach ($therapistInvoices as $index => $inv) {
                    if (isset($inv['id']) && $inv['id'] === $invoiceId) {
                        $invoice = $inv;
                        $therapistId = $tId;
                        $invoiceIndex = $index;
                        $invoices = $therapistInvoices;
                        break 2;
                    }
                }
            }
        }
        
        if ($invoice === null) {
            return null;
        }
        
        // Check if the current user is the client for this invoice
        if ($userId !== $invoice['clientId']) {
            return null;
        }
        
        // Get Square API credentials
        $squareAccessToken = $this->config->getAppValue(
            Application::APP_ID, 
            Application::CONFIG_SQUARE_ACCESS_TOKEN, 
            ''
        );
        $squareEnvironment = $this->config->getAppValue(
            Application::APP_ID, 
            Application::CONFIG_SQUARE_ENVIRONMENT, 
            'sandbox'
        );
        
        if (empty($squareAccessToken)) {
            return null;
        }
        
        try {
            // Initialize Square client
            $client = new SquareClient([
                'accessToken' => $squareAccessToken,
                'environment' => $squareEnvironment === 'production' ? Environment::PRODUCTION : Environment::SANDBOX
            ]);
            
            // Convert amount to cents
            $amountCents = (int)($invoice['amount'] * 100);
            
            // Create money object
            $money = new Money();
            $money->setAmount($amountCents);
            $money->setCurrency('USD');
            
            // Create payment request
            $paymentRequest = new CreatePaymentRequest($sourceId, uniqid());
            $paymentRequest->setAmountMoney($money);
            $paymentRequest->setNote('Payment for invoice ' . $invoiceId);
            
            // Process payment
            $response = $client->getPaymentsApi()->createPayment($paymentRequest);
            
            if ($response->isSuccess()) {
                $payment = $response->getResult()->getPayment();
                
                // Update invoice status
                $invoices[$invoiceIndex]['status'] = 'paid';
                $invoices[$invoiceIndex]['paidAt'] = time();
                $invoices[$invoiceIndex]['paymentId'] = $payment->getId();
                
                // Save the updated invoice
                $this->config->setUserValue(
                    $therapistId,
                    Application::APP_ID,
                    Application::CONFIG_INVOICES,
                    json_encode($invoices)
                );
                
                return [
                    'message' => 'Payment processed successfully',
                    'invoice' => $invoices[$invoiceIndex],
                    'payment' => [
                        'id' => $payment->getId(),
                        'status' => $payment->getStatus(),
                        'amount' => $payment->getAmountMoney()->getAmount() / 100,
                        'currency' => $payment->getAmountMoney()->getCurrency(),
                        'createdAt' => $payment->getCreatedAt()
                    ]
                ];
            } else {
                $this->logger->error('Square payment error: ' . json_encode($response->getErrors()));
                return null;
            }
        } catch (ApiException $e) {
            $this->logger->error('Square API exception: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Payment exception: ' . $e->getMessage());
            return null;
        }
    }
}