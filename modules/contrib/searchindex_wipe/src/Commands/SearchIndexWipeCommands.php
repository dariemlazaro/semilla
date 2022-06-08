<?php

namespace Drupal\searchindex_wipe\Commands;

use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Class SearchIndexWipeCommands.
 *
 * @package Drupal\searchindex_wipe\Commands
 */
class SearchIndexWipeCommands extends DrushCommands {

  /**
   * Wipes Search module generated index.
   *
   * @command searchindex-wipe
   * @aliases siw
   * @usage drush9_example:hello akanksha --msg
   *   Truncates search index tables.
   */
  public function wipe() {
    if ($this->io()->confirm(dt('Are you sure you want to clear Search index?'))) {
      searchindex_wipe_truncate_table();
    }
    else {
      throw new UserAbortException();
    }
  }

}
