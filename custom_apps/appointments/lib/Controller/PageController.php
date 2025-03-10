<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpFullyQualifiedNameUsageInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

namespace OCA\Appointments\Controller;

use OC\AppFramework\Middleware\Security\Exceptions\NotLoggedInException;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Appointments\AppInfo\Application;
use OCA\Appointments\Backend\BackendManager;
use OCA\Appointments\Backend\BackendUtils;
use OCA\Appointments\Backend\HintVar;
use OCA\Appointments\Backend\IBackendConnector;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Util;
use Psr\Log\LoggerInterface;

class PageController extends Controller
{
    const RND_SPS = 'abcdefghijklmnopqrstuvwxyz1234567890';
    const RND_SPU = '1234567890ABCDEF';

    const TEST_TOKEN_CNF = '3b719b44-8ec9-41e9-b161-00fb1515b1ed';

    private string|null $userId;
    private IConfig $c;
    private IMailer $mailer;
    private IL10N $l;
    private IBackendConnector $bc;
    private BackendUtils $utils;
    private LoggerInterface $logger;
    private IUserSession $userSession;
    private IURLGenerator $urlGenerator;

    public function __construct(IRequest        $request,
                                IConfig         $c,
                                IMailer         $mailer,
                                IL10N           $l,
                                IUserSession    $userSession,
                                BackendManager  $backendManager,
                                BackendUtils    $utils,
                                IURLGenerator   $urlGenerator,
                                LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->c = $c;
        $this->mailer = $mailer;
        $this->l = $l;
        $this->userSession = $userSession;
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->bc = $backendManager->getConnector();
        $this->utils = $utils;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->userId = $this->userSession->getUser()?->getUID();
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs, or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        $t = new TemplateResponse($this->appName, 'index');

        $disable = false;
        if (!empty($this->userId)) {
            $allowedGroups = $this->c->getAppValue($this->appName,
                BackendUtils::KEY_LIMIT_TO_GROUPS);
            if ($allowedGroups !== '') {
                $aga = json_decode($allowedGroups, true);
                if ($aga !== null) {
                    $user = $this->userSession->getUser();
                    $userGroups = \OC::$server->get(IGroupManager::class)->getUserGroups($user);
                    $disable = true;
                    foreach ($aga as $ag) {
                        if (array_key_exists($ag, $userGroups)) {
                            $disable = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($disable) {
            $t->setParams(['disabled' => true]);
        } else {
            // Add scripts and styles
            Util::addScript(Application::APP_ID, 'appointments-main');
            Util::addStyle(Application::APP_ID, 'style');
            
            // Check if the user is a therapist
            $isTherapist = false;
            if (!empty($this->userId)) {
                $isTherapist = $this->c->getUserValue(
                    $this->userId,
                    Application::APP_ID,
                    'is_therapist',
                    'false'
                ) === 'true';
            }
            
            // Get Square credentials
            $squareEnvironment = $this->c->getAppValue(
                Application::APP_ID,
                'square_environment',
                'sandbox'
            );
            
            $squareApplicationId = $this->c->getAppValue(
                Application::APP_ID,
                'square_application_id',
                ''
            );
            
            // Set template parameters
            $t->setParams([
                'userId' => $this->userId,
                'isTherapist' => $isTherapist,
                'squareEnvironment' => $squareEnvironment,
                'squareApplicationId' => $squareApplicationId
            ]);
        }

        $csp = $t->getContentSecurityPolicy();
        if ($csp === null) {
            $csp = new ContentSecurityPolicy();
            $t->setContentSecurityPolicy($csp);
        }
        $csp->addAllowedFrameDomain('\'self\'');
        
        // Add CSP for Square if needed
        if (!empty($squareApplicationId)) {
            $csp->addAllowedScriptDomain('https://sandbox.web.squarecdn.com');
            $csp->addAllowedScriptDomain('https://web.squarecdn.com');
            $csp->addAllowedConnectDomain('https://sandbox.squareup.com');
            $csp->addAllowedConnectDomain('https://squareup.com');
        }

        return $t;// templates/index.php
    }
}
