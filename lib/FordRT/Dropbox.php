<?php
/**
 * Created by PhpStorm.
 * User: focus
 * Date: 06.10.18
 * Time: 12:44
 */

declare(strict_types=1);

namespace FordRT;

use Kunnu\Dropbox\DropboxApp;

/**
 * Class Dropbox
 * @package FordRT
 */
class Dropbox
{
    /**
     * Download database and key from dropbox
     */
    public static function download () : void
    {
        $application = new DropboxApp(\Config::DROPBOX['clientId'], \Config::DROPBOX['clientSecret'], \Config::DROPBOX['accessToken']);

        $dropbox = new \Kunnu\Dropbox\Dropbox($application);

        try {
            $dropbox->download("/Lum_Shared_Dropbox/appdata/keepass/clients.kdbx", PROJECT_ROOT.'/tmp/dropbox/clients.kdbx');
            $dropbox->download("/Lum_Shared_Dropbox/appdata/keepass/clients.key", PROJECT_ROOT.'/tmp/dropbox/clients.key');
        } catch (\Exception $exception) {
            echo "Download failed\n\n";
            var_dump($exception);
            die();
        }
    }
}