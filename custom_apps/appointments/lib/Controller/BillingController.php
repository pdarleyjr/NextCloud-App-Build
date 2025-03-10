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

namespace OCA\Appointments\Controller;

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
        $customAmount = $this->request->getParam('amount');
        $description = $this->request->getParam('description', 'Therapy Session');
        
        // Validate required parameters
        if (empty($appointmentId) || empty($therapistId)) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        // Check if therapist exists
        if (!$this->therapistService->isTherapist($therapistId)) {
            return new JSONResponse(
                ['message' => 'Therapist not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        $invoice = $this->billingService->createInvoice(
            $appointmentId,
            $therapistId,
            $userId,
            $customAmount,
            $description
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
        
        $userId = $currentUser->getUID();
        $invoice = $this->billingService->getInvoice($id, $userId);
        
        if ($invoice === null) {
            return new JSONResponse(
                ['message' => 'Invoice not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse($invoice);
    }
    
    /**
     * Process a payment using Square
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
        $invoiceId = $this->request->getParam('invoiceId');
        $sourceId = $this->request->getParam('sourceId'); // This is the nonce from Square
        
        // Validate required parameters
        if (empty($invoiceId) || empty($sourceId)) {
            return new JSONResponse(
                ['message' => 'Missing required parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        $result = $this->billingService->processPayment($invoiceId, $sourceId, $userId);
        
        if ($result === null) {
            return new JSONResponse(
                ['message' => 'Payment processing failed'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse($result);
    }
}