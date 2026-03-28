<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\Operation;

class ProductProvider extends AbstractProduct implements ProviderInterface
{
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        if (isset($uriVariables['id'])) {
            $product = $this->productRepository->find($uriVariables['id']);

            return $product ? $this->transform($product) : null;
        } else {
            return null;
        }
    }
}
