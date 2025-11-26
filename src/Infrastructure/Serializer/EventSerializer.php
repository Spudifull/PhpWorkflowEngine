<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Serializer;

use Spudifull\PhpWorkflowEngine\Domain\Contract\DomainEventInterface;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class EventSerializer
{
    private Serializer $serializer;

    public function __construct()
    {
        $typeExtractor = new ReflectionExtractor();

        $normalizers = [
            new WorkflowIdNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                null,
                null,
                null,
                $typeExtractor
            ),
        ];

        $this->serializer = new Serializer($normalizers, [new JsonEncoder()]);
    }

    /**
     * @param DomainEventInterface $event
     * @return string
     * @throws ExceptionInterface
     */
    public function serialize(DomainEventInterface $event): string
    {
        return $this->serializer->serialize($event, 'json');
    }

    /**
     * @param string $json
     * @param string $className
     * @return DomainEventInterface
     * @throws ExceptionInterface
     */
    public function deserialize(string $json, string $className): DomainEventInterface
    {
        /** @var DomainEventInterface */
        return $this->serializer->deserialize($json, $className, 'json');
    }
}
