<?php declare(strict_types=1);

namespace Micha\ComingSoon\Service;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ComingSoonService
{
    public const CONFIG_DOMAIN = 'MichaComingSoon.config.';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $mediaRepository
    ) {
    }

    public function getConfig(?string $salesChannelId): ComingSoonConfig
    {
        return new ComingSoonConfig(
            $this->getBool('active', $salesChannelId),
            $this->getTrimmedString('title', $salesChannelId),
            $this->getTrimmedString('message', $salesChannelId),
            $this->getTrimmedString('backgroundMedia', $salesChannelId),
            $this->getTrimmedString('logoMedia', $salesChannelId),
            $this->getTrimmedString('accentColor', $salesChannelId) ?? '#2c84dd',
            $this->getBool('countdownActive', $salesChannelId),
            $this->parseDate($this->getTrimmedString('countdownDate', $salesChannelId)),
            $this->getBool('autoEndOnCountdown', $salesChannelId),
            $this->parseLines($this->getTrimmedString('ipWhitelist', $salesChannelId)),
            $this->parseLines($this->getTrimmedString('comingSoonPaths', $salesChannelId)),
            $this->getTrimmedString('previewToken', $salesChannelId)
        );
    }

    public function getMediaUrl(?string $mediaId, Context $context): ?string
    {
        if ($mediaId === null || !Uuid::isValid($mediaId)) {
            return null;
        }

        /** @var MediaEntity|null $media */
        $media = $this->mediaRepository->search(
            new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria([$mediaId]),
            $context
        )->first();

        if ($media === null) {
            return null;
        }

        $url = $media->getUrl();

        return $url !== '' ? $url : null;
    }

    private function getBool(string $key, ?string $salesChannelId): bool
    {
        return (bool) $this->systemConfigService->get(self::CONFIG_DOMAIN . $key, $salesChannelId);
    }

    private function getTrimmedString(string $key, ?string $salesChannelId): ?string
    {
        $value = $this->systemConfigService->get(self::CONFIG_DOMAIN . $key, $salesChannelId);

        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Splits a textarea value (newline-, comma- or semicolon-separated) into a
     * unique list of trimmed, non-empty entries.
     *
     * @return string[]
     */
    private function parseLines(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $entries = preg_split('/[\s,;]+/', $value, -1, \PREG_SPLIT_NO_EMPTY);

        if ($entries === false) {
            return [];
        }

        return array_values(array_unique(array_map('trim', $entries)));
    }
}
