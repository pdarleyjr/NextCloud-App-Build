<?php

declare(strict_types=1);

namespace OCA\Appointments\Service;

use OCA\Appointments\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;

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
     * @param IUserManager $userManager The user manager
     */
    public function __construct(IConfig $config, IUserManager $userManager) {
        $this->config = $config;
        $this->userManager = $userManager;
    }
    
    /**
     * Check if a user is a therapist
     * 
     * @param string $userId The user ID
     * @return bool True if the user is a therapist, false otherwise
     */
    public function isTherapist(string $userId): bool {
        return $this->config->getUserValue(
            $userId,
            Application::APP_ID,
            'is_therapist',
            'false'
        ) === 'true';
    }
    
    /**
     * Set a user as a therapist
     * 
     * @param string $userId The user ID
     * @param bool $isTherapist Whether the user is a therapist
     * @return bool True if successful, false otherwise
     */
    public function setTherapist(string $userId, bool $isTherapist): bool {
        $this->config->setUserValue(
            $userId,
            Application::APP_ID,
            'is_therapist',
            $isTherapist ? 'true' : 'false'
        );
        
        return true;
    }
    
    /**
     * Get all therapists
     * 
     * @return array The therapists
     */
    public function getAllTherapists(): array {
        $therapists = [];
        
        $this->userManager->callForAllUsers(function(IUser $user) use (&$therapists) {
            $userId = $user->getUID();
            
            if ($this->isTherapist($userId)) {
                $therapists[] = [
                    'id' => $userId,
                    'displayName' => $user->getDisplayName(),
                    'email' => $user->getEMailAddress(),
                    'specialties' => $this->getTherapistSpecialties($userId),
                    'bio' => $this->getTherapistBio($userId),
                    'hourlyRate' => $this->getTherapistHourlyRate($userId)
                ];
            }
        });
        
        return $therapists;
    }
    
    /**
     * Get a specific therapist
     * 
     * @param string $therapistId The therapist ID
     * @return array|null The therapist, or null if not found
     */
    public function getTherapist(string $therapistId): ?array {
        if (!$this->isTherapist($therapistId)) {
            return null;
        }
        
        $user = $this->userManager->get($therapistId);
        
        if ($user === null) {
            return null;
        }
        
        return [
            'id' => $therapistId,
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEMailAddress(),
            'specialties' => $this->getTherapistSpecialties($therapistId),
            'bio' => $this->getTherapistBio($therapistId),
            'hourlyRate' => $this->getTherapistHourlyRate($therapistId)
        ];
    }
    
    /**
     * Get a therapist's specialties
     * 
     * @param string $therapistId The therapist ID
     * @return array The specialties
     */
    public function getTherapistSpecialties(string $therapistId): array {
        $specialties = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'specialties',
            '[]'
        );
        
        return json_decode($specialties, true);
    }
    
    /**
     * Set a therapist's specialties
     * 
     * @param string $therapistId The therapist ID
     * @param array $specialties The specialties
     * @return bool True if successful, false otherwise
     */
    public function setTherapistSpecialties(string $therapistId, array $specialties): bool {
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'specialties',
            json_encode($specialties)
        );
        
        return true;
    }
    
    /**
     * Get a therapist's bio
     * 
     * @param string $therapistId The therapist ID
     * @return string The bio
     */
    public function getTherapistBio(string $therapistId): string {
        return $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'bio',
            ''
        );
    }
    
    /**
     * Set a therapist's bio
     * 
     * @param string $therapistId The therapist ID
     * @param string $bio The bio
     * @return bool True if successful, false otherwise
     */
    public function setTherapistBio(string $therapistId, string $bio): bool {
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'bio',
            $bio
        );
        
        return true;
    }
    
    /**
     * Get a therapist's hourly rate
     * 
     * @param string $therapistId The therapist ID
     * @return float The hourly rate
     */
    public function getTherapistHourlyRate(string $therapistId): float {
        $hourlyRate = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'hourly_rate',
            '0'
        );
        
        return (float) $hourlyRate;
    }
    
    /**
     * Set a therapist's hourly rate
     * 
     * @param string $therapistId The therapist ID
     * @param float $hourlyRate The hourly rate
     * @return bool True if successful, false otherwise
     */
    public function setTherapistHourlyRate(string $therapistId, float $hourlyRate): bool {
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'hourly_rate',
            (string) $hourlyRate
        );
        
        return true;
    }
    
    /**
     * Get a therapist's schedule
     * 
     * @param string $therapistId The therapist ID
     * @return array The schedule
     */
    public function getTherapistSchedule(string $therapistId): array {
        $schedule = $this->config->getUserValue(
            $therapistId,
            Application::APP_ID,
            'schedule',
            '[]'
        );
        
        return json_decode($schedule, true);
    }
    
    /**
     * Set a therapist's schedule
     * 
     * @param string $therapistId The therapist ID
     * @param array $schedule The schedule
     * @return bool True if successful, false otherwise
     */
    public function setTherapistSchedule(string $therapistId, array $schedule): bool {
        $this->config->setUserValue(
            $therapistId,
            Application::APP_ID,
            'schedule',
            json_encode($schedule)
        );
        
        return true;
    }
}