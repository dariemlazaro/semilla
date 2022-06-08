<?php /**
 * @file
 * Contains \Drupal\db_maintenance\Controller\DefaultController.
 */

namespace Drupal\db_maintenance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\db_maintenance\Module\Db\DbHandler;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Default controller for the db_maintenance module.
 */
class DefaultController extends ControllerBase {

  public function optimizeTables() {
    DbHandler::optimizeTables();
    $messenger = \Drupal::messenger();
    $messenger->addStatus(new FormattableMarkup('@message', [
      '@message' => t('Database tables optimized'),
    ]));
    //drupal_goto('admin/config/system/db_maintenance');
    return $this->redirect('db_maintenance.admin_settings');
  }

}
