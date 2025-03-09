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
use OCP\IUser;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Service class for therapist-related operations
 */
class TherapistService {
    /** @var IConfig */
    private IConfig $config;
    
    /** @var IUserManager */
    private IUserManager $userManager;
    
    /**
     * Constructor for TherapistService
     * 
     * @param IConfig $config The configuration service
     * @param IUserManager $userManager The user manager service
     */
    public function __construct(IConfig $config, IUserManager $userManager) {
        $this->config = $config;
        $this->userManager = $userManager;
    }
    
    /**
     * Check if a user is a therapist
     * 
     * @param string $userId The user ID to check
     * @return bool True if the user is a therapist, false otherwise
     */
    public function isTherapist(string $userId): bool {
        return $this->config->getUserValue(
            $userId, 
            Application::APP_ID, 
            Application::CONFIG_IS_THERAPIST, 
            'false'
        ) === 'true';
    }
    
    /**
     * Get a therapist by ID
     * 
     * @param string $therapistId The therapist ID to get
     * @return array|null The therapist data, or null if not found
     */
    public function getTherapist(string $therapistId): ?array {
        $user = $this->userManager->get($therapistId);
        
        if ($user === null || !$this->isTherapist($therapistId)) {
            return null;
        }
        
        return $this->formatTherapistData($user);
    }
    
    /**
     * Get all therapists
     * 
     * @return array Array of therapist data
     */
    public function getAllTherapists(): array {
        $users = $this->userManager->search('');
        $therapists = [];
        
        foreach ($users as $user) {
            $userId = $user->getUID();
            
            if ($this->isTherapist($userId)) {
                $therapists[] = $this->formatTherapistData($user);
            }
        }
        
        return $therapists;
    }
    
    /**
     * Format therapist data from a user object
     * 
     * @param IUser $user The user object
     * @return array The formatted therapist data
     */
    private function formatTherapistData(IUser $user): array {
        $userId = $user->getUID();
        
        return [
            'id' => $userId,
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEMailAddress(),
            'specialties' => json_decode($this->config->getUserValue(
                $userId,
                Application::APP_ID,
                Application::CONFIG_SPECIALTIES,
                '[]'
            ), true),
            'bio' => $this->config->getUserValue(
                $userId, 
                Application::APP_ID, 
                Application::CONFIG_BIO, 
                ''
            ),
            'hourlyRate' => (float)$this->config->getUserValue(
                $userId, 
                Application::APP_ID, 
                Application::CONFIG_HOURLY_RATE, 
                '0'
            )
        ];
    }
    
    /**
     * Get a therapist's schedule
     * 
     * @param string $therapistId The therapist ID
     * @return array|null The schedule data, or null if therapist not found
     */
    public function getTherapistSchedule(string $therapistId): ?array {
        if (!$this->isTherapist($therapistId)) {
            return null;
        }
        
        // Get the therapist's schedule
        $schedule = json_decode($this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_SCHEDULE,
            '{}'
        ), true);
        
        // Get booked appointments
        $appointments = json_decode($this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_APPOINTMENTS,
            '[]'
        ), true);
        
        return [
            'schedule' => $schedule,
            'appointments' => $appointments
        ];
    }
    
    /**
     * Update a therapist's schedule
     * 
     * @param string $therapistId The therapist ID
     * @param array $schedule The new schedule data
     * @return bool True if successful, false otherwise
     */
    public function updateTherapistSchedule(string $therapistId, array $schedule): bool {
        if (!$this->isTherapist($therapistId)) {
            return false;
        }
        
        // Save the schedule
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            Application::CONFIG_SCHEDULE,
            json_encode($schedule)
        );
        
        return true;
    }
}