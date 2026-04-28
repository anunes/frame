<?php

if (!function_exists('frame_cli_project_root')) {
    function frame_cli_project_root(): string
    {
        return dirname(__DIR__, 3);
    }
}

if (!function_exists('frame_configure_cli_environment')) {
    function frame_configure_cli_environment(): string
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '1');

        return frame_cli_project_root();
    }
}
