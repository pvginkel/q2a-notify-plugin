<?php

/*
  Plugin Name: Notification feed plugin for Q2A Notifier
  Plugin URI: https://github.com/pvginkel/q2anotify
  Plugin Description: Serves up a feed for the Q2A Notifier.
  Plugin Version: 1.0
  Plugin Date: 2018-03-28
  Plugin Author: Pieter van Ginkel
  Plugin Author URI: https://github.com/pvginkel
  Plugin License:
  Plugin Minimum Question2Answer Version: 1.8
  Plugin Update Check URI:
*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
        header('Location: ../../');
        exit;
}

qa_register_plugin_module('page', 'get-updates.php', 'notify_get_updates', 'Serves updates for the Q2A notifier');
qa_register_plugin_module('event', 'event-logger.php', 'notify_event_logger', 'Event logger');
