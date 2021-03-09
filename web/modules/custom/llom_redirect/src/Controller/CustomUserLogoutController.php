<?php

namespace Drupal\llom_redirect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class CustomUserLogoutController.
 */
class CustomUserLogoutController extends ControllerBase {

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {
    
      $logout_url = '/';
      $current_user = \Drupal::currentUser();
      $roles = $current_user->getRoles();


    if(in_array('student',$roles)){
      $logout_url = 'http://localhost:3000/';

    }
  


  
  user_logout();
  return new TrustedRedirectResponse($logout_url);
    
    
    
    

    
  }

}