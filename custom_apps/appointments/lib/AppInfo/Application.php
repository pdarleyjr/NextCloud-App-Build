<?php

namespace OCA\Appointments\AppInfo;

use OCA\Appointments\Backend\BeforeTemplateRenderedListener;
use OCA\Appointments\Backend\DavListener;
use OCA\Appointments\Backend\RemoveScriptsMiddleware;
use OCA\Appointments\Service\AppointmentService;
use OCA\Appointments\Service\AppointmentTypeService;
use OCA\Appointments\Service\BillingService;
use OCA\Appointments\Service\TherapistService;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;

class Application extends App implements IBootstrap
{
    // Application constants
    const APP_ID = 'appointments';
    const CONFIG_IS_THERAPIST = 'is_therapist';
    const CONFIG_SPECIALTIES = 'specialties';
    const CONFIG_BIO = 'bio';
    const CONFIG_HOURLY_RATE = 'hourly_rate';
    const CONFIG_SCHEDULE = 'schedule';
    const CONFIG_APPOINTMENTS = 'therapist_appointments';
    const CONFIG_INVOICES = 'invoices';
    const CONFIG_SUPERBILLS = 'superbills';
    const CONFIG_SQUARE_ENVIRONMENT = 'square_environment';
    const CONFIG_SQUARE_ACCESS_TOKEN = 'square_access_token';
    const CONFIG_SQUARE_APPLICATION_ID = 'square_application_id';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        // Register original event listeners
        $context->registerEventListener(CalendarObjectUpdatedEvent::class, DavListener::class);
        $context->registerEventListener(CalendarObjectMovedToTrashEvent::class, DavListener::class);
        $context->registerEventListener(SubscriptionDeletedEvent::class, DavListener::class);

        $context->registerService('ApptRemoveScriptsMiddleware', function ($c) {
            return new RemoveScriptsMiddleware();
        });
        $context->registerMiddleware('ApptRemoveScriptsMiddleware');

        $context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
        
        // Register our custom services
        $context->registerService(TherapistService::class, function($c) {
            return new TherapistService(
                $c->get(IConfig::class),
                $c->get(IUserManager::class)
            );
        });
        
        $context->registerService(AppointmentTypeService::class, function($c) {
            return new AppointmentTypeService(
                $c->get(IConfig::class)
            );
        });
        
        $context->registerService(AppointmentService::class, function($c) {
            return new AppointmentService(
                $c->get(IConfig::class),
                $c->get(IUserManager::class),
                $c->get(ILogger::class),
                $c->get(TherapistService::class),
                $c->get(AppointmentTypeService::class)
            );
        });
        
        $context->registerService(BillingService::class, function($c) {
            return new BillingService(
                $c->get(IConfig::class),
                $c->get(IUserManager::class),
                $c->get(ILogger::class)
            );
        });
    }

    public function boot(IBootContext $context): void
    {
        $config = $context->getServerContainer()->get(IConfig::class);
        
        // Set default Square environment if not set
        if ($config->getAppValue(self::APP_ID, self::CONFIG_SQUARE_ENVIRONMENT, '') === '') {
            $config->setAppValue(self::APP_ID, self::CONFIG_SQUARE_ENVIRONMENT, 'sandbox');
        }
    }
}