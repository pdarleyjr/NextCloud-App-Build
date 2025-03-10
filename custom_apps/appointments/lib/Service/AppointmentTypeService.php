<?php

declare(strict_types=1);

namespace OCA\Appointments\Service;

use OCA\Appointments\AppInfo\Application;
use OCP\IConfig;

/**
 * Service class for appointment type-related operations
 */
class AppointmentTypeService {
    /** @var IConfig */
    private IConfig $config;
    
    /**
     * Constructor for AppointmentTypeService
     * 
     * @param IConfig $config The configuration service
     */
    public function __construct(IConfig $config) {
        $this->config = $config;
    }
    
    /**
     * Get all appointment types
     * 
     * @return array The appointment types
     */
    public function getAllAppointmentTypes(): array {
        $appointmentTypes = $this->config->getAppValue(
            Application::APP_ID,
            'appointment_types',
            json_encode($this->getDefaultAppointmentTypes())
        );
        
        return json_decode($appointmentTypes, true);
    }
    
    /**
     * Get appointment types by category
     * 
     * @param string $category The category
     * @return array The appointment types
     */
    public function getAppointmentTypesByCategory(string $category): array {
        $allTypes = $this->getAllAppointmentTypes();
        
        if (isset($allTypes[$category])) {
            return $allTypes[$category];
        }
        
        return [];
    }
    
    /**
     * Get an appointment type by ID
     * 
     * @param string $id The appointment type ID
     * @return array|null The appointment type, or null if not found
     */
    public function getAppointmentType(string $id): ?array {
        $allTypes = $this->getAllAppointmentTypes();
        
        foreach ($allTypes as $category => $types) {
            foreach ($types as $type) {
                if ($type['id'] === $id) {
                    $type['category'] = $category;
                    return $type;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Update appointment types
     * 
     * @param array $appointmentTypes The appointment types
     * @return bool True if successful, false otherwise
     */
    public function updateAppointmentTypes(array $appointmentTypes): bool {
        $this->config->setAppValue(
            Application::APP_ID,
            'appointment_types',
            json_encode($appointmentTypes)
        );
        
        return true;
    }
    
    /**
     * Get default appointment types
     * 
     * @return array The default appointment types
     */
    private function getDefaultAppointmentTypes(): array {
        return [
            'Pediatric/Adolescent' => [
                [
                    'id' => 'ped_2person_cotreatment',
                    'name' => '2 Person Co-treatment (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ped_group',
                    'name' => 'Group (3 or more) (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ped_multidiscipline_eval',
                    'name' => 'Multidiscipline Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'ped_initial_speech_eval',
                    'name' => 'Pediatric/Adolescent Initial Speech Language Evaluation (120 minutes)',
                    'price' => 0.00,
                    'duration' => 120,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ped_feeding_eval',
                    'name' => 'Feeding/Swallowing Evaluation (120 minutes)',
                    'price' => 0.00,
                    'duration' => 120,
                    'packageBefore' => 15,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ped_comp_swallow_eval',
                    'name' => 'Comprehensive Swallow Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'ped_oral_mech_eval',
                    'name' => 'Oral Mech/Clinbedside Swallow Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'ped_language_eval',
                    'name' => 'Language Comprehension/Expression Eval (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'ped_stuttering_eval',
                    'name' => 'Stuttering/Fluency Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'ped_voice_eval',
                    'name' => 'Voice Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 5
                ]
            ],
            'Graduate Student Clinical' => [],
            'Occupational Therapy' => [
                [
                    'id' => 'ot_2person_cotreatment',
                    'name' => '2 Person Co-treatment (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ot_group',
                    'name' => 'Group (3 or more) (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ot_individual',
                    'name' => 'Individual (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'ot_evaluation',
                    'name' => 'OT Evaluation (60 minutes)',
                    'price' => 0.00,
                    'duration' => 60,
                    'packageBefore' => 15,
                    'packageAfter' => 15
                ]
            ],
            'Treatment' => [
                [
                    'id' => 'treat_ped_speech',
                    'name' => 'Pediatric/Adolescent Speech-Language Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_ped_ot',
                    'name' => 'Pediatric/Adolescent Occupational Therapy Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_adult_speech',
                    'name' => 'Adult Speech-Language Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_adult_ot',
                    'name' => 'Adult Occupational Therapy Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_adult_student',
                    'name' => 'Adult Student Co-treatment (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_lsvt',
                    'name' => 'LSVT (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_individual_adult',
                    'name' => 'Individual Adult Therapy (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_vital_stim',
                    'name' => 'Vital Stim (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_voice_30',
                    'name' => 'Voice/Resonance Therapy (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_swallowing_30',
                    'name' => 'Swallowing Therapy (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_aac_30',
                    'name' => 'AAC Therapy (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_ped_speech_30',
                    'name' => 'Pediatric/Adolescent Speech-Language Treatment (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_ped_ot_30',
                    'name' => 'Pediatric/Adolescent Occupational Therapy Treatment (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_adult_speech_30',
                    'name' => 'Adult Speech-Language Treatment (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_adult_ot_30',
                    'name' => 'Adult Occupational Therapy Treatment (30 minutes)',
                    'price' => 45.00,
                    'duration' => 30,
                    'packageBefore' => 10,
                    'packageAfter' => 5
                ],
                [
                    'id' => 'treat_group_ped',
                    'name' => 'Group Pediatric Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_2person_ped',
                    'name' => '2 Person Pediatric/Adolescent Co-Treatment (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_2person_adult',
                    'name' => 'Adult 2 Person Co-treatment (60 minutes)',
                    'price' => 80.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_voice_60',
                    'name' => 'Voice/Resonance Therapy (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_swallowing_60',
                    'name' => 'Swallowing Therapy (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_aac_60',
                    'name' => 'AAC Therapy (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ],
                [
                    'id' => 'treat_neurobased',
                    'name' => 'Neurobased Tx for Speech and Language Treatment (60 minutes)',
                    'price' => 85.00,
                    'duration' => 60,
                    'packageBefore' => 10,
                    'packageAfter' => 10
                ]
            ]
        ];
    }
}