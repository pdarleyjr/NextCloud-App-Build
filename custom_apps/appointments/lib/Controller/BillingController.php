<?php

declare(strict_types=1);

namespace OCA\Appointments\Controller;

use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Service\BillingService;
use OCA\Appointments\Service\AppointmentService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for billing-related operations
 */
class BillingController extends Controller {
    /** @var BillingService */
    private BillingService $billingService;
    
    /** @var AppointmentService */
    private AppointmentService $appointmentService;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /**
     * Constructor for BillingController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param BillingService $billingService The billing service
     * @param AppointmentService $appointmentService The appointment service
     * @param IUserSession $userSession The user session
     */
    public function __construct(
        string $appName,
        IRequest $request,
        BillingService $billingService,
        AppointmentService $appointmentService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->billingService = $billingService;
        $this->appointmentService = $appointmentService;
        $this->userSession = $userSession;
    }
    
    /**
     * Get Square credentials for the frontend
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getSquareCredentials(): JSONResponse {
        $credentials = $this->billingService->getSquareCredentials();
        
        return new JSONResponse([
            'environment' => $credentials['environment'],
            'applicationId' => $credentials['applicationId']
        ]);
    }
    
    /**
     * Process a payment for an appointment
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function processPayment(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $appointmentId = $this->request->getParam('appointmentId');
        $nonce = $this->request->getParam('nonce');
        $amount = (float) $this->request->getParam('amount');
        
        // Validate required parameters
        if (empty($appointmentId) || empty($nonce) || $amount <= 0) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Get the appointment to verify ownership
        $appointment = $this->appointmentService->getAppointment($appointmentId, $userId, false);
        
        if ($appointment === null) {
            return new JSONResponse(
                ['message' => 'Appointment not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        // Verify that the user is the client for this appointment
        if ($appointment['clientId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        // Process the payment
        $payment = $this->billingService->processPayment($appointmentId, $nonce, $amount);
        
        if ($payment === null) {
            return new JSONResponse(
                ['message' => 'Failed to process payment'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse($payment);
    }
    
    /**
     * Get all invoices for the current user
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getInvoices(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $invoices = $this->billingService->getInvoicesForClient($userId);
        
        return new JSONResponse($invoices);
    }
    
    /**
     * Get a specific invoice
     *
     * @NoAdminRequired
     * @param string $id The invoice ID
     * @return JSONResponse
     */
    public function getInvoice(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $invoice = $this->billingService->getInvoice($id);
        
        if ($invoice === null) {
            return new JSONResponse(
                ['message' => 'Invoice not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        // Verify that the user is the client for this invoice
        if ($invoice['clientId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        return new JSONResponse($invoice);
    }
    
    /**
     * Create an invoice for an appointment
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function createInvoice(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $appointmentId = $this->request->getParam('appointmentId');
        $therapistId = $this->request->getParam('therapistId');
        $amount = (float) $this->request->getParam('amount');
        $description = $this->request->getParam('description', '');
        $items = $this->request->getParam('items', []);
        
        // Validate required parameters
        if (empty($appointmentId) || empty($therapistId) || $amount <= 0) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Create the invoice
        $invoice = $this->billingService->createInvoice(
            $appointmentId,
            $therapistId,
            $userId,
            $amount,
            $description,
            $items
        );
        
        if ($invoice === null) {
            return new JSONResponse(
                ['message' => 'Failed to create invoice'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse($invoice, Http::STATUS_CREATED);
    }
}