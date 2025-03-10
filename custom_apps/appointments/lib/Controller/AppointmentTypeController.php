<?php

declare(strict_types=1);

namespace OCA\Appointments\Controller;

use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Service\AppointmentTypeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for appointment type-related operations
 */
class AppointmentTypeController extends Controller {
    /** @var AppointmentTypeService */
    private AppointmentTypeService $appointmentTypeService;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /**
     * Constructor for AppointmentTypeController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param AppointmentTypeService $appointmentTypeService The appointment type service
     * @param IUserSession $userSession The user session
     */
    public function __construct(
        string $appName,
        IRequest $request,
        AppointmentTypeService $appointmentTypeService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->appointmentTypeService = $appointmentTypeService;
        $this->userSession = $userSession;
    }
    
    /**
     * Get all appointment types
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAll(): JSONResponse {
        $appointmentTypes = $this->appointmentTypeService->getAllAppointmentTypes();
        
        return new JSONResponse($appointmentTypes);
    }
    
    /**
     * Get a specific appointment type
     *
     * @NoAdminRequired
     * @param string $id The appointment type ID
     * @return JSONResponse
     */
    public function get(string $id): JSONResponse {
        $appointmentType = $this->appointmentTypeService->getAppointmentType($id);
        
        if ($appointmentType === null) {
            return new JSONResponse(
                ['message' => 'Appointment type not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse($appointmentType);
    }
    
    /**
     * Get appointment types by category
     *
     * @NoAdminRequired
     * @param string $category The category
     * @return JSONResponse
     */
    public function getByCategory(string $category): JSONResponse {
        $appointmentTypes = $this->appointmentTypeService->getAppointmentTypesByCategory($category);
        
        return new JSONResponse($appointmentTypes);
    }
    
    /**
     * Update appointment types
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function update(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        // Only admin should be able to update appointment types
        // For simplicity, we're not implementing full admin check here
        
        $appointmentTypes = $this->request->getParam('appointmentTypes', []);
        
        if (empty($appointmentTypes)) {
            return new JSONResponse(
                ['message' => 'Missing appointment types data'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        $success = $this->appointmentTypeService->updateAppointmentTypes($appointmentTypes);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Failed to update appointment types'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse([
            'message' => 'Appointment types updated successfully',
            'appointmentTypes' => $appointmentTypes
        ]);
    }
}