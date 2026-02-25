<?php

session_start();

require "__feed_config.php";

class localAuth
{

    public function auth()
    {

        if (
            (isset($_SESSION[config::$authKey]) && $_SESSION[config::$authKey] > 0)
            || (isset($_POST['login']) && $_POST['login'] == config::$localUser
                && isset($_POST['password']) && $_POST['password'] == config::$localPassword)
            || (isset($_POST['master'])
                && isset($_POST['feed_id']) && $_POST['feed_id'] == config::$remote['feed_id']
                && isset($_POST['s']) && !empty($_POST['s'])
                && isset($_POST['master']) && $_POST['master'] == $this->masterSign())
        ) {
            $_SESSION[config::$authKey] = 1;

            return true;
        }

    }

    private function masterSign()
    {

        $s    = md5(config::$remote['feed_id'] . config::$remote['key'] . $_POST['s']);
        $sign = substr($s, 2, 10) . substr(md5(config::$remote['user_id'] . config::$remote['salt'] . $s), 3, 14);

        return $sign;
    }

    public function deAuth()
    {

        unset($_SESSION[config::$authKey]);
        session_destroy();
    }

    public function __invoke()
    {

        return $this->auth();
    }
}

class remote
{

    private $url        = null;
    private $uid        = null;
    private $feedId     = null;

    public $auth_error = array();

    /**
     * Gets file extension
     * @param string $fileName
     * @return string
     */
    public static function getExtension($fileName)
    {
        $dir = dirname($fileName);
        $fN  = substr($fileName, strlen($dir));

        $ext = explode('.', $fN);
        $ext = end($ext);

        if (empty($ext) || !strpos($fN, '.')) {
            $ext = 'no_ext';
        }

        return $ext;
    }

    public function __construct($url, $uid, $feedId)
    {
        $this->url    = $url;
        $this->uid    = $uid;
        $this->feedId = $feedId;
    }

    private function sign($key, $userId, $feedId)
    {
        $code = md5($userId . config::$remote['key'] . $key) . md5($key . $feedId . config::$remote['salt']);

        return $code;
    }

    public function getList()
    {
        $list = $this->sendRequest('list');

        if (!empty($list)) {
            $list = json_decode($list, true);
        } else {
            $list = array();
        }

        return $list;
    }

    private function _auth()
    {
        $r = $this->sendRequest('ping');

        if ($r == '403' || $r == '429' || substr($r, 4) == '429|') {
            switch ($r) {
                case '403':
                    $this->auth_error[] = '403 - Access denied.';
                    break;
                default:
                    $this->auth_error[] = 'Too many auth requests. Try later.';
                    break;
            }
        } else {

            $sig             = $this->sign($r, $this->uid, $this->feedId);
            $_SESSION[config::$sigKey] = $sig;
            $r2              = $this->sendRequest('pong', array('cc' => $sig));

            if ($r2 == 'ok') {
                $_SESSION[config::$sigTime] = time() + 60;
                return true;
            } else {
                unset($_SESSION[config::$sigTime]);
                unset($_SESSION[config::$sigKey]);
            }

        }

        return false;
    }

    public function auth()
    {
        if ((isset($_SESSION[config::$sigTime]) ? $_SESSION[config::$sigTime] : 0) <=time()) {

            if (isset($_SESSION[config::$sigTime])) {
                unset($_SESSION[config::$sigTime]);
                unset($_SESSION[config::$sigKey]);
            }
            $remoteAuth = $this->_auth();
        } else {
            $remoteAuth = true;
        }

        return $remoteAuth;
    }

