<?php
declare(strict_types=1);
/**
 * iceCMS2 v0.1a
 * Created by Sergey Peshalov https://github.com/desfpc
 * https://github.com/desfpc/iceCMS2
 *
 * Authorization Controller Class
 */

namespace app\Controllers\vendor;

use iceCMS2\Controller\AbstractController;
use iceCMS2\Controller\ControllerInterface;
use iceCMS2\Models\User;
use iceCMS2\Routing\Routing;
use iceCMS2\Settings\Settings;
use iceCMS2\Tools\Exception;
use iceCMS2\Tools\FlashVars;

class Authorize extends AbstractController implements ControllerInterface
{
    /** @var string Site page title */
    public string $title = 'Authorization';

    /** @var string Redirect string */
    public string $redirect = '';

    /** @var FlashVars Flashvars for alerts and errors */
    private FlashVars $flashVars;

    /**
     * @param Routing|null $routing
     * @param Settings|null $settings
     */
    public function __construct(?Routing $routing, ?Settings $settings)
    {
        parent::__construct($routing, $settings);

        $this->flashVars = new FlashVars();

        $this->requestParameters->getRequestValue('redirect');
        if (!empty($this->requestParameters->values->redirect)) {
            $this->redirect = $this->requestParameters->values->redirect;
        }
    }

    /**
     * Main method - login form and
     *
     * @return void
     * @throws Exception
     */
    public function main():void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->authorization->authorizeRequest();

            if ($this->authorization->getAuthStatus() === true) {
                //TODO redirect to redirect URL or default page
                echo 'yeah';
            } else {
                //TODO echo authorize errors
                echo 'azaza';
            }
        }

        $this->renderTemplate('main');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function registration():void {

        $this->requestParameters->getRequestValues(['email', 'password', 'rePassword']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User($this->settings);

            if (!empty($this->requestParameters->values->email) && !empty($this->requestParameters->values->password)
                && !empty($this->requestParameters->values->rePassword)
                && ($this->requestParameters->values->rePassword === $this->requestParameters->values->password)) {

                try {

                    $user->set([
                        'email' => $this->requestParameters->values->email,
                        'language' => $this->settings->locale,
                        'password' => $this->requestParameters->values->password,
                        'nikname' => $this->requestParameters->values->email,
                        'status' => User::STATUS_ACTIVE,
                        'role' => User::ROLE_USER,
                        'created_time' => date('Y-m-d H:i:s'),
                        'sex' => User::SEX_OTHER,
                    ]);

                    if ($user->save()) {
                        $this->flashVars->set('success', 'User created - please login with your credentials');
                        $this->renderTemplate('main');
                    } else {
                        $this->flashVars->set('error', 'User not created');
                    };

                } catch (Exception $e) {
                    $this->flashVars->set('error', $e->getMessage());
                }

            } else {
                $this->flashVars->set('error', 'Empty email or passwords, or passwords not equal');
            }
        }

        $this->renderTemplate('registration');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function resetPassword():void {
        $this->renderTemplate('resetPassword');
    }
}