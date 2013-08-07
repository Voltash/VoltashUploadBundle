<?php

namespace Voltash\UploadBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FileUploadType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['file_type'] = $options['file_type'];
        $view->vars['template'] = $options['template'];
        $view->vars['extensions'] = $options['extensions'];
        $view->vars['btn_name'] = $options['btn_name'];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'class' => 'upload',
            'btn_name' => 'Upload',
            'file_type' => 'default',
            'template' => 'FileUploadBundle:Upload:default.html.twig',
            'extensions' => ''
        ));
    }


    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'fileUpload';
    }
}