<?php
/**
 * Bootstrap file for Laravel Reverb
 * 
 * This file defines POSIX signal constants if the PCNTL extension is not available.
 * This is needed for running Reverb in environments where PCNTL is not installed
 * (e.g., some Docker containers, Windows, or restricted hosting environments).
 */

if (!defined('SIGINT')) {
    define('SIGINT', 2);
}

if (!defined('SIGTERM')) {
    define('SIGTERM', 15);
}

if (!defined('SIGTSTP')) {
    define('SIGTSTP', 20);
}

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
