<?php

namespace Voltash\UploadBundle\Controller;

use MyProject\Proxies\__CG__\stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class UploadController extends Controller
{
    public function uploadAction()
    {
        //TODO make multiple file upload

        $request = $this->getRequest();
        $this->uploadFile = $request->files->get('file');
        if (is_null($this->uploadFile)) {
            die('File not found!');
        }
        $data = array('success' => false, 'error' => 'Upload Error');
        $this->userToken = $request->get('token');
        $this->sessionToken = $this->get('session')->get('token');

        if ($this->uploadFile->isValid() && ($this->userToken === $this->sessionToken))
        {
            $filesConfig = $this->container->getParameter('file_upload.types');
            $fileSettings = $filesConfig[$request->get('type', 'default')];
            $sessionAttr = $request->get('field');

            $validator = $this->getFileValidator($fileSettings);
            if (!$validator) {
                $data = array('success' => false, 'error' => 'Ваш IP добавлен. В течении следующего часа с Вами свяжутся / Your IP added within. You will be contacted the next hour');

                return new JsonResponse($data);
            }
            $errorList = $this->get('validator')->validateValue($this->uploadFile, $validator);

            if (count($errorList) == 0)
            {
                $uploadDir = $this->get('kernel')->getRootDir() . '/../public_html'.$fileSettings['upload_dir'];
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);
                $fileName = 'img'.uniqid().'.'.$this->uploadFile->getClientOriginalExtension();
                $this->uploadFile->move($uploadDir, $fileName);

                $fileObj = new \stdClass();
                $fileObj->path =  $uploadDir.'/'.$fileName;
                $fileObj->extension = $this->uploadFile->getClientOriginalExtension();


                $fileCollection = new \SplObjectStorage();
                $fileCollection->type = $request->get('type', 'default');
                $fileCollection->attach($fileObj);

                $this->get('session')->set('file_upload_'.$sessionAttr, $fileCollection->serialize());
                $data = array('success' => true, 'file' => $fileSettings['upload_dir'].'/'.$fileName, 'name' => $this->uploadFile->getClientOriginalName());
            }
            else
            {
                $data = array('success' => false, 'error' => $errorList[0]->getMessage());
            }

        }

        return new JsonResponse($data);

    }

    protected function getFileValidator($settings)
    {
        $fileConstraint = null;

        $formats = explode(",", $settings['format']);
        $match = false;

        foreach ($formats as $format) {
            if (strtolower($format) == $this->uploadFile->getClientOriginalExtension())
                $match = true;
        }
        if ( !$match )
            return false;

        if ($settings['type'] == 'file')
        {
            $fileConstraint = new File();
            $fileConstraint->maxSize = $settings['max_size'];
            $fileConstraint->mimeTypes = $settings['mime_type'];
        }
        elseif ($settings['type'] == 'image')
        {
            $fileConstraint = new Image();
            $fileConstraint->maxSize = $settings['max_size'];
        }

        if (is_null($fileConstraint))
            throw new \Exception(' Not Found file type in config !');

        return $fileConstraint;

    }
}