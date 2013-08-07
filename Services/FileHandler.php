<?php
/**
 * Created by JetBrains PhpStorm.
 * User: volt
 * Date: 01.06.13
 * Time: 12:01
 * To change this template use File | Settings | File Templates.
 */

namespace Voltash\UploadBundle\Services;

use Voltash\UploadBundle\Services\ImageLib;

class FileHandler
{
    protected $session;
    protected $config;
    protected $sessionAttr;

    public function __construct(\Symfony\Component\HttpFoundation\Session\SessionInterface $session, $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    public function handleFilesAndSave($field, $dir, $json = false)
    {
        $this->sessionAttr = 'file_upload_'.$field;
        if ($this->session->has($this->sessionAttr))
        {
            $filesInfo = new \SplObjectStorage();
            $filesInfo->unserialize($this->session->get($this->sessionAttr));

            if ($this->config[$filesInfo->type]['type'] == 'file')
                $result = $this->saveFiles($filesInfo, $dir);
            elseif ($this->config[$filesInfo->type]['type'] == 'image')
                $result = $this->saveImages($filesInfo, $dir, $this->config[$filesInfo->type]['thumbnails']);
            else
                throw new \Exception('Unrecognized file type!');

            $this->clearSessionAttr();

            if ($json)
                $result = json_encode($result);

            return $result;
        }
        else
        {
            return false;
        }
    }

    private function saveFiles($filesInfo, $dir)
    {
        $this->checkDir($dir);

        $result = array();

        // TODO make uniqe files name from config
        foreach ($filesInfo as $file)
        {
            rename($file->path, $dir.'/file.'.$file->extension);
            $result[] = 'file.'.$file->extension;
        }

        return $result;
    }

    private function saveImages($filesInfo, $dir, $thumbs)
    {


        $this->checkDir($dir);
        $result = array();
        foreach ($filesInfo as $file)
        {
            foreach ($thumbs as $key => $thumb)
            {
                $magicianObj = new ImageLib($file->path);
                $magicianObj->resizeImage($thumb['width'], $thumb['height'], $thumb['crop'], true);
                $magicianObj->saveImage($dir.'/'.$key.'.'.$thumb['format'], $thumb['quality']);
                $result[] = $key.'.'.$thumb['format'];
            }
        }

        return $result;
    }

    private function checkDir($dir)
    {
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

    }

    private function clearSessionAttr()
    {
        $this->session->remove($this->sessionAttr);
    }



}