<?php
declare(strict_types=1);
/**
 * iceCMS2 v0.1a
 * Created by Sergey Peshalov https://github.com/desfpc
 * https://github.com/desfpc/iceCMS2
 *
 * File entity class
 */

namespace iceCMS2\Models;

use iceCMS2\Tools\Exception;

class File extends AbstractEntity
{
    /** @var string Entity DB table name */
    protected string $_dbtable = 'files';

    /** @var string File Type (enum: file, image, document) */
    protected string $_filetype = 'file';

    /**
     * Getting file extension
     *
     * @param string $filename
     * @return string
     */
    public static function getFileExtension(string $filename): string
    {
        $path_info = pathinfo($filename);
        if (!isset($path_info['extension'])) {
            return '';
        }
        return $path_info['extension'];
    }

    /**
     * Check POST file for class specification
     *
     * @param array $file
     * @return bool
     */
    protected function _checkFileType(array $file): bool
    {
        return true;
    }

    /**
     * Function before delete
     *
     * @return bool
     */
    protected function _beforeDel()
    {
        
        return true;
    }

    /**
     * Set Entity from POST value
     *
     * @param string $paramName
     * @param int|null $userId
     * @param bool|null $private
     * @return bool
     * @throws Exception
     */
    public function savePostFile(string $paramName, ?int $userId = null, bool $private = false): bool
    {
        if ($paramName == '' || empty($_FILES[$paramName])) {
            return false;
        }

        $file = $_FILES[$paramName];

        $tmp_name = $file["tmp_name"];

        //Setting entity params from File
        $this->_setByKeyAndValue('name', $file['name'], false);
        $this->_setByKeyAndValue('filename', $file['name'], false);
        $this->_setByKeyAndValue('extension', File::getFileExtension($file['name']), false);
        $this->_setByKeyAndValue('size', (int)$file['size'], false);
        $this->_setByKeyAndValue('filetype', $this->_filetype, false);
        $this->_setByKeyAndValue('private', (int)$private, false);
        $this->_setByKeyAndValue('created_time', time(), false);

        if (!is_null($userId)) {
            $this->_setByKeyAndValue('user_id', $userId, false);
        }

        //Setting entity params from POST values
        $this->set($_POST, null, true);
        
        if (!$this->_checkFileType($file)) {
            throw new Exception('Transferred file have incorrect type');
        }

        //Creating a file record in DB
        if (!$this->save()) {
            throw new Exception('Error in saving File Entity');
        }

        //Creating a server route for storing file
        $fileVsPath = $this->_createPath($private) . $this->_id;
        if (!empty($this->_values['extension'])) {
            $fileVsPath .= '.' . $this->_values['extension'];
        }

        //Store file on server
        if (!move_uploaded_file($tmp_name, $fileVsPath)) {
            $this->del();
            throw new Exception('Error in saving File on server');
        }

        //Updating file Entity URL
        $this->isLoaded = true;
        $this->_setByKeyAndValue('url', $this->getUrl());
        if (!$this->save()) {
            $this->del();
            throw new Exception('Error in saving Entity');
        }

        return true;
    }

    /**
     * Getting favicon image url
     *
     * @return string
     */
    public function getFaviconUrl(): string
    {

    }

    /**
     * Getting file path in OS
     *
     * @return string
     */
    public function getPath(): string
    {
        
    }

    /**
     * Getting file URL for web
     *
     * @return string
     */
    public function getUrl(): string
    {
        if (!$this->isLoaded) {
            throw new Exception('File is not loaded! Load file entry from DB first.');
        }

        $url = $this->_getUrlDirectory(
            $this->_values['private'],
            date('Ym', $this->_values['created_time'])
        );

        $url .= $this->_id;

        if (!empty($this->_values['extension'])) {
            $url .= '.' . $this->_values['extension'];
        }

        return $url;
    }

    /**
     * Getting directory for URL and Path
     *
     * @param bool $private
     * @param string|null $date
     * @return string
     */
    private function _getUrlDirectory(bool $private = false, ?string $date = null): string
    {
        $url = '/files/';
        if ($private) {
            $url .= 'private/';
        }

        if (is_null($date)) {
            $date = date('Ym');
        }

        $url .= $date . '/';

        return $url;
    }

    /**
     *  Create file patch directory
     *
     * @param bool $private
     * @return string
     */
    private function _createPath(bool $private = false): string
    {
        $url = $this->_getUrlDirectory($private);
        $dirpatch = $this->_settings->path . '/web' . $url;

        if (!is_dir($dirpatch)) {
            if (!$private) {
                mkdir($dirpatch, 0750);
            } else {
                mkdir($dirpatch, 0640);
            }
        }

        return $url;
    }
}