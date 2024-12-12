<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\Filesystem\Exception\FileAlreadyExistsException;
use ILIAS\FileUpload\Location;
use ILIAS\Filesystem\Exception\DirectoryNotFoundException;
use ILIAS\Filesystem\Exception\FileNotFoundException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Util\LegacyPathHelper;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\Data\DataSize;

/**
 * Class ilFileUtils
 *
 * @deprecated All Methods are widely used and there is currently no other service
 *             providing all of them, but please do not implement new methods in
 *             this class.
 */
class ilFileUtils
{
    /**
     * Recursively scans a given directory and writes path and filename into referenced array
     *
     * @param string $dir Directory to start from
     * @param array &$arr Referenced array which is filled with Filename and path
     *
     * @throws ilFileUtilsException
     * @deprecated Will be removed completely with ILIAS 9
     */
    public static function recursive_dirscan(string $dir, array &$arr): void
    {
        global $DIC;

        $lng = $DIC->language();

        $dirlist = opendir($dir);
        while (false !== ($file = readdir($dirlist))) {
            if (!is_file($dir . "/" . $file) && !is_dir($dir . "/" . $file)) {
                throw new ilFileUtilsException(
                    $lng->txt("filenames_not_supported"),
                    ilFileUtilsException::$BROKEN_FILE
                );
            }

            if ($file !== '.' && $file !== '..') {
                $newpath = $dir . '/' . $file;
                $level = explode('/', $newpath);
                if (is_dir($newpath)) {
                    ilFileUtils::recursive_dirscan($newpath, $arr);
                } else {
                    $arr["path"][] = $dir . "/";
                    $arr["file"][] = end($level);
                }
            }
        }
        closedir($dirlist);
    }

    /**
     * @deprecated in ILIAS 9 for ILIAS 10: Use Refinery\String\Encoding instead
     */
    public static function utf8_encode(string $string): string
    {
        global $DIC;
        return $DIC->refinery()->string()->encoding()->latin1ToUtf8()->transform($string);
    }

    /**
     * @deprecated
     */
    public static function getValidFilename(string $a_filename): string
    {
        global $DIC;
        $sanitizer = new ilFileServicesFilenameSanitizer(
            $DIC->fileServiceSettings()
        );

        return $sanitizer->sanitize($a_filename);
    }

    /**
     * @deprecated
     */
    public static function rename(string $a_source, string $a_target): bool
    {
        $pi = pathinfo($a_target);
        global $DIC;
        $sanitizer = new ilFileServicesFilenameSanitizer(
            $DIC->fileServiceSettings()
        );

        if (!$sanitizer->isClean($a_target)) {
            throw new ilFileUtilsException("Invalid target file");
        }

        return rename($a_source, $a_target);
    }

    /**
     * Copies content of a directory $a_sdir recursively to a directory $a_tdir
     *
     * @param string  $a_sdir                 source directory
     * @param string  $a_tdir                 target directory
     * @param boolean $preserveTimeAttributes if true, ctime will be kept.
     *
     * @return    boolean    TRUE for sucess, FALSE otherwise
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws IOException
     * @access     public
     * @static
     *
     * @deprecated in favour of Filesystem::copyDir() located at the filesystem service.
     * @see        Filesystem::copyDir()
     */
    public static function rCopy(
        string $a_sdir,
        string $a_tdir,
        bool $preserveTimeAttributes = false
    ): bool {
        $sourceFS = LegacyPathHelper::deriveFilesystemFrom($a_sdir);
        $targetFS = LegacyPathHelper::deriveFilesystemFrom($a_tdir);

        $sourceDir = LegacyPathHelper::createRelativePath($a_sdir);
        $targetDir = LegacyPathHelper::createRelativePath($a_tdir);

        // check if arguments are directories
        if (!$sourceFS->hasDir($sourceDir)) {
            return false;
        }

        $sourceList = $sourceFS->listContents($sourceDir, true);

        foreach ($sourceList as $item) {
            if ($item->isDir()) {
                continue;
            }
            try {
                $itemPath = $targetDir . '/' . substr(
                    $item->getPath(),
                    strlen($sourceDir)
                );
                $stream = $sourceFS->readStream($item->getPath());
                $targetFS->writeStream($itemPath, $stream);
            } catch (FileAlreadyExistsException) {
                // Do nothing with that type of exception
            }
        }

        return true;
    }

