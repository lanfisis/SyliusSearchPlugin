<?php

/*
 * This file is part of Monsieur Biz' Search plugin for Sylius.
 *
 * (c) Monsieur Biz <sylius@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSearchPlugin\AutoMapper;

use Jane\Bundle\AutoMapperBundle\Configuration\MapperConfigurationInterface;
use Jane\Component\AutoMapper\MapperGeneratorMetadataInterface;
use Jane\Component\AutoMapper\MapperMetadata;
use MonsieurBiz\SyliusSearchPlugin\AutoMapper\ProductAttributeValueReader\ReaderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;

final class ProductAttributeValueConfiguration implements MapperConfigurationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Configuration $configuration;

    /**
     * @var ReaderInterface[]
     */
    private array $productAttributeValueReaders;

    public function __construct(Configuration $configuration, iterable $productAttributeValueReaders)
    {
        $this->logger = new NullLogger();
        $this->configuration = $configuration;
        $this->productAttributeValueReaders = $productAttributeValueReaders instanceof \Traversable
            ? iterator_to_array($productAttributeValueReaders)
            : $productAttributeValueReaders;
    }

    public function process(MapperGeneratorMetadataInterface $metadata): void
    {
        if (!$metadata instanceof MapperMetadata) {
            return;
        }
        if (0 === \count($this->productAttributeValueReaders)) {
            throw new RuntimeException('Undefined product attribute value reader');
        }

        $metadata->forMember('value', function (ProductAttributeValueInterface $productAttributeValue) {
            if (null === $productAttributeValue->getType()) {
                return null;
            }
            if (!\array_key_exists($productAttributeValue->getType(), $this->productAttributeValueReaders)) {
                $this->logger->alert(sprintf('Missing product attribute value reader for "%s" type', $productAttributeValue->getType()));

                return null;
            }
            $reader = $this->productAttributeValueReaders[$productAttributeValue->getType()];

            return $reader->getValue($productAttributeValue);
        });
    }

    public function getSource(): string
    {
        return $this->configuration->getSourceClass('product_attribute_value');
    }

    public function getTarget(): string
    {
        return $this->configuration->getTargetClass('product_attribute');
    }
}
