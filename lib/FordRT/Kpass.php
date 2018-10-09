<?php
/**
 * Created by PhpStorm.
 * User: focus
 * Date: 06.10.18
 * Time: 12:32
 */

declare(strict_types=1);

namespace FordRT;

use KeePassPHP\Database;
use KeePassPHP\KeePassPHP;

/**
 * Class Kpass
 * @package FordRT
 */
class Kpass
{
    private const DB_FILE = PROJECT_ROOT.'/tmp/dropbox/clients.kdbx';
    private const KEY_FILE = PROJECT_ROOT.'/tmp/dropbox/clients.key';

    public static function parse()
    {
        if(!KeePassPHP::init(null, true)) {
            echo "Error: init failed\n";
            echo "Debug data: ". KeePassPHP::$debugData."\n";
            die();
        }

        $composite_key = KeePassPHP::masterKey();
        KeePassPHP::addPassword($composite_key, \Config::KEEPASS['password']);
        if(!KeePassPHP::addKeyFile($composite_key, self::KEY_FILE)) { errorAndDie("file key parsing error."); }

        $database = KeePassPHP::openDatabaseFile(self::DB_FILE, $composite_key, $error);
        if($database == null) { errorAndDie($error); }

        /** TODO: do not use globals */
        $GLOBALS['parsedKp'] = [];
        $GLOBALS['parsedKp'][] = [
            'Group' => 'Group',
            'Title' => 'Title',
            'Username' => 'Username',
            'Password' => 'Password',
            'Url' => 'Url',
            'Notes' => 'Notes',
        ];

        self::parseGroups($database->getGroups());
    }

    /**
     * @param array $groups
     * @param string $group_prefix
     */
    private static function parseGroups($groups, $group_prefix = '') {
        foreach($groups as $group) {
            if ($group->entries !== null) {
                foreach($group->entries as $entry) {
                    $GLOBALS['parsedKp'][] = [
                        'Group' => $group_prefix.$group->name,
                        'Title' => $entry->getStringField(Database::KEY_TITLE),
                        'Username' => $entry->getStringField(Database::KEY_USERNAME),
                        'Password' => ($entry->password === null ? '' : $entry->password->getPlainString()),
                        'Url' => $entry->getStringField(Database::KEY_URL),
                        'Notes' => $entry->getStringField("Notes"),
                    ];
                }
            }

            if ($group->groups !== null) {
                self::parseGroups($group->groups, $group_prefix.$group->name.' :: ');
            }
        }
    }
}