    /**
     * Create a new directory and all parent directories
     *
     * Creates a new directory and inherits all filesystem permissions of the parent directory
     * If the parent directories doesn't exist, they will be created recursively.
     * The directory name NEEDS TO BE an absolute path, because it seems that relative paths
     * are not working with PHP's file_exists function.
     *
     * @param string $a_dir The directory name to be created
     * @access     public
     * @static
     *
     * @return bool
     *
     * @author     Helmut Schottmüller <hschottm@tzi.de>
     * @deprecated in favour of Filesystem::createDir() located at the filesystem service.
     *
     * @see        \ILIAS\Filesystem\Filesystem::createDir()
     */
    public static function makeDirParents(string $a_dir): bool
    {
        $dirs = [$a_dir];
        $a_dir = dirname($a_dir);
        $last_dirname = '';
        while ($last_dirname !== $a_dir) {
            array_unshift($dirs, $a_dir);
            $last_dirname = $a_dir;
            $a_dir = dirname($a_dir);
        }

        // find the first existing dir
        $reverse_paths = array_reverse($dirs, true);
        $found_index = -1;
        foreach ($reverse_paths as $key => $value) {
            if ($found_index != -1) {
                continue;
            }
            if (!is_dir($value)) {
                continue;
            }
            $found_index = $key;
        }

        $old_mask = umask(0000);
        foreach ($dirs as $dirindex => $dir) {
            // starting with the longest existing path
            if ($dirindex >= $found_index) {
                if (!file_exists($dir)) {
                    if (strcmp(substr($dir, strlen($dir) - 1, 1), "/") == 0) {
                        // on some systems there is an error when there is a slash
                        // at the end of a directory in mkdir, see Mantis #2554
                        $dir = substr($dir, 0, strlen($dir) - 1);
                    }
                    if (!mkdir($dir)) {
                        error_log("Can't make directory: $dir");
                        return false;
                    }
                } elseif (!is_dir($dir)) {
                    error_log("$dir is not a directory");
                    return false;
                } else {
                    // get umask of the last existing parent directory
                    $umask = fileperms($dir);
                }
            }
        }
        umask($old_mask);

        return true;
    }

    /**
     * get data directory (outside webspace)
     *
     * @static
     *
     * @deprecated in favour of the filesystem service which should be used to operate on the storage directory.
     *
     * @see        \ILIAS\DI\Container::filesystem()
     * @see        \ILIAS\Filesystem\Filesystems::storage()
     */
    public static function getDataDir(): string
    {
        return CLIENT_DATA_DIR;
    }

    /**
     * get size of a directory or a file.
     *
     * @param string path to a directory or a file
     * @return integer. Returns -1, if the directory does not exist.
     * @static
     *
     */
    public static function dirsize(string $directory): int
    {
        $size = 0;
        if (!is_dir($directory)) {
            //       dirsize of non-existing directory
            $size = @filesize($directory);
            return ($size === false) ? -1 : $size;
        }
        if ($DIR = opendir($directory)) {
            while (($dirfile = readdir($DIR)) !== false) {
                if (is_link(
                    $directory . DIRECTORY_SEPARATOR . $dirfile
                )) {
                    continue;
                }
                if ($dirfile === '.') {
                    continue;
                }
                if ($dirfile === '..') {
                    continue;
                }
                if (is_file($directory . DIRECTORY_SEPARATOR . $dirfile)) {
                    $size += filesize(
                        $directory . DIRECTORY_SEPARATOR . $dirfile
                    );
                } elseif (is_dir($directory . DIRECTORY_SEPARATOR . $dirfile)) {
                    $dirSize = ilFileUtils::dirsize(
                        $directory . DIRECTORY_SEPARATOR . $dirfile
                    );
                    if ($dirSize >= 0) {
                        $size += $dirSize;
                    } else {
                        return -1;
                    }
                }
            }
            closedir($DIR);
        }
        return $size;
    }

