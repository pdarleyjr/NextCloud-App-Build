<?php
$CONFIG = array (
  // Optimize session settings
  'session_lifetime' => 3600,
  'session_keepalive' => false,
  
  // Theme settings
  'theme' => 'default',
  'themingdefaults' => [
    'name' => 'Nextcloud',
    'url' => 'https://nextcloud.com',
    'slogan' => 'A safe home for all your data',
    'color' => '#0082c9',
  ],
  
  // Background jobs settings
  'maintenance_window_start' => 1,
  'backgroundjobs_mode' => 'cron',
  
  // Logging settings
  'log_type' => 'file',
  'logfile' => '/var/www/html/data/nextcloud.log',
  'loglevel' => 2,
  
  // Performance optimizations
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'filelocking.enabled' => true,
  'memcache.locking' => '\\OC\\Memcache\\Redis',
  'redis' => [
    'host' => 'redis',
    'port' => 6379,
  ],
  
  // Security settings
  'trusted_domains' => [
    'localhost',
    'nextcloud-app',
    'sturdy-space-chainsaw-7vpx5w6gjgvpcpxgr-8080.app.github.dev'
  ],
  'overwriteprotocol' => 'https',
);