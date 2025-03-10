<?php

declare(strict_types=1);

namespace OCA\Appointments\Controller;

use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Service\BillingService;
use OCA\Appointments\Service\TherapistService;
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
    
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /**
     * Constructor for BillingController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param BillingService $billingService The billing service
     * @param TherapistService $therapistService The therapist service
     * @param IUserSession $userSession The user session
     */
    public function __construct(
        string $appName,
        IRequest $request,
        BillingService $billingService,
        TherapistService $therapistService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->billingService = $billingService;
        $this->therapistService = $therapistService;
        $this->userSession = $userSession;
    }
    
    /**
     * Create an invoice
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
        
        // Check if the user is a therapist
        if (!$this->therapistService->isTherapist($userId)) {
            return new JSONResponse(
                ['message' => 'Not a therapist'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        // Get invoice data from the request
        $appointmentId = $this->request->getParam('appointmentId');
        $clientId = $this->request->getParam('clientId');
        $amount = (float) $this->request->getParam('amount');
        $description = $this->request->getParam('description', '');
        $items = $this->request->getParam('items', []);
        
        // Validate required parameters
        if (empty($appointmentId) || empty($clientId) || $amount <= 0) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Create the invoice
        $invoice = $this->billingService->createInvoice(
            $appointmentId,
            $userId,
            $clientId,
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
    
    /**
     * Create a superbill
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function createSuperbill(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        
        // Check if the user is a therapist
        if (!$this->therapistService->isTherapist($userId)) {
            return new JSONResponse(
                ['message' => 'Not a therapist'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        // Get superbill data from the request
        $appointmentId = $this->request->getParam('appointmentId');
        $clientId = $this->request->getParam('clientId');
        $amount = (float) $this->request->getParam('amount');
        $description = $this->request->getParam('description', '');
        $diagnosisCodes = $this->request->getParam('diagnosisCodes', []);
        $procedureCodes = $this->request->getParam('procedureCodes', []);
        
        // Validate required parameters
        if (empty($appointmentId) || empty($clientId) || $amount <= 0) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Create the superbill
        $superbill = $this->billingService->createSuperbill(
            $appointmentId,
            $userId,
            $clientId,
            $amount,
            $description,
            $diagnosisCodes,
            $procedureCodes
        );
        
        if ($superbill === null) {
            return new JSONResponse(
                ['message' => 'Failed to create superbill'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse($superbill, Http::STATUS_CREATED);
    }
    
    /**
     * Get an invoice
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
        
        $invoice = $this->billingService->getInvoice($id);
        
        if ($invoice === null) {
            return new JSONResponse(
                ['message' => 'Invoice not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        // Check if the user has access to this invoice
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        if ($isTherapist && $invoice['therapistId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        } elseif (!$isTherapist && $invoice['clientId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        return new JSONResponse($invoice);
    }
    
    /**
     * Get a superbill
     *
     * @NoAdminRequired
     * @param string $id The superbill ID
     * @return JSONResponse
     */
    public function getSuperbill(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $superbill = $this->billingService->getSuperbill($id);
        
        if ($superbill === null) {
            return new JSONResponse(
                ['message' => 'Superbill not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        // Check if the user has access to this superbill
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        if ($isTherapist && $superbill['therapistId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        } elseif (!$isTherapist && $superbill['clientId'] !== $userId) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        return new JSONResponse($superbill);
    }
    
    /**
     * Process a payment
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
        
        // Get payment data from the request
        $invoiceId = $this->request->getParam('invoiceId');
        $nonce = $this->request->getParam('nonce');
        $amount = (float) $this->request->getParam('amount');
        
        // Validate required parameters
        if (empty($invoiceId) || empty($nonce) || $amount <= 0) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Process the payment
        $payment = $this->billingService->processPayment($invoiceId, $nonce, $amount);
        
        if ($payment === null) {
            return new JSONResponse(
                ['message' => 'Failed to process payment'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse($payment);
    }
    
    /**
     * Get Square credentials
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getSquareCredentials(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $credentials = $this->billingService->getSquareCredentials();
        
        // Only return the application ID and environment, not the access token
        return new JSONResponse([
            'environment' => $credentials['environment'],
            'applicationId' => $credentials['applicationId']
        ]);
    }
    
    /**
     * Set Square credentials (admin only)
     *
     * @return JSONResponse
     */
    public function setSquareCredentials(): JSONResponse {
        $environment = $this->request->getParam('environment');
        $accessToken = $this->request->getParam('accessToken');
        $applicationId = $this->request->getParam('applicationId');
        
        // Validate required parameters
        if (empty($environment) || empty($accessToken) || empty($applicationId)) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Set the credentials
        $success = $this->billingService->setSquareCredentials($environment, $accessToken, $applicationId);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Failed to set Square credentials'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse([
            'message' => 'Square credentials set successfully'
        ]);
    }
}