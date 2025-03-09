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
use OCP\IUser;

/**
 * Service class for appointment-related operations
 */
class AppointmentService {
    /** @var IConfig */
    private IConfig $config;
    
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /**
     * Constructor for AppointmentService
     * 
     * @param IConfig $config The configuration service
     * @param TherapistService $therapistService The therapist service
     */
    public function __construct(IConfig $config, TherapistService $therapistService) {
        $this->config = $config;
        $this->therapistService = $therapistService;
    }
    
    /**
     * Get all appointments for a user
     * 
     * @param string $userId The user ID
     * @param bool $isTherapist Whether the user is a therapist
     * @return array The appointments
     */
    public function getAppointmentsForUser(string $userId, bool $isTherapist): array {
        $appointments = [];
        
        if ($isTherapist) {
            // For therapists, get all appointments where they are the therapist
            $appointments = $this->getAppointmentsForTherapist($userId);
        } else {
            // For clients, get all appointments where they are the client
            $appointments = $this->getAppointmentsForClient($userId);
        }
        
        return $appointments;
    }
    
    /**
     * Get all appointments for a therapist
     * 
     * @param string $therapistId The therapist ID
     * @return array The appointments
     */
    private function getAppointmentsForTherapist(string $therapistId): array {
        return json_decode($this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            '[]'
        ), true);
    }
    
    /**
     * Get all appointments for a client
     * 
     * @param string $clientId The client ID
     * @return array The appointments
     */
    private function getAppointmentsForClient(string $clientId): array {
        $appointments = [];
        $therapists = $this->therapistService->getAllTherapists();
        
        foreach ($therapists as $therapist) {
            $therapistId = $therapist['id'];
            $therapistAppointments = $this->getAppointmentsForTherapist($therapistId);
            
            // Filter appointments for the current client
            foreach ($therapistAppointments as $appointment) {
                if (isset($appointment['clientId']) && $appointment['clientId'] === $clientId) {
                    $appointment['therapistId'] = $therapistId;
                    $appointment['therapistName'] = $therapist['displayName'];
                    $appointments[] = $appointment;
                }
            }
        }
        
        return $appointments;
    }
    
    /**
     * Get a specific appointment
     * 
     * @param string $appointmentId The appointment ID
     * @param string $userId The user ID
     * @param bool $isTherapist Whether the user is a therapist
     * @return array|null The appointment, or null if not found
     */
    public function getAppointment(string $appointmentId, string $userId, bool $isTherapist): ?array {
        if ($isTherapist) {
            // For therapists, check their appointments
            $appointments = $this->getAppointmentsForTherapist($userId);
            
            foreach ($appointments as $appt) {
                if (isset($appt['id']) && $appt['id'] === $appointmentId) {
                    $appt['therapistId'] = $userId;
                    return $appt;
                }
            }
        } else {
            // For clients, check all therapists' appointments
            $therapists = $this->therapistService->getAllTherapists();
            
            foreach ($therapists as $therapist) {
                $therapistId = $therapist['id'];
                $therapistAppointments = $this->getAppointmentsForTherapist($therapistId);
                
                foreach ($therapistAppointments as $appt) {
                    if (isset($appt['id']) && $appt['id'] === $appointmentId && 
                        isset($appt['clientId']) && $appt['clientId'] === $userId) {
                        $appt['therapistId'] = $therapistId;
                        $appt['therapistName'] = $therapist['displayName'];
                        return $appt;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Create a new appointment
     * 
     * @param string $clientId The client ID
     * @param string $clientName The client name
     * @param string $therapistId The therapist ID
     * @param string $startTime The start time
     * @param string $endTime The end time
     * @param string $title The appointment title
     * @param string $notes The appointment notes
     * @param bool $isRecurring Whether the appointment is recurring
     * @param array|null $recurringPattern The recurring pattern
     * @return array|null The created appointment, or null if creation failed
     */
    public function createAppointment(
        string $clientId,
        string $clientName,
        string $therapistId,
        string $startTime,
        string $endTime,
        string $title = 'Therapy Session',
        string $notes = '',
        bool $isRecurring = false,
        ?array $recurringPattern = null
    ): ?array {
        if (!$this->therapistService->isTherapist($therapistId)) {
            return null;
        }
        
        // Create the appointment
        $appointmentId = uniqid('appt_');
        $appointment = [
            'id' => $appointmentId,
            'clientId' => $clientId,
            'clientName' => $clientName,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'title' => $title,
            'notes' => $notes,
            'status' => 'scheduled',
            'createdAt' => time(),
            'isRecurring' => $isRecurring,
            'recurringPattern' => $recurringPattern
        ];
        
        // Save the appointment to the therapist's appointments
        $appointments = $this->getAppointmentsForTherapist($therapistId);
        $appointments[] = $appointment;
        
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            json_encode($appointments)
        );
        
        // Add therapist info to the response
        $appointment['therapistId'] = $therapistId;
        
        return $appointment;
    }
    
    /**
     * Update an appointment
     * 
     * @param string $appointmentId The appointment ID
     * @param string $userId The user ID
     * @param bool $isTherapist Whether the user is a therapist
     * @param array $updateData The data to update
     * @return array|null The updated appointment, or null if update failed
     */
    public function updateAppointment(
        string $appointmentId,
        string $userId,
        bool $isTherapist,
        array $updateData
    ): ?array {
        // Find the appointment and its therapist
        $therapistId = null;
        $appointmentIndex = -1;
        $appointments = [];
        
        if ($isTherapist) {
            // For therapists, check their own appointments
            $appointments = $this->getAppointmentsForTherapist($userId);
            
            foreach ($appointments as $index => $appt) {
                if (isset($appt['id']) && $appt['id'] === $appointmentId) {
                    $therapistId = $userId;
                    $appointmentIndex = $index;
                    break;
                }
            }
        } else {
            // For clients, find the therapist who has this appointment
            $therapists = $this->therapistService->getAllTherapists();
            
            foreach ($therapists as $therapist) {
                $tId = $therapist['id'];
                $therapistAppointments = $this->getAppointmentsForTherapist($tId);
                
                foreach ($therapistAppointments as $index => $appt) {
                    if (isset($appt['id']) && $appt['id'] === $appointmentId && 
                        isset($appt['clientId']) && $appt['clientId'] === $userId) {
                        $therapistId = $tId;
                        $appointmentIndex = $index;
                        $appointments = $therapistAppointments;
                        break 2;
                    }
                }
            }
        }
        
        if ($therapistId === null || $appointmentIndex === -1) {
            return null;
        }
        
        // Update the appointment
        foreach ($updateData as $key => $value) {
            if ($key !== 'id' && $key !== 'clientId' && $key !== 'therapistId') {
                $appointments[$appointmentIndex][$key] = $value;
            }
        }
        
        // Update the last modified timestamp
        $appointments[$appointmentIndex]['updatedAt'] = time();
        
        // Save the updated appointments
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            json_encode($appointments)
        );
        
        // Add therapist info to the response
        $appointment = $appointments[$appointmentIndex];
        $appointment['therapistId'] = $therapistId;
        
        return $appointment;
    }
    
    /**
     * Delete an appointment
     * 
     * @param string $appointmentId The appointment ID
     * @param string $userId The user ID
     * @param bool $isTherapist Whether the user is a therapist
     * @return bool True if deletion was successful, false otherwise
     */
    public function deleteAppointment(string $appointmentId, string $userId, bool $isTherapist): bool {
        // Find the appointment and its therapist
        $therapistId = null;
        $appointmentIndex = -1;
        $appointments = [];
        
        if ($isTherapist) {
            // For therapists, check their own appointments
            $appointments = $this->getAppointmentsForTherapist($userId);
            
            foreach ($appointments as $index => $appt) {
                if (isset($appt['id']) && $appt['id'] === $appointmentId) {
                    $therapistId = $userId;
                    $appointmentIndex = $index;
                    break;
                }
            }
        } else {
            // For clients, find the therapist who has this appointment
            $therapists = $this->therapistService->getAllTherapists();
            
            foreach ($therapists as $therapist) {
                $tId = $therapist['id'];
                $therapistAppointments = $this->getAppointmentsForTherapist($tId);
                
                foreach ($therapistAppointments as $index => $appt) {
                    if (isset($appt['id']) && $appt['id'] === $appointmentId && 
                        isset($appt['clientId']) && $appt['clientId'] === $userId) {
                        $therapistId = $tId;
                        $appointmentIndex = $index;
                        $appointments = $therapistAppointments;
                        break 2;
                    }
                }
            }
        }
        
        if ($therapistId === null || $appointmentIndex === -1) {
            return false;
        }
        
        // Remove the appointment
        array_splice($appointments, $appointmentIndex, 1);
        
        // Save the updated appointments
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            json_encode($appointments)
        );
        
        return true;
    }
}