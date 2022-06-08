<?php

/**
 * @file
 * Drush related functions.
 */

/**
 * Implements hook_drush_help().
 */
function searchindex_wipe_drush_help($section) {
  switch ($section) {
    case 'meta:searchindex_wipe:title':
      return dt('Search Index wipe commands');

    case 'meta:cache:summary':
      return dt('Wipes Search module generated index.');
  }
}

/**
 * Implements hook_drush_command().
 */
function searchindex_wipe_drush_command() {
  $items['searchindex-wipe'] = [
    'description' => 'Wipes the search index',
    'aliases' => ['siw'],
    'examples' => [
      'drush siw' => 'Wipes search index.',
    ],
  ];
  return $items;
}

/**
 * Ask for confirmation and drops indexes.
 */
function drush_searchindex_wipe() {
  if (drush_confirm('Are you sure you want to clear Search index?')) {
    searchindex_wipe_truncate_table();
  }
  else {
    drush_user_abort();
  }
}
