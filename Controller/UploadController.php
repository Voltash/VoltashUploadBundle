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
        $uploadFile = $request->files->get('file');

        $data = array('success' => false, 'error' => 'Upload Error');

        if ($uploadFile->isValid()) {
            $filesConfig = $this->container->getParameter('file_upload.types');
            $fileSettings = $filesConfig[$request->get('type', 'default')];
            $sessionAttr = $request->get('field');

            $validator = $this->getFileValidator($fileSettings);

            $errorList = $this->get('validator')->validateValue($uploadFile, $validator);

            if (count($errorList) == 0) {
                $uploadDir = $this->get('kernel')->getRootDir() . '/../public_html' . $fileSettings['upload_dir'];
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = 'img' . uniqid() . '.' . $uploadFile->getClientOriginalExtension();
                $uploadFile->move($uploadDir, $fileName);

                $fileObj = new \stdClass();
                $fileObj->path = $uploadDir . '/' . $fileName;
                $fileObj->extension = $uploadFile->getClientOriginalExtension();


                $fileCollection = new \SplObjectStorage();
                $fileCollection->type = $request->get('type', 'default');
                $fileCollection->attach($fileObj);

                $this->get('session')->set('file_upload_' . $sessionAttr, $fileCollection->serialize());
                $data = array(
                    'success' => true,
                    'file' => $fileSettings['upload_dir'] . '/' . $fileName,
                    'name' => $uploadFile->getClientOriginalName()
                );
            } else {
                $data = array('success' => false, 'error' => $errorList[0]->getMessage());
            }

        }

        return new JsonResponse($data);

    }

    protected function getFileValidator($settings)
    {
        $fileConstraint = null;

        if ($settings['type'] == 'file') {
            $fileConstraint = new File();
            $fileConstraint->maxSize = $settings['max_size'];
            $fileConstraint->mimeTypes = $settings['mime_type'];
        } elseif ($settings['type'] == 'image') {
            $fileConstraint = new Image();
            $fileConstraint->maxSize = $settings['max_size'];
        }

        if (is_null($fileConstraint)) {
            throw new \Exception(' Not Found file type in config !');
        }

        return $fileConstraint;

    }
}
