<?php

namespace Akyos\UXFileManager\Form\Type;

use Akyos\UXFileManager\Repository\FileRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileManagerUploadType extends AbstractType
{
    public function __construct(
        private FileRepository $fileRepository,
    ){}

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['path'] = $options['path'];
        $view->vars['button_clear'] = $options['button_clear'];
    }

    // add data transformer to the form
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('isDeleted', CheckboxType::class)
            ->add('file_db', HiddenType::class)
        ;

        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                $default = [
                    'file' => null,
                    'isDeleted' => false,
                    'file_db' => null,
                ];

                if (null === $value) {
                    return $default;
                }

                $file = $this->fileRepository->find($value);
                if ($file) {
                    return [
                        'file' => new File($file->getPath()),
                        'isDeleted' => false,
                        'file_db' => $file,
                    ];
                }

                if (!is_array($value)) {
                    return $default;
                }

                return $value;
            },
            function ($value) {
                return $value;
            }
        ));

        $builder->get('file_db')->addModelTransformer(new CallbackTransformer(
            function (?\Akyos\UXFileManager\Entity\File $file) {
                return $file?->getId();
            },
            function (?int $file) {
                if (null === $file) {
                    return null;
                }

                return $this->fileRepository->find($file);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'path' => null,
                'dir' => null,
                'button_clear' => true,
                'setter' => fn(mixed &$o, ?array $file, FormInterface $form) => null,
            ])
            ->setRequired('path')
        ;
    }

    public function getBlockPrefix()
    {
        return 'ux_filemanager_upload';
    }
}
