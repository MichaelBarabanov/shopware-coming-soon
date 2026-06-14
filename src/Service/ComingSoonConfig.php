<?php declare(strict_types=1);

namespace Micha\ComingSoon\Service;

final class ComingSoonConfig
{
    /**
     * @param string[] $ipWhitelist
     * @param string[] $comingSoonPaths
     */
    public function __construct(
        public readonly bool $active,
        public readonly ?string $title,
        public readonly ?string $message,
        public readonly ?string $backgroundMediaId,
        public readonly ?string $logoMediaId,
        public readonly string $accentColor,
        public readonly bool $countdownActive,
        public readonly ?\DateTimeImmutable $countdownDate,
        public readonly bool $autoEndOnCountdown,
        public readonly array $ipWhitelist,
        public readonly array $comingSoonPaths,
        public readonly ?string $previewToken
    ) {
    }

    public function isCountdownExpired(): bool
    {
        return $this->countdownDate !== null
            && $this->countdownDate <= new \DateTimeImmutable();
    }

    /**
     * Once auto-end is enabled and the launch date has passed, the shop opens
     * completely - regardless of the channel toggle or any configured paths.
     */
    public function isOpenedByCountdown(): bool
    {
        return $this->autoEndOnCountdown && $this->isCountdownExpired();
    }
}
