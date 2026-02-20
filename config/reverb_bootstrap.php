<?php
/**
 * Bootstrap file for Laravel Reverb
 * 
 * This file defines POSIX signal constants if the PCNTL extension is not available.
 * This is needed for running Reverb in environments where PCNTL is not installed
 * (e.g., some Docker containers, Windows, or restricted hosting environments).
 */

// Define all signal constants that Symfony Console and Reverb require
if (!defined('SIGINT')) {
    define('SIGINT', 2);
}

if (!defined('SIGTERM')) {
    define('SIGTERM', 15);
}

if (!defined('SIGTSTP')) {
    define('SIGTSTP', 20);
}

if (!defined('SIGQUIT')) {
    define('SIGQUIT', 3);
}

if (!defined('SIGUSR1')) {
    define('SIGUSR1', 10);
}

if (!defined('SIGUSR2')) {
    define('SIGUSR2', 12);
}

if (!defined('SIGALRM')) {
    define('SIGALRM', 14);
}

// Provide no-op implementations for PCNTL functions
if (!function_exists('pcntl_signal')) {
    function pcntl_signal($signal, $handler) {
        // No-op function for environments without PCNTL
        return true;
    }
}

if (!function_exists('pcntl_async_signals')) {
    function pcntl_async_signals($enable) {
        // No-op function for environments without PCNTL
        return true;
    }
}
