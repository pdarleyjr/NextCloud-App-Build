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

use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Service\AppointmentService;
use OCA\Appointments\Service\TherapistService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for appointment-related operations
 */
class AppointmentController extends Controller {
    /** @var AppointmentService */
    private AppointmentService $appointmentService;
    
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /**
     * Constructor for AppointmentController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param AppointmentService $appointmentService The appointment service
     * @param TherapistService $therapistService The therapist service
     * @param IUserSession $userSession The user session
     */
    public function __construct(
        string $appName,
        IRequest $request,
        AppointmentService $appointmentService,
        TherapistService $therapistService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->appointmentService = $appointmentService;
        $this->therapistService = $therapistService;
        $this->userSession = $userSession;
    }
    
    /**
     * Get all appointments for the current user
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAll(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        $appointments = $this->appointmentService->getAppointmentsForUser($userId, $isTherapist);
        
        return new JSONResponse($appointments);
    }
    
    /**
     * Get a specific appointment
     *
     * @NoAdminRequired
     * @param string $id The appointment ID
     * @return JSONResponse
     */
    public function get(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        $appointment = $this->appointmentService->getAppointment($id, $userId, $isTherapist);
        
        if ($appointment === null) {
            return new JSONResponse(
                ['message' => 'Appointment not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse($appointment);
    }
    
    /**
     * Create a new appointment
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function create(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $therapistId = $this->request->getParam('therapistId');
        $startTime = $this->request->getParam('startTime');
        $endTime = $this->request->getParam('endTime');
        $title = $this->request->getParam('title', 'Therapy Session');
        $notes = $this->request->getParam('notes', '');
        $isRecurring = $this->request->getParam('isRecurring', false);
        $recurringPattern = $this->request->getParam('recurringPattern', null);
        
        // Validate required parameters
        if (empty($therapistId) || empty($startTime) || empty($endTime)) {
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
        
        $appointment = $this->appointmentService->createAppointment(
            $userId,
            $currentUser->getDisplayName(),
            $therapistId,
            $startTime,
            $endTime,
            $title,
            $notes,
            $isRecurring,
            $recurringPattern
        );
        
        if ($appointment === null) {
            return new JSONResponse(
                ['message' => 'Failed to create appointment'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        // Add therapist name to the response
        $therapist = $this->therapistService->getTherapist($therapistId);
        if ($therapist !== null) {
            $appointment['therapistName'] = $therapist['displayName'];
        }
        
        return new JSONResponse($appointment, Http::STATUS_CREATED);
    }
    
    /**
     * Update an appointment
     *
     * @NoAdminRequired
     * @param string $id The appointment ID
     * @return JSONResponse
     */
    public function update(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        // Collect update data
        $updateData = [];
        
        $startTime = $this->request->getParam('startTime');
        if ($startTime !== null) {
            $updateData['startTime'] = $startTime;
        }
        
        $endTime = $this->request->getParam('endTime');
        if ($endTime !== null) {
            $updateData['endTime'] = $endTime;
        }
        
        $title = $this->request->getParam('title');
        if ($title !== null) {
            $updateData['title'] = $title;
        }
        
        $notes = $this->request->getParam('notes');
        if ($notes !== null) {
            $updateData['notes'] = $notes;
        }
        
        $status = $this->request->getParam('status');
        if ($status !== null) {
            $updateData['status'] = $status;
        }
        
        $isRecurring = $this->request->getParam('isRecurring');
        if ($isRecurring !== null) {
            $updateData['isRecurring'] = $isRecurring;
        }
        
        $recurringPattern = $this->request->getParam('recurringPattern');
        if ($recurringPattern !== null) {
            $updateData['recurringPattern'] = $recurringPattern;
        }
        
        $appointment = $this->appointmentService->updateAppointment($id, $userId, $isTherapist, $updateData);
        
        if ($appointment === null) {
            return new JSONResponse(
                ['message' => 'Appointment not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        // Add therapist name to the response if needed
        if (isset($appointment['therapistId']) && !isset($appointment['therapistName'])) {
            $therapist = $this->therapistService->getTherapist($appointment['therapistId']);
            if ($therapist !== null) {
                $appointment['therapistName'] = $therapist['displayName'];
            }
        }
        
        return new JSONResponse($appointment);
    }
    
    /**
     * Delete an appointment
     *
     * @NoAdminRequired
     * @param string $id The appointment ID
     * @return JSONResponse
     */
    public function delete(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        $isTherapist = $this->therapistService->isTherapist($userId);
        
        $success = $this->appointmentService->deleteAppointment($id, $userId, $isTherapist);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Appointment not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse([
            'message' => 'Appointment deleted successfully',
            'id' => $id
        ]);
    }
}