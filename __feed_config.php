<?php
class config {

    public static $key;

    public static $localUser          = 'admin';
    public static $localPassword      = '96f3577cd638';
    public static $fileTimeCorrection = 0;
    public static $tmpDir             = '_tmp';
    public static $filesIgnore        = array('__feed_config.php', '__update.php');
    public static $dirsIgnore         = array('__tmp');
    public static $authKey            = 'feed_auth';
    public static $sigKey             = 'feed_key';
    public static $sigTime            = 'feed_key_time';
    public static $basePath;

    public static function basePath() {
        if (!isset(self::$basePath)) {
            self::$basePath = '.' . DIRECTORY_SEPARATOR;
        }
        return self::$basePath;
    }

    public static $remote = array(
        'url'       => 'https://cp.zeroparallel.com/feed/out/',
        'feed_id'   => 4389,
        'user_id'   => 16980,
        'key'       => 'c19677b2734e8c',
        'salt'      => '38a2440b4',
        'plaintext' => array('php', 'txt', 'css', 'js', 'html', 'phtml', 'htm', 'ini', 'no_ext')
    );

}