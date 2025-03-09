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
use OCA\Appointments\Service\TherapistService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for the main app page
 */
class PageController extends Controller {
    /** @var IConfig */
    private IConfig $config;
    
    /** @var IUserSession */
    private IUserSession $userSession;
    
    /** @var TherapistService */
    private TherapistService $therapistService;
    
    /** @var string|null */
    private ?string $userId;
    
    /**
     * Constructor for PageController
     *
     * @param string $appName The app name
     * @param IRequest $request The request object
     * @param IConfig $config The configuration service
     * @param IUserSession $userSession The user session
     * @param TherapistService $therapistService The therapist service
     * @param string|null $userId The user ID
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        IUserSession $userSession,
        TherapistService $therapistService,
        ?string $userId
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->userSession = $userSession;
        $this->therapistService = $therapistService;
        $this->userId = $userId;
    }
    
    /**
     * Render the main app page
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function index(): TemplateResponse {
        $user = $this->userSession->getUser();
        $isTherapist = false;
        
        if ($user !== null) {
            $isTherapist = $this->therapistService->isTherapist($user->getUID());
        }
        
        $params = [
            'user_id' => $this->userId,
            'is_therapist' => $isTherapist,
            'square_environment' => $this->config->getAppValue(
                Application::APP_ID,
                Application::CONFIG_SQUARE_ENVIRONMENT,
                'sandbox'
            ),
            'square_application_id' => $this->config->getAppValue(
                Application::APP_ID,
                Application::CONFIG_SQUARE_APPLICATION_ID,
                ''
            )
        ];
        
        return new TemplateResponse('appointments', 'index', $params);
    }
}