    /**
     * creates a new directory and inherits all filesystem permissions of the parent directory
     * You may pass only the name of your new directory or with the entire path or relative path information.
     *
     * examples:
     * a_dir = /tmp/tests/your_dir
     * a_dir = ../tests/your_dir
     * a_dir = your_dir (--> creates your_dir in current directory)
     *
     * @access     public
     * @param string    [path] + directory name
     * @return    boolean
     * @static
     *
     * @deprecated in favour of Filesystem::createDir() located at the filesystem service.
     *
     * @see        \ILIAS\Filesystem\Filesystem::createDir()
     */
    public static function makeDir(string $a_dir): bool
    {
        $a_dir = trim($a_dir);

        // remove trailing slash (bugfix for php 4.2.x)
        if (str_ends_with($a_dir, "/")) {
            $a_dir = substr($a_dir, 0, -1);
        }

        // check if a_dir comes with a path
        if (($path = substr(
            $a_dir,
            0,
            strrpos($a_dir, "/") - strlen($a_dir)
        )) === '' || ($path = substr(
            $a_dir,
            0,
            strrpos($a_dir, "/") - strlen($a_dir)
        )) === '0') {
            $path = ".";
        }

        // create directory with file permissions of parent directory
        if (is_dir($a_dir)) {
            return true;
        }
        $old_mask = umask(0000);
        $result = @mkdir($a_dir, fileperms($path));
        umask($old_mask);

        return $result;
    }