    private function sendRequest($action = '', $data = array())
    {
        $rnd          = substr(md5(mt_rand()), 4, 5);
        $data = $data + array(
                'uid' => $this->uid,
                'feed_id' => $this->feedId,
                'act'     => $action,
                's'       => $rnd,
                's2'      => substr(md5($rnd . config::$remote['salt']), 0, 10)
            );
        $postFields   = array();

        if (!in_array($action, array('ping', 'pong'))) {
            $postFields[] = 'key=' . (isset($_SESSION[config::$sigKey]) ? $_SESSION[config::$sigKey] : '');
        }

        foreach ($data as $k => $v) {
            $postFields[] = urlencode($k) . '=' . urlencode($v);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $action . '/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $postFields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


        if ($action == 'file') {

            $ext = remote::getExtension($data['fileName']);
            if (in_array($ext, config::$remote['plaintext'])) {
                $mode = 'w';
            } else {
                $mode = 'wb';
            }

            $dirName = '.' . DIRECTORY_SEPARATOR . config::$tmpDir . DIRECTORY_SEPARATOR . dirname($data['fileName']);
            $data['fileName'] = config::basePath() . $data['fileName'];
            $data['fileName'] = str_replace('..', '', $data['fileName']);
            if (!is_dir($dirName)) {
                mkdir($dirName, 0755, true);
            }

            if (!is_dir(config::$tmpDir)) {
                mkdir(config::$tmpDir, 0755, true);
            }
            $file = fopen('.' . DIRECTORY_SEPARATOR . config::$tmpDir . DIRECTORY_SEPARATOR . $data['fileName'], $mode);
            curl_setopt($ch, CURLOPT_FILE, $file);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            fclose($file);

            $server_output = $this->getFileTime('.' . DIRECTORY_SEPARATOR . config::$tmpDir . DIRECTORY_SEPARATOR . $data['fileName']);
        } else {
            curl_setopt(
                $ch,
                CURLOPT_RETURNTRANSFER,
                true
            );
            $server_output = curl_exec($ch);
        }


        curl_close($ch);

        return $server_output;
    }

    public function getFile($fileName)
    {
        $oldFileTime = $this->getFileTime($fileName);
        $newFileTime = $this->sendRequest('file', array('fileName' => $fileName));

        return $oldFileTime != $newFileTime;
    }

    public function getFileTime($fName)
    {
        $fName = config::basePath() . $fName;
        $fName = str_replace('//', '/', $fName);

        return
            file_exists($fName) ?  filemtime($fName) : 0;
    }

    public function fileOutdated($fileName, $page)
    {
        $fileName = config::basePath() . $fileName;

        if (is_file($fileName)) {
            $fileTime = filemtime($fileName) + config::$fileTimeCorrection;
        } else {
            $fileTime = 0;
        }

        return
            ($fileTime <= (int)$page['headerUpdated']
                || $fileTime <= (int)$page['footerUpdated']
                || $fileTime <= (int)$page['contentUpdated']
                || $fileTime <= (int)$page['updated']);

    }

    public function tmpDirClean()
    {
        if (is_dir(config::basePath() . config::$tmpDir)) {

            $it = new RecursiveDirectoryIterator(config::basePath() . config::$tmpDir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator(
                $it,
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

        }

        return true;
    }
}

$auth = new localAuth();

if ($auth()) {

    $act = isset($_GET['act']) ? $_GET['act'] : '';?>
    <!DOCTYPE html>
    <div class="menu">
        <a href="?act=check-update">Check for updates</a> |
        <a href="?act=get-update">Get updates</a>
        <a style="float: right" href="?act=log-out">Log out</a>
    </div>
    <?php
    echo "<div class='code'>";
    switch ($act) {
        case 'log-out':
            unset($_SESSION);
            session_destroy();
            echo "<script type='text/javascript'> location.href='/__update.php' </script>";
            break;
        case 'check-update':

            $remote     = new remote(config::$remote['url'], config::$remote['user_id'], config::$remote['feed_id']);
            $remoteAuth = $remote->auth();

            if ($remoteAuth) {
                $list = $remote->getList();
                $existFileList = array();
                echo '<table class="report">';
                echo '<tr><td colspan="2" class="comment">Checking for updates</td></tr>';
                echo '<tr class="head"><td>Path</td><td>State</td></tr>';
                foreach ($list as $page) {
                    $existFileList[] = $page['fname'];

                    echo '<tr><td class="path">' . $page['fname'] . '</td><td>';

                    if ($remote->fileOutdated($page['fname'], $page)) {
                        echo '<span class="outdated">Outdated</span>';
                    } else {
                        echo '<span class="ok">OK</span>';
                    }

                    echo "</td></tr>";
                }
                /* check deleted files */
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(config::basePath()));
                while ($it->valid()) {
                    if (!$it->isDot() && !$it->isDir()) {
                        $file = $it->getSubPathName();
                        $fileV2 = strpos($file, '/') > 0 ? "/{$file}" : $file;
                        if (!in_array($file, $existFileList) && !in_array($fileV2, $existFileList) && !in_array($file, config::$filesIgnore)) {
                            $iDir = explode(DIRECTORY_SEPARATOR, $file);
                            $iDir = isset($iDir[1]) ? $iDir[1] : '';
                            if (!is_dir(config::basePath() . $iDir) || !in_array($iDir, config::$dirsIgnore)) {
                                $dir = dirname($file);
                                if (!in_array($dir, config::$dirsIgnore)) {
                                    echo '<tr><td class="path">' . $file . '</td><td><span class="outdated">Deleted</span></td></tr>';
                                }
                            }
                        }
                    }
                    $it->next();
                }
                /* check deleted files */
                echo '</table>';
            } else {
                echo "<span class='error'>Auth: error.</span> (<span class='comment'>" . implode(', ', $remote->auth_error) . "</span>)<br>";
            }

            break;
        case 'get-update':
            $remote     = new remote(config::$remote['url'], config::$remote['user_id'], config::$remote['feed_id']);
            $remoteAuth = $remote->auth();
            if ($remoteAuth) {
                $list     = $remote->getList();
                $existFileList = array();
                $filesAll = 0;
                $filesGot = 0;
                $remote->tmpDirClean();

                echo '<table class="report">';
                echo '<tr><td colspan="3" class="comment">Downloading files</td></tr>';
                echo '<tr class="head"><td colspan="2">Path</td><td>State</td></tr>';
                foreach ($list as $page) {
                    $existFileList[] = $page['fname'];
                    if ($remote->fileOutdated($page['fname'], $page)) {
                        $filesAll++;
                        $res = $remote->getFile($page['fname']);
                        if ($res) {
                            $filesGot++;
                        }
                        echo '<tr><td class="path">' . $page['fname'] . '</td><td class="action">download</td><td class="'. ($res ? 'ok' : 'error') .'">' . ($res ? 'updated' : 'error') . '</td></tr>';
                    } else {
                        echo '<tr><td class="path">' . $page['fname'] . '</td><td class="action"></td><td class="blue">up-to-date</td></tr>';
                    }
                    flush();
                }

                if ($filesAll == $filesGot) {
                    if ($filesAll > 0) {
                        echo '<tr><td colspan="3" class="comment">Moving files</td></tr>';
                        echo '<tr class="head"><td>Path</td><td>State</td></tr>';
                    }
                    /*move from tmp*/
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(config::basePath() . config::$tmpDir . DIRECTORY_SEPARATOR));
                    $moved = array();
                    while ($it->valid()) {
                        if (!$it->isDot() && !$it->isDir()) {
                            $file = $it->getSubPathName();
                            if (in_array($file, $moved)) {
                                $it->next();
                                continue;
                            }
                            $moved[] = $file;
                            $dir = dirname($file);
                            if (!is_dir(config::basePath() . $dir)) {
                                mkdir(config::basePath() . $dir, 0755, true);
                            }
                            $ok = rename(config::basePath() . config::$tmpDir . DIRECTORY_SEPARATOR . $file, config::basePath() . $file);
                            echo '<tr><td class="path">' . $file . '</td><td>moving</td><td class="' . ($ok ? 'ok' : 'error') . '">' . ($ok ? 'OK' : 'error') . '</td></tr>';
                        }
                        $it->next();
                    }

                    /*dir cleanup*/
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(config::basePath()));
                    $deletingFiles = [];
                    while ($it->valid()) {
                        if (!$it->isDot() && !$it->isDir()) {
                            $file = $it->getSubPathName();
                            if (strpos($file, '/') > 0) {
                                $fileV2 = '/' . $file;
                            } else {
                                $fileV2 = $file;
                            }
                            if (!in_array($file, $existFileList)
                                && !in_array($fileV2, $existFileList)
                                && !in_array($file, config::$filesIgnore)) {

                                $iDir = explode(DIRECTORY_SEPARATOR, $file);
                                $iDir = isset($iDir[1]) ? $iDir[1] : '';
                                if (is_dir(config::basePath() . $iDir) && in_array($iDir, config::$dirsIgnore)) {

                                } else {
                                    $dir = dirname($file);
                                    if (!in_array($dir, config::$dirsIgnore)) {
                                        $deletingFiles[$file] = unlink(config::basePath() . $file);
                                        if (count(scandir('.' . DIRECTORY_SEPARATOR . $dir)) == 2) {
                                            rmdir(config::basePath() . $dir);
                                        }
                                        $dir = explode(DIRECTORY_SEPARATOR, $dir);
                                        $dir = $dir[1];
                                        if (is_dir('.' . DIRECTORY_SEPARATOR . $dir) && count(scandir('.' . DIRECTORY_SEPARATOR . $dir)) == 2) {
                                            rmdir(config::basePath() . $dir);
                                        }
                                    }
                                }
                            }
                        }
                        $it->next();
                    }
                    if($deletingFiles){
                        echo '<tr><td colspan="3" class="comment">Deleting files</td></tr>';
                        foreach($deletingFiles as $file=>$ok){
                            echo '<tr><td class="path">' . $file . '</td><td>deleting</td><td class="' . ($ok ? 'ok' : 'error') . '">' . ($ok ? 'OK' : 'error') . '</td></tr>';
                        }
                    }

                }
                echo '</table>';

            } else {
                echo implode(',', $remote->auth_error);
            }


            break;
        default:
            break;
    }
    ?>
    </div>
    <style>
        body {
            margin: 0;
            background: rgb(115, 133, 155);
            color: white;
        }
        .code {
            border:1px dotted #bebebe;
            padding: 5px;
            font-family: monospace;
            background: #ffffff;
            margin: 5px;
            color: #000000;
        }
        .head {
            color: #0000ff;
            font-weight: bold;
        }
        .menu {
            margin-bottom: 5px;
            background: rgba(0,0,0,.75);
            height: 50px;
            padding-top: 20px;
            padding-bottom: 8px;
        }
        .outdated {
            color: red;
        }
        .ok {
            color: green;
        }
        .path {
            color: black;
            font-weight: bold;
        }
        .action {
            font-style: italic;
        }
        .error {
            color: #d83b00;
        }
        .blue {
            color: blue;
        }
        .comment {
            font-style: italic;
            color: #787878;
        }
        .menu a {
            font-family: "Courier New", Courier, monospace;
            color: white;
            padding: 3px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            vertical-align: middle;
            font-size: 20px;
            position: relative;
        }
        .menu a:hover:after {
            content: '';
            position: absolute;
            left: 0; top: 0;
            width: 100%;
            height: 100%;
            border-bottom: 1px solid white;
        }
    </style>
<?php
} else {
    if (!empty($_SERVER['QUERY_STRING'])) {
        echo "<script> location.href='/__update.php'</script>";
        die();
    }
    ?>
    <style>
        body {
            margin: 0;
            background: rgb(220, 215, 209);
            color: #222;
        }
        form {
            width: 300px;
            position: absolute;
            left: 50%;
            margin-left: -150px;
            top: 40px;
            background: #ffffff;
            border: 1px solid #868686;
            color: white;
            font-family: monospace;
            padding: 20px;
            text-align: center;
            box-shadow: 0px 0 6px 3px #83838E;
        }
        form table {
            font-size: 20px;
            width: 100%;
        }
        form input[type=submit]:hover {
            background: #ffffff;
            color: #222;
        }
        form input[type=submit] {
            border: 1px solid black;
            cursor: pointer;
            background: black;
            color: #ffffff;
            font-size: 16px;
            padding-left: 20px;
            padding-right: 20px;
            padding-top: 3px;
            padding-bottom: 1px;
            font-weight: bold;
            margin-top: 10px;
            transition: all 0.3s linear;
            -webkit-transition: all 0.3s linear;
            -moz-transition: all 0.3s linear;
            -o-transition: all 0.3s linear;
        }
        form input[type=text], form input[type=password] {
            width: 100%;
            border: 1px solid black;
            color: #222;
            padding: 5px;
        }
    </style>
    <form method="post">
        <table>
            <tr>
                <td>
                    Login:
                </td>
                <td>
                    <input name="login" class="auth" type="text"/>
                </td>
            </tr>
            <tr>
                <td>
                    Password:
                </td>
                <td>
                    <input name="password" class="auth" type="password"/>
                </td>
            </tr>
            <tr><td colspan="2" align="center"><input type="submit" value="OK"/></td></tr>
        </table>
        <div style="position:absolute; right: 2px; bottom: 0px;">v1.0</div>
    </form>
<?php }