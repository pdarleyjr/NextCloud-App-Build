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

use OCA\Appointments\Service\TherapistService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Controller for therapist-related operations
 */
class TherapistController extends Controller {
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /**
     * Constructor for TherapistController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param TherapistService $therapistService The therapist service
     */
    public function __construct(
        string $appName,
        IRequest $request,
        TherapistService $therapistService
    ) {
        parent::__construct($appName, $request);
        $this->therapistService = $therapistService;
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
        $schedule = $this->therapistService->getTherapistSchedule($id);
        
        if ($schedule === null) {
            return new JSONResponse(
                ['message' => 'Therapist not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
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
        $schedule = $this->request->getParam('schedule');
        
        if (!is_array($schedule)) {
            return new JSONResponse(
                ['message' => 'Invalid schedule format'],
                Http::STATUS_BAD_REQUEST
            );
        }
        
        $success = $this->therapistService->updateTherapistSchedule($id, $schedule);
        
        if (!$success) {
            return new JSONResponse(
                ['message' => 'Therapist not found'],
                Http::STATUS_NOT_FOUND
            );
        }
        
        return new JSONResponse([
            'message' => 'Schedule updated successfully',
            'schedule' => $schedule
        ]);
    }
}