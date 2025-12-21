<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Serializer;

use JsonException;
use RuntimeException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use Spudifull\PhpWorkflowEngine\Domain\Contract\DomainEventInterface;
use Spudifull\PhpWorkflowEngine\Domain\Contract\EventSerializerInterface;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowFailed;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;

final class EventSerializer implements EventSerializerInterface
{
    private const array EVENT_TYPE_MAP = [
        'workflow.started' => WorkflowStarted::class,
        'workflow.failed' => WorkflowFailed::class,
        'workflow.completed' => WorkflowCompleted::class
    ];

    private Serializer $serializer;

    private array $classToTypeMap;

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

        $this->classToTypeMap = array_flip(self::EVENT_TYPE_MAP);
    }

    /**
     * @param DomainEventInterface $event
     * @return string
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function serialize(DomainEventInterface $event): string
    {
        $className = get_class($event);

        if (!isset($this->classToTypeMap[$className])) {
            throw new RuntimeException(
                sprintf('Event class %s is not registered in EVENT_TYPE_MAP', $className)
            );
        }

        $eventData = $this->serializer->normalize($event, 'json');

        $envelope = [
            'type' => $this->classToTypeMap[$className],
            'data' => $eventData,
        ];

        return json_encode($envelope, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $data
     * @return DomainEventInterface
     * @throws ExceptionInterface
     * @throws JsonException
     */
    public function deserialize(string $data): DomainEventInterface
    {
        $envelope = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($envelope['type'])) {
            throw new RuntimeException('Event type is missing in serialized data');
        }

        if (!isset($envelope['data'])) {
            throw new RuntimeException('Event data is missing in serialized data');
        }

        $eventType = $envelope['type'];

        if (!isset(self::EVENT_TYPE_MAP[$eventType])) {
            throw new RuntimeException(
                sprintf('Unknown event type: %s', $eventType)
            );
        }

        $className = self::EVENT_TYPE_MAP[$eventType];

        $jsonData = json_encode($envelope['data'], JSON_THROW_ON_ERROR);

        /** @var DomainEventInterface */
        return $this->serializer->deserialize($jsonData, $className, 'json');
    }
}
