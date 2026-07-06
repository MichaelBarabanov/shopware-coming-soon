<?php declare(strict_types=1);

namespace Micha\ComingSoon;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class MichaComingSoon extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        if ($this->container === null) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement(
            'DELETE FROM `system_config` WHERE `configuration_key` LIKE :configKey',
            ['configKey' => 'MichaComingSoon.config.%']
        );
    }
}
