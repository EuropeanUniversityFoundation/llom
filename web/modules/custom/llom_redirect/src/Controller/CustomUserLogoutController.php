<?php

namespace Drupal\llom_redirect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Controller for custom user logout.
 */
class CustomUserLogoutController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {

    $logout_url = '/';
    $roles = $this->currentUser->getRoles();
    $config = $this->configFactory->get('llom_redirect.settings');

    if (in_array('student', $roles)) {
      $logout_url = $config->get('redirect_url');
    }

    user_logout();
    return new TrustedRedirectResponse($logout_url);
  }

}
