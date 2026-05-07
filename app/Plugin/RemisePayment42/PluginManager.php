<?php
namespace Plugin\RemisePayment42;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Payment;
use Eccube\Entity\PaymentOption;
use Eccube\Entity\Csv;
use Eccube\Entity\Layout;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PaymentOptionRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Service\Payment\Method\Cash;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Plugin\RemisePayment42\Entity\Config;
use Plugin\RemisePayment42\Entity\OrderResult;
use Plugin\RemisePayment42\Entity\OrderResultCard;
use Plugin\RemisePayment42\Entity\OrderResultCvs;
use Plugin\RemisePayment42\Entity\Payquick;
use Plugin\RemisePayment42\Entity\RemiseACImport;
use Plugin\RemisePayment42\Entity\RemiseACImportStack;
use Plugin\RemisePayment42\Entity\RemiseACImportStackResult;
use Plugin\RemisePayment42\Entity\RemiseACMember;
use Plugin\RemisePayment42\Entity\RemiseACResult;
use Plugin\RemisePayment42\Entity\RemiseACType;
use Plugin\RemisePayment42\Entity\RemiseMailTemplate;
use Plugin\RemisePayment42\Entity\RemisePayment;
use Plugin\RemisePayment42\Entity\RemiseCsvType;
use Plugin\RemisePayment42\Entity\RemiseSaleType;
use Plugin\RemisePayment42\Entity\RemiseTaxRate;
use Plugin\RemisePayment42\EntityBackup\BackupOrderResult;
use Plugin\RemisePayment42\EntityBackup\BackupOrderResultCard;
use Plugin\RemisePayment42\EntityBackup\BackupOrderResultCvs;
use Plugin\RemisePayment42\EntityBackup\BackupPayquick;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImport;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStack;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStackResult;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACMember;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACProduct;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACResult;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseACType;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseSaleType;
use Plugin\RemisePayment42\EntityBackup\BackupRemiseTaxRate;
use Plugin\RemisePayment42\Repository\ConfigRepository;
use Plugin\RemisePayment42\Repository\RemiseMailTemplateRepository;
use Plugin\RemisePayment42\Repository\RemisePaymentRepository;
use Plugin\RemisePayment42\Repository\OrderResultRepository;
use Plugin\RemisePayment42\Repository\OrderResultCardRepository;
use Plugin\RemisePayment42\Repository\OrderResultCvsRepository;
use Plugin\RemisePayment42\Repository\PayquickRepository;
use Plugin\RemisePayment42\Repository\RemiseSaleTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseCsvTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACMemberRepository;
use Plugin\RemisePayment42\Repository\RemiseACTypeRepository;
use Plugin\RemisePayment42\Repository\RemiseACResultRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportRepository;
use Plugin\RemisePayment42\Repository\RemiseTaxRateRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportStackRepository;
use Plugin\RemisePayment42\Repository\RemiseACImportStackResultRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupOrderResultRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupOrderResultCardRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupOrderResultCvsRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupPayquickRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseSaleTypeRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACMemberRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACTypeRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACProductRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACResultRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACImportRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseTaxRateRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACImportStackRepository;
use Plugin\RemisePayment42\RepositoryBackup\BackupRemiseACImportStackResultRepository;
use Plugin\RemisePayment42\Service\PaymentService;
use Psr\Container\ContainerInterface;
class PluginManager extends AbstractPluginManager
{

    // 一括INSERTの最大レコード数
    private $insetMaxCount = 1000;

    /**
     * プラグインのインストール
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    public function install(array $meta, ContainerInterface $container)
    {
        // マイグレーション
        $this->executeMigrations($meta, $container);
    }

    /**
     * プラグインのアンインストール
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        // 設定情報のエクスポート
        $this->exportConfig($meta, $container);
        // ルミーズ決済プラグインのテーブルバックアップ
        $this->backupTables($meta, $container);
        // 関連情報の削除
        $this->removeRemiseInfo($meta, $container);
        // ページ削除
        $this->removePage($meta, $container);
        // メールテンプレートの削除
        $this->removeMailTemplate($meta, $container);
        // CSV削除
        $this->removeCSV($meta, $container);
    }

    /**
     * プラグインのアップデート
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        // ルミーズテーブル作成
        $this->createTables($meta, $container);
        // ページ追加
        $this->createPage($meta, $container);
        // メールテンプレート登録
        $this->createMailTemplate($meta, $container);
        // CSV項目の追加
        $this->createCSV($meta, $container);
        // 消費税率設定情報の追加
        $this->insertRemiseTaxRate($meta, $container);
        // 自動継続課金の受注取込設定
        $this->setAcAcquisitionMethod($meta, $container);
        // マイグレーション
        $this->executeMigrations($meta, $container);
    }

    /**
     * プラグインの有効化
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        // 設定情報のインポート
        $this->importConfig($meta, $container);
        // ルミーズ決済プラグインのテーブルインポート
        $this->importTables($meta, $container);
        // 関連情報の有効化
        $this->enableRemiseInfo($meta, $container);
        // ページ追加
        $this->createPage($meta, $container);
        // メールテンプレート登録
        $this->createMailTemplate($meta, $container);
        // CSV項目の追加
        $this->createCSV($meta, $container);
        // 消費税率設定情報の追加
        $this->insertRemiseTaxRate($meta, $container);
    }

    /**
     * プラグインの無効化
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // 関連情報の無効化
        $this->disableRemiseInfo($meta, $container);

        $entityManager = $container->get('doctrine')->getManager();
        $this->migration($entityManager->getConnection(), $meta['code'], '0');
    }

    /**
     * ルミーズ決済プラグインのテーブル作成(Ver1.2.0以降)
     *
     * プラグインのアップデートの場合、新規テーブルは追加してくれないため必要となる
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     *
     */
    private function createTables(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] table create');

            $entityManager = $container->get('doctrine')->getManager();
            $Connection = $entityManager->getConnection();
            $dbPlatformName = $Connection->getDatabasePlatform()->getName();

