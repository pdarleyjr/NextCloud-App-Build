<?php

declare(strict_types=1);

namespace OCA\Appointments\Controller;

use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Service\TherapistService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for therapist-related operations
 */
class TherapistController extends Controller {
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /**
     * Constructor for TherapistController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param TherapistService $therapistService The therapist service
     * @param IUserSession $userSession The user session
     */
    public function __construct(
        string $appName,
        IRequest $request,
        TherapistService $therapistService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->therapistService = $therapistService;
        $this->userSession = $userSession;
    }
    
    /**
     * Get all therapists
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAll(): JSONResponse {
        $therapists = $this->therapistService->getAllTherapists();
        
        return new JSONResponse($therapists);
    }
    
    /**
     * Get a specific therapist
     *
     * @NoAdminRequired
     * @param string $id The therapist ID
     * @return JSONResponse
     */
    public function get(string $id): JSONResponse {
        $therapist = $this->therapistService->getTherapist($id);
        
        if ($therapist === null) {
            return new JSONResponse(
                ['message' => 'Therapist not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse($therapist);
    }
    
    /**
     * Get a therapist's schedule
     *
     * @NoAdminRequired
     * @param string $id The therapist ID
     * @return JSONResponse
     */
    public function getSchedule(string $id): JSONResponse {
        $therapist = $this->therapistService->getTherapist($id);
        
        if ($therapist === null) {
            return new JSONResponse(
                ['message' => 'Therapist not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        $schedule = $this->therapistService->getTherapistSchedule($id);
        
        return new JSONResponse($schedule);
    }
    
    /**
     * Update a therapist's schedule
     *
     * @NoAdminRequired
     * @param string $id The therapist ID
     * @return JSONResponse
     */
    public function updateSchedule(string $id): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        
        // Only the therapist can update their own schedule
        if ($userId !== $id) {
            return new JSONResponse(
                ['message' => 'Unauthorized'],
                Http::STATUS_FORBIDDEN
            );
        }
        
        // Get the schedule data from the request
        $scheduleData = $this->request->getParam('schedule', []);
        
        // Update the schedule
        $success = $this->therapistService->setTherapistSchedule($id, $scheduleData);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Failed to update schedule'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse([
            'message' => 'Schedule updated successfully',
            'schedule' => $scheduleData
        ]);
    }
    
    /**
     * Become a therapist
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function becomeTherapist(): JSONResponse {
        $currentUser = $this->userSession->getUser();
        
        if ($currentUser === null) {
            return new JSONResponse(
                ['message' => 'Not logged in'],
                Http::STATUS_UNAUTHORIZED
            );
        }
        
        $userId = $currentUser->getUID();
        
        // Set the user as a therapist
        $success = $this->therapistService->setTherapist($userId, true);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Failed to become a therapist'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        
        return new JSONResponse([
            'message' => 'Successfully became a therapist',
            'isTherapist' => true
        ]);
    }
    
    /**
     * Update therapist profile
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function updateProfile(): JSONResponse {
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
        
        // Get the profile data from the request
        $specialties = $this->request->getParam('specialties', []);
        $bio = $this->request->getParam('bio', '');
        $hourlyRate = (float) $this->request->getParam('hourlyRate', 0);
        
        // Update the profile
        $this->therapistService->setTherapistSpecialties($userId, $specialties);
        $this->therapistService->setTherapistBio($userId, $bio);
        $this->therapistService->setTherapistHourlyRate($userId, $hourlyRate);
        
        return new JSONResponse([
            'message' => 'Profile updated successfully',
            'profile' => [
                'specialties' => $specialties,
                'bio' => $bio,
                'hourlyRate' => $hourlyRate
            ]
        ]);
    }
}