    protected static function sanitateTargetPath(string $a_target): array
    {
        $target_file_system = match (true) {
            str_starts_with($a_target, 'public/' . ILIAS_WEB_DIR . '/' . CLIENT_ID),
            str_starts_with($a_target, './public/' . ILIAS_WEB_DIR . '/' . CLIENT_ID),
            str_starts_with($a_target, '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID),
            str_starts_with($a_target, './' . ILIAS_WEB_DIR . '/' . CLIENT_ID),
            str_starts_with($a_target, CLIENT_WEB_DIR) => Location::WEB,

            str_starts_with($a_target, CLIENT_DATA_DIR . "/temp") => Location::TEMPORARY,
            str_starts_with($a_target, CLIENT_DATA_DIR) => Location::STORAGE,

            str_starts_with($a_target, ILIAS_ABSOLUTE_PATH . '/Customizing') => Location::CUSTOMIZING,
            default => throw new InvalidArgumentException(
                "Can not move files to \"$a_target\" because path can not be mapped to web, storage or customizing location."
            ),
        };

        $absolute_target_dir = dirname($a_target);
        $target_dir = LegacyPathHelper::createRelativePath($absolute_target_dir);

        return [$target_file_system, $target_dir];
    }

    /**
     * move uploaded file
     *
     * @static
     *
     * @param string $a_file
     * @param string $a_name
     * @param string $a_target
     * @param bool   $a_raise_errors
     * @param string $a_mode
     *
     * @return bool
     *
     * @throws ilException Thrown if no uploaded files are found and raise error is set to true.
     *
     * @deprecated in favour of the FileUpload service.
     *
     * @see        \ILIAS\DI\Container::upload()
     */
    public static function moveUploadedFile(
        string $a_file,
        string $a_name,
        string $a_target,
        bool $a_raise_errors = true,
        string $a_mode = "move_uploaded"
    ): bool {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        $target_filename = basename($a_target);

        $target_filename = ilFileUtils::getValidFilename($target_filename);

        // Make sure the target is in a valid subfolder. (e.g. no uploads to ilias/setup_/....)
        [$target_filesystem, $target_dir] = self::sanitateTargetPath($a_target);

        $upload = $DIC->upload();

        // If the upload has not yet been processed make sure he gets processed now.
        if (!$upload->hasBeenProcessed()) {
            $upload->process();
        }

        try {
            if (!$upload->hasUploads()) {
                throw new ilException(
                    $DIC->language()->txt("upload_error_file_not_found")
                );
            }
            $upload_result = $upload->getResults()[$a_file] ?? null;
            if ($upload_result instanceof UploadResult) {
                if (!$upload_result->isOK()) {
                    throw new ilException($upload_result->getStatus()->getMessage());
                }
            } else {
                return false;
            }
        } catch (ilException $e) {
            if (!$a_raise_errors) {
                $main_tpl->setOnScreenMessage('failure', $e->getMessage(), true);
            } else {
                throw $e;
            }

            return false;
        }

        $upload->moveOneFileTo(
            $upload_result,
            $target_dir,
            $target_filesystem,
            $target_filename,
            true
        );

        return true;
    }

    /**
     * @deprecated Please refactor your code using $DIC->archives()->zip() (recommended) or $DIC->legacyArchives()->zip() instead.
     */
    public static function zip(
        string $a_dir,
        string $a_file,
        bool $compress_content = false
    ): bool {
        global $DIC;
        // ensure top directory should be the same behaviour as before, if you need it to be different, you should legacyArchives directly
        return $DIC->legacyArchives()->zip($a_dir, $a_file, true);
    }

    /**
     * removes a dir and all its content (subdirs and files) recursively
     *
     * @access     public
     *
     * @param string $a_dir dir to delete
     * @param bool   $a_clean_only
     *
     * @author     Unknown <flexer@cutephp.com> (source: http://www.php.net/rmdir)
     * @static
     *
     * @deprecated in favour of Filesystem::deleteDir() located at the filesystem service.
     *
     * @see        \ILIAS\Filesystem\Filesystem::deleteDir()
     */
    public static function delDir(string $a_dir, bool $a_clean_only = false): void
    {
        if (!is_dir($a_dir) || is_int(strpos($a_dir, ".."))) {
            return;
        }

        $current_dir = opendir($a_dir);

        $files = [];

        // this extra loop has been necessary because of a strange bug
        // at least on MacOS X. A looped readdir() didn't work
        // correctly with larger directories
        // when an unlink happened inside the loop. Getting all files
        // into the memory first solved the problem.
        while ($entryname = readdir($current_dir)) {
            $files[] = $entryname;
        }

        foreach ($files as $file) {
            if (is_dir(
                $a_dir . "/" . $file
            ) && ($file !== "." && $file !== "..")) {
                ilFileUtils::delDir($a_dir . "/" . $file);
            } elseif ($file !== "." && $file !== "..") {
                unlink($a_dir . "/" . $file);
            }
        }

        closedir($current_dir);
        if (!$a_clean_only) {
            @rmdir($a_dir);
        }
    }

    public static function getSafeFilename(string $a_initial_filename): string
    {
        $file_peaces = explode('.', $a_initial_filename);

        $file_extension = array_pop($file_peaces);

        if (SUFFIX_REPL_ADDITIONAL) {
            $string_extensions = SUFFIX_REPL_DEFAULT . "," . SUFFIX_REPL_ADDITIONAL;
        } else {
            $string_extensions = SUFFIX_REPL_DEFAULT;
        }

        $sufixes = explode(",", $string_extensions);

        if (in_array($file_extension, $sufixes)) {
            $file_extension = "sec";
        }

        $file_peaces[] = $file_extension;

        $safe_filename = "";
        foreach ($file_peaces as $piece) {
            $safe_filename .= "$piece";
            if ($piece != end($file_peaces)) {
                $safe_filename .= ".";
            }
        }

        return $safe_filename;
    }

    /**
     * get directory
     *
     * @static
     *
     * @param string      $a_dir
     * @param bool        $a_rec
     * @param string|null $a_sub_dir
     *
     * @return array
     *
     * @deprecated in favour of Filesystem::listContents() located at the filesystem service.
     *
     * @see        \ILIAS\Filesystem\Filesystem::listContents()
     */
    public static function getDir(
        string $a_dir,
        bool $a_rec = false,
        ?string $a_sub_dir = ""
    ): array {
        $current_dir = opendir($a_dir . $a_sub_dir);

        $dirs = [];
        $files = [];
        $subitems = [];
        while ($entry = readdir($current_dir)) {
            if (is_dir($a_dir . "/" . $entry)) {
                $dirs[$entry] = [
                    "type" => "dir",
                    "entry" => $entry,
                    "subdir" => $a_sub_dir
                ];
                if ($a_rec && $entry !== "." && $entry !== "..") {
                    $si = ilFileUtils::getDir(
                        $a_dir,
                        true,
                        $a_sub_dir . "/" . $entry
                    );
                    $subitems = array_merge($subitems, $si);
                }
            } elseif ($entry !== "." && $entry !== "..") {
                $size = filesize($a_dir . $a_sub_dir . "/" . $entry);
                $files[$entry] = [
                    "type" => "file",
                    "entry" => $entry,
                    "size" => $size,
                    "subdir" => $a_sub_dir
                ];
            }
        }
        ksort($dirs);
        ksort($files);

        return array_merge($dirs, $files, $subitems);
    }

    /**
     * get webspace directory
     *
     * @param string $mode                use "filesystem" for filesystem operations
     *                                    and "output" for output operations, e.g. images
     *
     * @static
     *
     * @return string
     *
     * @deprecated in favour of the filesystem service which should be used for operations on the web dir.
     *
     * @see        \ILIAS\DI\Container::filesystem()
     * @see        Filesystems::web()
     */
    public static function getWebspaceDir(string $mode = "filesystem"): string
    {
        if ($mode === "filesystem") {
            return "./" . ILIAS_WEB_DIR . "/" . CLIENT_ID;
        }
        if (defined("ILIAS_MODULE")) {
            return "../" . ILIAS_WEB_DIR . "/" . CLIENT_ID;
        }
        return "./" . ILIAS_WEB_DIR . "/" . CLIENT_ID;
    }

    /**
     * create directory
     *
     * @param string $a_dir
     * @param int    $a_mod
     *
     * @static
     *
     * @deprecated in favour of Filesystem::createDir() located at the filesystem service.
     *
     * @see        \ILIAS\Filesystem\Filesystem::createDir()
     */
    public static function createDirectory(string $a_dir, int $a_mod = 0755): void
    {
        ilFileUtils::makeDir($a_dir);
    }

    public static function getFileSizeInfo(): string
    {
        global $DIC;
        $size = new DataSize(self::getPhpUploadSizeLimitInBytes(), DataSize::MB);
        $max_filesize = $size->__toString();
        $lng = $DIC->language();

        return $lng->txt("file_notice") . " $max_filesize.";
    }

    /**
     * @deprecated
     */
    public static function getASCIIFilename(string $a_filename): string
    {
        global $DIC;
        $policy = new ilFileServicesPolicy($DIC->fileServiceSettings());
        return $policy->ascii($a_filename);
    }

    /**
     * Returns a unique and non existing Path for e temporary file or directory
     *
     * @param string|null $a_temp_path
     *
     * @return    string
     */
    public static function ilTempnam(?string $a_temp_path = null): string
    {
        $temp_path = $a_temp_path ?? ilFileUtils::getDataDir() . "/temp";

        if (!is_dir($temp_path)) {
            ilFileUtils::createDirectory($temp_path);
        }
        $temp_name = $temp_path . "/" . uniqid("tmp");

        return $temp_name;
    }

    /**
     * unzip file
     *
     * @param string  $a_file    full path/filename
     * @param boolean $overwrite pass true to overwrite existing files
     * @static
     *
     */
    public static function unzip(string $a_file, bool $overwrite = false, bool $a_flat = false): bool
    {
        if (defined('DEVMODE') && DEVMODE) {
            trigger_error('Deprecated method called: ' . __METHOD__, E_USER_DEPRECATED);
        }

        global $DIC;
        return $DIC->legacyArchives()->unzip(
            $a_file,
            null,
            $overwrite,
            $a_flat,
            false
        );
    }

    /**
     * @deprecated
     */
    public static function renameExecutables(string $a_dir): void
    {
        $def_arr = explode(",", SUFFIX_REPL_DEFAULT);
        foreach ($def_arr as $def) {
            self::rRenameSuffix($a_dir, trim($def), "sec");
        }

        $def_arr = explode(",", SUFFIX_REPL_ADDITIONAL);
        foreach ($def_arr as $def) {
            self::rRenameSuffix($a_dir, trim($def), "sec");
        }
    }

    /**
     * Renames all files with certain suffix and gives them a new suffix.
     * This words recursively through a directory.
     *
     * @deprecated
     */
    public static function rRenameSuffix(string $a_dir, string $a_old_suffix, string $a_new_suffix): bool
    {
        if ($a_dir === "/" || $a_dir === "" || is_int(strpos($a_dir, ".."))
            || trim($a_old_suffix) === "") {
            return false;
        }

        // check if argument is directory
        if (!@is_dir($a_dir)) {
            return false;
        }

        // read a_dir
        $dir = opendir($a_dir);

        while ($file = readdir($dir)) {
            if ($file !== "." && $file !== "..") {
                // triple dot is not allowed in filenames
                if ($file === '...') {
                    unlink($a_dir . "/" . $file);
                    continue;
                }
                // directories
                if (@is_dir($a_dir . "/" . $file)) {
                    ilFileUtils::rRenameSuffix($a_dir . "/" . $file, $a_old_suffix, $a_new_suffix);
                }

                // files
                if (@is_file($a_dir . "/" . $file)) {
                    // first check for files with trailing dot
                    if (strrpos($file, '.') == (strlen($file) - 1)) {
                        try {
                            rename($a_dir . '/' . $file, substr($a_dir . '/' . $file, 0, -1));
                        } catch (Throwable) {
                            // to avoid exploits we do delete this file and continue renaming
                            unlink($a_dir . '/' . $file);
                            continue;
                        }
                        $file = substr($file, 0, -1);
                    }

                    $path_info = pathinfo($a_dir . "/" . $file);

                    if (strtolower($path_info["extension"] ?? '') === strtolower($a_old_suffix)) {
                        $pos = strrpos($a_dir . "/" . $file, ".");
                        $new_name = substr($a_dir . "/" . $file, 0, $pos) . "." . $a_new_suffix;
                        // check if file exists
                        if (file_exists($new_name)) {
                            if (is_dir($new_name)) {
                                ilFileUtils::delDir($new_name);
                            } else {
                                unlink($new_name);
                            }
                        }
                        rename($a_dir . "/" . $file, $new_name);
                    }
                }
            }
        }
        return true;
    }

    public static function removeTrailingPathSeparators(string $path): string
    {
        $path = preg_replace("/[\/\\\]+$/", "", $path);
        return (string) $path;
    }

    /**
     * @deprecated should use DataSize instead
     */
    public static function getPhpUploadSizeLimitInBytes(): string
    {
        $convertPhpIniSizeValueToBytes = function ($phpIniSizeValue) {
            if (is_numeric($phpIniSizeValue)) {
                return $phpIniSizeValue;
            }

            $suffix = substr($phpIniSizeValue, -1);
            $value = substr($phpIniSizeValue, 0, -1);

            switch (strtoupper($suffix)) {
                case 'P':
                    $value *= 1024;
                    // no break
                case 'T':
                    $value *= 1024;
                    // no break
                case 'G':
                    $value *= 1024;
                    // no break
                case 'M':
                    $value *= 1024;
                    // no break
                case 'K':
                    $value *= 1024;
                    break;
            }

            return $value;
        };

        $uploadSizeLimitBytes = min(
            $convertPhpIniSizeValueToBytes(ini_get('post_max_size')),
            $convertPhpIniSizeValueToBytes(ini_get('upload_max_filesize'))
        );

        return $uploadSizeLimitBytes;
    }

    public static function _sanitizeFilemame(string $a_filename): string
    {
        return strip_tags(ilUtil::stripSlashes($a_filename));
    }
}
