<?php
namespace Plugin\RemisePayment42;

use Eccube\Common\EccubeNav;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DomCrawler\Crawler;
use Plugin\RemisePayment42\Controller\Admin\RemiseAcNavController;

class RemiseAcNav implements EccubeNav
{
    /**
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'remisePayment4AcNavManagement' => [
                        'id' => 'remisePayment4AcNavManagement',
                        'name' => 'remise_payment4.ac.nav.management.title',
                        'url' => 'remise_payment4_ac_management_index'
                    ],
                    'remisePayment4AcNavImport' => [
                        'id' => 'remisePayment4AcNavImport',
                        'name' => 'remise_payment4.ac.nav.import.title',
                        'url' => 'remise_payment4_ac_import_index'
                    ]
                ]
            ],
            'setting' => [
                'children' => [
                    'shop' => [
                        'children' => [
                            'remisePayment4AcNavTypeEdit' => [
                                'id' => 'remisePayment4AcNavTypeEdit',
                                'name' => 'remise_payment4.ac.nav.type.edit.title',
                                'url' => 'remise_payment4_ac_type_edit_index'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
