<?php

declare(strict_types=1);

namespace OCA\Appointments\Service;

use OCA\Appointments\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\ILogger;
use OCA\Appointments\Service\AppointmentTypeService;

/**
 * Service class for appointment-related operations
 */
class AppointmentService {
    /** @var IConfig */
    private IConfig $config;
    
    /** @var IUserManager */
    private IUserManager $userManager;
    
    /** @var ILogger */
    private ILogger $logger;
    
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /** @var AppointmentTypeService */
    private AppointmentTypeService $appointmentTypeService;
    
    /**
     * Constructor for AppointmentService
     *
     * @param IConfig $config The configuration service
     * @param IUserManager $userManager The user manager
     * @param ILogger $logger The logger
     * @param TherapistService $therapistService The therapist service
     * @param AppointmentTypeService $appointmentTypeService The appointment type service
     */
    public function __construct(
        IConfig $config,
        IUserManager $userManager,
        ILogger $logger,
        TherapistService $therapistService,
        AppointmentTypeService $appointmentTypeService
    ) {
        $this->config = $config;
        $this->userManager = $userManager;
        $this->logger = $logger;
        $this->therapistService = $therapistService;
        $this->appointmentTypeService = $appointmentTypeService;
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
        $appointments = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'therapist_appointments',
            '[]'
        );
        
        return json_decode($appointments, true);
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
     * @param string $appointmentTypeId The appointment type ID
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
        string $appointmentTypeId,
        string $title = 'Therapy Session',
        string $notes = '',
        bool $isRecurring = false,
        ?array $recurringPattern = null
    ): ?array {
        if (!$this->therapistService->isTherapist($therapistId)) {
            return null;
        }
        
        // Get appointment type details
        $appointmentType = $this->appointmentTypeService->getAppointmentType($appointmentTypeId);
        if ($appointmentType === null) {
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
            'appointmentTypeId' => $appointmentTypeId,
            'appointmentTypeName' => $appointmentType['name'],
            'appointmentTypeCategory' => $appointmentType['category'],
            'price' => $appointmentType['price'],
            'duration' => $appointmentType['duration'],
            'packageBefore' => $appointmentType['packageBefore'],
            'packageAfter' => $appointmentType['packageAfter'],
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
            'therapist_appointments',
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
            'therapist_appointments',
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
            'therapist_appointments',
            json_encode($appointments)
        );
        
        return true;
    }
    
    /**
     * Get analytics for a therapist
     * 
     * @param string $therapistId The therapist ID
     * @param string $startDate The start date (YYYY-MM-DD)
     * @param string $endDate The end date (YYYY-MM-DD)
     * @return array The analytics data
     */
    public function getTherapistAnalytics(string $therapistId, string $startDate, string $endDate): array {
        $appointments = $this->getAppointmentsForTherapist($therapistId);
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate) + 86399; // End of day
        
        // Filter appointments by date range
        $filteredAppointments = array_filter($appointments, function($appt) use ($startTimestamp, $endTimestamp) {
            $apptTimestamp = strtotime($appt['startTime']);
            return $apptTimestamp >= $startTimestamp && $apptTimestamp <= $endTimestamp;
        });
        
        // Calculate analytics
        $totalAppointments = count($filteredAppointments);
        $completedAppointments = 0;
        $cancelledByTherapist = 0;
        $cancelledByClient = 0;
        $totalRevenue = 0;
        
        foreach ($filteredAppointments as $appt) {
            if ($appt['status'] === 'completed') {
                $completedAppointments++;
                // Calculate revenue based on hourly rate and appointment duration
                $startTime = strtotime($appt['startTime']);
                $endTime = strtotime($appt['endTime']);
                $durationHours = ($endTime - $startTime) / 3600;
                $hourlyRate = $this->therapistService->getTherapistHourlyRate($therapistId);
                $totalRevenue += $durationHours * $hourlyRate;
            } elseif ($appt['status'] === 'cancelled') {
                if (isset($appt['cancelledBy']) && $appt['cancelledBy'] === 'therapist') {
                    $cancelledByTherapist++;
                } else {
                    $cancelledByClient++;
                }
            }
        }
        
        return [
            'totalAppointments' => $totalAppointments,
            'completedAppointments' => $completedAppointments,
            'cancelledByTherapist' => $cancelledByTherapist,
            'cancelledByClient' => $cancelledByClient,
            'completionRate' => $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0,
            'totalRevenue' => $totalRevenue
        ];
    }
    
    /**
     * Get analytics for the entire practice
     * 
     * @param string $startDate The start date (YYYY-MM-DD)
     * @param string $endDate The end date (YYYY-MM-DD)
     * @return array The analytics data
     */
    public function getPracticeAnalytics(string $startDate, string $endDate): array {
        $therapists = $this->therapistService->getAllTherapists();
        $practiceAnalytics = [
            'totalAppointments' => 0,
            'completedAppointments' => 0,
            'cancelledByTherapist' => 0,
            'cancelledByClient' => 0,
            'totalRevenue' => 0,
            'therapistAnalytics' => []
        ];
        
        foreach ($therapists as $therapist) {
            $therapistId = $therapist['id'];
            $therapistAnalytics = $this->getTherapistAnalytics($therapistId, $startDate, $endDate);
            
            // Add to practice totals
            $practiceAnalytics['totalAppointments'] += $therapistAnalytics['totalAppointments'];
            $practiceAnalytics['completedAppointments'] += $therapistAnalytics['completedAppointments'];
            $practiceAnalytics['cancelledByTherapist'] += $therapistAnalytics['cancelledByTherapist'];
            $practiceAnalytics['cancelledByClient'] += $therapistAnalytics['cancelledByClient'];
            $practiceAnalytics['totalRevenue'] += $therapistAnalytics['totalRevenue'];
            
            // Add therapist analytics
            $practiceAnalytics['therapistAnalytics'][] = [
                'therapistId' => $therapistId,
                'therapistName' => $therapist['displayName'],
                'analytics' => $therapistAnalytics
            ];
        }
        
        // Calculate completion rate for the practice
        $practiceAnalytics['completionRate'] = $practiceAnalytics['totalAppointments'] > 0 
            ? ($practiceAnalytics['completedAppointments'] / $practiceAnalytics['totalAppointments']) * 100 
            : 0;
        
        return $practiceAnalytics;
    }
}