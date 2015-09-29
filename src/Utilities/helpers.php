<?php
/**
 * Created by IntelliJ IDEA.
 * User: mduncan
 * Date: 9/29/15
 * Time: 12:05 PM
 */
if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return (__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '../storage/') . ($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}