            // ルミーズ販売種別の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_sale_type", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseSaleType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_sale_type']);
            }

            // ルミーズCSV種別の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_csv_type", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseCsvType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_csv_type']);
            }

            // ルミーズ定期購買種別の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_type", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_type']);
            }

            // ルミーズ定期購買情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_member", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACMember');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_member']);
            }

            // ルミーズ定期購買結果情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_result", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_result']);
            }

            // ルミーズ定期購買取込情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_import", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACImport');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_import']);
            }

            // ルミーズ消費税率設定情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_tax_rate", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseTaxRate');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_tax_rate']);
            }

            // ルミーズ定期購買バッチ取込管理情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_import_stack", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACImportStack');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_import']);
            }

            // ルミーズ定期購買バッチ取込情報の作成
            $tableCheckSql = $this->sqlFunctionTableCheck("plg_remise_payment4_remise_ac_import_stack_result", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if (!$stmt->fetchAllAssociative()) {
                $Metas = [];
                $Metas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\Entity\RemiseACImportStackResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->createSchema($Metas);
                log_info('[Remise] table create',['table_name:plg_remise_payment4_remise_ac_import']);
            }

            log_info('[Remise] table create -- Done');
        }
        catch (\Exception $e)
        {
            log_error('[Remise] table create -- Error', [$e]);
        }

        return;
    }

    /**
     * 関連情報の無効化
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    private function disableRemiseInfo(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] Disable Info');

            $entityManager = $container->get('doctrine')->getManager();

            // ルミーズ支払情報
            $remisePaymentRepository = $entityManager->getRepository(RemisePayment::class);
            $RemisePayments = $remisePaymentRepository->findAll();

            if ($RemisePayments) {
                // 支払オプション取得
                $paymentOptionRepository = $entityManager->getRepository(PaymentOption::class);
                $PaymentOptions = $paymentOptionRepository->findAll();

                foreach ($RemisePayments as $RemisePayment) {
                    // 支払方法無効化
                    $Payment = $entityManager->find(Payment::class, $RemisePayment->getId());
                    $Payment->setMethodClass(Cash::class);
                    $Payment->setVisible(false);
                    $Payment->setUpdateDate(new \DateTime());
                    $entityManager->persist($Payment);
                }
                $entityManager->flush();
            }

            log_info('[Remise] Disable Info -- Done');
        } catch (ServiceNotFoundException $e) {
            log_info('[Remise] Disable Info -- Done (Not Avtivate)');
        } catch (\Exception $e) {
            log_error('[Remise] Disable Info -- Error', [
                $e
            ]);
        }
    }

    /**
     * 関連情報の有効化
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    private function enableRemiseInfo(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] Enable Info');

            $entityManager = $container->get('doctrine')->getManager();

            // ルミーズ支払情報
            $remisePaymentRepository = $entityManager->getRepository(RemisePayment::class);
            $RemisePayments = $remisePaymentRepository->findAll();

            if ($RemisePayments) {
                // 支払オプション取得
                $paymentOptionRepository = $entityManager->getRepository(PaymentOption::class);
                $PaymentOptions = $paymentOptionRepository->findAll();

                foreach ($RemisePayments as $RemisePayment) {
                    // 支払方法有効化
                    $Payment = $entityManager->find(Payment::class, $RemisePayment->getId());
                    $Payment->setMethodClass(PaymentService::class);
                    $Payment->setVisible(true);
                    $Payment->setUpdateDate(new \DateTime());
                    $entityManager->persist($Payment);
                }
                $entityManager->flush();
            }

            log_info('[Remise] Enable Info -- Done');
        } catch (ServiceNotFoundException $e) {
            log_info('[Remise] Enable Info -- Done (Not Avtivate)');
        } catch (\Exception $e) {
            log_error('[Remise] Enable Info -- Error', [
                $e
            ]);
        }
    }

    /**
     * 関連情報の削除
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    private function removeRemiseInfo(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] Remove Info');

            $entityManager = $container->get('doctrine')->getManager();

            // ルミーズ支払情報
            $remisePaymentRepository = $entityManager->getRepository(RemisePayment::class);
            $RemisePayments = $remisePaymentRepository->findAll();

            if ($RemisePayments) {
                // 支払オプション取得
                $paymentOptionRepository = $entityManager->getRepository(PaymentOption::class);
                $PaymentOptions = $paymentOptionRepository->findAll();

                foreach ($RemisePayments as $RemisePayment) {
                    // 支払オプション削除
                    foreach ($PaymentOptions as $PaymentOption) {
                        if ($PaymentOption->getPaymentId() == $RemisePayment->getId()) {
                            $entityManager->remove($PaymentOption);
                        }
                    }

                    // 支払方法無効化
                    $Payment = $entityManager->find(Payment::class, $RemisePayment->getId());
                    $Payment->setMethodClass(Cash::class);
                    $Payment->setVisible(false);
                    $Payment->setUpdateDate(new \DateTime());
                    $entityManager->persist($Payment);
                }
                $entityManager->flush();
            }

            log_info('[Remise] Remove Info -- Done');
        } catch (ServiceNotFoundException $e) {
            log_info('[Remise] Remove Info -- Done (Not Avtivate)');
        } catch (\Exception $e) {
            log_error('[Remise] Remove Info -- Error', [
                $e
            ]);
        }
    }

    /**
     * 設定情報のエクスポート
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    private function exportConfig(array $meta, ContainerInterface $container)
    {
        try {
            $entityManager = $container->get('doctrine')->getManager();

            // プラグイン設定情報の取得
            $remiseConfigRepository = $entityManager->getRepository(Config::class);
            $RemiseConfigs = $remiseConfigRepository->findAll();

            // エクスポート可否
            $isExport = false;
            // プラグイン設定情報がある場合
            if ($RemiseConfigs) {
                foreach ($RemiseConfigs as $RemiseConfig) {
                    // 設定値がある場合、エクスポート対象
                    $info = $RemiseConfig->getInfo();
                    if (! empty($info)) {
                        $isExport = true;
                    }
                }
            }

            if (! $isExport)
                return;

            log_info('[Remise] Export Config');

            // エクスポート内容
            $exportInfo = array();

            // プラグイン設定情報
            $exports = array();
            if ($RemiseConfigs) {
                foreach ($RemiseConfigs as $RemiseConfig) {
                    $exports[] = [
                        'code' => $RemiseConfig->getCode(),
                        'name' => $RemiseConfig->getName(),
                        'info' => $RemiseConfig->getUnserializeInfo()
                    ];
                }
            }
            $exportInfo['Config'] = $exports;

            // ルミーズ支払情報
            $remisePaymentRepository = $entityManager->getRepository(RemisePayment::class);
            $RemisePayments = $remisePaymentRepository->findAll();

            $exports = array();
            if ($RemisePayments) {
                foreach ($RemisePayments as $RemisePayment) {
                    $exports[] = [
                        'id' => $RemisePayment->getId(),
                        'kind' => $RemisePayment->getKind()
                    ];
                }
            }
            $exportInfo['RemisePayments'] = $exports;

            // ルミーズメールテンプレート
            $remiseMailTemplateRepository = $entityManager->getRepository(RemiseMailTemplate::class);
            $RemiseMailTemplates = $remiseMailTemplateRepository->findAll();

            $exports = array();
            if ($RemiseMailTemplates) {
                foreach ($RemiseMailTemplates as $RemiseMailTemplate) {
                    $exports[] = [
                        'id' => $RemiseMailTemplate->getId(),
                        'kind' => $RemiseMailTemplate->getKind()
                    ];
                }
            }
            $exportInfo['RemiseMailTemplates'] = $exports;

            // バックアップファイル
            $filePath = $this->getBackupDirectory($meta) . (new \DateTime())->format('YmdHis');

            $serializeExportInfo = serialize($exportInfo);

            // ファイル書き込み
            log_info('[Remise] Export Config -- Export (' . basename($filePath) . ')');
            file_put_contents($filePath, $serializeExportInfo);

            log_info('[Remise] Export Config -- Done');
        } catch (ServiceNotFoundException $e) {
            log_info('[Remise] Export Config -- Done (Not Avtivate)');
        } catch (\Exception $e) {
            log_error('[Remise] Export Config -- Error', [
                $e
            ]);
        }
    }

    /**
     * 設定情報のインポート
     *
     * @param
     *            $meta
     * @param
     *            $container
     */
    private function importConfig(array $meta, ContainerInterface $container)
    {
        try {
            // ルミーズ設定情報の取得
            $entityManager = $container->get('doctrine')->getManager();
            $RemiseConfigs = $entityManager->find(Config::class, 1);

            // インポート可否
            $isImport = false;
            // ルミーズ設定情報がない場合、インポート対象
            if (! $RemiseConfigs) {
                $isImport = true;
            } else {
                // 設定値がない場合、インポート対象
                foreach ($RemiseConfigs as $RemiseConfig) {
                    $info = $RemiseConfig->getInfo();
                    if (empty($info)) {
                        $isImport = true;
                    }
                }
            }

            if (! $isImport)
                return;

            // バックアップディレクトリ
            $dirPath = $this->getBackupDirectory($meta);
            // ファイル一覧取得
            $results = glob($dirPath . '*');
            if (! $results || count($results) == 0)
                return;

            log_info('[Remise] Import Config');

            // 逆順ソート
            rsort($results);
            // 最新のバックアップファイル
            $filePath = $results[0];

            log_info('[Remise] Import Config -- Import (' . basename($filePath) . ')');

            // ファイル読み込み
            $serializeImportInfo = file_get_contents($filePath);
            if (empty($serializeImportInfo)) {
                return;
            }
            $importInfo = unserialize($serializeImportInfo);

            $entityManager = $container->get('doctrine')->getManager();

            // ルミーズ設定情報の登録
            if (isset($importInfo['Config'])) {
                $imports = $importInfo['Config'];
                foreach ($imports as $import) {
                    $RemiseConfig = new Config();
                    $RemiseConfig->setCode($import['code']);
                    $RemiseConfig->setName($import['name']);
                    $RemiseConfig->setSerializeInfo($import['info']);
                    $RemiseConfig->setCreateDate(new \DateTime());
                    $RemiseConfig->setUpdateDate(new \DateTime());

                    $entityManager->persist($RemiseConfig);
                    $entityManager->flush($RemiseConfig);
                }
            }

            // ルミーズ支払情報の登録
            if (isset($importInfo['RemisePayments'])) {
                $imports = $importInfo['RemisePayments'];
                foreach ($imports as $import) {
                    $RemisePayment = new RemisePayment();
                    $RemisePayment->setId($import['id']);
                    $RemisePayment->setKind($import['kind']);
                    $RemisePayment->setCreateDate(new \DateTime());
                    $RemisePayment->setUpdateDate(new \DateTime());

                    $entityManager->persist($RemisePayment);
                    $entityManager->flush($RemisePayment);
                }
            }

            // ルミーズメールテンプレートの登録
            if (isset($importInfo['RemiseMailTemplates'])) {
                $imports = $importInfo['RemiseMailTemplates'];
                foreach ($imports as $import) {
                    $RemiseMailTemplate = new RemiseMailTemplate();
                    $RemiseMailTemplate->setId($import['id']);
                    $RemiseMailTemplate->setKind($import['kind']);
                    $RemiseMailTemplate->setCreateDate(new \DateTime());
                    $RemiseMailTemplate->setUpdateDate(new \DateTime());

                    $entityManager->persist($RemiseMailTemplate);
                    $entityManager->flush($RemiseMailTemplate);
                }
            }

            log_info('[Remise] Import Config -- Done');
        } catch (ServiceNotFoundException $e) {
            log_info('[Remise] Import Config -- Done (Not Avtivate)');
        } catch (\Exception $e) {
            log_error('[Remise] Import Config -- Error', [
                $e
            ]);
        }
    }

    /**
     * バックアップディレクトリの取得
     *
     * @param
     *            $meta
     *
     * @return //バックアップディレクトリパス
     */
    private function getBackupDirectory(array $meta)
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $meta['code'] . "_backup_";
    }

    /**
     * ルミーズ決済プラグインのテーブルバックアップ
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     *
     */
    private function backupTables(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] table backup');

            $entityManager = $container->get('doctrine')->getManager();
            $Connection = $entityManager->getConnection();
            $dbPlatformName = $Connection->getDatabasePlatform()->getName();

            // 決済履歴
            $orderResultRepository = $entityManager->getRepository(OrderResult::class);
            $orderResultArray = $orderResultRepository->findAll();
            if ($orderResultArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_order_result VALUES';
                $orderResultCount = 0;

                foreach ($orderResultArray as $orderResult) {
                    if ($orderResultCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $orderResult->getId() . ",";
                    $sql .= "'" . $orderResult->getKind() . "',";
                    $sql .= $orderResult->getPaymentId() . ",";
                    $sql .= $orderResult->getPaymentTotal() . ",";
                    $sql .= $this->sqlFunctionToDate($orderResult->getRequestDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResult->getCompleteDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResult->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResult->getUpdateDate(), $dbPlatformName) . "";
                    $sql .= ')';

                    $orderResultCount ++;
                    if ($orderResultCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_order_result VALUES';
                        $orderResultCount = 0;
                    }
                }
                if (0 < $orderResultCount && $orderResultCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                }
            }

            // カード決済履歴詳細
            $orderResultCardRepository = $entityManager->getRepository(OrderResultCard::class);
            $orderResultCardArray = $orderResultCardRepository->findAll();
            if ($orderResultCardArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResultCard');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_order_result_card VALUES';
                $orderResultCardCount = 0;

                foreach ($orderResultCardArray as $orderResultCard) {
                    if ($orderResultCardCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $orderResultCard->getId() . ",";
                    $sql .= $orderResultCard->getState() . ",";
                    $sql .= "'" . $orderResultCard->getJob() . "',";
                    $sql .= "'" . $orderResultCard->getUsePayquickId() . "',";
                    $sql .= "'" . $orderResultCard->getTranid() . "',";
                    $sql .= "'" . $orderResultCard->getRCode() . "',";
                    $sql .= "'" . $orderResultCard->getPayquickId() . "',";
                    $sql .= "'" . $orderResultCard->getCardParts() . "',";
                    $sql .= "'" . $orderResultCard->getExpire() . "',";
                    $sql .= "'" . $orderResultCard->getName() . "',";
                    $sql .= "'" . $orderResultCard->getCardBrand() . "',";
                    $sql .= "'" . $orderResultCard->getMemberId() . "',";
                    $sql .= "" . $orderResultCard->getAcTotal() . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getAcNextDate(), $dbPlatformName) . ",";
                    $sql .= "'" . $orderResultCard->getAcInterval() . "',";
                    $sql .= "'" . $orderResultCard->getPrevTranid() . "',";
                    $sql .= "" . $orderResultCard->getPrevPaymentTotal() . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getResultDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getSalesDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getCancelDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCard->getUpdateDate(), $dbPlatformName) . "";
                    $sql .= ')';

                    $orderResultCardCount ++;
                    if ($orderResultCardCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_order_result_card VALUES';
                        $orderResultCardCount = 0;
                    }
                }
                if (0 < $orderResultCardCount && $orderResultCardCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                }
            }

            // マルチ決済履歴詳細
            $orderResultCvsRepository = $entityManager->getRepository(OrderResultCvs::class);
            $orderResultCvsArray = $orderResultCvsRepository->findAll();
            if ($orderResultCvsArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResultCvs');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_order_result_cvs VALUES';
                $orderResultCvsCount = 0;

                foreach ($orderResultCvsArray as $orderResultCvs) {
                    if ($orderResultCvsCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }

                    $sql .= $orderResultCvs->getId() . ",";
                    $sql .= "'" . $orderResultCvs->getJobid() . "',";
                    $sql .= "'" . $orderResultCvs->getRCode() . "',";
                    $sql .= "'" . $orderResultCvs->getPayWay() . "',";
                    $sql .= "'" . $orderResultCvs->getPayCsv() . "',";
                    $sql .= "'" . $orderResultCvs->getPayNo1() . "',";
                    $sql .= "'" . $orderResultCvs->getPayNo2() . "',";
                    $sql .= "'" . $orderResultCvs->getRecCvscode() . "',";
                    $sql .= "'" . $orderResultCvs->getRecCvsname() . "',";
                    $sql .= "'" . $orderResultCvs->getRecScode() . "',";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getPayDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getRecDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getCenDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getReceiptDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($orderResultCvs->getUpdateDate(), $dbPlatformName) . "";
                    $sql .= ')';

                    $orderResultCvsCount ++;
                    if ($orderResultCvsCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_order_result_cvs VALUES';
                        $orderResultCvsCount = 0;
                    }
                }
                if (0 < $orderResultCvsCount && $orderResultCvsCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                }
            }

            // ペイクイック
            $payquickRepository = $entityManager->getRepository(Payquick::class);
            $payquickArray = $payquickRepository->findAll();
            if ($payquickArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupPayquick');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_payquick VALUES';
                $payquickCount = 0;

                foreach ($payquickArray as $payquick) {
                    if ($payquickCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }

                    $sql .= $payquick->getId() . ",";
                    $sql .= $payquick->getCustomerId() . ",";
                    $sql .= "'" . $payquick->getPayquickId() . "',";
                    $sql .= "'" . $payquick->getCardParts() . "',";
                    $sql .= "'" . $payquick->getExpire() . "',";
                    $sql .= "'" . $payquick->getName() . "',";
                    $sql .= "'" . $payquick->getCardBrand() . "',";
                    $sql .= "'" . $payquick->getPrePayquickId() . "',";
                    $sql .= "'" . $payquick->getPreCardParts() . "',";
                    $sql .= "'" . $payquick->getPreExpire() . "',";
                    $sql .= "'" . $payquick->getPreName() . "',";
                    $sql .= "'" . $payquick->getPreCardBrand() . "',";
                    $sql .= $this->sqlFunctionToDate($payquick->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($payquick->getUpdateDate(), $dbPlatformName) . "";
                    $sql .= ')';

                    $payquickCount ++;
                    if ($payquickCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_payquick VALUES';
                        $payquickCount = 0;
                    }
                }
                if (0 < $payquickCount && $payquickCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                }
            }

            // ルミーズ販売種別
            $RemiseSaleTypeRepository = $entityManager->getRepository(RemiseSaleType::class);
            $RemiseSaleTypeArray = $RemiseSaleTypeRepository->findAll();
            if ($RemiseSaleTypeArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseSaleType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_sale_type VALUES';
                $RemiseSaleTypeCount = 0;

                foreach ($RemiseSaleTypeArray as $RemiseSaleType) {
                    if ($RemiseSaleTypeCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }

                    $sql .= $RemiseSaleType->getId() . ",";
                    $sql .= "'" . $RemiseSaleType->getName() . "',";
                    $sql .= $RemiseSaleType->getSaleType() . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseSaleType->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseSaleType->getUpdateDate(), $dbPlatformName) . "";
                    $sql .= ')';

                    $RemiseSaleTypeCount ++;
                    if ($RemiseSaleTypeCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_sale_type VALUES';
                        $RemiseSaleTypeCount = 0;
                    }
                }
                if (0 < $RemiseSaleTypeCount && $RemiseSaleTypeCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買メンバ情報
            $RemiseACMemberRepository = $entityManager->getRepository(RemiseACMember::class);
            $RemiseACMemberArray = $RemiseACMemberRepository->findAll();
            if ($RemiseACMemberArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACMember');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_member VALUES';
                $insertCount = 0;

                foreach ($RemiseACMemberArray as $RemiseACMember) {
                    if ($insertCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= "'" . $RemiseACMember->getId() . "',";
                    $sql .= $RemiseACMember->getTotal() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACMember->getCount()) . ",";
                    $sql .= $this->sqlFunctionBoolCheck($RemiseACMember->isLimitless(), $dbPlatformName) . ",";
                    $sql .= $RemiseACMember->getIntervalValue() . ",";
                    $sql .= "'" . $RemiseACMember->getIntervalMark() . "',";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACMember->getDayOfMonth()) . ",";
                    $sql .= $RemiseACMember->getAfterValue() . ",";
                    $sql .= "'" . $RemiseACMember->getAfterMark() . "',";
                    $sql .= $RemiseACMember->getSkip() . ",";
                    $sql .= $RemiseACMember->getStop() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACMember->getUsageValue()) . ",";
                    $sql .= "'" . $RemiseACMember->getUsageMark() . "',";
                    $sql .= "'" . $RemiseACMember->getCardparts() . "',";
                    $sql .= "'" . $RemiseACMember->getExpire() . "',";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getNextDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionBoolCheck($RemiseACMember->isStatus(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionBoolCheck($RemiseACMember->isSkipped(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getSkippedDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getSkippedNextDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getStartDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getStopDate(), $dbPlatformName) . ",";
                    $sql .= "'" . $RemiseACMember->getNote() . "',";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACMember->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';
                    $insertCount ++;
                    if ($insertCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_member VALUES';
                        $insertCount = 0;
                    }
                }
                if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買種別
            $RemiseACTypeRepository = $entityManager->getRepository(RemiseACType::class);
            $RemiseACTypeArray = $RemiseACTypeRepository->findAll();
            if ($RemiseACTypeArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_type VALUES';
                $insertCount = 0;

                foreach ($RemiseACTypeArray as $RemiseACType) {
                    if ($insertCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $RemiseACType->getId() . ",";
                    $sql .= "'" . $RemiseACType->getName() . "',";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACType->getCount()) . ",";
                    $sql .= $this->sqlFunctionBoolCheck($RemiseACType->isLimitless(), $dbPlatformName) . ",";
                    $sql .= $RemiseACType->getIntervalValue() . ",";
                    $sql .= "'" . $RemiseACType->getIntervalMark() . "',";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACType->getDayOfMonth()) . ",";
                    $sql .= $RemiseACType->getAfterValue() . ",";
                    $sql .= "'" . $RemiseACType->getAfterMark() . "',";
                    $sql .= $RemiseACType->getSkip() . ",";
                    $sql .= $RemiseACType->getStop() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACType->getUsageValue()) . ",";
                    $sql .= "'" . $RemiseACType->getUsageMark() . "',";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACType->getSortNo()) . ",";
                    $sql .= $this->sqlFunctionBoolCheck($RemiseACType->isVisible(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACType->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACType->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';
                    $insertCount ++;
                    if ($insertCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_type VALUES';
                        $insertCount = 0;
                    }
                }
                if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買商品情報
            $ProductClassRepository = $entityManager->getRepository(ProductClass::class);
            $ProductClassArray = $ProductClassRepository->findAll();
            if ($ProductClassArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACProduct');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_product';
                $sql .= ' SELECT id, remise_payment4_ac_actype_id, remise_payment4_ac_amount, remise_payment4_ac_point_flg, create_date, update_date FROM dtb_product_class';
                $entityManager->getConnection()
                ->prepare($sql)
                ->execute();
            }

            // ルミーズ定期購買結果情報
            $RemiseACResultRepository = $entityManager->getRepository(RemiseACResult::class);
            $RemiseACResultArray = $RemiseACResultRepository->findAll();
            if ($RemiseACResultArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_result VALUES';
                $insertCount = 0;

                foreach ($RemiseACResultArray as $RemiseACResult) {
                    if ($insertCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $RemiseACResult->getId() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACResult->getResult()) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACResult->getChargeDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACResult->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACResult->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';
                    $insertCount ++;
                    if ($insertCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_result VALUES';
                        $insertCount = 0;
                    }
                }
                if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買結果情報
            $RemiseACImportRepository = $entityManager->getRepository(RemiseACImport::class);
            $RemiseACImportArray = $RemiseACImportRepository->findAll();
            if ($RemiseACImportArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImport');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import VALUES';
                $insertCount = 0;

                foreach ($RemiseACImportArray as $RemiseACImport) {
                    if ($insertCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $RemiseACImport->getId() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getSuccessCnt()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getSuccessAmount()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getFailCnt()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getFailAmount()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getTotalCnt()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getTotalAmount()) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACImport->getExecDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getImportFlg()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseACImport->getImportCnt()) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACImport->getImportDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACImport->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseACImport->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';
                    $insertCount ++;
                    if ($insertCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import VALUES';
                        $insertCount = 0;
                    }
                }
                if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ消費税率設定情報
            $RemiseTaxRateRepository = $entityManager->getRepository(RemiseTaxRate::class);
            $RemiseTaxRateArray = $RemiseTaxRateRepository->findAll();
            if ($RemiseTaxRateArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseTaxRate');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_tax_rate VALUES';
                $insertCount = 0;

                foreach ($RemiseTaxRateArray as $RemiseTaxRate) {
                    if ($insertCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }
                    $sql .= $RemiseTaxRate->getId() . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseTaxRate->getTaxRate()) . ",";
                    $sql .= $this->sqlFunctionIntNullCheck($RemiseTaxRate->getCalcRule()) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseTaxRate->getApplyDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseTaxRate->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($RemiseTaxRate->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';
                    $insertCount ++;
                    if ($insertCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_tax_rate VALUES';
                        $insertCount = 0;
                    }
                }
                if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買バッチ取込管理情報
            $remiseACImportStackRepository = $entityManager->getRepository(RemiseACImportStack::class);
            $remiseACImportStackArray = $remiseACImportStackRepository->findAll();

            if ($remiseACImportStackArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStack');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import_stack VALUES';
                $remiseACImportStackCount = 0;

                foreach ($remiseACImportStackArray as $remiseACImportStack) {
                    if ($remiseACImportStackCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }

                    $sql .= $remiseACImportStack->getId() . ",";
                    $sql .= $this->sqlFunctionToDate($remiseACImportStack->getImportDate(), $dbPlatformName) . ",";
                    $sql .= $remiseACImportStack->getStartNum() . ",";
                    $sql .= $remiseACImportStack->getEndNum() . ",";
                    $sql .= $remiseACImportStack->getExecFlg() . ",";
                    $sql .= $remiseACImportStack->getCompleteFlg() . ",";
                    $sql .= $this->sqlFunctionToDate($remiseACImportStack->getCreateDate(), $dbPlatformName) . ",";
                    $sql .= $this->sqlFunctionToDate($remiseACImportStack->getUpdateDate(), $dbPlatformName);
                    $sql .= ')';

                    $remiseACImportStackCount ++;

                    if ($remiseACImportStackCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import_stack VALUES';
                        $remiseACImportStackCount = 0;
                    }
                }

                if (0 < $remiseACImportStackCount && $remiseACImportStackCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            // ルミーズ定期購買バッチ取込情報
            $remiseACImportStackResultRepository = $entityManager->getRepository(RemiseACImportStackResult::class);
            $remiseACImportStackResultArray = $remiseACImportStackResultRepository->findAll();

            if ($remiseACImportStackResultArray) {
                // バックアップ用テーブルの作成
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStackResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $schemaTool->createSchema($bkupMetas);
                $entityManager->flush();

                $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import_stack_result VALUES';
                $remiseACImportStackResultCount = 0;

                foreach ($remiseACImportStackResultArray as $remiseACImportStackResult) {
                    if ($remiseACImportStackResultCount == 0) {
                        $sql .= '(';
                    } else {
                        $sql .= ',(';
                    }

                    $sql .= $remiseACImportStackResult->getId() . ",";
                    $sql .= $this->sqlFunctionToDate($remiseACImportStackResult->getImportDate(), $dbPlatformName) . ",";
                    $sql .= "'" . $remiseACImportStackResult->getRCode() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getMemberId() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getSKaiinNo() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getAcName() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getAcKana() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getAcTel() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getAcMail() . "',";
                    $sql .= $remiseACImportStackResult->getAcAmount() . ",";
                    $sql .= "'" . $remiseACImportStackResult->getAcResult() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getTranid() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getErrcode() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getErrinfo() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getAcNextDate() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getCard() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getExpire() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getNote() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getStatus() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getOrderId() . "',";
                    $sql .= "'" . $remiseACImportStackResult->getOrderNo() . "'";
                    $sql .= ')';

                    $remiseACImportStackResultCount ++;

                    if ($remiseACImportStackResultCount == $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                        $sql = 'INSERT INTO backup_plg_remise_payment4_remise_ac_import_stack_result VALUES';
                        $remiseACImportStackResultCount = 0;
                    }
                }

                if (0 < $remiseACImportStackResultCount && $remiseACImportStackResultCount < $this->insetMaxCount) {
                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }
            }

            log_info('[Remise] table backup -- Done');
        } catch (\Exception $e) {
            log_error('[Remise] table backup -- Error', [
                $e
            ]);
        }

        return;
    }

    /**
     * ルミーズ決済プラグインのテーブルインポート
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     *
     */
    private function importTables(array $meta, ContainerInterface $container)
    {
        try {
            log_info('[Remise] table import');

            $entityManager = $container->get('doctrine')->getManager();
            $Connection = $entityManager->getConnection();
            $dbPlatformName = $Connection->getDatabasePlatform()->getName();

            // 決済履歴
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_order_result", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            $result = $stmt->fetchAllAssociative();
            if ($result) {
                $backupOrderResultRepository = $entityManager->getRepository(BackupOrderResult::class);
                $backupOrderResultArray = $backupOrderResultRepository->findAll();

                if ($backupOrderResultArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_order_result VALUES';
                    $orderResultCount = 0;

                    foreach ($backupOrderResultArray as $backupOrderResult) {
                        if ($orderResultCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupOrderResult->getId() . ",";
                        $sql .= "'" . $backupOrderResult->getKind() . "',";
                        $sql .= $backupOrderResult->getPaymentId() . ",";
                        $sql .= $backupOrderResult->getPaymentTotal() . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResult->getRequestDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResult->getCompleteDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResult->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResult->getUpdateDate(), $dbPlatformName) . "";
                        $sql .= ')';

                        $orderResultCount ++;
                        if ($orderResultCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                                ->prepare($sql)
                                ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_order_result VALUES';
                            $orderResultCount = 0;
                        }
                    }
                    if (0 < $orderResultCount && $orderResultCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買メンバ情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_member", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseACMemberRepository = $entityManager->getRepository(BackupRemiseACMember::class);
                $BackupRemiseACMemberArray = $BackupRemiseACMemberRepository->findAll();

                if ($BackupRemiseACMemberArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_member VALUES';
                    $insertCount = 0;

                    foreach ($BackupRemiseACMemberArray as $BackupRemiseACMember) {
                        if ($insertCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }
                        $sql .= "'" . $BackupRemiseACMember->getId() . "',";
                        $sql .= $BackupRemiseACMember->getTotal() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACMember->getCount()) . ",";
                        $sql .= $this->sqlFunctionBoolCheck($BackupRemiseACMember->isLimitless(), $dbPlatformName) . ",";
                        $sql .= $BackupRemiseACMember->getIntervalValue() . ",";
                        $sql .= "'" . $BackupRemiseACMember->getIntervalMark() . "',";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACMember->getDayOfMonth()) . ",";
                        $sql .= $BackupRemiseACMember->getAfterValue() . ",";
                        $sql .= "'" . $BackupRemiseACMember->getAfterMark() . "',";
                        $sql .= $BackupRemiseACMember->getSkip() . ",";
                        $sql .= $BackupRemiseACMember->getStop() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACMember->getUsageValue()) . ",";
                        $sql .= "'" . $BackupRemiseACMember->getUsageMark() . "',";
                        $sql .= "'" . $BackupRemiseACMember->getCardparts() . "',";
                        $sql .= "'" . $BackupRemiseACMember->getExpire() . "',";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getNextDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionBoolCheck($BackupRemiseACMember->isStatus(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionBoolCheck($BackupRemiseACMember->isSkipped(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getSkippedDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getSkippedNextDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getStartDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getStopDate(), $dbPlatformName) . ",";
                        $sql .= "'" . $BackupRemiseACMember->getNote() . "',";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACMember->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $insertCount ++;
                        if ($insertCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_member VALUES';
                            $insertCount = 0;
                        }
                    }
                    if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACMember');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // カード決済履歴詳細
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_order_result_card", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {
                $backupOrderResultCardRepository = $entityManager->getRepository(BackupOrderResultCard::class);
                $backupOrderResultCardArray = $backupOrderResultCardRepository->findAll();
                if ($backupOrderResultCardArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_order_result_card VALUES';
                    $orderResultCardCount = 0;

                    foreach ($backupOrderResultCardArray as $backupOrderResultCard) {
                        if ($orderResultCardCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupOrderResultCard->getId() . ",";
                        $sql .= $backupOrderResultCard->getState() . ",";
                        $sql .= "'" . $backupOrderResultCard->getJob() . "',";
                        $sql .= "'" . $backupOrderResultCard->getUsePayquickId() . "',";
                        $sql .= "'" . $backupOrderResultCard->getTranid() . "',";
                        $sql .= "'" . $backupOrderResultCard->getRCode() . "',";
                        $sql .= "'" . $backupOrderResultCard->getPayquickId() . "',";
                        $sql .= "'" . $backupOrderResultCard->getCardParts() . "',";
                        $sql .= "'" . $backupOrderResultCard->getExpire() . "',";
                        $sql .= "'" . $backupOrderResultCard->getName() . "',";
                        $sql .= "'" . $backupOrderResultCard->getCardBrand() . "',";
                        $sql .= "'" . $backupOrderResultCard->getMemberId() . "',";
                        $sql .= "" . $backupOrderResultCard->getAcTotal() . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getAcNextDate(), $dbPlatformName) . ",";
                        $sql .= "'" . $backupOrderResultCard->getAcInterval() . "',";
                        $sql .= "'" . $backupOrderResultCard->getPrevTranid() . "',";
                        $sql .= "" . $backupOrderResultCard->getPrevPaymentTotal() . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getResultDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getSalesDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getCancelDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCard->getUpdateDate(), $dbPlatformName) . "";
                        $sql .= ')';

                        $orderResultCardCount ++;
                        if ($orderResultCardCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                                ->prepare($sql)
                                ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_order_result_card VALUES';
                            $orderResultCardCount = 0;
                        }
                    }
                    if (0 < $orderResultCardCount && $orderResultCardCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResultCard');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // マルチ決済履歴詳細
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_order_result_cvs", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $backupOrderResultCvsRepository = $entityManager->getRepository(BackupOrderResultCvs::class);
                $backupOrderResultCvsArray = $backupOrderResultCvsRepository->findAll();

                if ($backupOrderResultCvsArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_order_result_cvs VALUES';
                    $orderResultCvsCount = 0;

                    foreach ($backupOrderResultCvsArray as $backupOrderResultCvs) {
                        if ($orderResultCvsCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupOrderResultCvs->getId() . ",";
                        $sql .= "'" . $backupOrderResultCvs->getJobid() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getRCode() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getPayWay() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getPayCsv() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getPayNo1() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getPayNo2() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getRecCvscode() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getRecCvsname() . "',";
                        $sql .= "'" . $backupOrderResultCvs->getRecScode() . "',";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getPayDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getRecDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getCenDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getReceiptDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupOrderResultCvs->getUpdateDate(), $dbPlatformName) . "";
                        $sql .= ')';

                        $orderResultCvsCount ++;
                        if ($orderResultCvsCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                                ->prepare($sql)
                                ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_order_result_cvs VALUES';
                            $orderResultCvsCount = 0;
                        }
                    }
                    if (0 < $orderResultCvsCount && $orderResultCvsCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupOrderResultCvs');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ペイクイック
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_payquick", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $backupPayquickRepository = $entityManager->getRepository(BackupPayquick::class);
                $backupPayquickArray = $backupPayquickRepository->findAll();

                if ($backupPayquickArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_payquick VALUES';
                    $payquickCount = 0;

                    foreach ($backupPayquickArray as $backupPayquick) {
                        if ($payquickCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupPayquick->getId() . ",";
                        $sql .= $backupPayquick->getCustomerId() . ",";
                        $sql .= "'" . $backupPayquick->getPayquickId() . "',";
                        $sql .= "'" . $backupPayquick->getCardParts() . "',";
                        $sql .= "'" . $backupPayquick->getExpire() . "',";
                        $sql .= "'" . $backupPayquick->getName() . "',";
                        $sql .= "'" . $backupPayquick->getCardBrand() . "',";
                        $sql .= "'" . $backupPayquick->getPrePayquickId() . "',";
                        $sql .= "'" . $backupPayquick->getPreCardParts() . "',";
                        $sql .= "'" . $backupPayquick->getPreExpire() . "',";
                        $sql .= "'" . $backupPayquick->getPreName() . "',";
                        $sql .= "'" . $backupPayquick->getPreCardBrand() . "',";
                        $sql .= $this->sqlFunctionToDate($backupPayquick->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupPayquick->getUpdateDate(), $dbPlatformName) . "";
                        $sql .= ')';

                        $payquickCount ++;
                        if ($payquickCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                                ->prepare($sql)
                                ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_payquick VALUES';
                            $payquickCount = 0;
                        }
                    }
                    if (0 < $payquickCount && $payquickCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupPayquick');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ販売種別
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_sale_type", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $backupRemiseSaleTypeRepository = $entityManager->getRepository(BackupRemiseSaleType::class);
                $backupRemiseSaleTypeArray = $backupRemiseSaleTypeRepository->findAll();

                if ($backupRemiseSaleTypeArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_sale_type VALUES';
                    $RemiseSaleTypeCount = 0;

                    foreach ($backupRemiseSaleTypeArray as $backupRemiseSaleType) {
                        if ($RemiseSaleTypeCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupRemiseSaleType->getId() . ",";
                        $sql .= "'" . $backupRemiseSaleType->getName() . "',";
                        $sql .= $backupRemiseSaleType->getSaleType() . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseSaleType->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseSaleType->getUpdateDate(), $dbPlatformName) . "";
                        $sql .= ')';

                        $RemiseSaleTypeCount ++;
                        if ($RemiseSaleTypeCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_sale_type VALUES';
                            $RemiseSaleTypeCount = 0;
                        }
                    }
                    if (0 < $RemiseSaleTypeCount && $RemiseSaleTypeCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseSaleType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買種別
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_type", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseACTypeRepository = $entityManager->getRepository(BackupRemiseACType::class);
                $BackupRemiseACTypeArray = $BackupRemiseACTypeRepository->findAll();

                if ($BackupRemiseACTypeArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_type VALUES';
                    $insertCount = 0;

                    foreach ($BackupRemiseACTypeArray as $BackupRemiseACType) {
                        if ($insertCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }
                        $sql .= $BackupRemiseACType->getId() . ",";
                        $sql .= "'" . $BackupRemiseACType->getName() . "',";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACType->getCount()) . ",";
                        $sql .= $this->sqlFunctionBoolCheck($BackupRemiseACType->isLimitless(), $dbPlatformName) . ",";
                        $sql .= $BackupRemiseACType->getIntervalValue() . ",";
                        $sql .= "'" . $BackupRemiseACType->getIntervalMark() . "',";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACType->getDayOfMonth()) . ",";
                        $sql .= $BackupRemiseACType->getAfterValue() . ",";
                        $sql .= "'" . $BackupRemiseACType->getAfterMark() . "',";
                        $sql .= $BackupRemiseACType->getSkip() . ",";
                        $sql .= $BackupRemiseACType->getStop() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACType->getUsageValue()) . ",";
                        $sql .= "'" . $BackupRemiseACType->getUsageMark() . "',";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACType->getSortNo()) . ",";
                        $sql .= $this->sqlFunctionBoolCheck($BackupRemiseACType->isVisible(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACType->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACType->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $insertCount ++;
                        if ($insertCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_type VALUES';
                            $insertCount = 0;
                        }
                    }
                    if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACType');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買商品情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_product", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseACProductRepository = $entityManager->getRepository(BackupRemiseACProduct::class);
                $BackupRemiseACProductArray = $BackupRemiseACProductRepository->findAll();

                if ($BackupRemiseACProductArray) {

                    if ('mysql' === $dbPlatformName)
                    {
                        // mysql
                        $sql = 'UPDATE dtb_product_class';
                        $sql .= ' INNER JOIN backup_plg_remise_payment4_remise_ac_product ON dtb_product_class.id = backup_plg_remise_payment4_remise_ac_product.id';
                        $sql .= ' SET';
                        $sql .= " dtb_product_class.remise_payment4_ac_actype_id = backup_plg_remise_payment4_remise_ac_product.ac_type_id,";
                        $sql .= " dtb_product_class.remise_payment4_ac_amount = backup_plg_remise_payment4_remise_ac_product.amount,";
                        $sql .= " dtb_product_class.remise_payment4_ac_point_flg = backup_plg_remise_payment4_remise_ac_product.point_flg";
                    }else{
                        // postgre
                        $sql = 'UPDATE dtb_product_class';
                        $sql .= ' SET';
                        $sql .= " remise_payment4_ac_actype_id = backup_plg_remise_payment4_remise_ac_product.ac_type_id,";
                        $sql .= " remise_payment4_ac_amount = backup_plg_remise_payment4_remise_ac_product.amount,";
                        $sql .= " remise_payment4_ac_point_flg = backup_plg_remise_payment4_remise_ac_product.point_flg";
                        $sql .= ' FROM  backup_plg_remise_payment4_remise_ac_product';
                        $sql .= ' WHERE dtb_product_class.id = backup_plg_remise_payment4_remise_ac_product.id';
                    }

                    $entityManager->getConnection()
                    ->prepare($sql)
                    ->execute();
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACProduct');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買結果情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_result", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseACResultRepository = $entityManager->getRepository(BackupRemiseACResult::class);
                $BackupRemiseACResultArray = $BackupRemiseACResultRepository->findAll();

                if ($BackupRemiseACResultArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_result VALUES';
                    $insertCount = 0;

                    foreach ($BackupRemiseACResultArray as $BackupRemiseACResult) {
                        if ($insertCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }
                        $sql .= $BackupRemiseACResult->getId() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACResult->getResult()) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACResult->getChargeDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACResult->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACResult->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $insertCount ++;
                        if ($insertCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_result VALUES';
                            $insertCount = 0;
                        }
                    }
                    if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買取込情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_import", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseACImportRepository = $entityManager->getRepository(BackupRemiseACImport::class);
                $BackupRemiseACImportArray = $BackupRemiseACImportRepository->findAll();

                if ($BackupRemiseACImportArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import VALUES';
                    $insertCount = 0;

                    foreach ($BackupRemiseACImportArray as $BackupRemiseACImport) {
                        if ($insertCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }
                        $sql .= $BackupRemiseACImport->getId() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getSuccessCnt()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getSuccessAmount()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getFailCnt()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getFailAmount()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getTotalCnt()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getTotalAmount()) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACImport->getExecDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getImportFlg()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseACImport->getImportCnt()) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACImport->getImportDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACImport->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseACImport->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $insertCount ++;
                        if ($insertCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import VALUES';
                            $insertCount = 0;
                        }
                    }
                    if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImport');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ消費税率設定情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_tax_rate", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            if ($stmt->fetchAllAssociative()) {

                $BackupRemiseTaxRateRepository = $entityManager->getRepository(BackupRemiseTaxRate::class);
                $BackupRemiseTaxRateArray = $BackupRemiseTaxRateRepository->findAll();

                if ($BackupRemiseTaxRateArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_tax_rate VALUES';
                    $insertCount = 0;

                    foreach ($BackupRemiseTaxRateArray as $BackupRemiseTaxRate) {
                        if ($insertCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }
                        $sql .= $BackupRemiseTaxRate->getId() . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseTaxRate->getTaxRate()) . ",";
                        $sql .= $this->sqlFunctionIntNullCheck($BackupRemiseTaxRate->getCalcRule()) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseTaxRate->getApplyDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseTaxRate->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($BackupRemiseTaxRate->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $insertCount ++;
                        if ($insertCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_tax_rate VALUES';
                            $insertCount = 0;
                        }
                    }
                    if (0 < $insertCount && $insertCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseTaxRate');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買バッチ取込管理情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_import_stack", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            $result = $stmt->fetchAllAssociative();

            if ($result) {
                $backupRemiseACImportStackRepository = $entityManager->getRepository(BackupRemiseACImportStack::class);
                $backupRemiseACImportStackArray = $backupRemiseACImportStackRepository->findAll();

                if ($backupRemiseACImportStackArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import_stack VALUES';
                    $remiseACImportStackCount = 0;

                    foreach ($backupRemiseACImportStackArray as $backupRemiseACImportStack) {
                        if ($remiseACImportStackCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupRemiseACImportStack->getId() . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseACImportStack->getImportDate(), $dbPlatformName) . ",";
                        $sql .= $backupRemiseACImportStack->getStartNum() . ",";
                        $sql .= $backupRemiseACImportStack->getEndNum() . ",";
                        $sql .= $backupRemiseACImportStack->getExecFlg() . ",";
                        $sql .= $backupRemiseACImportStack->getCompleteFlg() . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseACImportStack->getCreateDate(), $dbPlatformName) . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseACImportStack->getUpdateDate(), $dbPlatformName);
                        $sql .= ')';

                        $remiseACImportStackCount ++;

                        if ($remiseACImportStackCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import_stack VALUES';
                            $remiseACImportStackCount = 0;
                        }
                    }

                    if (0 < $remiseACImportStackCount && $remiseACImportStackCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStack');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            // ルミーズ定期購買バッチ取込情報
            $tableCheckSql = $this->sqlFunctionTableCheck("backup_plg_remise_payment4_remise_ac_import_stack_result", $dbPlatformName);
            $stmt = $entityManager->getConnection()->query($tableCheckSql);
            $result = $stmt->fetchAllAssociative();

            if ($result) {
                $backupRemiseACImportStackResultRepository = $entityManager->getRepository(BackupRemiseACImportStackResult::class);
                $backupRemiseACImportStackResultArray = $backupRemiseACImportStackResultRepository->findAll();

                if ($backupRemiseACImportStackResultArray) {
                    $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import_stack_result VALUES';
                    $RemiseACImportStackResultCount = 0;

                    foreach ($backupRemiseACImportStackResultArray as $backupRemiseACImportStackResult) {
                        if ($RemiseACImportStackResultCount == 0) {
                            $sql .= '(';
                        } else {
                            $sql .= ',(';
                        }

                        $sql .= $backupRemiseACImportStackResult->getId() . ",";
                        $sql .= $this->sqlFunctionToDate($backupRemiseACImportStackResult->getImportDate(), $dbPlatformName) . ",";
                        $sql .= "'" . $backupRemiseACImportStackResult->getRCode() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getMemberId() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getSKaiinNo() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcName() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcKana() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcTel() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcMail() . "',";
                        $sql .= $backupRemiseACImportStackResult->getAcAmount() . ",";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcResult() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getTranid() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getErrcode() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getErrinfo() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getAcNextDate() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getCard() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getExpire() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getNote() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getStatus() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getOrderId() . "',";
                        $sql .= "'" . $backupRemiseACImportStackResult->getOrderNo() . "'";
                        $sql .= ')';

                        $RemiseACImportStackResultCount ++;

                        if ($RemiseACImportStackResultCount == $this->insetMaxCount) {
                            $entityManager->getConnection()
                            ->prepare($sql)
                            ->execute();
                            $sql = 'INSERT INTO plg_remise_payment4_remise_ac_import_stack_result VALUES';
                            $RemiseACImportStackResultCount = 0;
                        }
                    }

                    if (0 < $RemiseACImportStackResultCount && $RemiseACImportStackResultCount < $this->insetMaxCount) {
                        $entityManager->getConnection()
                        ->prepare($sql)
                        ->execute();
                    }
                }

                // バックアップ用テーブルの削除
                $bkupMetas = [];
                $bkupMetas[] = $entityManager->getMetadataFactory()->getMetadataFor('Plugin\RemisePayment42\EntityBackup\BackupRemiseACImportStackResult');
                $schemaTool = new SchemaTool($entityManager);
                $schemaTool->dropSchema($bkupMetas);
                $entityManager->flush();
            }

            log_info('[Remise] table import -- Done');
        }
        catch (\Exception $e)
        {
            log_error('[Remise] table import -- Error', [$e]);
        }

        return;
    }

    /**
     * SQL文のTO_DATE関数を作成する。
     *
     * @param $date
     * @param $dbPlatformName
     *
     * @return $sql
     *
     */
    private function sqlFunctionToDate($date, $dbPlatformName)
    {
        $sql = "";

        if($date != null && $date !="")
        {
            if ('mysql' === $dbPlatformName)
            {
                // mysql
                $sql = "STR_TO_DATE('".gmdate('Y-m-d H:i:s', strtotime($date->format('Y-m-d H:i:s')))."','%Y-%c-%d %H:%i:%S')";
            }else{
                // postgre
                $sql = "TO_TIMESTAMP('".gmdate('Y-m-d H:i:s', strtotime($date->format('Y-m-d H:i:s')))."','YYYY-MM-DD HH24:MI:SS')";
            }
        }
        else
       {
           $sql = "NULL";
        }

        return $sql;
    }

    /**
     * SQL文のTO_DATE関数を作成する。
     *
     * @param
     *            $date
     * @param
     *            $dbPlatformName
     *
     * @return $sql
     *
     */
    private function sqlFunctionIntNullCheck($int)
    {
        if ($int != null && $int != "") {
            return $int;
        } else {
            return "NULL";
        }
    }

    /**
     * SQL文のTO_DATE関数を作成する。
     *
     * @param
     *            $date
     * @param
     *            $dbPlatformName
     *
     * @return $sql
     *
     */
    private function sqlFunctionBoolCheck($bool, $dbPlatformName)
    {
        if ('mysql' === $dbPlatformName)
        {
            if ($bool == true) {
                return 1;
            } else {
                return 0;
            }
        }else{
            if ($bool == true) {
                return 'true';
            } else {
                return 'false';
            }
        }
    }

    /**
     * テーブルの存在チェックSQLを生成する。
     *
     * @param $tableName
     * @param $dbPlatformName
     *
     * @return $sql
     *
     */
    private function sqlFunctionTableCheck($tableName, $dbPlatformName)
    {
        $sql = "";

        if ('mysql' === $dbPlatformName)
        {
            // mysql
            $sql = "SHOW TABLES LIKE '".$tableName."'";
        }else{
            // postgre
            $sql = "SELECT relname FROM pg_class WHERE relkind = 'r' AND relname = '".$tableName."'";
        }

        return $sql;
    }

    private function createPage(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // ページデータ取得
        $pageDataArray = $this->getPageData();

        // ページを追加
        foreach ($pageDataArray as $pageData) {
            $Page = $entityManager->getRepository(Page::class)->findOneBy(['url' => $pageData['url']]);
            if (null === $Page) {
                $this->createPageSub($entityManager, $pageData);
            }
        }

    }

    private function createPageSub($entityManager, $pageData)
    {
        $Page = new Page();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName($pageData['name']);
        $Page->setUrl($pageData['url']);
        $Page->setFileName($pageData['fileName']);

        // DB登録
        $entityManager->persist($Page);
        $entityManager->flush($Page);
        $Layout = $entityManager->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
        ->setPageId($Page->getId())
        ->setLayout($Layout)
        ->setLayoutId($Layout->getId())
        ->setSortNo(0);
        $entityManager->persist($PageLayout);
        $entityManager->flush($PageLayout);
    }

    private function removePage(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // ページデータ取得
        $pageDataArray = $this->getPageData();

        // ページを削除
        foreach ($pageDataArray as $pageData) {
            $Page = $entityManager->getRepository(Page::class)->findOneBy(['url' => $pageData['url']]);
            if ($Page) {
                $this->removePageSub($entityManager, $pageData['url']);
            }
        }

    }

    private function removePageSub(EntityManagerInterface $em, $url)
    {
        $Page = $em->getRepository(Page::class)->findOneBy(['url' => $url]);
        if (!$Page) {
            return;
        }
        foreach ($Page->getPageLayouts() as $PageLayout) {
            $em->remove($PageLayout);
            $em->flush($PageLayout);
        }
        $em->remove($Page);
        $em->flush($Page);
    }

    private function getPageData(){
        $pageDataArray = array();
        $pageDataArray[] = ['url' => 'remise_payment4_ac_mypage_card_update',
            'name'=> 'ルミーズ決済プラグイン　マイページ/ご注文履歴詳細/定期購買登録カード情報更新',
            'fileName' => '@RemisePayment42/sub_mypage_ac_card_update'
        ];
        $pageDataArray[] = ['url' => 'remise_payment4_ac_mypage_card_update_return',
            'name'=> 'ルミーズ決済プラグイン　マイページ/ご注文履歴詳細/定期購買登録カード情報更新（リダイレクト用）',
            'fileName' => '@RemisePayment42/sub_mypage_ac_card_update'
        ];
        $pageDataArray[] = ['url' => 'remise_payment4_ac_mypage_skip',
            'name'=> 'ルミーズ決済プラグイン　マイページ/ご注文履歴詳細/定期購買スキップ',
            'fileName' => '@RemisePayment42/sub_mypage_ac_skip'
        ];
        $pageDataArray[] = ['url' => 'remise_payment4_ac_mypage_cancel',
            'name'=> 'ルミーズ決済プラグイン　マイページ/ご注文履歴詳細/定期購買解約',
            'fileName' => '@RemisePayment42/sub_mypage_ac_cancel'
        ];
        $pageDataArray[] = ['url' => 'remise_payment4_ac_mypage_cancel_return',
            'name'=> 'ルミーズ決済プラグイン　マイページ/ご注文履歴詳細/定期購買解約（リダイレクト用）',
            'fileName' => '@RemisePayment42/sub_mypage_ac_cancel'
        ];
        $pageDataArray[] = ['url' => 'remise_shopping',
            'name'=> 'ルミーズ決済プラグイン　商品購入（定期購買情報）',
            'fileName' => '@RemisePayment42/sub_shopping_index_autocharge'
        ];
        $pageDataArray[] = ['url' => 'remise_shopping_complete',
            'name'=> 'ルミーズ決済プラグイン　商品購入/ご注文完了（定期購買情報）',
            'fileName' => '@RemisePayment42/sub_shopping_complete_autocharge'
        ];

        return $pageDataArray;
    }

    /**
     * EC-CUBEメールテンプレートの作成
     *
     */
    private function createMailTemplate(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // ルミーズメールテンプレート
        $kindArray = Array();
        // 入金のお知らせメール
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.acpt');
        // 定期購買注文受付メール
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.ac_order');
        // 定期購買カード情報更新通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.mypage.card_update');
        // 定期購買解約通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.mypage.cancel');
        // 定期購買スキップ通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.mypage.skip');
        // 定期購買結果取込通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.result_import.success');
        // 定期購買結果取込エラー通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.result_import.error');
        // 定期購買結果取込受付メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.result_import_stack.accept');
        // 定期購買結果バッチ取込エラー通知メール（REMISE定期購買）
        $kindArray[] = $this->trans('remise_payment4.common.label.mail_template.kind.result_import_stack.error');

        foreach ($kindArray as $kind) {

            // ルミーズメールテンプレートの取得
            $remiseMailTemplateRepository = $entityManager->getRepository(RemiseMailTemplate::class);
            $RemiseMailTemplate = $remiseMailTemplateRepository->findOneByKind($kind);

            // EC-CUBEメールテンプレート
            $MailTemplate = null;

            // ルミーズメールテンプレートが未登録の場合
            if (!$RemiseMailTemplate)
            {
                // EC-CUBEメールテンプレートの新規生成
                $MailTemplate = $this->createMailTemplateSub($kind);

                // EC-CUBEメールテンプレートの登録
                $entityManager->persist($MailTemplate);
                $entityManager->flush($MailTemplate);

                // ルミーズメールテンプレートの新規作成
                $RemiseMailTemplate = new RemiseMailTemplate();
                $RemiseMailTemplate->setId($MailTemplate->getId());
                $RemiseMailTemplate->setKind($kind);
                $RemiseMailTemplate->setCreateDate(new \DateTime());
            }
            // ルミーズメールテンプレートが登録済の場合
            else
          {
                // EC-CUBEメールテンプレートの取得
                $MailTemplateRepository = $entityManager->getRepository(MailTemplate::class);
                $MailTemplate = $MailTemplateRepository->find($RemiseMailTemplate->getId());

                // 取得できなかった場合
                if (!$MailTemplate)
                {
                    // EC-CUBEメールテンプレートの新規生成
                    $MailTemplate = $this->createMailTemplateSub($kind);

                    // EC-CUBEメールテンプレートの登録
                    $entityManager->persist($MailTemplate);
                    $entityManager->flush($MailTemplate);

                    $RemiseMailTemplate->setId($MailTemplate->getId());
                }
            }

            // ルミーズメールテンプレートの更新
            $RemiseMailTemplate->setUpdateDate(new \DateTime());

            // ルミーズメールテンプレートの登録
            $entityManager->persist($RemiseMailTemplate);
            $entityManager->flush($RemiseMailTemplate);
        }
    }

    /**
     * EC-CUBEメールテンプレートのEntity作成
     *
     */
    private function createMailTemplateSub($kind)
    {
        $MailTemplate = new MailTemplate();

        switch ($kind) {
            case $this->trans('remise_payment4.common.label.mail_template.kind.acpt'):
                // 入金お知らせメール（REMISEマルチ決済）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.acpt'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/order_acpt.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.acpt'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.ac_order'):
                // 定期購買注文受付メールREMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.ac_order'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_order.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.ac_order'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.mypage.card_update'):
                // 定期購買カード情報更新通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.mypage.card_update'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_card_update_complete.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.mypage.card_update'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case  $this->trans('remise_payment4.common.label.mail_template.kind.mypage.cancel'):
                // 定期購買解約通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.mypage.cancel'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_cancel_complete.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.mypage.cancel'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.mypage.skip'):
                    // 定期購買スキップ通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.mypage.skip'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_skip_complete.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.mypage.skip'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.result_import.success'):
                // 定期購買結果取込通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.result_import.success'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_complete.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.result_import.success'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.result_import.error'):
                // 定期購買結果取込エラー通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.result_import.error'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_error.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.result_import.error'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.result_import_stack.accept'):
                // 定期購買結果取込受付メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.result_import_stack.accept'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_stack_accept.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.result_import_stack.accept'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
            case $this->trans('remise_payment4.common.label.mail_template.kind.result_import_stack.error'):
                // 定期購買結果バッチ取込エラー通知メール（REMISE定期購買）
                $MailTemplate->setName($this->trans('remise_payment4.common.label.mail_template.name.result_import_stack.error'));
                $MailTemplate->setFileName('RemisePayment42/Resource/template/mail/ac_result_import_stack_error.twig');
                $MailTemplate->setMailSubject($this->trans('remise_payment4.common.label.mail_template.subject.result_import_stack.error'));
                $MailTemplate->setCreateDate(new \DateTime());
                $MailTemplate->setUpdateDate(new \DateTime());
                break;
        }

        return $MailTemplate;
    }

    /**
     * EC-CUBEメールテンプレートの削除
     *
     */
    private function removeMailTemplate(array $meta, ContainerInterface $container)
    {

        $entityManager = $container->get('doctrine')->getManager();

        // ルミーズメールテンプレート
        $remiseMailTemplateRepository = $entityManager->getRepository(RemiseMailTemplate::class);
        $RemiseMailTemplates = $remiseMailTemplateRepository->findAll();

        $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);

        if ($RemiseMailTemplates) {
            foreach ($RemiseMailTemplates as $RemiseMailTemplate) {
                $MailTemplate = $mailTemplateRepository->find($RemiseMailTemplate->getId());
                if($MailTemplate){
                    $entityManager->remove($MailTemplate);
                    $entityManager->flush($MailTemplate);
                }
            }
        }

    }

    /**
     * CSVの作成
     *
     */
    private function createCSV(array $meta, ContainerInterface $container)
    {
        $csvArray = array();
        // 定期購買受注情報
        $csvArray[] = array('type' => $this->trans('remise_payment4.ac.csv_type'),
                             'name'=> $this->trans('remise_payment4.ac.csv_type.name'));
        // 自動継続課金取込結果
        $csvArray[] = array('type' => $this->trans('remise_payment4.ac.csv_type.ac_result'),
                             'name' => $this->trans('remise_payment4.ac.csv_type.ac_result.name'));

        foreach($csvArray as $csv){
            // CSV種別設定
            $this->insertRemiseCsvType($csv['type'], $csv['name'], $container);
        }

        // 定期購買受注情報 CSV項目設定
        $this->insertCsv($container);

        // 自動継続課金取込結果 CSV項目設定
        $this->insertCsvAcResult($container);

    }

    /**
     * ルミーズCSV種別の追加
     */
    private function insertRemiseCsvType($type, $name, $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $remiseCsvTypeRepository = $entityManager->getRepository(RemiseCsvType::class);
        $csvTypeRepository = $entityManager->getRepository(CsvType::class);

        // ルミーズCSV種別の既存レコード確認
        $RemiseCsvType = $remiseCsvTypeRepository->findOneBy([
            'csv_type' => $type
        ], [
            'id' => 'ASC'
        ]);

        // 存在する場合
        if ($RemiseCsvType) {

            // ルミーズCSV種別に紐づくCSV種別マスタを取得
            $CsvType = $csvTypeRepository->findOneBy([
                'id' => $RemiseCsvType->getId()
            ]);

            // CSV種別マスタが存在しない場合、ルミーズCSV種別を削除
            if (! $CsvType) {
                $entityManager->remove($RemiseCsvType);
                $entityManager->flush();
            } else {
                return;
            }
        }

        // CSV種別マスタのID最大値取得
        $CsvTypeIdMax = $csvTypeRepository->findOneBy([], [
            'id' => 'DESC'
        ]);
        // CSV種別マスタのソートNo最大値取得
        $CsvTypeSortNoMax = $csvTypeRepository->findOneBy([], [
            'sort_no' => 'DESC'
        ]);

        // CSV種別マスタ追加
        $CsvType = new CsvType();
        $CsvType->setId($CsvTypeIdMax->getId() + 1);
        $CsvType->setSortNo($CsvTypeSortNoMax->getSortNo() + 1);
        $CsvType->setName($name);
        $entityManager->persist($CsvType);
        $entityManager->flush($CsvType);

        // ルミーズCSV種別追加
        $RemiseCsvType = new RemiseCsvType();
        $RemiseCsvType->setId($CsvType->getId());
        $RemiseCsvType->setName($name);
        $RemiseCsvType->setCsvType($type);
        $RemiseCsvType->setCreateDate(new \DateTime());
        $RemiseCsvType->setUpdateDate(new \DateTime());
        $entityManager->persist($RemiseCsvType);
        $entityManager->flush($RemiseCsvType);

        return;
    }

    /**
     * CSV項目の追加(定期購買受注情報)
     */
    private function insertCsv($container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $remiseCsvTypeRepository = $entityManager->getRepository(RemiseCsvType::class);
        $csvTypeRepository = $entityManager->getRepository(CsvType::class);
        $csvRepository = $entityManager->getRepository(Csv::class);

        // ルミーズCSV種別の既存レコード取得(1件目の自動登録されたレコードのみを対象にする。)
        $RemiseCsvType = $remiseCsvTypeRepository->findOneBy([
            'csv_type' => $this->trans('remise_payment4.ac.csv_type')
        ], [
            'id' => 'ASC'
        ]);

        // ルミーズCSV種別に紐づくCSV種別マスタを取得
        $csvType = $csvTypeRepository->findOneBy([
            'id' => $RemiseCsvType->getId()
        ]);

        // ルミーズCSV種別に紐づくCSVを取得
        $csvs = $csvRepository->findBy([
            'CsvType' => $csvType
        ]);

        // CSV項目が存在した場合、何もしない
        if ($csvs) {
            return;
        }

        $sort_no = 0;
        while(true) {
            $sort_no = $sort_no + 1;
            $messageKey = 'remise_payment4.ac.csv_items.active.'.$sort_no.'.entity_name';
            $entity_name = $this->trans('remise_payment4.ac.csv_items.active.'.$sort_no.'.entity_name');
            if (strcmp($entity_name,$messageKey) != 0)
            {
                $field_name = $this->trans('remise_payment4.ac.csv_items.active.'.$sort_no.'.field_name');
                $disp_name = $this->trans('remise_payment4.ac.csv_items.active.'.$sort_no.'.disp_name');
                $reference_field_name = $this->trans('remise_payment4.ac.csv_items.active.'.$sort_no.'.reference_field_name');

                $newCsv = new Csv();
                $newCsv->setEntityName($entity_name);
                $newCsv->setFieldName($field_name);
                $newCsv->setDispName($disp_name);
                if(strcmp($reference_field_name,'remise_payment4.ac.csv_items.active.'.$sort_no.'.reference_field_name') != 0){
                    $newCsv->setReferenceFieldName($reference_field_name);
                }
                $newCsv->setSortNo($sort_no);
                $newCsv->setCsvType($csvType);
                $newCsv->setEnabled(true);
                $entityManager->persist($newCsv);
                $entityManager->flush();
            }else{
                break;
            }
        }

        $sort_no = 0;
        while(true) {
            $sort_no = $sort_no + 1;
            $messageKey = 'remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.entity_name';
            $entity_name = $this->trans('remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.entity_name');
            if (strcmp($entity_name,$messageKey) != 0)
            {
                $field_name = $this->trans('remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.field_name');
                $disp_name = $this->trans('remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.disp_name');
                $reference_field_name = $this->trans('remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.reference_field_name');

                $newCsv = new Csv();
                $newCsv->setEntityName($entity_name);
                $newCsv->setFieldName($field_name);
                $newCsv->setDispName($disp_name);
                if(strcmp($reference_field_name,'remise_payment4.ac.csv_items.nonactive.'.$sort_no.'.reference_field_name') != 0){
                    $newCsv->setReferenceFieldName($reference_field_name);
                }
                $newCsv->setSortNo($sort_no);
                $newCsv->setCsvType($csvType);
                $newCsv->setEnabled(false);
                $entityManager->persist($newCsv);
                $entityManager->flush();
            }else{
                break;
            }
        }

        return;
    }

    /**
     * CSV項目の追加(自動継続課金結果取込)
     */
    private function insertCsvAcResult($container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $remiseCsvTypeRepository = $entityManager->getRepository(RemiseCsvType::class);
        $csvTypeRepository = $entityManager->getRepository(CsvType::class);
        $csvRepository = $entityManager->getRepository(Csv::class);

        // ルミーズCSV種別の既存レコード取得(1件目の自動登録されたレコードのみを対象にする。)
        $RemiseCsvType = $remiseCsvTypeRepository->findOneBy([
            'csv_type' => $this->trans('remise_payment4.ac.csv_type.ac_result')
        ], [
            'id' => 'ASC'
        ]);

        // ルミーズCSV種別に紐づくCSV種別マスタを取得
        $csvType = $csvTypeRepository->findOneBy([
            'id' => $RemiseCsvType->getId()
        ]);

        // ルミーズCSV種別に紐づくCSVを取得
        $csvs = $csvRepository->findBy([
            'CsvType' => $csvType
        ]);

        // CSV項目が存在した場合、何もしない
        if ($csvs) {
            return;
        }

        $sort_no = 0;
        while(true) {
            $sort_no = $sort_no + 1;
            $messageKey = 'remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.entity_name';
            $entity_name = $this->trans('remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.entity_name');
            if (strcmp($entity_name,$messageKey) != 0)
            {
                $field_name = $this->trans('remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.field_name');
                $disp_name = $this->trans('remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.disp_name');
                $reference_field_name = $this->trans('remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.reference_field_name');

                $newCsv = new Csv();
                $newCsv->setEntityName($entity_name);
                $newCsv->setFieldName($field_name);
                $newCsv->setDispName($disp_name);
                if(strcmp($reference_field_name,'remise_payment4.ac.csv_ac_result_items.active.'.$sort_no.'.reference_field_name') != 0){
                    $newCsv->setReferenceFieldName($reference_field_name);
                }
                $newCsv->setSortNo($sort_no);
                $newCsv->setCsvType($csvType);
                $newCsv->setEnabled(true);
                $entityManager->persist($newCsv);
                $entityManager->flush();
            }else{
                break;
            }
        }

        return;
    }

    /**
     * CSVの削除
     *
     */
    private function removeCSV(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // ルミーズCSV種別の既存レコード取得(1件目の自動登録されたレコードのみを対象にする。)
        $remiseCsvTypeRepository = $entityManager->getRepository(RemiseCsvType::class);
        $RemiseCsvTypes = $remiseCsvTypeRepository->findAll();

        foreach($RemiseCsvTypes as $RemiseCsvType) {
            // ルミーズCSV種別に紐づくCSV種別マスタを取得
            $csvTypeRepository = $entityManager->getRepository(CsvType::class);
            $csvType = $csvTypeRepository->findOneBy([
                'id' => $RemiseCsvType->getId()

            ]);

            if($csvType) {
                // ルミーズCSV種別に紐づくCSVを取得
                $csvRepository = $entityManager->getRepository(Csv::class);
                $csvs = $csvRepository->findBy([
                    'CsvType' => $csvType
                ]);

                foreach ($csvs as $csv) {
                    $entityManager->remove($csv);
                    $entityManager->flush($csv);
                }

                $entityManager->remove($csvType);
                $entityManager->flush($csvType);
            }
        }
    }

    /**
     * ルミーズ消費税率設定情報の追加
     *
     */
    private function insertRemiseTaxRate(array $meta, ContainerInterface $container)
    {
        try {
            $entityManager = $container->get('doctrine')->getManager();
            $RemiseTaxRateRepository = $entityManager->getRepository(RemiseTaxRate::class);
            $RemiseTaxRateArray = $RemiseTaxRateRepository->findAll();

            // 存在する場合
            if ($RemiseTaxRateArray) {
                return;
            }

            // ルミーズ消費税率設定情報追加
            // 税率8%
            $RemiseTaxRate = new RemiseTaxRate();
            $RemiseTaxRate->setTaxRate(8);
            $RemiseTaxRate->setCalcRule(1);
            $RemiseTaxRate->setApplyDate(new \DateTime("2014-04-01 00:00:00"));
            $RemiseTaxRate->setCreateDate(new \DateTime());
            $RemiseTaxRate->setUpdateDate(new \DateTime());
            $entityManager->persist($RemiseTaxRate);
            $entityManager->flush();

            // 税率10%
            $RemiseTaxRate = new RemiseTaxRate();
            $RemiseTaxRate->setTaxRate(10);
            $RemiseTaxRate->setCalcRule(1);
            $RemiseTaxRate->setApplyDate(new \DateTime("2019-10-01 00:00:00"));
            $RemiseTaxRate->setCreateDate(new \DateTime());
            $RemiseTaxRate->setUpdateDate(new \DateTime());
            $entityManager->persist($RemiseTaxRate);
            $entityManager->flush();

        } catch (\Exception $e) {
            // 独自プラグインのアップデートを行うと何故かこける。
            log_error('[Remise] table import -- Error', [$e]);
            return;
        }

        return;
    }

    /**
     * 自動継続課金の受注取込設定
     *
     * @param   $meta
     * @param   $container
     *
     */
    private function setAcAcquisitionMethod(array $meta, ContainerInterface $container)
    {
        try {
            $entityManager = $container->get('doctrine')->getManager();

            // プラグイン設定情報の取得
            $ConfigRepository = $entityManager->getRepository(Config::class);
            $Config = $ConfigRepository->findOneByCode(trans('remise_payment4.common.label.plugin_code'));

            if (!$Config) {
                return;
            }

            // プラグイン設定詳細情報の取得
            $ConfigInfo = $Config->getUnserializeInfo();

            if (!$ConfigInfo) {
                return;
            }

            // 取込設定なしの場合、「リアルタイム取込」を設定
            if (empty($ConfigInfo->getAcAcquisitionMethod())) {
                $ConfigInfo->setAcAcquisitionMethod('real');
                $Config->setSerializeInfo($ConfigInfo);
                $entityManager->persist($Config);
                $entityManager->flush();
            }
        } catch (\Exception $e) {
            log_error('[Remise] Set AcAcquisitionMethod -- Error', [$e]);
            return;
        }

        return;
    }

    /**
     * messages.ja.yamlの読み込み
     *
     */
    private function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        $input = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . "Resource" . DIRECTORY_SEPARATOR . "locale" . DIRECTORY_SEPARATOR . "messages.ja.yaml");
        $result = \Symfony\Component\Yaml\Yaml::parse($input);
        if(isset($result[$id])) {
            return $result[$id];
        } else if(isset($result[0]) && isset($result[0][$id])) {
            return $result[0][$id];
        } else {
            return $id;
        }
    }

    /**
     * マイグレーションの実行
     *
     */
    private function executeMigrations(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->migration($entityManager->getConnection(), $meta['code']);
    }
}
