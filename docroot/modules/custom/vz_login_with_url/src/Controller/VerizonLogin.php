<?php

namespace Drupal\vz_login_with_url\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core;


/**
 * @file
 * Contains \Drupal\hello\Controller\HelloController.
 */
class VerizonLogin extends ControllerBase {

  public function login() {
    $account = user_load_by_mail('guest@guest.com');
    if (!empty($account->id())) {
      $uid = $account->id();
      $user = User::load($uid);
      user_login_finalize($user);
    }
    return '<div></div>';
  }
}
