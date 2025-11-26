<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Serializer;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class WorkflowIdNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string|null $format
     * @param array $context
     * @return string
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        /** @var WorkflowId $data */
        return (string) $data;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return WorkflowId
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): WorkflowId
    {
        return new WorkflowId((string) $data);
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array $context
     * @return bool
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof WorkflowId;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return bool
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === WorkflowId::class;
    }

    /**
     * @param string|null $format
     * @return true[]
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            WorkflowId::class => true,
        ];
    }
}