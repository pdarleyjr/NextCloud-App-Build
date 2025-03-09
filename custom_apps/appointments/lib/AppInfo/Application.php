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

namespace OCA\Appointments\AppInfo;

use OCA\Appointments\Service\AppointmentService;
use OCA\Appointments\Service\TherapistService;
use OCA\Appointments\Service\BillingService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;

/**
 * Main application class for the Appointments app
 */
class Application extends App implements IBootstrap {
    // Application constants
    public const APP_ID = 'appointments';
    public const CONFIG_IS_THERAPIST = 'is_therapist';
    public const CONFIG_SPECIALTIES = 'specialties';
    public const CONFIG_BIO = 'bio';
    public const CONFIG_HOURLY_RATE = 'hourly_rate';
    public const CONFIG_SCHEDULE = 'schedule';
    public const CONFIG_APPOINTMENTS = 'appointments';
    public const CONFIG_INVOICES = 'invoices';
    public const CONFIG_SQUARE_ENVIRONMENT = 'square_environment';
    public const CONFIG_SQUARE_ACCESS_TOKEN = 'square_access_token';
    public const CONFIG_SQUARE_APPLICATION_ID = 'square_application_id';

    /**
     * Constructor for the Application class
     */
    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    /**
     * Register application services
     */
    public function register(IRegistrationContext $context): void {
        // Register services for dependency injection
        $context->registerService(AppointmentService::class, function($c) {
            return new AppointmentService(
                $c->get(IConfig::class),
                $c->get(TherapistService::class)
            );
        });

        $context->registerService(TherapistService::class, function($c) {
            return new TherapistService(
                $c->get(IConfig::class),
                $c->get('UserManager')
            );
        });

        $context->registerService(BillingService::class, function($c) {
            return new BillingService(
                $c->get(IConfig::class),
                $c->get('UserManager'),
                $c->get('Logger')
            );
        });
    }

    /**
     * Boot the application
     */
    public function boot(IBootContext $context): void {
        // Initialize app settings if needed
        $config = $context->getServerContainer()->get(IConfig::class);
        
        // Set default Square environment if not set
        if ($config->getAppValue(self::APP_ID, self::CONFIG_SQUARE_ENVIRONMENT, '') === '') {
            $config->setAppValue(self::APP_ID, self::CONFIG_SQUARE_ENVIRONMENT, 'sandbox');
        }
    }
}